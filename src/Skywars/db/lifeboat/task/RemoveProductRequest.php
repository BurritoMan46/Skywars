<?php

namespace Skywars\db\lifeboat\task;

use pocketmine\Server;
use pocketmine\utils\Utils;
use Skywars\player\CustomPlayer;

/**
 * Send request to remove product from db
 */
class RemoveProductRequest extends DatabaseTask {
	/** @var int */
	public $productId;
	/** @var int */
	public $amount;
	/** @var \stdClass */
	public $virtualProducts;

	/**
	 * Base class constructor, save params as fields
	 * 
	 * @param CustomPlayer $player
	 * @param int $productId
	 * @param int $amount
	 */
	public function __construct(CustomPlayer $player, $productId, $amount = 1) {
		parent::__construct($player);

		$this->productId = $productId;
		$this->amount = $amount;
	}

	/**
	 * Send request - ask for user products, then save new amount of specified product
	 * or remove it
	 */
	public function onRun() {
		$player = $this->dbName;
		var_dump($this->dbName);

		$result = Utils::postURL('http://data.lbsg.net/apiv3/database.php', array(
					'auth' => $this->authString,
					'return' => true,
					'cmd' => "SELECT virtualProductsBought FROM login WHERE username = '" . $this->playerName . "'"
						), 5);
		if (substr($result, 0, 4) != "fail") {
			$raw_data = json_decode($result, true);
			$virtualProductsStr = $raw_data["virtualProductsBought"];
			$virtualProducts = AddProductRequest::getProductListArray($virtualProductsStr);

			if (isset($virtualProducts[$this->productId])) {
				$virtualProducts[$this->productId] -= $this->amount;
				if ($virtualProducts[$this->productId] === 0) {
					unset($virtualProducts[$this->productId]);
				}
			}

			$this->virtualProducts = (object) $virtualProducts;

			$virtualProductsStr = AddProductRequest::getProductListString($virtualProducts);

			Utils::postURL('http://data.lbsg.net/apiv3/database.php', array(
				'auth' => $this->authString,
				'return' => false,
				'cmd' => "UPDATE login SET virtualProductsBought = '$virtualProductsStr' WHERE username = '$this->playerName'"
					), 5);
		}
	}

	/**
	 * When request is finished, save new options in player's data
	 * 
	 * @param Server $server
	 * @return void
	 */
	public function onCompletion(Server $server) {
		$player = $server->getPlayer($this->playerName);
		if ($player === null || !($player instanceof CustomPlayer)) {
			return;
		}
		$player->virtualPurchases = $this->virtualProducts;
	}

}
