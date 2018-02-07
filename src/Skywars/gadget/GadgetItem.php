<?php

namespace Skywars\gadget;

use Skywars\player\action\NamedItem;
use Skywars\player\CustomPlayer;

/**
 * Extends NamedItem with such additional options as color and popup when selected
 */
class GadgetItem extends NamedItem {
	/** @var string */
	private $color;
	/** @var int */
	private $prodId;

	/**
	 * Create gadget item
	 * 
	 * @param int $id
	 * @param int $meta
	 * @param int $count
	 * @param int $prodId
	 * @param string $name
	 * @param string $color
	 */
	public function __construct($id, $meta, $count, $prodId, $name, $color = "") {
		parent::__construct($id, $meta, $count, $name);
		$this->prodId = $prodId;
		$this->color = $color;
	}

	/**
	 * Send info about gadget when it has been selected
	 * 
	 * @param CustomPlayer $player
	 */
	public function selected(CustomPlayer $player) {
		$player->sendPopup($this->color . $player->getProductAmount($this->prodId) . " " . $this->name);
	}

}
