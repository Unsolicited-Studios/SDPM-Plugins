<?php

declare(strict_types=1);

namespace alvin0319\ItemRegistrar;

use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\utils\Utils;
use Webmozart\PathUtil\Path;
use function file_get_contents;
use pocketmine\item\ItemTypeIds;
use pocketmine\plugin\PluginBase;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockTypeIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\SingletonTrait;
use const pocketmine\BEDROCK_DATA_PATH;
use pocketmine\item\StringToItemParser;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;
use pocketmine\data\bedrock\block\CachingBlockStateSerializer;
use pocketmine\data\bedrock\block\CachingBlockStateDeserializer;

final class libItemRegistrar extends PluginBase
{
	use SingletonTrait;

	/** @var Item[] */
	private array $registeredItems = [];
	/** @var Block[] */
	private array $registeredBlocks = [];

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

		StringToItemParser::getInstance()->override($item->getName(), static fn () => $item);
		$serializer = GlobalItemDataHandlers::getSerializer();
		$deserializer = GlobalItemDataHandlers::getDeserializer();

		// TODO: Is there a better way than this?
		$namespace = $namespace === "" ? "minecraft:" . strtolower(str_replace(" ", "_", $item->getName())) : $namespace;

		(function () use ($item, $serializeCallback, $namespace): void {
			if (isset($this->itemSerializers[$item->getTypeId()])) {
				unset($this->itemSerializers[$item->getTypeId()]);
			}
			$this->map($item, $serializeCallback !== null ? $serializeCallback : static fn () => new SavedItemData($namespace));
		})->call($serializer);

		(function () use ($item, $deserializeCallback): void {
			if (isset($this->deserializers[$item->getName()])) {
				unset($this->deserializers[$item->getName()]);
			}
			$this->map($item->getName(), $deserializeCallback !== null ? $deserializeCallback : static fn (SavedItemData $_) => $item);
		})->call($deserializer);

		$dictionary = GlobalItemTypeDictionary::getInstance()->getDictionary();
		(function () use ($item, $runtimeId): void {
			$this->stringToIntMap[$item->getName()] = $runtimeId;
			$this->intToStringIdMap[$runtimeId] = $item;
		})->call($dictionary);
	}

	// TODO: BLOCK ITEMS (ItemSerializer->mapBlock)
	public function registerBlock(Block $block, bool $force = false, string $namespace = "", ?CompoundTag $compoundTag = null): void
	{
		BlockFactory::getInstance()->register($block, $force);

		$namespace = $namespace === "" ? "minecraft:" . strtolower(str_replace(" ", "_", $block->getName())) : $namespace;

		$serializer = GlobalBlockStateHandlers::getSerializer();
		$deserializer = GlobalBlockStateHandlers::getDeserializer();

		assert($serializer instanceof CachingBlockStateSerializer);
		assert($deserializer instanceof CachingBlockStateDeserializer);

		$blockStateData = (function () use ($block): BlockStateData {
			return $this->serialize($block->getStateId());
		})->call($serializer);

		(function () use ($blockStateData): void {
			$this->deserialize($blockStateData);
		})->call($deserializer);

		$blockStateDictionary = RuntimeBlockMapping::getInstance()->getBlockStateDictionary();
		(function () use ($block, $namespace, $compoundTag): void {
			$cache = $this->stateDataToStateIdLookupCache;
			(function () use ($block, $namespace, $compoundTag): void {
				$this->nameToNetworkIdsLookup[$namespace] = new BlockStateData($namespace, GlobalBlockStateHandlers::getUpgrader()->upgradeBlockStateNbt($compoundTag ?? CompoundTag::create())->getStates(), $block->getStateId());
			})->call($cache);
		})->call($blockStateDictionary);
	}

	/**
	 * Returns a next item id and increases it.
	 *
	 * @return int
	 */
	public function getNextItemId(): int
	{
		return ItemTypeIds::newId();
	}

	public function getNextBlockId(): int
	{
		return BlockTypeIds::newId();
	}

	public function getItemByLegacyId(int $legacyNumericId, int $meta, int $count, ?CompoundTag $nbt = null): Item
	{
		return GlobalItemDataHandlers::getDeserializer()->deserializeType(GlobalItemDataHandlers::getUpgrader()->upgradeItemTypeDataInt($legacyNumericId, $meta, $count, $nbt)->getTypeData());
	}

	public function getItemByTypeId(int $typeId): ?Item
	{
		return $this->registeredItems[$typeId] ?? null;
	}

	public function getBlockByTypeId(int $typeId): ?Block
	{
		try {
			return BlockFactory::getInstance()->fromTypeId($typeId);
		} catch (\Throwable $e) {
			return $this->registeredBlocks[$typeId] ?? null;
		}
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
