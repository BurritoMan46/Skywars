<?php

namespace Skywars\gadget;

use pocketmine\item\Item;
use pocketmine\level\Explosion;
use pocketmine\utils\TextFormat;
use Skywars\player\action\NamedItem;
use Skywars\player\CustomPlayer;

/**
 * Describe TNT item logic
 */
class ExplodeItem extends NamedItem {

	public function __construct() {
		parent::__construct(Item::TNT, 0, 1, TextFormat::RED . "Explode");
	}

	/**
	 * Calls when item is selected - explode TNT
	 * 
	 * @param CustomPlayer $player
	 * @return void
	 */
	public function selected(CustomPlayer $player) {
		parent::selected($player);

		$player->getInventory()->setHeldItemIndex($player->getEmptyHotbarSlot());
		if (!$player->cooldown(CustomPlayer::COOLDOWN_LOBBY, "morph_creeper_explode", 100, TextFormat::RED . "Explode")) {
			return;
		}
		$e = new Explosion($player->getPosition(), 8, $player);
		$e->explodeB();
	}

}
