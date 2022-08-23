<?php

declare(strict_types=1);

namespace alvin0319\PlayerTrade\event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

final class TradeStartEvent extends TradeEvent implements Cancellable
{
    use CancellableTrait;
}
