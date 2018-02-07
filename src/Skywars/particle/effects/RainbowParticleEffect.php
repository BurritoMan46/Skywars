<?php

namespace Skywars\particle\effects;

use pocketmine\level\particle\DustParticle;
use Skywars\player\CustomPlayer;

/**
 * Describes logic for rainbow particle effect
 */
class RainbowParticleEffect implements ParticleEffect {

	const PRODUCT_ID = 15;

	/**
	 * Set current effect counter in player data
	 * @param CustomPlayer $player
	 */
	public function select(CustomPlayer $player) {
		$player->particleEffectExtra["i"] = 0;
	}

	/**
	 * Transfer hsv color code to rgb
	 * 
	 * @param int $h
	 * @param int $s
	 * @param int $v
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 */
	public static function hsv2rgb($h, $s, $v, &$r, &$g, &$b) {
		$h = (($h % 360) / 359) * 6;
		$s = ($s % 101) / 100;
		$i = floor($h);
		$f = $h - $i;

		$v = ($v % 101) / 100 * 255;
		$m = $v * (1 - $s) * 255;
		$n = $v * (1 - $s * $f) * 255;
		$k = $v * (1 - $s * (1 - $f)) * 255;

		$r = $g = $b = 0;
		if ($i == 0) {
			$r = $v;
			$g = $k;
			$b = $m;
		} else if ($i == 1) {
			$r = $n;
			$g = $v;
			$b = $m;
		} else if ($i == 2) {
			$r = $m;
			$g = $v;
			$b = $k;
		} else if ($i == 3) {
			$r = $m;
			$g = $n;
			$b = $v;
		} else if ($i == 4) {
			$r = $k;
			$g = $m;
			$b = $v;
		} else if ($i == 5 || $i == 6) {
			$r = $v;
			$g = $m;
			$b = $n;
		}
	}

	/**
	 * Repeatable math function to show effect for other players
	 * 
	 * @param $currentTick
	 * @param CustomPlayer $player
	 * @param array|null $showTo
	 */
	public function tick($currentTick, CustomPlayer $player, $showTo) {
		$n = $player->particleEffectExtra["i"] ++;
		RainbowParticleEffect::hsv2rgb($n * 2, 100, 100, $r, $g, $b);

		if ($player->lastMove < $currentTick - 5) {
			// idle particles
			$v = 2 * M_PI / 120 * ($n % 120);
			$i = 2 * M_PI / 60 * ($n % 60);
			$x = cos($i);
			$y = cos($v) * 0.5;
			$z = sin($i);

			$player->getLevel()->addParticle(new DustParticle($player->add($x, 2 - $y, -$z), $r, $g, $b), $showTo);
			$player->getLevel()->addParticle(new DustParticle($player->add(-$x, 2 - $y, $z), $r, $g, $b), $showTo);
		} else {
			// move particles
			for ($i = 0; $i < 2; $i++) {
				$distance = -0.5 + lcg_value();
				$yaw = $player->yaw * M_PI / 180 + (-0.5 + lcg_value()) * 90;
				$x = $distance * cos($yaw);
				$z = $distance * sin($yaw);
				$y = lcg_value() * 0.4 + 0.5;
				$player->getLevel()->addParticle(new DustParticle($player->add($x, $y, $z), $r, $g, $b), $showTo);
			}
		}
	}

}
