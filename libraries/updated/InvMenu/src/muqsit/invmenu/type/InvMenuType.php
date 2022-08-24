<?php

declare(strict_types=1);

namespace muqsit\invmenu\type;

use muqsit\invmenu\InvMenu;
use pocketmine\player\Player;
use pocketmine\inventory\Inventory;
use muqsit\invmenu\type\graphic\InvMenuGraphic;

interface InvMenuType
{

	public function createGraphic(InvMenu $menu, Player $player): ?InvMenuGraphic;

	public function createInventory(): Inventory;
}
