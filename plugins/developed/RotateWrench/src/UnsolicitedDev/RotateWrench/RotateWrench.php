<?php

/**                                                                        **\
 *               █░█ █▄░█ █▀ █▀█ █░░ █ █▀▀ █ ▀█▀ █▀▀ █▀▄                    *
 *               █▄█ █░▀█ ▄█ █▄█ █▄▄ █ █▄▄ █ ░█░ ██▄ █▄▀                    *
 *                                                                          *
 *                       █▀ ▀█▀ █░█ █▀▄ █ █▀█ █▀                            *
 *                       ▄█ ░█░ █▄█ █▄▀ █ █▄█ ▄█                            *
 *                https://github.com/Unsolicited-Studios                    *
 *                                                                          *
 *                  Copyright 2022 Unsolicited-Studios                      *
 *    Licensed under the Apache License, Version 2.0 (the 'License');       *
 *   you may not use this file except in compliance with the License.       *
 *                                                                          *
 *                You may obtain a copy of the License at                   *
 *              http://www.apache.org/licenses/LICENSE-2.0                  *
 *                                                                          *
 *  Unless required by applicable law or agreed to in writing, software     *
 *   distributed under the License is distributed on an 'AS IS' BASIS,      *
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. *
 *  See the License for the specific language governing permissions and     *
 *                    limitations under the License.                        *
 *                                                                          *
 */                                                                        //

declare(strict_types=1);

namespace UnsolicitedDev\RotateWrench;

use pocketmine\block\Block;
use pocketmine\block\Chest;
use pocketmine\item\Shovel;
use pocketmine\math\Facing;
use pocketmine\player\Player;
use pocketmine\item\VanillaItems;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\block\tile\Chest as ChestTile;
use UnsolicitedDev\EssentialsSD\api\BlockAPI;
use UnsolicitedDev\RotateWrench\command\RotateCommand;
use UnsolicitedDev\RotateWrench\command\WrenchCommand;
use pocketmine\data\bedrock\item\upgrade\LegacyItemIdToStringIdMap;

class RotateWrench extends PluginBase
{
    use SingletonTrait;
    
    public const DOUBLE_CHEST = -1;

    public const FACING = 1;
    public const AXIS = 2;
    public const ROTATION = 3;

    public function onEnable(): void
    {
        self::setInstance($this);

        $this->getServer()->getCommandMap()->register('rotatewrench', new RotateCommand($this, 'rotate', 'Rotate any blocks you are looking at that has a facing'));
        $this->getServer()->getCommandMap()->register('rotatewrench', new WrenchCommand($this, 'wrench', 'Receive a wrench that can rotate the block you are looking at'));

        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
    }

    public static function rotateBlock(Player $player, Block $block): bool
    {
        $type = false;
        if (method_exists($block, 'setFacing')) {
            if ($block instanceof Chest) {
                $tile = $block->getPosition()->getWorld()->getTile($block->getPosition());
                if ($tile instanceof ChestTile && $tile->isPaired()) {
                    $type = self::DOUBLE_CHEST;
                    goto skip;
                }
            }
            $type = self::FACING;
        } elseif (method_exists($block, 'setAxis')) {
            $type = self::AXIS;
        } elseif (method_exists($block, 'setRotation')) {
            $type = self::ROTATION;
        }

        skip:

        // TODO: traits really messes up PHPStan and IDEs, any fixes?
        $match = match ($type) {
            self::DOUBLE_CHEST => $block->setFacing(Facing::opposite($block->getFacing())), /** @phpstan-ignore-line */
            self::FACING => $block->setFacing(Facing::opposite($player->getHorizontalFacing())), /** @phpstan-ignore-line */
            self::AXIS => $block->setAxis(Facing::axis($player->getHorizontalFacing())), /** @phpstan-ignore-line */
            self::ROTATION => $block->setRotation(((int) floor((($player->getLocation()->getYaw() + 180) * 16 / 360) + 0.5)) & 0xf), /** @phpstan-ignore-line */
            default => false
        };
        BlockAPI::updateBlockChange($block);

        return !$match ? false : true;
    }

    public static function rotateBlockAndAlert(Player $player, Block $block): bool
    {
        if (self::rotateBlock($player, $block)) {
            // TODO: Is there a better way than this? This causes identification issues with blocks like Logs.
            $namespace = "minecraft:" . strtolower(str_replace(" ", "_", $block->getName()));
            $legacyId = (int) array_search($namespace, LegacyItemIdToStringIdMap::getInstance()->getLegacyToStringMap());

            $player->sendMessage('Block rotated! Block: ' . $block->getName() . ' (' . $legacyId . ')');
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
