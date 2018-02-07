<?php

namespace Skywars\game;

use pocketmine\block\Block;
use pocketmine\event\TimingsHandler;
use pocketmine\level\Level;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;
use Skywars\player\action\ReturnToLobbyActionItem;
use Skywars\player\CustomPlayer;
use Skywars\Area;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\tile\Chest;
use Skywars\Skywars;

/**
 * Base class for a game
 *
 */
class Game {
	/** @var string */
	public static $type = "Unknown";
	/** @var TimingsHandler */
	protected static $loadTimings = null;
	/** @var TimingsHandler */
	protected static $chunkCopyTimings = null;

	/**
	 * Create TimingsHandler objects for game creation and copy chunk
	 */
	protected static function initTimings() {
		self::$loadTimings = new TimingsHandler("Game Creation");
		self::$chunkCopyTimings = new TimingsHandler("Chunk Copying");
	}

	/** @var Skywars */
	protected $plugin;
	/** @var GameManager */
	protected $gameManager;
	/** @var int */
	public $noDamageTime = 5; // for how long players won't get hurt after the match actually starts
	/** @var bool */
	public $displayDamageList = true; // if user should get the whole damage list (what caused an attack and how much hp it took) after he dies
	/** @var int */
	public $multipleDeaths = 0; // should multiple deaths be allowed: -1 - unlimited, 0 - none, 1 - 1 respawn, 2 - 2 respawns [...]
	/** @var int */
	public $coinsForParticipation = 1; // how many coins should be given for joining the game and finishing it
	/** @var int */
	public $coinsForWinning = 5; // how many coins should be given for winning the game
	/** @var int */
	public $coinsForKill = 2; // how many coins should be given for a single kill
	/** @var int */
	public $coinsForAssist = 1; // how many coins should be given for a single kill assist
	/** @var bool */
	public $pvpAllowed = true;
	/** @var bool */
	public $blockBreakAllowed = false;
	/** @var bool */
	public $fillChests = true;
	/** @var bool */
	public $allowDroppedItems = true;
	/** @var string */
	public $name;
	/** @var Area */
	public $area;
	/** @var GameMapSourceInfo */
	private $mapSource;
	/** @var CustomPlayer[] */
	public $players = [];
	/** @var CustomPlayer[] */
	public $spectators = [];
	/** @var int */
	public $minPlayers;
	/** @var int */
	public $maxPlayers;
	/** @var bool */
	public $started = false;
	/** @var int */
	public $countdown = -1;
	/** @var array|null */
	public $startingPositions = null;
	/** @var array|null */
	public $avalibleStartingPositions = null;
	/** @var int */
	public $_noDamageTime = 0;
	/** @var bool */
	public $restarting = false;
	/** @var bool */
	public $private = false; // can players join it?
	/** @var bool */
	public $dataUpdated = false;

	/**
	 * Basic game constructor, save options like players count, map, name,
	 * starting positions
	 * 
	 * @param Skywars $plugin
	 * @param GameManager $gameManager
	 * @param string $name
	 * @param Area $gameArea
	 * @param GameMapSourceInfo $source
	 * @param int $minPlayers
	 * @param int $maxPlayers
	 */
	public function __construct(Skywars $plugin, GameManager $gameManager, $name, Area $gameArea, GameMapSourceInfo $source, $minPlayers, $maxPlayers) {
		$plugin->getLogger()->info("Created " . $name . " at " . $gameArea->centerX . ", " . $gameArea->centerZ);

		if (self::$loadTimings === null) {
			self::initTimings();
		}

		self::$loadTimings->startTiming();
		$this->plugin = $plugin;
		$this->gameManager = $gameManager;
		$this->name = $name;
		$this->area = $gameArea;
		$this->mapSource = $source;
		$this->minPlayers = $minPlayers;
		$this->maxPlayers = $maxPlayers;
		$this->lootPositions = [];

		$level = $plugin->level;
		for ($x = ($this->area->centerX - $this->area->size) >> 4; $x <= ($this->area->centerX + $this->area->size) >> 4; $x++) {
			for ($z = ($this->area->centerZ - $this->area->size) >> 4; $z <= ($this->area->centerZ + $this->area->size) >> 4; $z++) {
				$this->loadChunk($level, $x, $z);
			}
		}

		$startingPositions = [];
		foreach ($source->positions as $pos) {
			$startingPositions[] = new Vector3($pos->x + (($gameArea->centerX >> 4) - ($source->pos->x >> 4)) * 16, $source->pos->y, $pos->z + (($gameArea->centerZ >> 4) - ($source->pos->z >> 4)) * 16);
		}
		$this->startingPositions = $startingPositions;
		$this->avalibleStartingPositions = $startingPositions;

		self::$loadTimings->stopTiming();
	}

	/**
	 * Add player to current game
	 * 
	 * @param CustomPlayer $player
	 * @param bool $restart
	 * @return boolean
	 */
	public function join(CustomPlayer $player, $restart = false) {
		//set options when player is spectator
		if ($player->isSpectating) {
			$player->sendMessage($player->getTranslatedString("GAME_PREFIX", TextFormat::GRAY) . $player->getTranslatedString("IS_SPECTATOR", TextFormat::BOLD));
			$player->currentGame = $this;
			$this->area->setAreaFor($player);
			$player->spectate();
			$this->spectators[$player->getName()] = $player;
			return true;
		}
		//when player try to join already started game - make him a spectator
		if ($this->started && $this->countdown <= 0) {
			$player->sendMessage($player->getTranslatedString("GAME_PREFIX", TextFormat::RED) . $player->getTranslatedString("GAME_IN_PROGRESS", TextFormat::BOLD));
			$player->currentGame = $this;
			$this->area->setAreaFor($player);
			$player->spectate();
			$this->spectators[$player->getName()] = $player;
			return true;
		//when game is not started but full - make him a spectator
		} else if ($this->maxPlayers <= count($this->players) + 1) {
			$player->sendMessage($player->getTranslatedString("GAME_PREFIX", TextFormat::RED) . $player->getTranslatedString("PLAYER_LIMIT_ERROR", TextFormat::BOLD));
			$player->currentGame = $this;
			$this->area->setAreaFor($player);
			$player->spectate();
			$this->spectators[$player->getName()] = $player;
			return true;
		} else {//when game is not full and not started
			$found = false;
			foreach ($this->avalibleStartingPositions as $posId => $pos) {
				$this->area->setAreaFor($player, $pos->add(0.5, 0, 0.5));
				$this->playerJoined($player, $pos);
				unset($this->avalibleStartingPositions[$posId]);
				$player->gameStartingPos = $posId;
				$found = true;
				break;
			}

			if (!$found) {
				$this->plugin->getLogger()->debug(TextFormat::RED . "Couldn't find starting position for " . $player->getPlayer() . " [" . $this->name . "]");
				$player->sendMessage($player->getTranslatedString("ERROR_PREFIX", TextFormat::RED) . $player->getTranslatedString("NO_START_POSITION", TextFormat::BOLD));
				return false;
			}
			$player->setFoodEnabled(true);
		}
		//look for restarting
		if ($restart) {
			return false;
		}
		//save player in game data and current game in player data
		$this->plugin->getLogger()->info($player->getName() . " joined " . $this->name);
		$this->players[$player->getName()] = $player;
		$this->broadcastMessageLocalized("JOIN_PREFIX", array(), TextFormat::GRAY, $player->getDisplayName());
		$player->currentGame = $this;
		//prepare hotbar items
		$actions = [
			1 => new ReturnToLobbyActionItem()
		];

		$player->setHotbarActions($actions);
		//send countdown message if needs
		if (!$this->started) {
			$player->showNotification($player->getTranslatedString("PLAYER_WAIT", TextFormat::YELLOW), -1);
		} else {
			$player->showNotification(null);

			if (count($this->players) >= $this->maxPlayers) {
				if ($this->countdown >= 3) {
					$this->countdown = 3;
				}
			}
		}
		//start game 
		if (!$this->started && count($this->players) >= $this->minPlayers) {
			$this->start();
		}

		return true;
	}

	/**
	 * Calls when player leave game
	 * 
	 * @param CustomPlayer $player
	 * @return void
	 */
	public function leave(CustomPlayer $player) {
		//remove player from active players list
		if (isset($this->players[$player->getName()])) {
			unset($this->players[$player->getName()]);
			$this->broadcastMessageLocalized("QUIT_PREFIX", array(), TextFormat::GRAY, $player->getDisplayName());
			$this->plugin->getLogger()->info($player->getName() . " left " . $this->name);
			//clear start position
			if ($player->gameStartingPos !== -1) {
				$this->avalibleStartingPositions[$player->gameStartingPos] = $this->startingPositions[$player->gameStartingPos];
				$this->playerLeft($player, $this->startingPositions[$player->gameStartingPos]);
				$player->gameStartingPos = -1;
			} else {
				$this->plugin->getLogger()->error("Player left a game without a starting position set!");
				//$this->playerLeft($player, null);
			}
			//stop the game if amount of players remain less than min players count
			if ($this->started && $this->countdown > 0) {
				if (count($this->players) < $this->minPlayers) {
					// stop the game!
					$this->started = false;
					$this->countdown = -1;
					$this->broadcastNotification(TextFormat::YELLOW . "Waiting for players...");
				}
			}
			//finish game if needs
			if ($this->started && $this->countdown <= 0) {
				$this->finishGame($player);
				return;
			}
		}
    	//remove player from spectator list
		if (isset($this->spectators[$player->getName()])) {
			unset($this->spectators[$player->getName()]);
			$this->broadcastMessageLocalized("QUIT_PREFIX", array(), TextFormat::GRAY, $player->getDisplayName());
		}
		//destroy game if nobody remained
		if (count($this->players) === 0) {
			$this->destroy();
		}
	}

	/**
	 * Finish current game if we have a winner (or not)
	 * 
	 * @param CustomPlayer $player
	 * @param bool $won
	 */
	public function finishGame(CustomPlayer $player, $won = false) {
		//send messages if somebody won
		if ($won) {
			$this->broadcastMessageLocalized("PLAYER_WON", array($player->getName(), $this->plugin->serverGameType), TextFormat::GOLD);
		} else if ($this->displayDamageList) {
			$this->displayDamageList($player);
		}
		$player->sendMessage(TextFormat::GOLD . TextFormat::STRIKETHROUGH . "==================================");
		if ($won) {
			$player->sendMessage(TextFormat::BOLD . TextFormat::YELLOW . $player->getTranslatedString("YOU_WON"));
		} else {
			$player->sendMessage(TextFormat::BOLD . $player->getTranslatedString("GAME_RESULTS"));
			$player->showNotification($player->getTranslatedString("YOU_DIED"), 2);
		}
		//money math
		$coins = $this->finishGameCoinsInfo($player, $this->coinsForParticipation, "participation");
		$coins += $this->finishGameCoinsInfo($player, $this->coinsForWinning, "winning the game", $won);
		$coins += $this->finishGameCoinsInfo($player, count($player->kills) * $this->coinsForKill, count($player->kills) . " kills");
		$coins += $this->finishGameCoinsInfo($player, count($player->killAssists) * $this->coinsForAssist, count($player->killAssists) . " kill assists");
		$coins += $this->finishGameCoinsInfo($player, $coins, "being a VIP", $player->isVIP());
		$player->sendMessage($player->getTranslatedString("TOTAL_RESULT", TextFormat::YELLOW) . TextFormat::BOLD . TextFormat::GOLD . $coins . TextFormat::RESET . TextFormat::YELLOW . $player->getTranslatedString("JUST_COINS"));
		$player->addCoins($coins);

		$player->sendMessage(TextFormat::GOLD . TextFormat::STRIKETHROUGH . "==================================");
		//destroy game if nobody won
		if (!$won) {
			if (count($this->players) === 1) {
				foreach ($this->players as $winner) {
					$this->finishGame($winner, true);
					$this->destroy();
					break;
				}
			} else if (count($this->players) <= 0) {
				$this->destroy();
			}
		}
	}

	/**
	 * Use to get amount of coins for gaming and to inform player about these coins
	 * 
	 * @param Player $player
	 * @param int $coins
	 * @param string $for
	 * @param bool $add
	 * @return int
	 */
	private function finishGameCoinsInfo(Player $player, $coins, $for, $add = true) {
		if ($add && $coins > 0) {
			$coins = round($coins);
			$player->sendMessage(TextFormat::BOLD . TextFormat::GOLD . "+" . $coins . TextFormat::RESET . $player->getTranslatedString("PREP_FOR") . $for);
			return $coins;
		}
		return 0;
	}

	/**
	 * Start current game, fill chests
	 */
	public function start() {
		$this->started = true;
		$this->countdown = $this->plugin->gameCountdown;

		if ($this->fillChests) {
			$level = $this->plugin->level;
			for ($x = floor(($this->area->centerX - $this->area->size) / 16); $x <= ceil(($this->area->centerX + $this->area->size) / 16); $x++) {
				for ($z = floor(($this->area->centerZ - $this->area->size) / 16); $z <= ceil(($this->area->centerZ + $this->area->size) / 16); $z++) {
					if ($this->fillChests) {
						$entities = $level->getChunkTiles($x, $z);
						if ($entities == null)
							continue;
						foreach ($entities as $entity) {
							if ($entity instanceof Chest) {
								$this->fillChest($entity);
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Destroy current game
	 */
	public function destroy() {
		//move all players from game to lobby
		foreach ($this->players as $id => $player) {
			unset($this->players[$id]);
			$player->currentGame = null;
			$player->setStateInLobby();
			$player->returnToLobby();
		}
		//move all spectators to lobby
		foreach ($this->spectators as $id => $player) {
			unset($this->spectators[$id]);
			$player->currentGame = null;
			$player->setStateInLobby();
			$player->returnToLobby();
		}
		//restart map
		$this->restarting = true;
		
		$level = $this->plugin->level;
		for ($x = ($this->area->centerX - $this->area->size) >> 4; $x <= ($this->area->centerX + $this->area->size) >> 4; $x++) {
			for ($z = ($this->area->centerZ - $this->area->size) >> 4; $z <= ($this->area->centerZ + $this->area->size) >> 4; $z++) {
				$chunk = $level->getChunk($x, $z);
				$chunk->allowUnload = true;			
			}
		}

		$this->restarting = false;

		$this->gameManager->destroy($this);

		$this->plugin->getLogger()->info("Destroyed " . $this->name);
	}

	
	/**
	 * Calls when spectator moves in game - teleport them to starting position
	 * 
	 * @param PlayerMoveEvent $event
	 * @return void
	 */
	public function onPlayerMove($event) {
		if (!isset($this->players[$event->getPlayer()->getName()])) {
			return;
		}

		$spectating = $event->getPlayer()->isSpectating;
		if (!$spectating && (!$this->started || $this->countdown > 0)) {
			if ($event->getPlayer()->gameStartingPos === -1) {
				return;
			}
			$startingPos = $this->startingPositions[$event->getPlayer()->gameStartingPos];
			if (round($startingPos->x + 0.5) !== round($event->getTo()->x) ||
					round($startingPos->y) !== round($event->getTo()->y) ||
					round($startingPos->z + 0.5) !== round($event->getTo()->z)
			) {
				$event->getPlayer()->teleport($startingPos->add(0.5, 0, 0.5));
			}
		}
	}

	/**
	 * 
	 * @param CustomPlayer $player
	 * @param Vector3 $pos
	 */
	public function playerJoined(CustomPlayer $player, Vector3 $pos) {
		// to be overridden
	}

	/**
	 * 
	 * @param CustomPlayer $player
	 * @param Vector3 $pos
	 */
	public function playerLeft(CustomPlayer $player, Vector3 $pos) {
		// to be overridden
	}

	public function countdownFinished() {
		// to be overridden
	}

	/**
	 * Calls when player died in current game
	 * 
	 * @param CustomPlayer $player
	 * @param string $damageName
	 */
	public function playerDied(CustomPlayer $player, $damageName) {
		//drop player inventory
		if ($player->y > 10) {
			foreach ($player->getDrops() as $item) {
				$player->getLevel()->dropItem($player, $item);
			}
		}
		$this->broadcastMessageLocalized("PLAYER_WAS_KILLED", array($player->getName(), $damageName));		
		//make him a spectator
		if (count($this->players) > 2) {
			$player->teleport(new Vector3($player->x, 100, $player->z));
			$player->spectate();

			if ($player->gameStartingPos !== -1) {
				$this->avalibleStartingPositions[$player->gameStartingPos] = $this->startingPositions[$player->gameStartingPos];
				$this->playerLeft($player, $this->startingPositions[$player->gameStartingPos]);
				$player->gameStartingPos = -1;
			}
		} else {
			//move him to lobby if only a winner remained
			$player->returnToLobby();
		}
	}


	/**
	 * Show all damages for player
	 * 
	 * @param CustomPlayer $player
	 */
	public function displayDamageList(CustomPlayer $player) {
		$i = count($player->damageList) - 1;
		$currentTime = round(microtime(true) * 1000);
		foreach ($player->damageList as $time => $attackInfo) {
			$player->sendMessage(TextFormat::GRAY . "#" . $i . ": " . TextFormat::BOLD . TextFormat::YELLOW 
					. $attackInfo->toString() . TextFormat::RESET . TextFormat::RED 
					. " [" . $attackInfo->damage . "dmg]" . TextFormat::GRAY 
					. " (" . (($currentTime - $time) / 1000) . $player->getTranslatedString("SECONDS_BEFORE"));
			$i--;
		}
	}

	/**
	 * Fill chest with random items
	 * 
	 * @param Chest $chest
	 * @param int $maxWeight
	 */
	protected function fillChest(Chest $chest, $maxWeight = -1) {
		$inv = $chest->getInventory();
		$inv->clearAll();
		$weight = 0;
		$maxWeight = $maxWeight == -1 ? (rand(20, 35)) : $maxWeight;
		for ($slotId = 0; $slotId < $inv->getSize(); $slotId++) {
			$item = ChestLoot::getRandomItem($weight);
			if ($weight > $maxWeight) {
				break;
			}
			if ($item != null) {
				$inv->setItem($slotId, $item);
			}
		}
	}

	/**
	 * Load chunk by specified coords
	 * 
	 * @param Level $level
	 * @param int $x
	 * @param int $z
	 */
	protected function loadChunk(Level $level, $x, $z) {
		$centerX = $this->area->centerX >> 4;
		$centerZ = $this->area->centerZ >> 4;
		$dX = $x - $centerX;
		$dZ = $z - $centerZ;
		$sX = ($this->mapSource->pos->x >> 4) + $dX;
		$sZ = ($this->mapSource->pos->z >> 4) + $dZ;
		$this->copySourceChunk($level, (int) $sX, (int) $sZ, (int) $x, (int) $z);
	}

	/**
	 * Make copy of specified chunk
	 * 
	 * @param Level $level
	 * @param int $srcX
	 * @param int $srcZ
	 * @param int $destX
	 * @param int $destZ
	 * @return void
	 */
	protected function copySourceChunk(Level $level, $srcX, $srcZ, $destX, $destZ) {
		self::$chunkCopyTimings->startTiming();
		$hash = Level::chunkHash($srcX, $srcZ);
		$data = $this->mapSource->chunks[$hash];
		if ($data === null){
			return;
		}
		$c = GameChunk::fromData($destX, $destZ, $data[0], $data[1], $data[3], $data[2], $data[4], $data[5]);
		$level->setChunk($destX, $destZ, $c, false);

		foreach ($data[6] as $tile) {
			if ($tile[0] === Tile::CHEST) {
				$nbt = new Compound("", [
					new Enum("Items", []),
					new StringTag("id", Tile::CHEST),
					new IntTag("x", $destX * 16 + $tile[1]),
					new IntTag("y", $tile[2]),
					new IntTag("z", $destZ * 16 + $tile[3])
				]);
				$nbt->Items->setTagType(NBT::TAG_Compound);
				Tile::createTile($tile[0], $c, $nbt);
			}
		}
		self::$chunkCopyTimings->stopTiming();
	}

	/**
	 * Destroy block in game with particle effect
	 * 
	 * @param Level $level
	 * @param Vector3 $pos
	 * @param bool $drops
	 */
	protected function destroyBlock(Level $level, Vector3 $pos, $drops = false) {
		$b = $level->getBlock($pos);
		$players = $level->getUsingChunk($b->x >> 4, $b->z >> 4);
		$level->addParticle(new DestroyBlockParticle($b->add(0.5, 0.5, 0.5), $b), $players);
		$level->setBlock($pos, Block::get(Block::AIR));
	}
	
	/**
	 * Send message to all players in game depending on their language
	 * 
	 * @param string $msg
	 * @param array $args
	 * @param string $prefix
	 * @param string $suffix
	 */
	public function broadcastMessageLocalized($msg, $args = array(), $prefix = "", $suffix = "") {
		foreach ($this->players as $player) {
			$player->sendLocalizedMessage($msg, $args, $prefix, $suffix);
		}
		foreach ($this->spectators as $player) {
			$player->sendLocalizedMessage($msg, $args, $prefix, $suffix);
		}
	}

	/**
	 * Show notifications to each player in game depending on his language
	 * 
	 * @param string $msg
	 * @param int $time
	 * @param array $args
	 * @param string $prefix
	 * @param string $suffix
	 */
	public function broadcastNotification($msg, $time = -1, $args = array(), $prefix = "", $suffix = "") {
		foreach ($this->players as $player) {
			$player->showNotification($player->getTranslatedString($msg, $prefix, $args, $suffix), $time);
		}
		foreach ($this->spectators as $player) {
			$player->showNotification($player->getTranslatedString($msg, $prefix, $args, $suffix), $time);
		}
	}	


}
