<?php

namespace Skywars;

use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Chest;
use pocketmine\tile\Tile;
use Skywars\db\lifeboat\LifeboatDatabase;
use Skywars\gadget\GadgetManager;
use Skywars\game\GameManager;
use Skywars\game\GameMapSourceInfo;
use Skywars\game\JoinGameTask;
use Skywars\game\kit\KitManager;
use Skywars\particle\ParticleManager;
use Skywars\player\PlayerManager;
use Skywars\npc\NPCManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;
use Kits\Kit;
use LbCore\language\Translate;

use Skywars extends PluginBase {
  
	/** @var Skywars */
	public static $instance = null;

	/** @var Config */
	public $config = null;

	/** @var DatabaseManager */
	public $dbManager;

	/** @var PlayerManager */
	public $playerManager;

	/** @var ParticleManager */
	public $particleManager;

	/** @var NPCManager */
	public $npcManager;

	/** @var GameManager */
	public $gameManager;

	/** @var GadgetManager */
	public $gadgetManager;

	/** @var Area */
	public $lobbyArea;

	/** @var Level */
	public $level;
	public $gameCountdown;

	/** @var JoinGameTask */
	public $joinTask;

	/** @var PortalTask */
	public $portalTask = null;

	/** @var Vector3 */
	public $portalPos = null;

	/** @var AxisAlignedBB */
	public $portalAABB = null;
	/** @var string */
	public $serverGameType = "";
	/** @var string */
	public $serverGameTypeShort = "";

 	/**
	 * Main plugin method, calls while server start
	 */
	public function onEnable() {
		self::$instance = $this;
		//create translations
		Translate::getInstance()->createTranslations('Skywars\language\\');
		//set level freezed time
		$this->level = $this->getServer()->getLevelByName("world");
		$this->level->setTime(9000);
		$this->level->stopTime();
		$this->level->setAutoSave(false);
		//prepare main config object
		$config = new Config("worlds/" . $this->level->getFolderName() . "/config.yml", Config::YAML);
		$this->config = $config;
		//save gametype name
		$this->serverGameType = $config->get("gametype");
		$this->serverGameTypeShort = $config->get("gametypeShort");
		//create manager objects
		$this->dbManager = new LifeboatDatabase($this);
		$this->npcManager = new NPCManager($this);
    
    		$this->gameManager = new GameManager($this);
		$this->gadgetManager = new GadgetManager($this);
		$this->particleManager = new ParticleManager($this);
		//prepare lobby area
		$lobbyConfig = $config->get("lobby");
		$this->lobbyArea = new Area($this, $lobbyConfig["posX"], $lobbyConfig["posY"], $lobbyConfig["posZ"], $lobbyConfig["size"], $lobbyConfig["kickSize"], false, $lobbyConfig["time"], false, true);

		$portalPos = $config->get("portal");
		if ($portalPos !== false) {
			$this->portalPos = new Vector3($portalPos["posX"], $portalPos["posY"], $portalPos["posZ"]);
			$this->portalAABB = new AxisAlignedBB($this->portalPos->x - 1, $this->portalPos->y - 1, $this->portalPos->z - 1, $this->portalPos->x + 1, $this->portalPos->y + 1, $this->portalPos->z + 1);
		}
    
 		$this->gameCountdown = $config->get("gameCountdown");

		for ($i = 2; $i < 10; $i++) {
			$this->gameManager->gamePositions[] = new Vector3($this->lobbyArea->centerX + $i * 600, 100, $this->lobbyArea->centerZ + $i * 600);
			$this->gameManager->gamePositions[] = new Vector3($this->lobbyArea->centerX + $i * 600, 100, $this->lobbyArea->centerZ - $i * 600);
			$this->gameManager->gamePositions[] = new Vector3($this->lobbyArea->centerX - $i * 600, 100, $this->lobbyArea->centerZ + $i * 600);
			$this->gameManager->gamePositions[] = new Vector3($this->lobbyArea->centerX - $i * 600, 100, $this->lobbyArea->centerZ - $i * 600);
			$this->gameManager->gamePositions[] = new Vector3($this->lobbyArea->centerX, 100, $this->lobbyArea->centerZ + $i * 600);
			$this->gameManager->gamePositions[] = new Vector3($this->lobbyArea->centerX, 100, $this->lobbyArea->centerZ - $i * 600);
			$this->gameManager->gamePositions[] = new Vector3($this->lobbyArea->centerX + $i * 600, 100, $this->lobbyArea->centerZ);
			$this->gameManager->gamePositions[] = new Vector3($this->lobbyArea->centerX - $i * 600, 100, $this->lobbyArea->centerZ);
		}
		//create games data
		$games = $config->get("games");
		foreach ($games as $gameName => $gameInfo) {
			$s = new GameMapSourceInfo();
			$gameType = isset($gameInfo["game"]) ? $gameInfo["game"] : $this->serverGameType;
			$y = isset($gameInfo["spawnY"]) ? $gameInfo["spawnY"] : 100;
			$s->pos = new Vector3($gameInfo["x"], $y, $gameInfo["z"]);
			$s->size = $gameInfo["size"];
			$s->kickSize = $gameInfo["kickSize"];
			$s->minPlayers = $gameInfo["minPlayers"];
			$s->maxPlayers = $gameInfo["maxPlayers"];
			$gamePositions = [];
			$gamePositionsInfo = $gameInfo["positions"];
			foreach ($gamePositionsInfo as $id => $positionInfo) {
				$positionX = $positionInfo["x"];
				$positionY = isset($positionInfo["spawnY"]) ? $positionInfo["spawnY"] : $y;
				$positionZ = $positionInfo["z"];
				$gamePositions[$id - 1] = new Vector3($positionX, $positionY, $positionZ);
			}   
      
      			$s->positions = $gamePositions;
			$s->time = $gameInfo["time"];
			if ($s->time == "day") {
				$s->time = 1200;
			} else if ($s->time = "evening") {
				$s->time = 5000;
			}
			$s->dayCycle = $gameInfo["dayCycle"];

			for ($x = ($s->pos->x - $s->size) >> 4; $x <= ($s->pos->x + $s->size) >> 4; $x++) {
				for ($z = ($s->pos->z - $s->size) >> 4; $z <= ($s->pos->z + $s->size) >> 4; $z++) {
					$chunk = $this->level->getChunk($x, $z, true);
					$data = [$chunk->getBlockIdArray(), $chunk->getBlockDataArray(), $chunk->getBlockLightArray(), $chunk->getBlockSkyLightArray(), $chunk->getBiomeColorArray(), $chunk->getHeightMapArray(), []];
					foreach ($chunk->getTiles() as $tile) {
						if ($tile instanceof Chest) {
							$data[6][] = [Tile::CHEST, $tile->x - $x * 16, $tile->y, $tile->z - $z * 16];
						}
					}
					$s->chunks[Level::chunkHash($x, $z)] = $data;
					$this->level->unloadChunk($x, $z, false);
				}
			}

			if (!isset($this->gameManager->sourcePositions[$gameType])) {
				$this->gameManager->sourcePositions[$gameType] = [];
			}
			$this->gameManager->sourcePositions[$gameType][] = $s;
		}

		//prepare player options
		$this->playerManager = new PlayerManager($this);

		Kit::enable($this);
		KitManager::enable($this);
		//create repeatable tasks
		if ($this->portalPos !== null) {
			$this->portalTask = new PortalTask($this, $this->level, $this->portalPos->x, $this->portalPos->y, $this->portalPos->z);
			$this->getServer()->getScheduler()->scheduleRepeatingTask($this->portalTask, 1);
		}

		$this->joinTask = new JoinGameTask($this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask($this->joinTask, 20);

		if ($this->portalPos !== null) {
			$this->npcManager->addNPC("== PLAY ==", $this->portalPos->x, $this->portalPos->y - 4.05, $this->portalPos->z, 180, 0);
		}
		//enable pets
		$this->getServer()->getPluginManager()->registerEvents(new \Pets\PetsManager($this), $this);
		//call main plugin event listener
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
	}
  
  	/**
	 * Call when server is shut down
	 */
	public function onDisable() {
		self::$instance = null;
	}

	/**
	 * Used for specific plugin commands like /lobby
	 * (don't forget to write new command in plugin.yml)
	 * 
	 * @param CommandSender $sender
	 * @param Command $command
	 * @param $label
	 * @param array $args
	 * @return boolean
	 */
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
		if (strtolower($command->getName()) === "lobby") {
			$sender->returnToLobby();
			return true;
		}
		return parent::onCommand($sender, $command, $label, $args);
	}

}
