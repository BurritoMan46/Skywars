<?php

namespace Skywars\particle;

use pocketmine\scheduler\PluginTask;
use Skywars\particle\effects\ParticleEffect;
use Skywars\player\CustomPlayer;
use Skywars\Skywars;

/**
 * Main task to show repeatable particle effects for specified players
 */
class ParticleTask extends PluginTask {
	/** @var SkyWars */
	private $plugin;
	/** @var array */
	private $effects = [];

	/**
	 * Basic class constructor
	 * 
	 * @param Skywars $plugin
	 */
	public function __construct(Skywars $plugin) {
		parent::__construct($plugin);
		$this->plugin = $plugin;
	}

	/**
	 * Set some effect for specified player
	 * 
	 * @param CustomPlayer $player
	 * @param ParticleEffect $effect
	 */
	public function setPlayerParticleEffect(CustomPlayer $player, ParticleEffect $effect) {
		$player->particleEffectExtra = [];
		$this->effects[$player->getId()] = [$player, $effect];
		$effect->select($player);
	}

	/**
	 * Basic repeatable task to show active effects to all viewers
	 * 
	 * @param $currentTick
	 */
	public function onRun($currentTick) {
		foreach ($this->effects as $id => $data) {
			/** @var CustomPlayer $player */
			$player = $data[0];
			/** @var ParticleEffect $effect */
			$effect = $data[1];

			if ($player->closed) {
				unset($this->effects[$id]);
				continue;
			}

			if ($player->isSpectating) {
				continue;
			}

			$showTo = $player->getViewers();
			$showTo[] = $player;
			$effect->tick($currentTick, $player, $showTo);
		}
	}

}
