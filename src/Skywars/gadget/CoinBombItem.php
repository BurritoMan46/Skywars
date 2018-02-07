<?php

namespace Skywars\gadget;

use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\utils\TextFormat;
use Skywars\player\action\ActionItem;
use Skywars\player\CustomPlayer;

/**
 * CoinBomb is a bonus item which changes player's skin,
 * gives him throwable coins
 */
class CoinBombItem extends ActionItem {

	/**
	 * Base class constructor
	 */
	public function __construct() {
		parent::__construct(175, 0, 1, TextFormat::GOLD . "Coin Bomb", "Tap again to throw a coin bomb. " . TextFormat::RED . "Costs 100 coins.");
	}

	/**
	 * Calls when player try to throw coinbomb - allow it if he has enough money
	 * 
	 * @param CustomPlayer $player
	 * @return void
	 */
	public function useItem(CustomPlayer $player) {
		parent::useItem($player);

		if (!$player->cooldown(CustomPlayer::COOLDOWN_LOBBY, "morph_villager_coinbomb", 100, TextFormat::GOLD . "Coin Bomb")) {
			return;
		}
		if ($player->coinsNum > 100) {
			$player->addCoins(-100);
			$this->launchCoinBomb($player);
		} else {
			$player->sendTip($player->getTranslatedString("ERR_INSUFFICENT_COINS", TextFormat::RED));
		}
	}

	/**
	 * Throwing bomb math logic
	 * 
	 * @param CustomPlayer $player
	 */
	private function launchCoinBomb(CustomPlayer $player) {
		$nbt = new Compound("", [
			"Pos" => new Enum("Pos", [
				new DoubleTag("", $player->x),
				new DoubleTag("", $player->y + $player->getEyeHeight()),
				new DoubleTag("", $player->z)
					]),
			"Motion" => new Enum("Motion", [
				new DoubleTag("", -sin(($player->yaw) / 180 * M_PI) * cos(($player->pitch) / 180 * M_PI)),
				new DoubleTag("", -sin(($player->pitch) / 180 * M_PI)),
				new DoubleTag("", cos(($player->yaw) / 180 * M_PI) * cos(($player->pitch) / 180 * M_PI))
					]),
			"Rotation" => new Enum("Rotation", [
				new FloatTag("", $player->yaw),
				new FloatTag("", $player->pitch)
					]),
		]);
		$f = 1.5;
		$snowball = new CoinBombProjectile($player->chunk, $nbt, $player);
		$snowball->setMotion($snowball->getMotion()->multiply($f));
		$snowball->spawnToAll();
	}

}
