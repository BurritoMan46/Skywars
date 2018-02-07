<?php

namespace Skywars\gadget;

use pocketmine\entity\Living;
use pocketmine\network\protocol\AddMobPacket;
use pocketmine\Player;

/**
 * Describes logic of slime spawning
 */
class Slime extends Living {

	const NETWORK_ID = 37;
	/** @var int */
	public $size = 3;

	/**
	 * Get custom name of entity
	 * 
	 * @return string
	 */
	public function getName() {
		return "Slime";
	}

	/**
	 * Get info about entity
	 * 
	 * @return array
	 */
	public function getData() {
		return [
			16 => ["type" => 0, "value" => $this->size]
		];
	}

	/**
	 * Set specific spawn options for slime
	 * 
	 * @param Player $player
	 */
	public function spawnTo(Player $player) {
		$pk = new AddMobPacket();
		$pk->type = Slime::NETWORK_ID;
		$pk->eid = $this->getId();
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->pitch = $this->pitch;
		$pk->yaw = $this->yaw;
		$pk->metadata = $this->getData();

		$player->dataPacket($pk);
		$player->addEntityMotion($this->getId(), $this->motionX, $this->motionY, $this->motionZ);
		parent::spawnTo($player);
	}

}
