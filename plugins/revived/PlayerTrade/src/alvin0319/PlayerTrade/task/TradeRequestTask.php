<?php

declare(strict_types=1);

namespace alvin0319\PlayerTrade\task;

use pocketmine\scheduler\Task;
use alvin0319\PlayerTrade\trade\TradeManager;

final class TradeRequestTask extends Task
{
	public function onRun(): void
	{
		TradeManager::checkRequests();
	}
}
