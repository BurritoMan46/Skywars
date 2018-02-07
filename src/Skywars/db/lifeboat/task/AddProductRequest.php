<?php

namespace Skywars\db\lifeboat\task;

use pocketmine\Server;
use pocketmine\utils\Utils;
use Skywars\player\CustomPlayer;
use pocketmine\utils\TextFormat;

/**
 * Send request to db to save bonus and new amount of coins (after transfer)
 */
class AddProductRequest extends DatabaseTask {
	/** @var bool*/
	public $buy;
	/** @var int */
	public $productId;
	/** @var string */
	public $productName;
	/** @var bool */
	public $single;
	/** @var int */
	public $amount;
	/** @var int */
	public $coins;
	/** @var \stdClass */
	public $virtualProducts;
	/** @var bool */
	public $alreadyOwned = false;
	/** @var bool */
	public $notEnoughCoins = false;

	/**
	 * Base class constructor, save buy params as fields
	 * 
	 * @param CustomPlayer $player
	 * @param bool $buy
	 * @param int $productId
	 * @param int $amount
	 */
	public function __construct(CustomPlayer $player, $buy, $productId, $amount = 1) {
		parent::__construct($player);

		$this->buy = $buy;
		$this->productId = $productId;
		$this->amount = $amount;
	}

	/**
	 * Send requests to db about valid buy
	 * 
	 * @return void
	 */
	public function onRun() {
		$player = $this->dbName;
		//find product in db
		$result = Utils::postURL('http://data.lbsg.net/apiv3/database.php', array(
					'auth' => $this->authString,
					'return' => true,
					'cmd' => "SELECT title,single,price FROM virtual_products WHERE ID = " . $this->productId
						), 5);
		if (substr($result, 0, 4) != "fail") {
			$raw_data = json_decode($result, true);
			$price = intval($raw_data["price"]) * $this->amount;
			$single = $raw_data["single"];
			$this->single = $single;
			$title = $raw_data["title"];
			$this->productName = str_replace("&", "§", $title);

			$result = Utils::postURL('http://data.lbsg.net/apiv3/database.php', array(
						'auth' => $this->authString,
						'return' => true,
						'cmd' => "SELECT coins,virtualProductsBought FROM login WHERE username = '$this->playerName'"
							), 5);
			if (substr($result, 0, 4) != "fail") {
				$raw_data = json_decode($result, true);
				$coins = $raw_data["coins"];
				$this->coins = $coins;
				$virtualProductsStr = $raw_data["virtualProductsBought"];
				$virtualProducts = AddProductRequest::getProductListArray($virtualProductsStr);
				$this->virtualProducts = (object) $virtualProducts;
				//check if player already have this product
				$owned = 0;
				if (isset($virtualProducts[$this->productId])) {
					if ($single) {
						$this->alreadyOwned = true;
						return;
					}
					$owned = $virtualProducts[$this->productId];
				}
				//check for enough amount of coins
				if ($this->buy && $coins < $price) {
					$this->notEnoughCoins = true;
					return;
				}

				$virtualProducts[$this->productId] = $owned + $this->amount;
				$this->virtualProducts = (object) $virtualProducts;
				if ($this->buy)
					$this->coins -= $price;

				$virtualProductsStr = AddProductRequest::getProductListString($virtualProducts);
				//save new amount of coins for player
				if ($this->buy) {
					Utils::postURL('http://data.lbsg.net/apiv3/database.php', array(
						'auth' => $this->authString,
						'return' => false,
						'cmd' => "UPDATE login SET coins = coins - $price, virtualProductsBought = '$virtualProductsStr' WHERE username = '". $this->playerName ."'"
							), 5);
				} else {
					Utils::postURL('http://data.lbsg.net/apiv3/database.php', array(
						'auth' => $this->authString,
						'return' => false,
						'cmd' => "UPDATE login SET virtualProductsBought = '$virtualProductsStr' WHERE username = '$this->playerName'"
							), 5);
				}
			}
		}
	}

	/**
	 * When db request is finished, save result in player data and inform player 
	 * about results
	 * 
	 * @param Server $server
	 * @return void
	 */
	public function onCompletion(Server $server) {
		$player = $server->getPlayer($this->playerName);
		if ($player === null || !($player instanceof CustomPlayer)) {
			return;
		}
		$player->coinsNum = $this->coins;
		$player->virtualPurchases = $this->virtualProducts;

		if (!$this->buy) {
			return;
		}

		$player->hasPurchaseTask = false;

		if (!$this->alreadyOwned && !$this->notEnoughCoins) {
			if ($this->single) {
				$player->sendMessage($player->getTranslatedString("PURCHASED_ITEM") . TextFormat::BOLD . $this->productName . TextFormat::RESET . TextFormat::GREEN . "!");
				$player->sendPopup($player->getTranslatedString("PURCHASED_ITEM") . TextFormat::BOLD . $this->productName . TextFormat::RESET . TextFormat::GREEN . "!");
			}
		} else {
			if ($this->notEnoughCoins) {
				$player->sendMessage($player->getTranslatedString("ERROR_PREFIX", TextFormat::RED) . $player->getTranslatedString("ERR_INSUFFICENT_COINS"));
				$player->sendPopup($player->getTranslatedString("ERR_INSUFFICENT_COINS"));
			} else {
				$player->sendMessage($player->getTranslatedString("ERROR_PREFIX", TextFormat::RED) . $player->getTranslatedString("ALREADY_PURCHASED"));
				$player->sendPopup($player->getTranslatedString("ALREADY_PURCHASED"));
			}
		}
	}

}
