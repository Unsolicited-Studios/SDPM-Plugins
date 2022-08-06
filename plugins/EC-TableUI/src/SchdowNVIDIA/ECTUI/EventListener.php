<?php

namespace SchdowNVIDIA\ECTUI;

use pocketmine\event\Listener;
use pocketmine\block\BlockLegacyIds;
use pocketmine\event\player\PlayerInteractEvent;

class EventListener implements Listener
{
    public function onTouch(PlayerInteractEvent $event): void
    {
        $block = $event->getBlock();
        if ($block->getId() === BlockLegacyIds::ENCHANTMENT_TABLE) {
            $event->cancel();
            if (!$event->getPlayer()->isSneaking()) {
                Main::openEnchantUI($event->getPlayer(), $event->getItem(), $block);
            }
        }
    }
}
