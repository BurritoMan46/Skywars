<?php

namespace Skywars\game;

use pocketmine\event\entity\ItemSpawnEvent;
use pocketmine\utils\TextFormat;
use Skywars\Area;
use Skywars\game\modes\SkyWars\SkyWarsGame;
use Skywars\player\CustomPlayer;
use Skywars\player\DamageInfo;
use Skywars\Skywars;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\math\Vector3;

/**
 * Manages the games.
 */
class GameManager implements Listener {
	/** @var Skywars */
	private $plugin;
	/** @var GameCountdownTask */
	private $task;
	/** @var TaskHandler */
	private $taskHandler;
	/** @var Game[] */
	public $games = [];
	/** @var array */
	private static $gameTypes;
	/** @var array */
	public $gamePositions = [];
	/** @var GameMapSourceInfo[] */
	public $sourcePositions = [];

	/**
	 * Save specified game class as element of gametypes array
	 * 
	 * @param $clazz
	 */
	static public function registerGameType($clazz) {
		$name = $clazz::$type;
		self::$gameTypes[$name] = $clazz;
	}

	/**
	 * Save all necessary gametype classes
	 */
	static public function registerGameTypes() {
		self::registerGameType(SkyWarsGame::class);
	}

	/**
	 * Base class constructor, create events listening and repeatable task
	 * 
	 * @param Skywars $plugin
	 */
	public function __construct(Skywars $plugin) {
		$this->plugin = $plugin;
		self::registerGameTypes();

		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);

		$this->task = new GameCountdownTask($plugin, $this);
		$this->taskHandler = $plugin->getServer()->getScheduler()->scheduleRepeatingTask($this->task, 20);
	}

	/**
	 * Save game object inside gameManager property
	 * 
	 * @param \Skywars\game\Game $game
	 */
	public function addGame(Game $game) {
		$this->games[$game->name] = $game;
	}

	/**
	 * Create game of specified type
	 * 
	 * @param string $type
	 * @return string
	 */
	public function createGame($type) {
		$minPlayers = 2;
		$maxPlayers = 8;
		//find empty id
		for ($id = 1; $id < 60; $id++) {
			if (isset($this->games["#" . $id])) {
				continue;
			}
			break;
		}
		$name = "#" . $id;
		//create new Area, set properties for game type
		foreach ($this->gamePositions as $id => $pos) {
			$sourcePos = $this->sourcePositions[$type];
			$sourcePos = $sourcePos[rand(0, count($sourcePos) - 1)];

			$gameType = self::$gameTypes[$type];
			$game = new $gameType($this->plugin, $this, $name, new Area($this->plugin, $pos->x, $pos->y, $pos->z, $sourcePos->size, $sourcePos->kickSize, true, $sourcePos->time, $sourcePos->dayCycle), $sourcePos, $minPlayers, $maxPlayers);
			$this->addGame($game);
			unset($this->gamePositions[$id]);
			return $name;
		}
	}

	/**
	 * Destroy game and add its coords to available game positions
	 * @param Game $game
	 */
	public function destroy(Game $game) {
		unset($this->games[$game->name]);

		$this->gamePositions[] = new Vector3($game->area->centerX, $game->area->y, $game->area->centerZ);
	}

	/**
	 * Use it to get name of current game
	 * 
	 * @param Game $game
	 * @return string
	 */
	public function getNameOf(Game $game) {
		return $game->name;
	}

	/**
	 * Add player to game area,
	 * contains necessary checks before do that
	 * 
	 * @param string $name
	 * @param CustomPlayer $player
	 * @param bool $notify
	 * @return boolean
	 */
	public function joinGame($name, CustomPlayer $player, $notify = false) {
		$this->plugin->getLogger()->info($player->getName() . " is attempting to join " . $name);
		//login error
		if ($player->isRegistered() && !$player->isAuthorized()) {
			$player->sendMessage(TextFormat::RED . $player->getTranslatedString("GAME_PREFIX") . $player->getTranslatedString("NEEDS_LOGIN", TextFormat::BOLD));
			return false;
		}
		//game does not exist
		if (!array_key_exists($name, $this->games)) {
			$player->sendMessage(TextFormat::RED . $player->getTranslatedString("ERROR_PREFIX") . $player->getTranslatedString("NO_GAME", TextFormat::BOLD, array($name)));
			return false;
		}
		//player is already in that game
		if ($player->currentGame === $this->games[$name]) {
			$player->sendMessage(TextFormat::GOLD . $player->getTranslatedString("GAME_PREFIX") . $player->getTranslatedString("ON_GAME", TextFormat::BOLD, array($name)));
			return false;
		}
		//move player from one game...
		if ($player->currentGame !== null) {
			$player->currentGame->leave($player);
			$player->currentGame = null;
			$player->kills = [];
			$player->killAssists = [];
			$player->damageList = [];
			$player->gameStartingPos = -1;
			$player->walkedDist = 0;
			$player->lastGroundPos = null;
		}
		//...to another
		if ($this->games[$name]->join($player)) {
			if ($this->plugin->portalTask != null) {
				$this->plugin->portalTask->despawnFromPlayer($player);
			}
			$player->walkedDist = 0;
			$player->lastGroundPos = null;
			$player->removeAllEffects();
			$player->setAllowFlight(false);
			$player->setAutoJump(true);
			$player->setOnFire(0);
			if ($notify) {
				$player->sendMessage(TextFormat::GREEN . $player->getTranslatedString("GAME_PREFIX") . $player->getTranslatedString("IN_GAME", TextFormat::BOLD, array($name)));
			}
			$player->currentGame = $this->games[$name];
			$player->setStateCountdown($name);
			return true;
		}
		return false;
	}

	// events

	/**
	 * Calls when player quit the game - remove player from this game
	 * 
	 * @param PlayerQuitEvent $event
	 * @return void
	 */
	public function onPlayerQuit(PlayerQuitEvent $event) {
		if (!$event->getPlayer()->spawned) {
			return;
		}

		$player = $event->getPlayer();
		if ($player->currentGame != null) {
			$player->currentGame->leave($player);
			$player->currentGame = null;
			return;
		}
	}

	/**
	 * Calls when entity got some damage in game from player
	 * 
	 * @param EntityDamageEvent $event
	 * @return type
	 */
	public function onDamage(EntityDamageEvent $event) {
		if ($event->isCancelled()) {
			return;
		}

		if (!($event->getEntity() instanceof Player)) {
			return;
		}
		//do not bite deadman
		if ($event->getEntity()->getHealth() <= 0) {
			$event->setCancelled(true);
			return;
		}

		/** @var CustomPlayer $player */
		$player = $event->getEntity();
		//player can attack only when he is in game and the game is started, 
		//and he is not spectator
		if ($player->currentGame === NULL){
			$event->setCancelled(true);
			return;
		}
		if ($player->currentGame->_noDamageTime > 0) {
			$event->setCancelled(true);
			return;
		}

		if ($player->currentGame->started && $player->currentGame->countdown > 0) {
			$event->setCancelled(true);
			return;
		}
		if ($player->isSpectating) {
			$event->setCancelled(true);
			return;
		}
		$currentTime = round(microtime(true) * 1000);
		//take away health
		$newHP = $event->getEntity()->getHealth() - $event->getDamage();
		$name = $event->getEntity()->getName();

		if ($event instanceof EntityDamageByEntityEvent) {
			$p = $event->getDamager();
			if ($p instanceof CustomPlayer) {
				if (!$p->currentGame->pvpAllowed) {
					$event->setCancelled(true);
					return;
				}

				if ($newHP <= 0) {
					if (isset($p->killAssists[$name])) {
						unset($p->killAssists[$name]);
					}
					$p->kills[$name] = $name;
				} else {
					$p->killAssists[$name] = $name;
				}
			}
		}

		$addDamage = 0;
		$last = end($player->damageList);
		$lastTime = key($player->damageList);
		//save damage cause
		if ($last !== false && $last->damageType === $event->getCause()) {
			if ($event->getCause() !== EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
				$addDamage = $last->damage;
				unset($player->damageList[$lastTime]);
			} else if ($event instanceof EntityDamageByEntityEvent) {
				$p = $event->getDamager();
				$name = "";
				if ($p instanceof Player) {
					$name = $p->getName();
				}

				if ($name === $last->damagerName) {
					$addDamage = $last->damage;
					unset($player->damageList[$lastTime]);
				}
			}
		}
		$player->damageList[$currentTime] = new DamageInfo($event, $addDamage);
		//save cause of death
		if ($newHP <= 0) {
			if ($event->getCause() === EntityDamageEvent::CAUSE_VOID ||
					$event->getCause() === EntityDamageEvent::CAUSE_LAVA) {
				end($player->damageList);
				$last = prev($player->damageList);
				if ($last != false && $last->damagerName !== null && $currentTime - key($player->damageList) < 8000) {
					if (isset($player->currentGame->players[$last->damagerName])) {
						$p = $player->currentGame->players[$last->damagerName];
						if (isset($p->killAssists[$name])) {
							unset($p->killAssists[$name]);
						}
						$p->kills[$name] = $name;
					}
				}
			}

			$player->currentGame->playerDied($event->getEntity(), $player->damageList[$currentTime]->toString());
			$event->setCancelled(true);
		}
	}

	/**
	 * Calls when player try to break block
	 * allowed only in started game
	 * 
	 * @param BlockBreakEvent $event
	 * @return void
	 */
	public function onBlockBreak(BlockBreakEvent $event) {
		$player = $event->getPlayer();
		if ($player->currentGame == NULL) {
			return;
		}

		if (!$player->currentGame->blockBreakAllowed || !$player->currentGame->started || $player->currentGame->countdown > 0) {
			$event->setCancelled(true);
			return;
		}
	}

	/**
	 * Calls when item spawn/ Kill if it's not in allowed game map
	 * 
	 * @param ItemSpawnEvent $event
	 * @return void
	 */
	public function onItemSpawn(ItemSpawnEvent $event) {
		$x = $event->getEntity()->x;
		$z = $event->getEntity()->z;
		foreach ($this->games as $game) {
			if ($game->area->inArea($x, $z)) {
				if (!$game->allowDroppedItems) {
					$event->getEntity()->kill();
				}
				return;
			}
		}
	}

}
