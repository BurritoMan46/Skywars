<?php

namespace Skywars;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use Skywars\player\CustomPlayer;
use pocketmine\utils\TextFormat;
use pocketmine\block\Block;
use pocketmine\block\TNT;
use pocketmine\level\Explosion;
use pocketmine\math\Vector3;
use LbCore\LbEventListener;
use Skywars\db\lifeboat\task\GetProductTask;
use LbCore\LbCore;
use pocketmine\event\entity\EntityExplodeEvent;

/**
 * Contain base plugin events, main player class is also overridden here
 */
class EventListener extends LbEventListener implements Listener {
	/** @var Skywars */
	private $skywars;

	/**
	 * Base class constructor
	 * 
	 * @param Skywars $plugin
	 */
	public function __construct($plugin) {
		$this->skywars = $plugin;
		parent::__construct($plugin);
	}

	/**
	 * Create new player as instance of CustomPlayer
	 * 
	 * @param PlayerCreationEvent $event
	 */
	public function onPlayerCreation(PlayerCreationEvent $event) {
		$event->setPlayerClass(CustomPlayer::class);
	}

	/**
	 * Calls when player join server, create task searching for bought bonuses,
	 * save lobby coords
	 * 
	 * @param PlayerLoginEvent $event
	 */
	public function onPlayerLogin(PlayerLoginEvent $event) {
		parent::onPlayerLogin($event);
		$p = $event->getPlayer();
		$p->gamemode = 0;
		$p->x = $this->skywars->lobbyArea->centerX;
		$p->y = $this->skywars->lobbyArea->y;
		$p->z = $this->skywars->lobbyArea->centerZ;
		$this->skywars->getServer()->getScheduler()->scheduleAsyncTask(new GetProductTask($p));
	}

	/**
	 * Calls when player join game - spawn portal to player
	 * 
	 * @param PlayerJoinEvent $event
	 */
	public function onPlayerJoin(PlayerJoinEvent $event) {
		$event->getPlayer()->returnToLobby();

		if ($this->skywars->portalTask != null) {
			$this->skywars->portalTask->spawnToPlayer($event->getPlayer());
		}
	}

	/**
	 * Calls when player go away from server, remove player from portal task
	 * 
	 * @param PlayerQuitEvent $event
	 * @return void
	 */
	public function onPlayerQuit(PlayerQuitEvent $event) {
		if (!$event->getPlayer()->loggedIn) {
			return;
		}

		if ($this->skywars->portalTask != null) {
			$this->skywars->portalTask->despawnFromPlayer($event->getPlayer());
		}
		$event->getPlayer()->showToAll();
	}

	/**
	 * Calls when player respawn after death,
   	 * move him to lobby
	 * 
	 * @param PlayerRespawnEvent $event
	 * @return void
	 */
	public function onPlayerRespawn(PlayerRespawnEvent $event) {
		$area = $event->getPlayer()->currentArea;
		if ($area === null) {
			return;
		}
		$event->getPlayer()->setSpawn(new Vector3($this->skywars->lobbyArea->centerX, $this->skywars->lobbyArea->y, $this->skywars->lobbyArea->centerZ));
		$event->setRespawnPosition(new Position($this->skywars->lobbyArea->centerX, $this->skywars->lobbyArea->y, $this->skywars->lobbyArea->centerZ, $this->skywars->level));

		if ($area !== $this->skywars->lobbyArea) {
			$event->getPlayer()->returnToLobby();
		}
	}

	/**
	 * Calls each time player is moving,
	 * use move listener from current game if player is in game,
	 * check: if he was falling away from lobby island then move him to lobby
	 * check: if he is inside portal - keep away from moving
	 * 
	 * @param PlayerMoveEvent $event
	 * @return void
	 */
	public function onPlayerMove(PlayerMoveEvent $event) {
		$player = $event->getPlayer();
		if($player->needSendSettings()){
			$player->sendSettings();
		}
		//movings in game
		if ($player->currentGame !== null) {
			$player->currentGame->onPlayerMove($event);
		}
		//move player to spawn if he was falling away from lobby
		if ($event->getTo()->getY() <= 15) {
			$area = $player->currentArea;
			if ($area === null) {
				return;
			}

			if ($area === $this->skywars->lobbyArea) {
				$event->getPlayer()->teleport(new Vector3($this->skywars->lobbyArea->centerX, $this->skywars->lobbyArea->y, $this->skywars->lobbyArea->centerZ));
			}
		} elseif ($this->skywars->portalPos != null &&
				$event->getTo()->x >= $this->skywars->portalPos->x - 2 && $event->getTo()->x <= $this->skywars->portalPos->x + 2 &&
				$event->getTo()->z >= $this->skywars->portalPos->z - 2 && $event->getTo()->z <= $this->skywars->portalPos->z + 2) {
			$id = $event->getPlayer()->getID();
			//stand still in portal
			if (!$event->getPlayer()->canPlay()) {
				if ($event->getPlayer()->motionX === 0 && $event->getPlayer()->motionY === 0 && $event->getPlayer()->motionZ === 0) {
					$event->getPlayer()->sendMessage($event->getPlayer()->getTranslatedString("GAME_PREFIX", TextFormat::RED) . $event->getPlayer()->getTranslatedString("NEEDS_LOGIN"));
				}
				if ($this->skywars->portalPos->distance($event->getFrom()) > $this->skywars->portalPos->distance($event->getTo())) {
					$event->getPlayer()->setMotion(new Vector3(($event->getFrom()->x - $event->getTo()->x) * 3, 1, ($event->getFrom()->z - $event->getTo()->z) * 3));
				}
				return;
			}
			$player->showNotification($player->getTranslatedString("GAME_FINDING", TextFormat::YELLOW ));
			$player->joining = true;
			$this->skywars->joinTask->queuePlayer($event->getPlayer(), $this->skywars->serverGameType, $this->skywars->portalAABB);			
		}
	}

	/**
	 * Calls when player try to set block - allow this only in game
	 * 
	 * @param BlockPlaceEvent $event
	 * @return void
	 */
	public function onBlockPlace(BlockPlaceEvent $event) {
		$area = $event->getPlayer()->currentArea;
		if ($area === null) {
			return;
		}

		if ($area === $this->skywars->lobbyArea) {
			$event->setCancelled(true);
		}
	}

	/**
	 * Calls when player try to break block:
	 * - describe TNT logic,
	 * - allow to break other blocks only in game
	 * 
	 * @param BlockBreakEvent $event
	 * @return void
	 */
	public function onBlockBreak(BlockBreakEvent $event) {
		if ($event->getBlock() instanceof TNT) {
			$this->skywars->level->setBlock($event->getBlock(), Block::get(Block::AIR), true);
			$explosion = new Explosion($event->getBlock(), 4);
			$explosion->explodeA();
			$explosion->explodeB();
			$event->setCancelled();
			return;
		}
    
    		$area = $event->getPlayer()->currentArea;
		if ($area === null) {
			return;
		}

		if ($area === $this->skywars->lobbyArea) {
			$event->setCancelled(true);
		}
	}

	/**
	 * Calls when player try to drop inventory item - allow this only in game
	 * 
	 * @param PlayerDropItemEvent $event
	 * @return void
	 */
	public function onDropItem(PlayerDropItemEvent $event) {
		$area = $event->getPlayer()->currentArea;
		if ($area === null) {
			return;
		}

		if ($area === $this->skywars->lobbyArea) {
			$event->setCancelled(true);
		}
	}

	/**
	 * Used to set server name and other params
	 * 
	 * @param QueryRegenerateEvent $event
	 */
	public function onQuery(QueryRegenerateEvent $event) {
		$lbcore = LbCore::getInstance();
		if (isset($lbcore->playerCount->sw_players)) {
			$event->setPlayerCount($lbcore->playerCount->sw_players);
		}
		if (isset($lbcore->playerCount->sw_slots)) {
			$event->setMaxPlayerCount($lbcore->playerCount->sw_slots);
		}
		$event->setServerName('{-name-:-AversionPE ' . $this->skywars->serverGameType . '-,-node-:{-type-:-' . $this->skywars->serverGameTypeShort . '-,-ip-:-unknown-,-players-:-' . count($this->skywars->getServer()->getOnlinePlayers()) . '-,-maxplayers-:70,-tps-:' . $this->skywars->getServer()->getTicksPerSecond() . '}}');
		$this->skywars->getServer()->getNetwork()->setName(TextFormat::AQUA . "Life" . TextFormat::RED . "Boat " . TextFormat::BOLD . TextFormat::GOLD . "SkyWars");
	}
    
	/**
	 * Calls when TNT explode
	 * 
	 * @param EntityExplodeEvent $event
	 */
	public function onEntityExplode(EntityExplodeEvent $event){
		$event->setBlockList([]);
	}

}
