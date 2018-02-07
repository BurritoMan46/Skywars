<?php

namespace Skywars\player\action;

use pocketmine\item\Item;
use Skywars\player\CustomPlayer;

/**
 * Base class for plugin specific items like compass, returntolobby item, etc
 */
class NamedItem extends Item {

	/**
	 * Base class constructor, also save item name
	 * 
	 * @param int $id
	 * @param int $meta
	 * @param int $count
	 * @param string $name
	 */
	public function __construct($id, $meta, $count, $name) {
		parent::__construct($id, $meta, $count, $name);
		$this->name = $name;
	}

	/**
	 * Send popup to player when item is selected
	 * 
	 * @param CustomPlayer $player
	 */
	public function selected(CustomPlayer $player) {
		$player->sendPopup($this->name);
	}

}
