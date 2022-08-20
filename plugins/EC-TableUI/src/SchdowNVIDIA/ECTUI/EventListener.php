<?php

namespace SchdowNVIDIA\ECTUI;

use pocketmine\event\Listener;
use pocketmine\block\EnchantingTable;
use pocketmine\event\player\PlayerInteractEvent;

class EventListener implements Listener
{
    public function onTouch(PlayerInteractEvent $event): void
    {
        $block = $event->getBlock();
        if ($block instanceof EnchantingTable) {
            $event->cancel();
            if (!$event->getPlayer()->isSneaking()) {
                Main::openEnchantUI($event->getPlayer(), $event->getItem(), $block);
            }
        }
    }
}
