<?php

namespace Skywars;

use Skywars\player\CustomPlayer;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\SetTimePacket;

/**
 * Class Area create object for every map and for lobby,
 * save all params of current area (number of players, spawnpoint, players, etc)
 */
class Area {
	/** @var SkyWars */
	private $plugin;
	//spawnpoint coords
	/** @var int */
	public $centerX;
	/** @var int */
	public $y;
	/** @var int */
	public $centerZ;
	/** @var int */
	public $size;
	/** @var int */
	public $kickSize;
	/** @var bool */
	public $canPlaceBreak;
	/** @var int - time in seconds */
	public $time;
	/** @var bool */
	public $doCycle;
	/** @var bool */
	public $noDamage;

	/**
	 * Area object constructor, save main fields from params
	 * 
	 * @param \Skywars\Skywars $plugin
	 * @param int $centerX
	 * @param int $y
	 * @param int $centerZ
	 * @param int $size
	 * @param int $kickSize
	 * @param bool $canPlaceBreak
	 * @param int $time
	 * @param bool $doCycle
	 * @param bool $noDamage
	 */
	public function __construct(
			Skywars $plugin, 
			int $centerX, 
			int $y, 
			int $centerZ, 
			int $size, 
			int $kickSize, 
			bool $canPlaceBreak = false, 
			int $time = 1200, 
			bool $doCycle = false, 
			bool $noDamage = false) {
		$this->plugin = $plugin;
		$this->centerX = $centerX;
		$this->y = $y;
		$this->centerZ = $centerZ;
		$this->size = $size / 2;
		$this->kickSize = $kickSize / 2;
		$this->canPlaceBreak = $canPlaceBreak;
		$this->time = $time;
		$this->doCycle = $doCycle;
		$this->noDamage = $noDamage;
	}

	/**
	 * Teleport player to area,
	 * set options for player
	 * 
	 * @param CustomPlayer $player
	 * @param Vector3 $newPos
	 */
	public function setAreaFor(CustomPlayer $player, $newPos = NULL) {
		$player->currentArea = $this;
		if (!isset($newPos) || $newPos === null) {
			$newPos = new Vector3($this->centerX, $this->y, $this->centerZ);
		}
		$player->teleport($newPos);

		$player->setHealth(20);
		$player->setFood(20);
		$setTimePacket = new SetTimePacket();
		$setTimePacket->started = true;
		$setTimePacket->time = $this->time;
		$setTimePacket->started = $this->doCycle;
	}

	/**
	 * Check if player can place and break blocks inside current area
	 * @return bool
	 */
	public function canPlaceBreakBlocks() {
		return $this->canPlaceBreak;
	}

	/**
	 * Check if specified coords are inside area
	 * 
	 * @param int $x
	 * @param int $z
	 * @return bool
	 */
	public function inArea(int $x, int $z) {
		return ($x > $this->centerX - $this->kickSize && $z > $this->centerZ - $this->kickSize &&
				$x < $this->centerX + $this->kickSize && $z < $this->centerZ + $this->kickSize);
	}

}
