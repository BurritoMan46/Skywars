<?php

namespace Skywars\db\lifeboat\task;

use pocketmine\Server;
use pocketmine\utils\Utils;
use Skywars\player\CustomPlayer;

/**
 * Send request to db about bought products
 */
class GetProductTask extends DatabaseTask {
	/** @var array|null */
	private $virtualProducts = null;

	/**
	 * Base class constructor (from parent)
	 * @param CustomPlayer $player
	 */
	public function __construct(CustomPlayer $player) {
		parent::__construct($player);
	}

	/**
	 * Send request and save result products list as object
	 */
	public function onRun() {
		$result = Utils::postURL('http://data.lbsg.net/apiv3/database.php', array(
					'auth' => $this->authString,
					'return' => true,
					'cmd' => "SELECT virtualProductsBought FROM login WHERE username = '$this->playerName'"
						), 5);

		if ($result !== false && !stristr($result, "fail")) {
			$raw_data = json_decode($result, true);
			if (is_array($raw_data) && isset($raw_data["virtualProductsBought"])) {
				$virtualProductsStr = $raw_data["virtualProductsBought"];
				$virtualProducts = self::getProductListArray($virtualProductsStr);
				$this->virtualProducts = (object) $virtualProducts;
			}
		}
	}

	/**
	 * Save reqult ob request into player's data
	 * 
	 * @param Server $server
	 * @return void
	 */
	public function onCompletion(Server $server) {
		$player = $server->getPlayer($this->playerName);
		if ($player === null || !($player instanceof CustomPlayer)) {
			return;
		}
		if (!is_null($this->virtualProducts)) {
			$player->virtualPurchases = $this->virtualProducts;
		}
	}

}
