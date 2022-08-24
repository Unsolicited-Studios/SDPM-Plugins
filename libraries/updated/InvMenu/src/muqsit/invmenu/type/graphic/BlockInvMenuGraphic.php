<?php

declare(strict_types=1);

namespace muqsit\invmenu\type\graphic;

use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\inventory\Inventory;
use pocketmine\block\tile\Spawnable;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use muqsit\invmenu\type\graphic\network\InvMenuGraphicNetworkTranslator;

final class BlockInvMenuGraphic implements PositionedInvMenuGraphic
{

	public function __construct(
		private Block $block,
		private Vector3 $position,
		private ?InvMenuGraphicNetworkTranslator $network_translator = null,
		private int $animation_duration = 0
	) {
	}

	public function getPosition(): Vector3
	{
		return $this->position;
	}

	public function send(Player $player, ?string $name): void
	{
		$player->getNetworkSession()->sendDataPacket(UpdateBlockPacket::create(
			BlockPosition::fromVector3($this->position),
			RuntimeBlockMapping::getInstance()->toRuntimeId($this->block->getStateId()),
			UpdateBlockPacket::FLAG_NETWORK,
			UpdateBlockPacket::DATA_LAYER_NORMAL
		));
	}

	public function sendInventory(Player $player, Inventory $inventory): bool
	{
		return $player->setCurrentWindow($inventory);
	}

	public function remove(Player $player): void
	{
		$network = $player->getNetworkSession();
		$world = $player->getWorld();
		$blockPosition = BlockPosition::fromVector3($this->position);
		$runtime_block_mapping = RuntimeBlockMapping::getInstance();
		$block = $world->getBlockAt($this->position->x, $this->position->y, $this->position->z);

		$network->sendDataPacket(UpdateBlockPacket::create(
			$blockPosition,
			$runtime_block_mapping->toRuntimeId($block->getStateId()),
			UpdateBlockPacket::FLAG_NETWORK,
			UpdateBlockPacket::DATA_LAYER_NORMAL
		), true);

		$tile = $world->getTileAt($this->position->x, $this->position->y, $this->position->z);
		if ($tile instanceof Spawnable) {
			$network->sendDataPacket(BlockActorDataPacket::create($blockPosition, $tile->getSerializedSpawnCompound()), true);
		}
	}

	public function getNetworkTranslator(): ?InvMenuGraphicNetworkTranslator
	{
		return $this->network_translator;
	}

	public function getAnimationDuration(): int
	{
		return $this->animation_duration;
	}
}
