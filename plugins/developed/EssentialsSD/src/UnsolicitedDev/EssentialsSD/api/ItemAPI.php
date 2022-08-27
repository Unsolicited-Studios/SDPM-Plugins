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

use pocketmine\item\Item;
use pocketmine\utils\Utils;
use Webmozart\PathUtil\Path;
use function file_get_contents;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\SingletonTrait;
use const pocketmine\BEDROCK_DATA_PATH;
use pocketmine\item\StringToItemParser;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;
use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;

/**
 * Most of the parts in this class has been taken from:
 * https://github.com/alvin0319/libItemRegistrar
 */
class ItemAPI
{
    use SingletonTrait;

	/** @var Item[] */
	private array $registeredItems = [];

	protected function onLoad(): void
	{
		self::setInstance($this);
	}

	/**
	 * @param Item          $item the Item to register
	 * @param int           $runtimeId the runtime id that will be used by the server to send the item to the player.
	 * This usually can be found using BDS, or included in {@link \pocketmine\BEDROCK_DATA_PATH/required_item_list.json}. for custom items, you should generate this manually.
	 * @param bool          $force
	 * @param string        $namespace the item's namespace. This usually can be found in {@link ItemTypeNames}.
	 * @param \Closure|null $serializeCallback the callback that will be used to serialize the item.
	 * @param \Closure|null $deserializeCallback the callback that will be used to deserialize the item.
	 *
	 * @return void
	 * @see ItemTypeDictionaryFromDataHelper
	 * @see libItemRegistrar::getRuntimeIdByName()
	 */
	public function registerItem(Item $item, int $runtimeId, bool $force = false, string $namespace = "", ?\Closure $serializeCallback = null, ?\Closure $deserializeCallback = null): void
	{
		if ($serializeCallback !== null) {
			Utils::validateCallableSignature(static function (Item $item): void {
			}, $serializeCallback);
		}
		if ($deserializeCallback !== null) {
			Utils::validateCallableSignature(static function (SavedItemData $data): void {
			}, $deserializeCallback);
		}

		if (isset($this->registeredItems[$item->getTypeId()]) && !$force) {
			throw new AssumptionFailedError("Item {$item->getTypeId()} is already registered");
		}
		$this->registeredItems[$item->getTypeId()] = $item;

		StringToItemParser::getInstance()->override($item->getName(), static fn () => clone $item);
		$serializer = GlobalItemDataHandlers::getSerializer();
		$deserializer = GlobalItemDataHandlers::getDeserializer();

		// TODO: Is there a better way than this?
		$namespace = $namespace === "" ? "minecraft:" . strtolower(str_replace(" ", "_", $item->getName())) : $namespace;

		(function () use ($item, $serializeCallback, $namespace): void {
			$this->itemSerializers[$item->getTypeId()][get_class($item)] = $serializeCallback !== null ? $serializeCallback : static fn() => new SavedItemData($namespace);
		})->call($serializer);

		(function () use ($item, $deserializeCallback, $namespace): void {
			if (isset($this->deserializers[$item->getName()])) {
				unset($this->deserializers[$item->getName()]);
			}
			$this->map($namespace, $deserializeCallback !== null ? $deserializeCallback : static fn(SavedItemData $_) => clone $item);
		})->call($deserializer);

		$dictionary = GlobalItemTypeDictionary::getInstance()->getDictionary();
		(function() use ($runtimeId, $namespace) : void{
			$this->stringToIntMap[$namespace] = $runtimeId;
			$this->intToStringIdMap[$runtimeId] = $namespace;
            $this->itemTypes[] = new ItemTypeEntry($namespace, $runtimeId, true);
		})->call($dictionary);
	}

	public function getItemByLegacyId(int $legacyNumericId, int $meta, int $count, ?CompoundTag $nbt = null): Item
	{
		return GlobalItemDataHandlers::getDeserializer()->deserializeType(GlobalItemDataHandlers::getUpgrader()->upgradeItemTypeDataInt($legacyNumericId, $meta, $count, $nbt)->getTypeData());
	}

	public function getItemByTypeId(int $typeId): ?Item
	{
		return $this->registeredItems[$typeId] ?? null;
	}

	/**
	 * Returns the runtime id of given item name. (only for vanilla items)
	 *
	 * @param string $name
	 *
	 * @return int|null null if runtime id does not exist.
	 */
	public function getRuntimeIdByName(string $name): ?int
	{
		static $mappedJson = [];
		if ($mappedJson === []) {
			$mappedJson = $this->reprocessKeys(json_decode(file_get_contents(Path::join(BEDROCK_DATA_PATH, "required_item_list.json")), true));
		}
		$name = str_replace(" ", "_", strtolower($name));
		return $mappedJson[$name]["runtime_id"] ?? null;
	}

	private function reprocessKeys(array $data): array
	{
		$new = [];
		foreach ($data as $key => $value) {
			$new[str_replace("minecraft:", "", $key)] = $value;
		}
		return $new;
	}
}
