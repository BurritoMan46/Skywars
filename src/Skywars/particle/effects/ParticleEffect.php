<?php

namespace Skywars\particle\effects;

use Skywars\player\CustomPlayer;

/**
 * Describe basic particle effect methods
 */
interface ParticleEffect {
	/**
	 * Calls when player set effect as selected
	 * @param CustomPlayer $player
	 */
	public function select(CustomPlayer $player);

	/**
	 * Common repeatable method with math logic of effect
	 * 
	 * @param $currentTick
	 * @param CustomPlayer $player
	 * @param array|null $showTo
	 */
	public function tick($currentTick, CustomPlayer $player, $showTo);
}
