<?php

namespace Heisenburger69\BurgerCustomArmor\Abilities\Togglable;

use Heisenburger69\BurgerCustomArmor\Utils\Utils;
use pocketmine\player\Player;

class CapeAbility extends TogglableAbility
{
    private string $file;

    public function __construct(string $file)
    {
        $this->file = $file;
    }

    public function on(Player $player): void
    {
        Utils::addCape($player, $this->file);
    }

    public function off(Player $player): void
    {
        Utils::removeCape($player);
    }
}