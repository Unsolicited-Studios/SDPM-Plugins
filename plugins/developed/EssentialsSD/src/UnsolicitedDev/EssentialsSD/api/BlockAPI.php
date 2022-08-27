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

namespace UnsolicitedDev\EssentialsSD\api;

use pocketmine\Server;
use pocketmine\block\Block;
use pocketmine\world\format\Chunk;

class BlockAPI
{
    /**
     * Sends a client-side update of the block to players that have the loaded the specific chunk. 
     */
    public static function updateBlockChange(Block $block): bool
    {
        $position = $block->getPosition();

        $chunkX = $position->x >> Chunk::COORD_BIT_SIZE;
        $chunkZ = $position->z >> Chunk::COORD_BIT_SIZE;

        $block->writeStateToWorld();
        $block->onNearbyBlockChange();
        return Server::getInstance()->broadcastPackets($position->getWorld()->getChunkPlayers($chunkX, $chunkZ), $position->getWorld()->createBlockUpdatePackets([$position->asVector3()]));
    }
}
