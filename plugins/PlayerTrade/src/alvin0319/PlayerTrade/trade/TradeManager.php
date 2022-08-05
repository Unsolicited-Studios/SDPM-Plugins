<?php

declare(strict_types=1);

namespace alvin0319\PlayerTrade\trade;

use pocketmine\Server;
use pocketmine\player\Player;
use alvin0319\PlayerTrade\PlayerTrade;

class TradeManager
{
	public static string $prefix = "§b§l[PlayerTrade] §r§7";

	/** @var TradeSession[] */
	public static array $sessions = [], $playerToSessionMap = [];
	public static array $requests = [];

	public static function createTradeSession(Player $sender, Player $receiver): void
	{
		$session = new TradeSession($sender, $receiver);

		self::$sessions[spl_object_id($session)] = $session;
		self::$playerToSessionMap[$sender->getName()] = $session;
		self::$playerToSessionMap[$receiver->getName()] = $session;
	}

	public static function removeTradeSession(TradeSession $session): void
	{
		unset(self::$sessions[spl_object_id($session)]);
		unset(self::$playerToSessionMap[$session->getSender()->getName()]);
		unset(self::$playerToSessionMap[$session->getReceiver()->getName()]);
	}

	public static function getSessionByPlayer(Player $player): ?TradeSession
	{
		return self::$playerToSessionMap[$player->getName()] ?? null;
	}

	public static function addRequest(Player $sender, Player $receiver): bool
	{
		if (!TradeManager::hasRequest($sender)) {
			self::$requests[$sender->getName()] = [
				"receiver" => $receiver->getName(),
				"expireAt" => time() + PlayerTrade::$expireTime
			];
			return true;
		}
		return false;
	}

	public static function removeRequest(Player $sender): bool
	{
		if (isset(self::$requests[$sender->getName()])) {
			unset(self::$requests[$sender->getName()]);
			return true;
		}
		return false;
	}

	public static function hasRequest(Player $sender): bool
	{
		return isset(self::$requests[$sender->getName()]);
	}

	public static function hasRequestFrom(Player $sender, Player $receiver): bool
	{
		if (self::hasRequest($sender) && self::$requests[$sender->getName()]["receiver"] === $receiver->getName()) {
			return true;
		}
		return false;
	}

	public static function acceptRequest(Player $sender, Player $receiver): bool
	{
		if (self::hasRequestFrom($sender, $receiver)) {
			self::createTradeSession($sender, $receiver);
			self::removeRequest($sender);
			return true;
		}
		return false;
	}

	public static function denyRequest(Player $sender, Player $receiver): bool
	{
		if (self::hasRequestFrom($sender, $receiver)) {
			self::removeRequest($sender);
			return true;
		}
		return false;
	}

	public static function checkRequests(): void
	{
		foreach (self::$requests as $senderName => $requestData) {
			$sender = Server::getInstance()->getPlayerExact($senderName);
			$receiver = Server::getInstance()->getPlayerExact($requestData["receiver"]);

			if (!$sender instanceof Player || !$receiver instanceof Player) {
				if ($sender instanceof Player) {
					$sender->sendMessage(PlayerTrade::$prefix . PlayerTrade::getInstance()->getLanguage()->translateString("trade.requestCanceled.receiverLeft"));
				}
				if ($receiver instanceof Player) {
					$receiver->sendMessage(PlayerTrade::$prefix . PlayerTrade::getInstance()->getLanguage()->translateString("trade.requestCanceled.senderLeft", [
						$senderName
					]));
				}
				unset(self::$requests[$senderName]);
				continue;
			}

			if (time() > $requestData["expireAt"]) {
				$sender->sendMessage(PlayerTrade::$prefix . PlayerTrade::getInstance()->getLanguage()->translateString("trade.requestExpired.sender"));
				$receiver->sendMessage(PlayerTrade::$prefix . PlayerTrade::getInstance()->getLanguage()->translateString("trade.requestExpired.receiver", [
					$senderName
				]));
				unset(self::$requests[$senderName]);
			}
		}
	}
}
