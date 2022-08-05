<?php

namespace Heisenburger69\BurgerCustomArmor\Abilities\Togglable;

use pocketmine\player\Player;

class ScaleAbility extends TogglableAbility
{
    private float $scale;

    public function __construct(float $scale)
    {
        $this->scale = $scale;
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