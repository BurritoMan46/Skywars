<?php

namespace Skywars\player\action;

use pocketmine\utils\TextFormat;
use Skywars\player\CustomPlayer;

/**
 * This is the base class for an action item.
 * An action item is an item that can be touched twice (in hotbar or inventory) to execute an action.
 */
class ActionItem extends NamedItem {
	/** @var string */
	private $tip;

	/**
	 * Main action item class constructor,
	 * call it for all action items, add extra info how to use item
	 * 
	 * @param int $id
	 * @param string $meta
	 * @param int $count
	 * @param string $name
	 * @param string $tip
	 */
	public function __construct($id, $meta, $count, $name, $tip = "Tap again to use") {
		parent::__construct($id, $meta, $count, $name);
		$this->tip = $tip;
	}

	/**
	 * Set action item as selected
	 * common actions are sendPopup and save in player settings
	 * 
	 * @param CustomPlayer $player
	 */
	public function selected(CustomPlayer $player) {
		if ($player->actionItem == $this) {
			$this->useItem($player);
			$player->actionItem = null;
		} else {
			$player->sendPopup(
					TextFormat::BOLD . $this->name . TextFormat::RESET . "\n" .
					TextFormat::GRAY . $this->tip
			);
			$player->actionItem = $this;
		}
		$player->getInventory()->setHeldItemIndex($player->getEmptyHotbarSlot());
		$player->previousHeldSlot = -1;
	}

	/**
	 * Calls when item has being used - popup item name
	 * 
	 * @param CustomPlayer $player
	 */
	public function useItem(CustomPlayer $player) {
		$player->sendPopup(TextFormat::BOLD . $this->name);
	}

}
