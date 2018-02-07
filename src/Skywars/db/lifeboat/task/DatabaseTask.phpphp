<?php

namespace Skywars\db\lifeboat\task;

use pocketmine\scheduler\AsyncTask;
use Skywars\db\lifeboat\LifeboatDatabase;
use Skywars\player\CustomPlayer;

/**
 * Common class for shop db tasks,
 * contains formatting functions for product list
 */
abstract class DatabaseTask extends AsyncTask {
	/** @var string */
	protected $authString;
	/** @var string */
	protected $playerName;
	/** @var string */
	protected $dbName;

	/**
	 * Base class constructor, save player name and common db options
	 * 
	 * @param CustomPlayer $player
	 */
	public function __construct(CustomPlayer $player) {
		$this->authString = LifeboatDatabase::AUTH_STRING;
		$this->playerName = $player->getName();

		$this->playerName = preg_replace('/[^a-z_\-0-9]/i', '', $this->playerName);
		$this->dbName = str_replace('_', '\_', $this->playerName);
	}

	/**
	 * Format product list string as array
	 * 
	 * @param string $str
	 * @return int|array
	 */
	public static function getProductListArray($str) {
		$arr = [];
		if ($str == "") {
			return $arr;
		}
		foreach (explode(",", $str) as $product) {
			$split = strpos($product, ":");
			if ($split !== false) {
				$prodId = intval(substr($product, 0, $split));
				$prodCount = intval(substr($product, $split + 1));
				$arr[$prodId] = $prodCount;
			} else {
				$arr[intval($product)] = 1;
			}
		}
		return $arr;
	}

	/**
	 * Format product array to string
	 * 
	 * @param array $arr
	 * @return string
	 */
	public static function getProductListString($arr) {
		$str = "";
		$first = true;
		foreach ($arr as $id => $count) {
			if ($first) {
				$first = false;
			} else {
				$str .= ",";
			}
			$str .= $id . ":" . $count;
		}
		return $str;
	}

}
