<?php

namespace Skywars\db\lifeboat\task;

use pocketmine\utils\Utils;
use Skywars\player\CustomPlayer;

/**
 * Send db request to add coins on player's bill
 */
class AddCoinsRequest extends DatabaseTask {
	/** @var int */
	public $coins;

	/**
	 * Constructor calls parent method, save coins amount as field
	 * 
	 * @param CustomPlayer $player
	 * @param int $coins
	 */
	public function __construct(CustomPlayer $player, $coins) {
		parent::__construct($player);
		$this->coins = $coins;
	}

	/**
	 * Send db request
	 */
	public function onRun() {
		$player = $this->playerName;
		$coins = intval($this->coins);
		Utils::postURL('http://data.lbsg.net/apiv3/database.php', array(
			'auth' => $this->authString,
			'return' => false,
			'cmd' => "UPDATE login SET coins = coins + $coins WHERE username = '$this->playerName'"
				), 5);
	}

}
