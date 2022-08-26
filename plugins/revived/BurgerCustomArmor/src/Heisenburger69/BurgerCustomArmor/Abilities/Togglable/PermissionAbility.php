<?php

namespace Heisenburger69\BurgerCustomArmor\Abilities\Togglable;

use Heisenburger69\BurgerCustomArmor\BurgerCustomArmor;
use pocketmine\player\Player;

class PermissionAbility extends TogglableAbility
{
    public function __construct(
        private string $permission
    ) {
    }

    public function on(Player $player): void
    {
        $player->addAttachment(BurgerCustomArmor::getInstance(), $this->permission, true);
    }

    public function off(Player $player): void
    {
        $player->addAttachment(BurgerCustomArmor::getInstance(), $this->permission, false);
    }
}