<?php

namespace Skywars\gadget;

use pocketmine\entity\Item;

/**
 * Help class to throw coins as bomb, describes motion logic
 */
class CoinBombItemEntity extends Item {
	/** @var int */
	public $value = 5;

	/**
	 * Set motion options when bomb has been thrown
	 */
	public function updateMovement() {
		if (($this->lastMotionX != $this->motionX or $this->lastMotionY != $this->motionY or $this->lastMotionZ != $this->motionZ)) {
			$this->lastMotionX = $this->motionX;
			$this->lastMotionY = $this->motionY;
			$this->lastMotionZ = $this->motionZ;

			foreach ($this->hasSpawned as $player) {
				$player->addEntityMotion($this->id, $this->motionX, $this->motionY, $this->motionZ);
			}
		}
	}

}
