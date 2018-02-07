<?php

namespace Skywars\game;

use pocketmine\item\Item;

/**
 * Chest loot related stuff
 */
class ChestLoot {
	/** @var bool */
	public static $lootInitialized = false;
	/** @var aray */
	public static $loot = [];
	/** @var int */
	public static $lootTotal = 0;

	/**
	 * create array of available items to game chests
	 */
	public static function initLoot() {
		self::$loot = [];

		// resources
		array_push(self::$loot, new ChestLoot(0.04, 0.2, Item::STONE, 0, 6, 64));
		array_push(self::$loot, new ChestLoot(0.05, 0.1, Item::COBBLESTONE, 0, 6, 64));
		array_push(self::$loot, new ChestLoot(0.05, 0.1, Item::DIRT, 0, 6, 64));
		array_push(self::$loot, new ChestLoot(0.05, 0.1, Item::WOODEN_PLANKS, 0, 6, 64));
		array_push(self::$loot, new ChestLoot(0.02, 0.35, Item::WOOD, 0, 4, 16));
		array_push(self::$loot, new ChestLoot(0.02, 0.24, Item::SAND, 0, 6, 32));
		array_push(self::$loot, new ChestLoot(0.02, 0.24, Item::GRAVEL, 0, 6, 32));
		array_push(self::$loot, new ChestLoot(0.001, 12, Item::BUCKET, 10, 1, 1));

		// food
		array_push(self::$loot, new ChestLoot(0.01, 0.3, Item::RAW_PORKCHOP, 0, 2, 12));
		array_push(self::$loot, new ChestLoot(0.006, 0.6, Item::COOKED_PORKCHOP, 0, 1, 6));
		array_push(self::$loot, new ChestLoot(0.01, 0.3, Item::RAW_CHICKEN, 0, 2, 12));
		array_push(self::$loot, new ChestLoot(0.006, 0.6, Item::COOKED_CHICKEN, 0, 1, 6));
		array_push(self::$loot, new ChestLoot(0.01, 0.3, Item::RAW_BEEF, 0, 2, 12));
		array_push(self::$loot, new ChestLoot(0.006, 0.6, Item::COOKED_BEEF, 0, 1, 6));
		array_push(self::$loot, new ChestLoot(0.008, 0.4, Item::APPLE, 0, 1, 10));
		array_push(self::$loot, new ChestLoot(0.008, 0.4, Item::BAKED_POTATO, 0, 1, 10));
		array_push(self::$loot, new ChestLoot(0.008, 0.4, Item::BEETROOT, 0, 1, 10));
		array_push(self::$loot, new ChestLoot(0.005, 2, Item::CAKE, 0, 1, 1));

		// armor
		array_push(self::$loot, new ChestLoot(0.04, 1, Item::LEATHER_CAP, 0, 1, 1));
		array_push(self::$loot, new ChestLoot(0.02, 6, Item::LEATHER_TUNIC, 0, 1, 1));
		array_push(self::$loot, new ChestLoot(0.025, 4, Item::LEATHER_PANTS, 0, 1, 1));
		array_push(self::$loot, new ChestLoot(0.04, 1, Item::LEATHER_BOOTS, 0, 1, 1));

		array_push(self::$loot, new ChestLoot(0.02, 2 + 1, Item::GOLD_HELMET, 0, 1, 1));
		array_push(self::$loot, new ChestLoot(0.006, 10 + 3, Item::GOLD_CHESTPLATE, 0, 1, 1));
		array_push(self::$loot, new ChestLoot(0.008, 6 + 3, Item::GOLD_LEGGINGS, 0, 1, 1));
		array_push(self::$loot, new ChestLoot(0.039, 1 + 1, Item::GOLD_BOOTS, 0, 1, 1));

		array_push(self::$loot, new ChestLoot(0.019, 2 + 2, Item::CHAIN_HELMET, 0, 1, 1));
		array_push(self::$loot, new ChestLoot(0.005, 10 + 4, Item::CHAIN_CHESTPLATE, 0, 1, 1));
		array_push(self::$loot, new ChestLoot(0.007, 8 + 4, Item::CHAIN_LEGGINGS, 0, 1, 1));
		array_push(self::$loot, new ChestLoot(0.038, 1 + 2, Item::CHAIN_BOOTS, 0, 1, 1));

		array_push(self::$loot, new ChestLoot(0.018, 2 + 3, Item::IRON_HELMET, 0, 1, 1));
		array_push(self::$loot, new ChestLoot(0.0006, 12 + 5, Item::IRON_CHESTPLATE, 0, 1, 1));
		array_push(self::$loot, new ChestLoot(0.0009, 10 + 5, Item::IRON_LEGGINGS, 0, 1, 1));
		array_push(self::$loot, new ChestLoot(0.018, 2 + 3, Item::IRON_BOOTS, 0, 1, 1));

		array_push(self::$loot, new ChestLoot(0.001, 3 + 4, Item::DIAMOND_HELMET, 0, 1, 1));
		array_push(self::$loot, new ChestLoot(0.001, 3 + 4, Item::DIAMOND_BOOTS, 0, 1, 1));

		// tools
		array_push(self::$loot, new ChestLoot(0.05, 1.2, Item::WOODEN_SWORD, 0, 1, 1));
		array_push(self::$loot, new ChestLoot(0.01, 3.6, Item::STONE_SWORD, 0, 1, 1));
		array_push(self::$loot, new ChestLoot(0.007, 6, Item::GOLD_SWORD, 0, 1, 1));
		array_push(self::$loot, new ChestLoot(0.001, 12, Item::IRON_SWORD, 0, 1, 1));
		array_push(self::$loot, new ChestLoot(0.0004, 15, Item::DIAMOND_SWORD, 0, 1, 1));
		array_push(self::$loot, new ChestLoot(0.007, 7, Item::IRON_AXE, 0, 1, 1));
		array_push(self::$loot, new ChestLoot(0.001, 12, Item::DIAMOND_AXE, 0, 1, 1));
		array_push(self::$loot, new ChestLoot(0.01, 0.3, Item::SNOWBALL, 0, 4, 16));

		self::$lootInitialized = true;

		$totalValue = 0;
		foreach (self::$loot as $loot) {
			$totalValue += $loot->chances;
		}
		self::$lootTotal = $totalValue;
	}

	/**
	 * Logic of randomizing items
	 * 
	 * @param int $totalWeight
	 * @return null
	 */
	public static function getRandomItem(&$totalWeight) {
		if (!self::$lootInitialized) {
			self::initLoot();
		}

		$random = lcg_value();
		$currValue = 0.0;
		foreach (self::$loot as $loot) {
			if ($random >= $currValue && $random < $currValue + $loot->chances) {
				$totalWeight += $loot->weight;
				return $loot->getItem();
			}
			$currValue += $loot->chances;
		}
		return null;
	}

	/** @var float */
	public $chances;
	/** @var float */
	public $weight;
	/** @var int */
	public $itemId;
	/** @var int */
	public $itemDmg;
	/** @var int */
	public $minCount;
	/** @var int */
	public $maxCount;

	/**
	 * Base class constructor, creates chest item with additional options
	 * 
	 * @param float $chances
	 * @param float $weight
	 * @param int $itemId
	 * @param int $itemDmg
	 * @param int $minCount
	 * @param int $maxCount
	 */
	public function __construct($chances, $weight, $itemId, $itemDmg, $minCount, $maxCount) {
		$this->chances = $chances;
		$this->weight = $weight;
		$this->itemId = $itemId;
		$this->itemDmg = $itemDmg;
		$this->minCount = $minCount;
		$this->maxCount = $maxCount;
	}

	/**
	 * Use to get item from current ChestLoot
	 * @return Item
	 */
	public function getItem() {
		return Item::get($this->itemId, $this->itemDmg, rand($this->minCount, $this->maxCount));
	}

}
