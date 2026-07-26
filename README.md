# Profinet NRT 协议包 — 支持 DCP 设备发现和 Record Data 读写，端口 34964

> [English](README.en.md)

Profinet NRT（非实时）通道，DCP 设备发现 + Record Data 读写。RT/IRT 实时通道需专用硬件芯片（Siemens ERTEC）。

## 安装

```bash
composer require erikwang2013/industrial-protocols-profinet
```

## 架构

ProfinetDriver（UDP DCP + TCP Record Data）→ ProfinetFrame 帧编解码。DCP Identify 广播（Frame ID 0xFEFE），Record Data（Frame ID 0xFEFD）。

## 功能

DCP Identify 广播设备发现、Record Data Read/Write、模块诊断数据读取、ProfinetException 异常

## 使用说明

```php
$conn = $kernel->getConnectionManager()->connect('pn-device');
$devices = $conn->discoverDevices(5);          // DCP 广播
$result = $conn->read('0:1:1:0xAFF0');        // 读诊断
```

## 配置示例

```php
'devices' => [
    'pn-device' => [
        'protocol' => 'profinet', 'variant' => 'nrt',
        'host' => '192.168.1.30', 'port' => 34964,
        'transport' => 'udp', 'timeout' => 5000,
    ],
],
```

## 适配厂商

Siemens (S7-1200/S7-1500/ET 200SP)、Phoenix Contact (AXL F BK PN)、Hilscher (netX)

## 兼容框架

Laravel / Webman / Hyperf / ThinkPHP / Yii2 / Plain PHP

## 系统要求

- PHP >= 8.1
- Composer
- erikwang2013/industrial-protocols-kernel

## License

MIT — Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
