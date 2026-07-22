# Profinet NRT 协议包 — DCP 设备发现 + Record Data 读写（RT 通道需 ERTEC 硬件）

> [中文](README.md)

Profinet NRT 协议包 — DCP 设备发现 + Record Data 读写（RT 通道需 ERTEC 硬件）。Pure PHP implementation, compatible with 6 PHP runtimes via kernel framework adapters.

## Installation

```bash
composer require erikwang2013/industrial-protocols-kernel erikwang2013/industrial-protocols-profinet
```

> Depends on [erikwang2013/industrial-protocols-kernel](https://github.com/erikwang2013/industrial-protocols-kernel) for connection management, protocol registry, coroutine adaptation, event system and more.

## Architecture

Built on kernel SDK interfaces (ProtocolInterface/ConnectorInterface/DriverInterface/FrameInterface), with ProfinetDriver for transport and ProfinetConnector for unified ConnectorInterface.

## Features

Complete profinet protocol frame encode/decode, driver transport, Connector wrapper, health check, connection strategies (Lazy/Eager/Pooled)

## Supported Frameworks

Compatible with 6 PHP runtimes via kernel framework adapters: Laravel (ServiceProvider+Facade+artisan), Webman (config/plugin auto-discovery+ProtocolProcess), Hyperf (ConfigProvider+DI+KernelFactory), ThinkPHP (services.php+IndustrialProtocolsService), Yii2 (Bootstrap+component), Plain PHP (direct Kernel instantiation)

### Laravel

```php
// AppServiceProvider::boot()
$kernel = app(Kernel::class);
$kernel->getProtocolRegistry()->register(new ModbusProtocol());
$kernel->boot();
$conn = $kernel->getConnectionManager()->connect('device-id');
```

### Webman

Auto-boot via ProtocolProcess on worker start. Configure at `config/plugin/erikwang2013/industrial-protocols-kernel/config/industrial-protocols.php`.

### Hyperf

```php
$kernel = \Hyperf\Context\ApplicationContext::getContainer()->get(Kernel::class);
```

## Usage

```php
$conn = $kernel->getConnectionManager()->connect('pn-device');
$devices = $conn->discoverDevices(5);        // DCP Identify broadcast
$result  = $conn->read('0:0:1:0xAFF0');      // api:slot:subslot:index
```

## Configuration

```php
'devices' => [
    'device-id' => [
        'protocol' => 'profinet',
        'host'     => '192.168.1.10',
        'port'     => 34964,
        'timeout'  => 3000,
    ],
],
```

## Adapter Vendors

Siemens (S7-1200/1500, ET 200SP/MP, CP 5611), Hilscher (netX, cifX RE/DP), Phoenix Contact (AXL F BK PN, ILC 191), Moxa (MGate 5101-PBM-MN)

## Requirements

- PHP >= 8.1
- Composer
- erikwang2013/industrial-protocols-kernel

## Related Links

- [Industrial Protocols Main Project](https://github.com/erikwang2013/industrial-protocols)
- [Kernel](https://github.com/erikwang2013/industrial-protocols-kernel)
- [All 42 Protocol Packages](https://github.com/erikwang2013/industrial-protocols#supported-protocols)

## License

MIT — Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
