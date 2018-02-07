<?php

namespace Skywars\player;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\Player;

/**
 * Handle damage info methods - save damage type, item, damager name
 */
class DamageInfo {

	/** @var int */
	public $damage;
	/** @var int */
	public $damageType;
	/** @var string */
	public $damagerName = null;
	/** @var Item */
	public $damagerItem = null;

	/**
	 * Base class constructor, prepare fields with damage info
	 * 
	 * @param EntityDamageEvent $event
	 * @param int $addDamage
	 */
	public function __construct(EntityDamageEvent $event, $addDamage = 0) {
		$this->damageType = $event->getCause();
		$this->damage = $event->getDamage() + $addDamage;
		if ($event instanceof EntityDamageByEntityEvent) {
			$damager = $event->getDamager();
			if ($damager instanceof Player) {
				$this->damagerName = $damager->getName();
				$this->damagerItem = $damager->getInventory()->getItemInHand();
			}
		}
	}

	/**
	 * Returns item name by its id
	 * 
	 * @param int $id
	 * @return string
	 */
	private function getNameForItem($id) {
		if ($id === 276) {
			return "Diamond Sword";
		} else if ($id === 283) {
			return "Gold Sword";
		} else if ($id === 267) {
			return "Iron Sword";
		} else if ($id === 272) {
			return "Stone Sword";
		} else if ($id === 268) {
			return "Wooden Sword";
		} else if ($id === 279) {
			return "Diamond Axe";
		} else if ($id === 286) {
			return "Gold Axe";
		} else if ($id === 258) {
			return "Iron Axe";
		} else if ($id === 275) {
			return "Stone Axe";
		} else if ($id === 271) {
			return "Wooden Axe";
		} else if ($id === 278) {
			return "Diamond Pickaxe";
		} else if ($id === 285) {
			return "Gold Pickaxe";
		} else if ($id === 257) {
			return "Iron Pickaxe";
		} else if ($id === 274) {
			return "Stone Pickaxe";
		} else if ($id === 270) {
			return "Wooden Pickaxe";
		} else if ($id === 277) {
			return "Diamond Shovel";
		} else if ($id === 284) {
			return "Gold Shovel";
		} else if ($id === 256) {
			return "Iron Shovel";
		} else if ($id === 273) {
			return "Stone Shovel";
		} else if ($id === 269) {
			return "Wooden Shovel";
		} else if ($id === 332) {
			return "Snowball";
		} else if ($id === 281) {
			return "Bow";
		}
		return null;
	}

	/**
	 * Save name for current damage type
	 * @return string
	 */
	public function toString() {
		$damageName = "Unknown";
		if ($this->damageType === EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
			if ($this->damagerName !== null) {
				$damageName = $this->damagerName;
				if ($this->damagerItem !== null && $this->damagerItem->getId() !== Item::AIR) {
					$itmName = $this->getNameForItem($this->damagerItem->getID());
					if ($itmName !== null) {
						$damageName .= " [" . $itmName . "]";
					}
				}
			} else {
				$damageName = "Entity";
			}
		} else {
			$causeId = $this->damageType;
			if ($causeId === 0) {
				$damageName = "Contact";
			} else if ($causeId === 1) {
				$damageName = "Entity";
			} else if ($causeId === 2) {
				$damageName = "Projectile";
			} else if ($causeId === 3) {
				$damageName = "Suffocation";
			} else if ($causeId === 4) {
				$damageName = "Fall";
			} else if ($causeId === 5) {
				$damageName = "Fire";
			} else if ($causeId === 6) {
				$damageName = "Fire";
			} else if ($causeId === 7) {
				$damageName = "Lava";
			} else if ($causeId === 8) {
				$damageName = "Drowning";
			} else if ($causeId === 9) {
				$damageName = "Explosion";
			} else if ($causeId === 10) {
				$damageName = "Explosion";
			} else if ($causeId === 11) {
				$damageName = "Void";
			} else if ($causeId === 12) {
				$damageName = "Suicide";
			} else if ($causeId === 13) {
				$damageName = "Magic";
			}
		}
		return $damageName;
	}

}
