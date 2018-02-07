<?php

namespace Skywars\gadget;

use pocketmine\scheduler\PluginTask;
use Skywars\Skywars;

class SpawnTreasureChestTask extends PluginTask {
	/** @var TreasureChestManager */
	private $mgr;

	/**
	 * Base class constructor, save TreasureChestManager object as field
	 * 
	 * @param Skywars $plugin
	 * @param \Skywars\gadget\TreasureChestManager $mgr
	 */
	public function __construct(Skywars $plugin, TreasureChestManager $mgr) {
		parent::__construct($plugin);
		$this->mgr = $mgr;
	}

	/**
	 * Repeatable task to create treasure chest randomly
	 * 
	 * @param int $currentTick
	 */
	public function onRun($currentTick) {
		$this->mgr->spawnChestAtRandomPosition();
		$this->getOwner()->getServer()->getScheduler()->scheduleDelayedTask($this, 20 * rand(60 * 60 * 2, 60 * 60 * 6));
	}

}
