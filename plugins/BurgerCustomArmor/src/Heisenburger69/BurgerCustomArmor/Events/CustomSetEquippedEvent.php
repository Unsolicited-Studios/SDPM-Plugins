<?php

namespace Heisenburger69\BurgerCustomArmor\Events;

use pocketmine\Server;
use pocketmine\event\Event;
use pocketmine\player\Player;
use pocketmine\console\ConsoleCommandSender;
use Heisenburger69\BurgerCustomArmor\ArmorSets\CustomArmorSet;

class CustomSetEquippedEvent extends Event
{
    /**
     * @var CustomArmorSet
     */
    private $armorSet;

    /**
     * @var Player
     */
    private $player;

    public function __construct(Player $player, CustomArmorSet $armorSet)
    {
        $this->player = $player;
        $this->armorSet = $armorSet;
    }

    /**
     * @return CustomArmorSet
     */
    public function getArmorSet(): CustomArmorSet
    {
        return $this->armorSet;
    }

    public function call(): void
    {
        foreach ($this->armorSet->getEquippedCommands() as $command) {
            $command = str_replace("{PLAYER}", $this->player->getName(), $command);
            Server::getInstance()->getCommandMap()->dispatch(new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage()), $command);
        }
        foreach ($this->armorSet->getEquippedMessages() as $msg) {
            $msg = str_replace("{PLAYER}", $this->player->getName(), $msg);
            $this->player->sendMessage($msg);
        }
        parent::call();
    }

    /**
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }
}