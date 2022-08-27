<?php

namespace UnsolicitedDev\BuilderWand\command;

use pocketmine\player\Player;
use pocketmine\item\VanillaItems;
use CortexPE\Commando\BaseCommand;
use pocketmine\command\CommandSender;

class WandCommand extends BaseCommand
{
    public function prepare(): void
    {
        $this->setPermission('builderwand.cmd');
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage('You must be in-game to run this command!');
            return;
        }
        if (!$this->testPermissionSilent($sender)) {
            $sender->sendMessage('You do not have permission to run this command!');
            return;
        }

        // TODO: Actual item implementation
        $item = VanillaItems::BLAZE_ROD();
        $item->getNamedTag()->setString('builder_wand', 'yes');

        $sender->getInventory()->addItem($item);
    }
}
