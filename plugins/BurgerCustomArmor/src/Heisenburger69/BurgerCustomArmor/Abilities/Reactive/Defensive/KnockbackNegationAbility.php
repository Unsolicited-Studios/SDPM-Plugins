<?php

namespace Heisenburger69\BurgerCustomArmor\Abilities\Reactive\Defensive;

use pocketmine\player\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class KnockbackNegationAbility extends DefensiveAbility
{
    public function __construct(
        private float $multiplier
    ) {
    }

    public function canActivate(Player $damager): bool
    {
        return true;
    }

    public function activate(EntityDamageByEntityEvent $event): void
    {
        $kb = $event->getKnockBack();
        $event->setKnockBack($kb * $this->multiplier);
    }
}