<?php

declare(strict_types=1);

namespace muqsit\simplepackethandler;

use InvalidArgumentException;
use pocketmine\plugin\Plugin;
use pocketmine\event\EventPriority;
use muqsit\simplepackethandler\monitor\PacketMonitor;
use muqsit\simplepackethandler\monitor\IPacketMonitor;
use muqsit\simplepackethandler\interceptor\PacketInterceptor;
use muqsit\simplepackethandler\interceptor\IPacketInterceptor;

final class SimplePacketHandler
{
	public static function createInterceptor(Plugin $registerer, int $priority = EventPriority::NORMAL, bool $handleCancelled = false): IPacketInterceptor
	{
		if ($priority === EventPriority::MONITOR) {
			throw new InvalidArgumentException("Cannot intercept packets at MONITOR priority");
		}
		return new PacketInterceptor($registerer, $priority, $handleCancelled);
	}

	public static function createMonitor(Plugin $registerer, bool $handleCancelled = false): IPacketMonitor
	{
		return new PacketMonitor($registerer, $handleCancelled);
	}
}
