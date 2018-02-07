<?php

namespace Skywars\particle\effects;

use pocketmine\level\particle\LavaDripParticle;
use pocketmine\level\particle\LavaParticle;
use Skywars\player\CustomPlayer;

/**
 * Class to show lava effects for players
 */
class LavaParticleEffect implements ParticleEffect {

	const PRODUCT_ID = 8;

	/**
	 * Overwrite as not selectable
	 * 
	 * @param CustomPlayer $player
	 */
	public function select(CustomPlayer $player) {
		//
	}

	/**
	 * Repeatable method with math logic to show lava effect around player
	 * 
	 * @param int $currentTick
	 * @param CustomPlayer $player
	 * @param array|null $showTo
	 */
	public function tick($currentTick, CustomPlayer $player, $showTo) {
		$player->getLevel()->addParticle(new LavaParticle($player->add(0, 1 + lcg_value(), 0)), $showTo);

		if ($player->lastMove >= $currentTick - 5) {
			$distance = -0.5 + lcg_value();
			$yaw = $player->yaw * M_PI / 180;
			$x = $distance * cos($yaw);
			$z = $distance * sin($yaw);
			$player->getLevel()->addParticle(new LavaDripParticle($player->add($x, 0.2, $z)), $showTo);
		}
	}

}
