<?php

namespace Skywars\game;

use pocketmine\math\Vector3;

/**
 * Contains information about current game map
 */
class GameMapSourceInfo {

	/** @var Vector3 */
	public $pos;
	/** @var int*/
	public $size;
	/** @var int */
	public $kickSize;
	/** @var int */
	public $minPlayers = 6;
	/** @var int */
	public $maxPlayers = 8;
	/** @var int */
	public $time = 0;
	/** @var bool */
	public $dayCycle = true;
	/** @var array */
	public $positions = [];
	/** @var array */
	public $chunks = [];

}
