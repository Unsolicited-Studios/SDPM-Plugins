<?php

declare(strict_types=1);

namespace alvin0319\PlayerTrade\event;

use pocketmine\event\Event;
use pocketmine\player\Player;

abstract class TradeEvent extends Event
{
	public function __construct(
		protected Player $sender,
		protected Player $receiver
	) {
	}

	public function getSender(): Player
	{
		return $this->sender;
	}

	public function getReceiver(): Player
	{
		return $this->receiver;
	}
}
