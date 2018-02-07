<?php

namespace Skywars\player\action;

use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
use Skywars\player\CustomPlayer;

/**
 * Handle compass teleporting actions
 */
class CompassActionItem extends ActionItem {

	public function __construct() {
		parent::__construct(Item::COMPASS, 0, 1, TextFormat::GREEN . "Compass", "Tap again to teleport to nearest player");
	}

	/**
	 * Base method - calls when player use item in game
	 * 
	 * @param CustomPlayer $player
	 */
	public function useItem(CustomPlayer $player) {
		$game = $player->currentGame;
		$tpPlayer = null;
		$tpDist = -1;
		foreach ($game->players as $p) {
			$d = $p->distance($player);
			if ($tpDist === -1 || $tpDist > $d) {
				$tpPlayer = $p;
				$tpDist = $d;
			}
		}
		if ($tpPlayer !== null) {
			$player->teleport($tpPlayer);
			$player->sendPopup($player->getTranslatedString("YOU_TELEPORTED", TextFormat::GREEN) . TextFormat::BOLD . $tpPlayer->getName());
		} else {
			$player->sendPopup($player->getTranslatedString("BASE_ERROR", TextFormat::RED));
		}
	}

}
