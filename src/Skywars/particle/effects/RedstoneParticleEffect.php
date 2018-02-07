<?php

namespace Skywars\particle\effects;

use pocketmine\level\particle\RedstoneParticle;
use Skywars\player\CustomPlayer;

/**
 * Describes math for redstone particle effect
 */
class RedstoneParticleEffect implements ParticleEffect {

	const PRODUCT_ID = 7;

	/**
	 * Set current effect counter in player data
	 * 
	 * @param CustomPlayer $player
	 */
	public function select(CustomPlayer $player) {
		$player->particleEffectExtra["i"] = 0;
	}

	/**
	 * Repeatable math function to show effect for other players
	 * 
	 * @param $currentTick
	 * @param CustomPlayer $player
	 * @param array|null $showTo
	 */
	public function tick($currentTick, CustomPlayer $player, $showTo) {
		if ($player->lastMove < $currentTick - 5) {
			// idle particles
			$n = $player->particleEffectExtra["i"] ++;

			$v = 2 * M_PI / 120 * ($n % 120);
			$i = 2 * M_PI / 70 * ($n % 70);
			$x = cos($i);
			$y = cos($v);
			$z = sin($i);

			$player->getLevel()->addParticle(new RedstoneParticle($player->add($x, 1 - $y, -$z)), $showTo);
			$player->getLevel()->addParticle(new RedstoneParticle($player->add(-$x, 1 - $y, $z)), $showTo);
		} else {
			// move particles
			if ($player->particleEffectExtra["i"] !== 0) {
				$player->particleEffectExtra["i"] = 0;
			}

			$distance = -0.5 + lcg_value();
			$yaw = $player->yaw * M_PI / 180;
			$x = $distance * cos($yaw);
			$z = $distance * sin($yaw);
			$y = lcg_value() * 0.4;
			$player->getLevel()->addParticle(new RedstoneParticle($player->add($x, $y, $z)), $showTo);
		}
	}

}
