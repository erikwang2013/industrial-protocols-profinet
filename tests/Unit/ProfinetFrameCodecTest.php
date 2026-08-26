<?php

/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Erikwang2013\IndustrialProtocols\Profinet\Tests\Unit;

use Erikwang2013\IndustrialProtocols\Profinet\Frame\ProfinetFrame;
use PHPUnit\Framework\TestCase;

class ProfinetFrameCodecTest extends TestCase
{
    public function testWriteRecordFrameStructure(): void
    {
        $frame = ProfinetFrame::writeRecord(1, 2, 3, 0x1234, 'DATA');
        $bytes = $frame->toBytes();

        $this->assertSame(0xFEFD, unpack('v', substr($bytes, 0, 2))[1]);
        $this->assertSame(18 + 4, unpack('v', substr($bytes, 2, 2))[1]); // 2 serviceId + 16 block
        $this->assertSame("\x02\x00", substr($bytes, 4, 2));             // Write Record service
        $this->assertSame(1, unpack('v', substr($bytes, 6, 2))[1]);      // api
        $this->assertSame(2, unpack('v', substr($bytes, 8, 2))[1]);      // slot
        $this->assertSame(3, unpack('v', substr($bytes, 10, 2))[1]);     // subslot
        $this->assertSame(0x1234, unpack('V', substr($bytes, 14, 4))[1]); // index
        $this->assertSame(4, unpack('V', substr($bytes, 18, 4))[1]);      // data length
        $this->assertSame('DATA', substr($bytes, 22, 4));                 // payload
    }

    public function testReadRecordDefaults(): void
    {
        $frame = ProfinetFrame::readRecord();
        $bytes = $frame->toBytes();
        $this->assertSame(22, strlen($bytes));
        $this->assertSame("\x01\x00", substr($bytes, 4, 2)); // Read Record service
        $this->assertSame(0, unpack('v', substr($bytes, 6, 2))[1]);  // api default 0
        $this->assertSame(1, unpack('v', substr($bytes, 10, 2))[1]); // subslot default 1
    }

    public function testDcpIdentifyFrameStructure(): void
    {
        $frame = ProfinetFrame::dcpIdentify();
        $bytes = $frame->toBytes();

        $this->assertSame(0xFEFE, unpack('v', substr($bytes, 0, 2))[1]);
        $this->assertSame(21, strlen($bytes)); // 4 header + 17 body
        $this->assertSame("\x00\x00", substr($bytes, 4, 2)); // Service ID 0 = Identify
        // Body: serviceId(2) + serviceType(1) + xid(4) + delay(2) + dcpLen(2) = 11,
        // so the first block starts at byte offset 15.
        // Block: option 2 (DeviceNameOfStation), suboption 1, length 0 (wildcard)
        $this->assertSame(2, unpack('v', substr($bytes, 15, 2))[1]);
        $this->assertSame(1, unpack('v', substr($bytes, 17, 2))[1]);
    }

    public function testDcpResponseBlockParsing(): void
    {
        // fromBytes() parses blocks after the 11-byte DCP header
        // (service_id 2 + service_type 1 + xid 4 + response_delay 2 + dcp_length 2).
        $body = "\x00\x00"          // service id (Identify)
              . "\x01"              // service type: response
              . pack('N', 0x01020304) // xid
              . pack('v', 0)        // response delay
              . pack('v', 8)        // dcp data length
              . pack('v', 2)        // block: option = 2
              . pack('v', 2)        // block: suboption = 2
              . pack('v', 8)        // block length
              . 'DEVICE-1';         // block data
        $bytes = pack('v', 0xFEFE) . pack('v', strlen($body)) . $body;

        $frame = ProfinetFrame::fromBytes($bytes);
        $blocks = $frame->getBlocks();
        $this->assertCount(1, $blocks);
        $this->assertSame(2, $blocks[0]['option']);
        $this->assertSame(2, $blocks[0]['suboption']);
        $this->assertSame(8, $blocks[0]['blockLen']);
        $this->assertSame('DEVICE-1', $blocks[0]['blockData']);
    }

    public function testWriteRecordRoundTripPreservesData(): void
    {
        $original = ProfinetFrame::writeRecord(0, 0, 1, 7, "\x01\x02\x03");
        $parsed = ProfinetFrame::fromBytes($original->toBytes());
        $this->assertSame(0xFEFD, $parsed->getFrameId());
        $this->assertSame(0xFEFD, $parsed->getData()['frame_id']);
    }

    public function testFromBytesTooShortThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Profinet frame too short');
        ProfinetFrame::fromBytes("\xFE\xFE");
    }
}
