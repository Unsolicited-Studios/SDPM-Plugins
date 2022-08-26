<?php

namespace UnsolicitedDev\BuilderWand;

use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\math\Facing;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use UnsolicitedDev\BuilderWand\command\WandCommand;

class BuilderWand extends PluginBase
{
    use SingletonTrait;

    public function onEnable(): void
    {
        self::setInstance($this);
        
        $this->saveDefaultConfig();
        
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        $this->getServer()->getCommandMap()->register('builderwand', new WandCommand($this, 'builderwand', 'Receive a builder wand that can help with your builds', ['bw', 'bwand']));
    }

    public static function placeBlocksCompatible(Block $leaderBlock, array $blocksToPlace = [], array $blocksNotCompatible = []): void
    {
        // A very complicated system. Does anyone have a better way?
        // 
        // Red => $leaderBlock
        // Blue => $sideBlock
        // Yellow => $memberBlock

        $blocksToPlace = empty($blocksToPlace) ? [$leaderBlock] : $blocksToPlace;
        $iteration = 0;
        foreach (self::getSideBlocks($leaderBlock) as $sideBlock) {
            if (
                in_array($sideBlock->getPosition(), $blocksToPlace) &&
                (!$sideBlock->isSolid() || !$sideBlock instanceof Air)
            ) {
                continue;
            }

            $isChecked = false;
            foreach (self::getSideBlocks($sideBlock) as $memberBlock) {
                if (!in_array($memberBlock->getPosition(), $blocksNotCompatible)) {
                    if ($leaderBlock->getTypeId() === $memberBlock->getTypeId()) {
                        if (!$isChecked) {
                            $blocksToPlace[] = $sideBlock->getPosition();
                            $isChecked = true;
                            $iteration++;
                        }
                    } else {
                        $blocksNotCompatible[] = $memberBlock->getPosition();
                    }
                }
            }
        }

        if ($iteration > 0) {
            foreach (self::getSideBlocks($leaderBlock) as $sideBlock) {
                self::placeBlocksCompatible($sideBlock, $blocksToPlace, $blocksNotCompatible);
            }
        } else {
            foreach ($blocksToPlace as $blockPos) {
                $leaderBlock->getPosition()->getWorld()->setBlock($blockPos, $leaderBlock, false);
            }
        }
    }

    /**
     * @return Block[]
     */
    public static function getSideBlocks(Block $block): array
    {
        $blocks = [];
        foreach (Facing::ALL as $facing) {
            $blocks[] = $block->getSide($facing);
        }
        return $blocks;
    }
}
