<?php

namespace UnsolicitedDev\BuilderWand;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;

class EventListener implements Listener
{
    public function onInteract(PlayerInteractEvent $event): void
    {
        $item = $event->getItem();
        if (
            $item->getNamedTag()->getString('builder_wand', '') === 'yes' &&
            $event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK
        ) {
            BuilderWand::placeBlocksCompatible($event->getBlock());
        }
    }
}
