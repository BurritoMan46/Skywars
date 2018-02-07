<?php

namespace Skywars\db\lifeboat;

use Skywars\player\CustomPlayer;
use Skywars\Skywars;

/**
 * Describe product tasks sending requests to db, 
 * handle vip product ids for each game type
 */
class LifeboatDatabase {

	const AUTH_STRING = 'bjPzxd84W99s3gsy5kX2f9Ww';
	const PRODUCT_UBERVIP = 1;
	const PRODUCT_SG_VIP = 2;
	const PRODUCT_SG_VIP_PLUS = 3;
	const PRODUCT_CTF_VIP = 4;
	const PRODUCT_WALLS_OLD_VIP = 5;
	const PRODUCT_SW_VIP = 6;
	const PRODUCT_WALL_VIP = 7;
	const PRODUCT_WALL_VIP_PLUS = 8;
	/** @var Skywars */
	private $plugin;

	public function __construct(Skywars $plugin) {
		$this->plugin = $plugin;
	}

	public function addCoins(CustomPlayer $player, $amount) {
		//$this->plugin->getServer()->getScheduler()->scheduleAsyncTask(new task\AddCoinsRequest($player, $amount));
	}

	public function removeProduct(CustomPlayer $player, $productId, $amount) {
		$this->plugin->getServer()->getScheduler()->scheduleAsyncTask(new task\RemoveProductRequest($player, $productId, $amount));
	}

	public function buyProduct(CustomPlayer $player, $productId, $amount) {
		$this->plugin->getServer()->getScheduler()->scheduleAsyncTask(new task\AddProductRequest($player, true, $productId, $amount));
	}

}
