<?php

declare(strict_types=1);

namespace alvin0319\PlayerTrade;

use pocketmine\plugin\PluginBase;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\utils\SingletonTrait;
use pocketmine\lang\Language as BaseLang;
use alvin0319\PlayerTrade\trade\TradeManager;
use alvin0319\PlayerTrade\command\TradeCommand;
use alvin0319\PlayerTrade\task\TradeRequestTask;

final class PlayerTrade extends PluginBase
{
	use SingletonTrait;

	public static string $prefix = "§b§l[PlayerTrade] §r§7";
	public static int $expireTime = 45;

	protected BaseLang $lang;

	public function onLoad(): void
	{
		self::setInstance($this);
	}

	public function onEnable(): void
	{
		self::$prefix = $this->getConfig()->get("prefix", "§b§l[PlayerTrade] §r§7");
		if (!InvMenuHandler::isRegistered()) {
			InvMenuHandler::register($this);
		}

		$this->getScheduler()->scheduleRepeatingTask(new TradeRequestTask(), 20);
		$this->getServer()->getCommandMap()->register("playertrade", new TradeCommand());
		$this->saveDefaultConfig();

		if (!file_exists($this->getDataFolder() . "lang/" . $this->getConfig()->get("lang", "eng") . ".ini") && $this->saveResource("lang/{$this->getConfig()->get("lang", "eng")}.ini")) {
			$this->getLogger()->alert("Language file not found... use english as default...");
			$this->getConfig()->set("lang", "eng");
		}

		$this->saveResource("lang/" . $this->getConfig()->get("lang", "eng"));

		$this->lang = new BaseLang($this->getConfig()->get("lang", "eng"), $this->getDataFolder() . "lang/");
		self::$expireTime = (int) $this->getConfig()->get("requestExpire", 15);
	}

	public function getLanguage(): BaseLang
	{
		return $this->lang;
	}

	public function onDisable(): void
	{
		foreach (TradeManager::$sessions as $session) {
			$session->cancel(false, false);
		}
	}
}
