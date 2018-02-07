<?php

namespace Skywars\particle;

use Skywars\particle\effects\LavaParticleEffect;
use Skywars\particle\effects\ParticleEffect;
use Skywars\particle\effects\PortalParticleEffect;
use Skywars\particle\effects\RainbowParticleEffect;
use Skywars\particle\effects\RedstoneParticleEffect;
use Skywars\player\CustomPlayer;
use Skywars\Skywars;

/**
 * Handle particle actions and save instance of each effect
 */
class ParticleManager {
	/** @var LavaParticleEffect */
	public static $lava;
	/** @var RedstoneParticleEffect */
	public static $redstone;
	/** @var PortalParticleEffect */
	public static $portal;
	/** @var RainbowParticleEffect */
	public static $rainbow;

	/**
	 * Create instances of each particle effect
	 */
	public static function initParticleEffects() {
		self::$lava = new LavaParticleEffect();
		self::$redstone = new RedstoneParticleEffect();
		self::$portal = new PortalParticleEffect();
		self::$rainbow = new RainbowParticleEffect();
	}

	/** @var SkyWars */
	private $plugin;
	/** @var ParticleTask */
	private $task;
	/** @var TaskHandler */
	private $taskHandler;

	/**
	 * Base class constructor,
	 * initialize available particle effects
	 * create repeatable ParticleTask
	 * 
	 * @param Skywars $plugin
	 */
	public function __construct(Skywars $plugin) {
		self::initParticleEffects();
		$this->plugin = $plugin;
		$this->task = new ParticleTask($plugin);
		$this->taskHandler = $plugin->getServer()->getScheduler()->scheduleRepeatingTask($this->task, 1);
	}

	/**
	 * Recall task method to set particle effect on player
	 * 
	 * @param CustomPlayer $player
	 * @param ParticleEffect $effect
	 */
	public function setPlayerParticleEffect(CustomPlayer $player, ParticleEffect $effect) {
		$this->task->setPlayerParticleEffect($player, $effect);
	}

}
