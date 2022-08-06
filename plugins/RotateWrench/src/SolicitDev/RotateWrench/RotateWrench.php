<?php

/**
 *          █▀ █▀█ █░░ █ █▀▀ █ ▀█▀
 *          ▄█ █▄█ █▄▄ █ █▄▄ █ ░█░
 * 
 * █▀▄ █▀▀ █░█ █▀▀ █░░ █▀█ █▀█ █▀▄▀█ █▀▀ █▄░█ ▀█▀
 * █▄▀ ██▄ ▀▄▀ ██▄ █▄▄ █▄█ █▀▀ █░▀░█ ██▄ █░▀█ ░█░
 *    https://github.com/Solicit-Development
 * 
 *    Copyright 2022 Solicit-Development
 *    Licensed under the Apache License, Version 2.0 (the 'License');
 *    you may not use this file except in compliance with the License.
 *    You may obtain a copy of the License at
 * 
 *        http://www.apache.org/licenses/LICENSE-2.0
 * 
 *    Unless required by applicable law or agreed to in writing, software
 *    distributed under the License is distributed on an 'AS IS' BASIS,
 *    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *    See the License for the specific language governing permissions and
 *    limitations under the License.
 * 
 */

declare(strict_types=1);

namespace SolicitDev\RotateWrench;

use pocketmine\Server;
use pocketmine\block\Block;
use pocketmine\item\Shovel;
use pocketmine\player\Player;
use pocketmine\item\VanillaItems;
use pocketmine\plugin\PluginBase;
use pocketmine\world\format\Chunk;
use pocketmine\block\utils\HorizontalFacingTrait;
use SolicitDev\RotateWrench\command\RotateCommand;
use SolicitDev\RotateWrench\command\WrenchCommand;

class RotateWrench extends PluginBase
{
    private static RotateWrench $instance;

    public static function getInstance(): RotateWrench
    {
        return self::$instance;
    }

    public function onEnable(): void
    {
        self::$instance = $this;

        $this->getServer()->getCommandMap()->register('rotatewrench', new RotateCommand($this, 'rotate', 'Rotate the block you are looking at'));
        $this->getServer()->getCommandMap()->register('rotatewrench', new WrenchCommand($this, 'wrench', 'Receive a wrench that can rotate the block you are looking at'));
    
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
    }

    public static function rotateBlock(Player $player, Block $block, int $face): bool
    {
        if (method_exists($block, 'setFacing')) {
            /** @var HorizontalFacingTrait $block */
            $block->setFacing($face);

            /** @var Block $block */
            $position = $block->getPosition();

            $chunkX = $position->x >> Chunk::COORD_BIT_SIZE;
            $chunkZ = $position->z >> Chunk::COORD_BIT_SIZE;

            $block->writeStateToWorld();
            Server::getInstance()->broadcastPackets($position->getWorld()->getChunkPlayers($chunkX, $chunkZ), $position->getWorld()->createBlockUpdatePackets([$position->asVector3()]));
            
            $player->sendMessage('Block rotated! Block: ' . $block->getName() . ' (' . $block->getId() . ':' . $block->getMeta() . ')');
            return true;
        }
        $player->sendMessage('Failed to rotate block! May be possible that this block has no facing trait.');
        return false;
    }

    public static function getWrench(): Shovel
    {
        $item = VanillaItems::IRON_SHOVEL()
            ->setCustomName('Rotating Wrench');

        $item->getNamedTag()->setString('RotateWrench', 'Wrench');
        return $item;
    }
}
