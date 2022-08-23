<?php

declare(strict_types=1);

namespace alvin0319\PlayerTrade\task;

use pocketmine\scheduler\Task;
use alvin0319\PlayerTrade\trade\TradeSession;

final class TradeSessionTask extends Task
{
	public function __construct(
		protected TradeSession $session
	) {
	}

	public function onRun(): void
	{
		if (!$this->session->getReceiver()->isOnline()) {
			$this->session->cancel();
		} elseif (!$this->session->getSender()->isOnline()) {
			$this->session->cancel(true, true);
		}
	}
}
