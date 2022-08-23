<?php

namespace Heisenburger69\BurgerCustomArmor\Events;

use pocketmine\Server;
use pocketmine\console\ConsoleCommandSender;

class CustomSetUnequippedEvent extends ArmorEvent
{
    public function call(): void
    {
        foreach ($this->getArmorSet()->getUnequippedCommands() as $command) {
            $command = str_replace("{PLAYER}", $this->getPlayer()->getName(), $command);
            Server::getInstance()->getCommandMap()->dispatch(new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage()), $command);
        }
        foreach ($this->getArmorSet()->getUnequippedMessages() as $msg) {
            $msg = str_replace("{PLAYER}", $this->getPlayer()->getName(), $msg);
            $this->getPlayer()->sendMessage($msg);
        }
        parent::onCall();
    }
}
