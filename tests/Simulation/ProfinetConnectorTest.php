<?php

/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Erikwang2013\IndustrialProtocols\Profinet\Tests\Simulation;

use Erikwang2013\IndustrialProtocols\Connection\ConnectionState;
use Erikwang2013\IndustrialProtocols\Profinet\ProfinetConnector;
use Erikwang2013\IndustrialProtocols\Profinet\ProfinetProtocol;
use PHPUnit\Framework\TestCase;

class ProfinetConnectorTest extends TestCase
{
    /**
     * Start a fake Profinet device (TCP echo server) in a child process.
     * Echoing the request back is a valid Profinet frame, so the driver can
     * parse it and the connector can report the round trip.
     */
    private function startEchoServer(int $port): array
    {
        $proc = proc_open([PHP_BINARY, '-r', <<<'STUB'
            $server = stream_socket_server('tcp://127.0.0.1:' . $argv[1], $errno, $errstr);
            if (!$server) { fwrite(STDERR, "server fail: $errstr\n"); exit(1); }
            echo "READY\n";
            flush();
            $client = @stream_socket_accept($server, 5);
            if ($client) {
                while (($req = fread($client, 4096)) !== '' && $req !== false) {
                    fwrite($client, $req);
                }
                fclose($client);
            }
            fclose($server);
STUB, (string) $port], [1 => ['pipe', 'w']], $pipes);

        fgets($pipes[1]);
        return [$proc, $pipes];
    }

    public function testReadWriteRoundTripOverTcp(): void
    {
        [$proc] = $this->startEchoServer(15060);

        $connector = new ProfinetConnector([
            'host' => '127.0.0.1', 'port' => 15060, 'timeout' => 2000, 'transport' => 'tcp',
        ]);
        $connector->connect();
        $this->assertTrue($connector->isConnected());
        $this->assertSame(ConnectionState::HEALTHY, $connector->getHealth()->state);

        $result = $connector->read('0:1:1:1');
        $this->assertSame(0xFEFD, $result['0:1:1:1']['frame_id']);

        // Multiple addresses in one call
        $multi = $connector->read(['0:0:1:0', '1:2:3:4']);
        $this->assertCount(2, $multi);
        $this->assertArrayHasKey('0:0:1:0', $multi);
        $this->assertArrayHasKey('1:2:3:4', $multi);

        // Write round trip (numeric and string values)
        $written = $connector->write('0:1:1:2', [42]);
        $this->assertSame(0xFEFD, $written['0:1:1:2']['frame_id']);
        $writtenStr = $connector->write('0:1:1:3', ['ABC']);
        $this->assertSame(0xFEFD, $writtenStr['0:1:1:3']['frame_id']);

        $connector->disconnect();
        $this->assertFalse($connector->isConnected());

        proc_close($proc);
    }

    public function testUdpConnectAndReadTimeout(): void
    {
        // No server on this UDP port: connect() is lazy and succeeds,
        // but read() must time out.
        $connector = new ProfinetConnector([
            'host' => '127.0.0.1', 'port' => 15962, 'timeout' => 1500,
        ]);
        $connector->connect();
        $this->assertTrue($connector->isConnected());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Profinet response timeout');
        $connector->read('0:1:1:1');

        $connector->disconnect();
    }

    public function testConnectRefusedOnClosedTcpPort(): void
    {
        $connector = new ProfinetConnector([
            'host' => '127.0.0.1', 'port' => 15963, 'timeout' => 1000, 'transport' => 'tcp',
        ]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Profinet connect failed');
        $connector->connect();
    }

    public function testDiscoverDevicesReturnsArray(): void
    {
        $connector = new ProfinetConnector([]);
        $devices = $connector->discoverDevices(1);
        $this->assertIsArray($devices);
    }

    public function testHealthBeforeConnect(): void
    {
        $connector = new ProfinetConnector([]);
        $this->assertSame(ConnectionState::CLOSED, $connector->getHealth()->state);
    }

    public function testProtocolCreateConnector(): void
    {
        $protocol = new ProfinetProtocol();
        $this->assertSame('profinet', $protocol->getName());
        $this->assertSame('1.1.1', $protocol->getVersion());
        $this->assertSame(34964, $protocol->getDefaultPort());
        $this->assertContains('nrt', $protocol->getSupportedVariants());

        $connector = $protocol->createConnector(['host' => '192.168.1.10']);
        $this->assertInstanceOf(ProfinetConnector::class, $connector);
    }
}
