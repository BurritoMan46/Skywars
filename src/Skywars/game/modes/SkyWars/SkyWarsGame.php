<?php

namespace Skywars\game\modes\SkyWars;

use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\inventory\ChestInventory;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\tile\Tile;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\level\Level;
use pocketmine\block\Block;
use pocketmine\item\Item;
use Skywars\Area;
use Skywars\game\Game;
use Skywars\game\GameManager;
use Skywars\game\GameMapSourceInfo;
use Skywars\player\CustomPlayer;
use Skywars\Skywars as Plugin;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ShortTag;
use Skywars\particle\ItemParticle;

/**
 * The SkyWars game logic
 *
 */
class SkyWarsGame extends Game implements Listener {
	/** @var string */
	public static $type = "SkyWars";
	/** @var int */
	public $noDamageTime = 5;
	/** @var bool */
	public $displayDamageList = true;
	/** @var int */
	public $multipleDeaths = 0;
	/** @var int */
	public $coinsForParticipation = 1;
	/** @var int */
	public $coinsForWinning = 5;
	/** @var int */
	public $coinsForKill = 2;
	/** @var int */
	public $coinsForAssist = 1;
	/** @var bool */
	public $blockBreakAllowed = true;
	/** @var bool */
	public $fillChests = true;
	/** @var array */
	public $lootPositions = [];

	/**
	 * Base class construct - calls parent constructor
	 * 
	 * @param Plugin $plugin
	 * @param GameManager $gameManager
	 * @param string $name
	 * @param Area $gameArea
	 * @param GameMapSourceInfo $source
	 * @param int $minPlayers
	 * @param int $maxPlayers
	 */
	public function __construct(Plugin $plugin, GameManager $gameManager, $name, Area $gameArea, GameMapSourceInfo $source, $minPlayers, $maxPlayers) {
		parent::__construct($plugin, $gameManager, $name, $gameArea, $source, $minPlayers, $maxPlayers);
	}

	/**
	 * Calls parent start method
	 */
	public function start() {
		parent::start();
	}

	/**
	 * Set block to specified position
	 * 
	 * @param Level $level
	 * @param Vector3 $pos
	 * @param Block $block
	 */
	private function setBlock(Level $level, Vector3 $pos, $block) {
		$level->setBlock($pos, $block);
		$level->setBlockSkyLightAt($pos->x, $pos->y, $pos->z, 15);
		$level->setBlockSkyLightAt($pos->x + 1, $pos->y, $pos->z, 15);
		$level->setBlockSkyLightAt($pos->x - 1, $pos->y, $pos->z, 15);
		$level->setBlockSkyLightAt($pos->x, $pos->y, $pos->z + 1, 15);
		$level->setBlockSkyLightAt($pos->x, $pos->y, $pos->z - 1, 15);
		$level->setBlockSkyLightAt($pos->x, $pos->y + 1, $pos->z, 15);
		$level->setBlockSkyLightAt($pos->x, $pos->y - 1, $pos->z, 15);
	}

	/**
	 * Make skyBox of glass inside game to put inside it player or something else
	 * 
	 * @param Level $level
	 * @param Vector3 $pos
	 * @param bool $remove
	 */
	public function makeSkyBox(Level $level, Vector3 $pos, $remove = false) {
		$block = Block::get(Block::GLASS);
		if ($remove) {
			$block = Block::get(Block::AIR);
		}
		$this->makeSkyBoxLayer($level, $pos->add(0, -1, 0), $block, false);
		$this->makeSkyBoxLayer($level, $pos->add(0, 0, 0), $block);
		$this->makeSkyBoxLayer($level, $pos->add(0, 1, 0), $block);
		$this->makeSkyBoxLayer($level, $pos->add(0, 2, 0), $block);
		$this->makeSkyBoxLayer($level, $pos->add(0, 3, 0), $block, false);
	}

	/**
	 * Calls in makeSkyBox method to create glass blocks with or without hole in center
	 * 
	 * @param Level $level
	 * @param Vector3 $pos
	 * @param Block $block
	 * @param bool $holeInCenter
	 */
	private function makeSkyBoxLayer(Level $level, Vector3 $pos, Block $block, $holeInCenter = true) {
		if (!$holeInCenter) {
			$this->setBlock($level, $pos->add(0, 0, 0), $block);
		}

		$this->setBlock($level, $pos->add(-1, 0, 0), $block);
		$this->setBlock($level, $pos->add(1, 0, 0), $block);
		$this->setBlock($level, $pos->add(0, 0, -1), $block);
		$this->setBlock($level, $pos->add(0, 0, 1), $block);
		$this->setBlock($level, $pos->add(-1, 0, -1), $block);
		$this->setBlock($level, $pos->add(1, 0, 1), $block);
		$this->setBlock($level, $pos->add(-1, 0, 1), $block);
		$this->setBlock($level, $pos->add(1, 0, -1), $block);
	}

	/**
	 * Create chest in game by specified coords
	 * 
	 * @param Level $level
	 * @param Vector3 $pos
	 * @return Tile
	 */
	private function createChest(Level $level, Vector3 $pos) {
		$block = Block::get(Block::CHEST);
		$this->setBlock($level, $pos, $block, true, true);
		$nbt = new Compound(false, [
			new Enum("Items", []),
			new StringTag("id", Tile::CHEST),
			new IntTag("x", $pos->x),
			new IntTag("y", $pos->y),
			new IntTag("z", $pos->z)
		]);
		$nbt->Items->setTagType(NBT::TAG_Compound);
		$tile = Tile::createTile("Chest", $level->getChunk($pos->x >> 4, $pos->z >> 4), $nbt);
		return $tile;
	}

	/**
	 * Initialize glass island with a chest inside
	 * 
	 * @param Level $level
	 * @param Vector3 $pos
	 * @param Block $block
	 */
	public function createLootIsland(Level $level, Vector3 $pos, Block $block) {
		$this->makeSkyBoxLayer($level, $pos->add(0, -1, 0), $block, false);
		$this->setBlock($level, $pos->add(-1, 0, -1), $block);
		$this->setBlock($level, $pos->add(1, 0, 1), $block);
		$this->setBlock($level, $pos->add(-1, 0, 1), $block);
		$this->setBlock($level, $pos->add(1, 0, -1), $block);
		$this->setBlock($level, $pos->add(-1, 1, -1), $block);
		$this->setBlock($level, $pos->add(1, 1, 1), $block);
		$this->setBlock($level, $pos->add(-1, 1, 1), $block);
		$this->setBlock($level, $pos->add(1, 1, -1), $block);
		$this->makeSkyBoxLayer($level, $pos->add(0, 2, 0), $block);
		$this->setBlock($level, $pos->add(0, 3, 0), $block);
		$this->createChest($level, $pos);
		$this->lootPositions[] = $pos;
	}

	/**
	 * Calls when player is joined
	 * Create box of waiting for player
	 * 
	 * @param CustomPlayer $player
	 * @param Vector3 $pos
	 */
	public function playerJoined(CustomPlayer $player, Vector3 $pos) {
		$this->makeSkyBox($this->plugin->level, $pos);
	}

	/**
	 * Calls when player left the game
	 * create box
	 * 
	 * @param CustomPlayer $player
	 * @param Vector3 $pos
	 */
	public function playerLeft(CustomPlayer $player, Vector3 $pos) {
		$this->makeSkyBox($this->plugin->level, $pos, true);
	}

	/**
	 * Calls when countdown is finished and remove start position blocks
	 */
	public function countdownFinished() {
		foreach ($this->players as $player) {
			$posId = $player->gameStartingPos;
			$pos = $this->startingPositions[$posId];
			$this->plugin->level->setBlock($pos->add(0, -1, 0), Block::get(Block::AIR));
		}
	}

	/**
	 * Calls when player open chest
	 * 
	 * @param InventoryOpenEvent $event
	 * @return void
	 */
	public function onChestOpen(InventoryOpenEvent $event) {
		$inv = $event->getInventory();
		if ($inv instanceof ChestInventory) {
			$tile = $inv->getHolder();
			foreach ($this->lootPositions as $id => $position) {
				if ($position->x == $tile->x && $position->y == $tile->y && $position->z == $tile->z) {
					$this->plugin->level->setBlock($tile, Block::get(Block::AIR));
					$tile->close();
					$event->setCancelled(true);
					$this->launchItemParticle($tile->x + 0.5, $tile->y + 0.1, $tile->z + 0.5, 0.3, 0.7, 0, 0, 0, 51, 0, 12);
					$this->launchItemParticle($tile->x + 0.5, $tile->y + 0.1, $tile->z + 0.5, -0.3, 0.7, 0, 0, 0, 51, 0, 12);
					$this->launchItemParticle($tile->x + 0.5, $tile->y + 0.1, $tile->z + 0.5, 0, 0.7, 0.3, 0, 0, 51, 0, 12);
					$this->launchItemParticle($tile->x + 0.5, $tile->y + 0.1, $tile->z + 0.5, 0, 0.7, -0.3, 0, 0, 51, 0, 12);
					$this->launchItemParticle($tile->x + 0.5, $tile->y + 0.1, $tile->z + 0.5, 0.2, 0.7, 0.2, 0, 0, 51, 0, 12);
					$this->launchItemParticle($tile->x + 0.5, $tile->y + 0.1, $tile->z + 0.5, 0.2, 0.7, -0.2, 0, 0, 51, 0, 12);
					$this->launchItemParticle($tile->x + 0.5, $tile->y + 0.1, $tile->z + 0.5, -0.2, 0.7, 0.2, 0, 0, 51, 0, 12);
					$this->launchItemParticle($tile->x + 0.5, $tile->y + 0.1, $tile->z + 0.5, -0.2, 0.7, -0.2, 0, 0, 51, 0, 12);

					//loot lottery
					$lootNum = rand(0, 14);
					$particleItem = 0;
					if ($lootNum == 0) {
						$event->getPlayer()->getInventory()->addItem(Item::get(Item::DIAMOND_SWORD));
						$particleItem = Item::DIAMOND_SWORD;
					} else if ($lootNum == 1) {
						$event->getPlayer()->getInventory()->addItem(Item::get(Item::IRON_CHESTPLATE));
						$particleItem = Item::IRON_CHESTPLATE;
					} else if ($lootNum == 2) {
						$event->getPlayer()->getInventory()->addItem(Item::get(Item::IRON_LEGGINGS));
						$particleItem = Item::IRON_LEGGINGS;
					} else if ($lootNum >= 3 && $lootNum < 10) {
						$particleItem = 175;
						$coins = rand(15, 90);
						//$this->plugin->getServer()->getScheduler()->scheduleAsyncTask(new AddCoinsRequest($this->plugin, $event->getPlayer()->getName(), $coins));
					} else if ($lootNum == 11) {
						$particleItem = 175;
						$coins = rand(80, 150);
						//$this->plugin->getServer()->getScheduler()->scheduleAsyncTask(new AddCoinsRequest($this->plugin, $event->getPlayer()->getName(), $coins));
					}
					$this->launchItemParticle($tile->x + 0.5, $tile->y + 0.1, $tile->z + 0.5, 0, 0.5, 0, 0, 0, $particleItem, 0, 12);
					unset($this->lootPositions[$id]);
					return;
				}
			}
		}
	}

	/**
	 * Create particles for specified item (such as chest)
	 * 
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $mX
	 * @param int $mY
	 * @param int $mZ
	 * @param int $yaw
	 * @param int $pitch
	 * @param int $itemId
	 * @param float $itemDmg
	 * @param int $duration
	 */
	protected function launchItemParticle($x, $y, $z, $mX, $mY, $mZ, $yaw, $pitch, $itemId, $itemDmg, $duration) {
		$nbt = new Compound("", [
			"Pos" => new Enum("Pos", [
				new DoubleTag("", $x),
				new DoubleTag("", $y),
				new DoubleTag("", $z)
					]),
			"Motion" => new Enum("Motion", [
				new DoubleTag("", $mX),
				new DoubleTag("", $mY),
				new DoubleTag("", $mZ)
					]),
			"Rotation" => new Enum("Rotation", [
				new FloatTag("", $yaw),
				new FloatTag("", $pitch)
					]),
			"Age" => new ShortTag("Age", 6000 - $duration),
			"Health" => new ShortTag("Health", 1),
			"PickupDelay" => new ShortTag("PickupDelay", $duration + 10),
			"Item" => new Compound("Item", [
				"id" => new ShortTag("id", $itemId),
				"Damage" => new ShortTag("Damage", $itemDmg),
				"Count" => new ByteTag("Count", 1)
					])
		]);

		$item = new ItemParticle($this->plugin->level->getChunk($x >> 4, $z >> 4), $nbt);
		$item->spawnToAll();
	}

}
