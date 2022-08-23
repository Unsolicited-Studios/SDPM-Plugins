<?php

namespace Heisenburger69\BurgerCustomArmor\Abilities\Togglable;

use pocketmine\player\Player;

class ScaleAbility extends TogglableAbility
{
    public function __construct(
        private float $scale
    ) {
    }

    public function on(Player $player): void
    {
        $player->setScale($this->scale);
    }

    public function off(Player $player): void
    {
        $player->setScale(1);
    }
}