<?php

/**                                                                        **\
 *                        █▀ █▀█ █░░ █ █▀▀ █ ▀█▀                            *
 *                        ▄█ █▄█ █▄▄ █ █▄▄ █ ░█░                            *
 *                                                                          *
 *            █▀▄ █▀▀ █░█ █▀▀ █░░ █▀█ █▀█ █▀▄▀█ █▀▀ █▄░█ ▀█▀                *
 *            █▄▀ ██▄ ▀▄▀ ██▄ █▄▄ █▄█ █▀▀ █░▀░█ ██▄ █░▀█ ░█░                *
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

namespace UnsolicitedDev\RotateWrench\command;

use pocketmine\block\Block;
use pocketmine\player\Player;
use CortexPE\Commando\BaseCommand;
use pocketmine\command\CommandSender;
use UnsolicitedDev\RotateWrench\RotateWrench;

class RotateCommand extends BaseCommand
{
    public function prepare(): void
    {
        $this->setPermission('rotatewrench.cmd.rotate');
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

        $block = $sender->getTargetBlock(10);
        if (!$block instanceof Block) {
            $sender->sendMessage('No block found! Please make sure that you are looking at a block and that you are not too far from the block.');
            return;
        }

        RotateWrench::rotateBlockAndAlert($sender, $block);
    }
}
