<?php

namespace Skywars\gadget;

use pocketmine\entity\Entity;
use pocketmine\entity\Snowball;
use pocketmine\item\Item;
use pocketmine\network\Network;
use pocketmine\network\protocol\AddItemEntityPacket;
use pocketmine\network\protocol\SetEntityMotionPacket;
use pocketmine\Player;

/**
 * Describe coinbomb spawn logic
 */
class CoinBombProjectile extends Snowball {
	/** @var float */
	protected $gravity = 0.04;
	/** @var float */
	protected $drag = 0.02;

	/**
	 * Set spawn options for coinbomb
	 * 
	 * @param Player $player
	 */
	public function spawnTo(Player $player) {
		$pk = new AddItemEntityPacket;
		$pk->eid = $this->getID();
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->roll = 0;
		$pk->item = Item::get(175);
		$player->dataPacket($pk);

		$pk = new SetEntityMotionPacket();
		$pk->entities = [
			[$this->getID(), $this->motionX, $this->motionY, $this->motionZ]
		];
		$player->dataPacket($pk);

		Entity::spawnTo($player);
	}

}
