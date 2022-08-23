<?php

namespace Heisenburger69\BurgerCustomArmor\Abilities\Reactive\Defensive;

use pocketmine\player\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use Heisenburger69\BurgerCustomArmor\Abilities\Reactive\ReactiveAbility;

class DefensiveAbility extends ReactiveAbility
{
    public function canActivate(Player $damager): bool
    {
        return true;
    }

    public function activate(EntityDamageByEntityEvent $event): void
    {
    }
}
