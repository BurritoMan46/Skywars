<?php

namespace Skywars\player\action;

use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
use Skywars\player\CustomPlayer;

/**
 * Logic for item returning to lobby
 */
class ReturnToLobbyActionItem extends ActionItem {

	public function __construct() {
		parent::__construct(Item::END_PORTAL, 0, 1, TextFormat::GOLD . "Return to lobby", "Tap again to return to lobby");
	}

	/**
	 * Base function, called when player use item
	 * @param CustomPlayer $player
	 */
	public function useItem(CustomPlayer $player) {
		parent::useItem($player);
		$player->returnToLobby();
	}

}
