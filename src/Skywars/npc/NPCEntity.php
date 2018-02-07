<?php

namespace Skywars\npc;

use pocketmine\entity\Human;
use pocketmine\Player;
use pocketmine\network\protocol\MovePlayerPacket;

/**
 * The NPC entity (used for kit statues)
 */
class NPCEntity extends Human {

	public $kitName = "";

	public function getSaveId() {
		return "Human";
	}

	public function spawnTo(Player $player) {
		parent::spawnTo($player);
	}

}
