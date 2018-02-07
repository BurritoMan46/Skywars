<?php

namespace Skywars\player;

use pocketmine\scheduler\PluginTask;

/**
 * Handle cooldown ticks (calls once per second)
 */
class CooldownTickTask extends PluginTask {

	public function onRun($currentTick) {
		foreach ($this->getOwner()->getServer()->getOnlinePlayers() as $player) {
			if ($player instanceof CustomPlayer) {
				$player->cooldownTick();
			}
		}
	}

}
