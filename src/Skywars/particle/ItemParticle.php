<?php

namespace Skywars\particle;

use pocketmine\entity\Item;

class ItemParticle extends Item {
	/** @var float */
	protected $gravity = 0.1;
	/** @var float */
	protected $drag = 0.4;

}
