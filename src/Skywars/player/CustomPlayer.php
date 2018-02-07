<?php

namespace Skywars\player;

use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\MoveEntityPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Skywars\Area;
use Skywars\game\Game;
use Skywars\player\action\CompassActionItem;
use Skywars\player\action\NamedItem;
use Skywars\player\action\ReturnToLobbyActionItem;
use Skywars\Skywars;
use LbCore\player\LbPlayer;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\nbt\tag\IntTag;
use pocketmine\network\protocol\SetPlayerGameTypePacket;
use pocketmine\network\protocol\ContainerSetContentPacket;

/**
 * CustomPlayer class override basic LbPlayer class 
 * to add specific functions and fields
 */
class CustomPlayer extends LbPlayer {

	// cooldown
	const COOLDOWN_GAME = 0;
	const COOLDOWN_LOBBY = 1;
	/** @var int */
	public $morphedInto = -1;
	/** @var int */
	public $morphHeight = 0;
	/** @var array|null */
	private $morphMeta = null;
	/** @var \stdClass */
	public $virtualPurchases;
	/** @var Game */
	public $currentGame = null;
	/** @var int */
	public $gameStartingPos = -1;
	/** @var Area */
	public $currentArea = null;
	/** @var bool */
	public $isSpectating = false;
	/** @var array */
	public $damageList = [];
	/** @var array */
	public $kills = [];
	/** @var array */
	public $killAssists = [];
	/** @var Item[] */
	public $hotbarItems = [];
	/** @var ActionItem|null */
	public $actionItem = null;
	/** @var int */
	private $hotbarActionEmptySlot = -1;
	/** @var int */
	public $previousHeldSlot = -1;
	/** @var int|null */
	public $buyingItem = null;
	/** @var array */
	public $removeQueue = [];
	/** @var bool */
	public $hasPurchaseTask = false;
	/** @var array|null */
	public $particleEffectExtra = null;
	/** @var int|null */
	public $lastGroundPos = null;
	/** @var int */
	public $lastMove = -1;
	/** @var int */
	public $lastAction = PHP_INT_MAX; // move/rotation
	/** @var int */
	public $walkedDist = 0;
	/** @var bool */
	public $hasNotification = false;
	/** @var int */
	public $notificationTime = -1;
	/** @var array */
	private $cooldown = [];
	/** @var int */
	protected $needSendSettings = 0;

    protected $particleHotbar = [3, 4, 5, 6];

	/**
	 * used to switch name with the one in database
	 * 
	 * @param string $newName
	 */
	public function setName($newName) {
		$this->username = $newName;
		$this->iusername = strtolower($newName);
	}

	/**
	 * Try to add coins to player
	 * 
	 * @param int $amount
	 * @return void
	 */
	public function addCoins($amount) {
		if (!$this->isRegistered()) {
			return;
		}

		$this->coinsNum += $amount;
		Skywars::$instance->dbManager->addCoins($this, $amount);
	}

	/**
	 * Delete specified amount of bonus items from player data
	 * 
	 * @param int $prodId
	 * @param int $amount
	 * @param bool $instant
	 * @return void
	 */
	public function removeProduct($prodId, $amount, $instant = false) {
		if ($this->isRegistered() && !$this->isAuthorized()) {
			return;
		}

		if (isset($this->virtualPurchases->$prodId)) {
			$this->virtualPurchases->$prodId -= $amount;
		} else {
			$this->virtualPurchases->$prodId = -$amount;
		}

		//check if bonus is permanent
		if ($instant) {
			Skywars::$instance->dbManager->removeProduct($this, $prodId, $amount);
		} else {
			if (isset($this->removeQueue[$prodId])) {
				$this->removeQueue[$prodId] += $amount;
			} else {
				$this->removeQueue[$prodId] = $amount;
			}
		}
	}

	/**
	 * Remove bonus saved in queue for deletion
	 */
	public function removeQueued() {
		foreach ($this->removeQueue as $prodId => $amount) {
			Skywars::$instance->dbManager->removeProduct($this, $prodId, $amount);
		}
	}

	/**
	 * Calls when player try to buy specific amount of bonus items
	 * 
	 * @param int $prodId
	 * @param int $amount
	 * @return boolean
	 */
	public function buyProduct($prodId, $amount = 1) {
		if (!$this->isRegistered()) {
			return false;
		}
		if ($this->hasPurchaseTask) {
			return false;
		}
    		$this->hasPurchaseTask = true;
		$this->removeQueued();
		Skywars::$instance->dbManager->buyProduct($this, $prodId, $amount);
		return true;
	}

	/**
	 * Check if specified item issat in player data
	 * 
	 * @param int $productId
	 * @return bool
	 */
	public function hasBought($productId) {
		return isset($this->virtualPurchases->$productId);
	}

	/**
	 * Look for amount of specified product in player's data
	 * 
	 * @param int $productId
	 * @return int
	 */
	public function getProductAmount($productId) {
		if (isset($this->virtualPurchases->$productId)) {
			return $this->virtualPurchases->$productId;
		}
		return 0;
	}

	/**
	 * Check if player can play game 
	 * (allowed only for non-registered and authorized players)
	 * @return boolean
	 */
	public function canPlay() {
		if (!$this->isRegistered() || $this->isAuthorized()) {
			return true;
		}
		return false;
	}

	/**
	 * Check if player can place or break blocks:
	 * he must be non-registered or authorized, be in game, not spectator,
	 * then redirect to area check
	 * 
	 * @return boolean
	 */
	public function canPlaceBreakBlocks() {
		if ($this->isRegistered() && !$this->isAuthorized()) {
			return false;
		}
		if ($this->currentArea === null) {
			return false;
		}
		if ($this->isSpectating) {
			return false;
		}

		return $this->currentArea->canPlaceBreakBlocks();
	}

	/**
	 * Morph player into some entity
	 * 
	 * @param int $eid
	 * @param array $meta
	 * @param int $h
	 */
	public function morphInto($eid, $meta = [], $h = 1) {
		$this->despawnFromAll();
		$this->morphedInto = $eid;
		$this->morphMeta = $meta;
		$this->morphMeta[self::DATA_NAMETAG] = [self::DATA_TYPE_STRING, $this->getDisplayName()];
		$this->morphMeta[self::DATA_SHOW_NAMETAG] = [self::DATA_TYPE_BYTE, 1];
		$this->morphHeight = $h;
		$this->spawnToAll();
	}

	/**
	 * Spawn morphed player to other player
	 * 
	 * @param Player $player
	 * @return void
	 */
	public function spawnTo(Player $player) {
		if ($this->morphedInto !== -1) {
			if ($this->spawned and $player->spawned and 
					$this->isAlive() and 
					$player->isAlive() and 
					$player->getLevel() === $this->level and 
					$player->canSee($this) and 
					! $this->isSpectator() and
					$player !== $this and 
					! isset($this->hasSpawned[$player->getId()])) {
				$this->hasSpawned[$player->getId()] = $player;

				$pk = new AddEntityPacket();
				$pk->eid = $this->getId();
				$pk->type = $this->morphedInto;
				$pk->x = $this->getX();
				$pk->y = $this->getY();
				$pk->z = $this->getZ();
				$pk->yaw = 0;
				$pk->pitch = 0;
				$pk->metadata = $this->morphMeta;
				$player->dataPacket($pk);
			}
			return;
		}
		parent::spawnTo($player);
	}

	/**
	 * Despawn morphed player from other player
	 * 
	 * @param Player $player
	 * @return void
	 */
	public function despawnFrom(Player $player) {
		if ($this->morphedInto !== -1) {
			if (isset($this->hasSpawned[$player->getId()])) {
				unset($this->hasSpawned[$player->getId()]);
				$pk = new RemoveEntityPacket();
				$pk->eid = $this->id;
				$player->dataPacket($pk);
			}
			return;
		}
		parent::despawnFrom($player);
	}

	/**
	 * Send position for morphed player
	 * 
	 * @param Vector3 $pos
    * @param int $yaw
	 * @param int $pitch
	 * @param int $mode
	 * @param array $targets
	 * @return void
	 */
	public function sendPosition(Vector3 $pos, $yaw = null, $pitch = null, $mode = 0, array $targets = null) {
		if ($this->morphedInto !== -1) {
			if ($targets === null) {
				parent::sendPosition($pos, $yaw, $pitch, $mode, $targets);
				return;
			}

			$yaw = $yaw === null ? $this->yaw : $yaw;
			$pitch = $pitch === null ? $this->pitch : $pitch;

			$pk = new MoveEntityPacket;
			$pk->entities = [[$this->getId(), $pos->x, $pos->y, $pos->z, $yaw, $yaw, $pitch]];
			Server::broadcastPacket($targets, $pk);
			return;
		}
		parent::sendPosition($pos, $yaw, $pitch, $mode, $targets);
	}

	/**
	 * Get cooldown isset for using special items
	 * 
	 * @param int $type
	 * @param string $name
	 * @param int $ticks
	 * @param string $displayName
	 * @return boolean
	 */
	public function cooldown($type, $name, $ticks, $displayName = null) {
		if (isset($this->cooldown[$name])) {
			$this->sendTip($this->getTranslatedString("PLAYER_WAIT_SECONDS", "", array(round($this->cooldown[$name][1] / 20))));
			return false;
		}

		$this->cooldown[$name] = [$type, $ticks, $displayName];
		return true;
	}

	/**
	 * Decrement cooldown timer, calls in CooldownTick task
	 */
	public function cooldownTick() {
		foreach ($this->cooldown as $id => &$data) {
			$data[1] --; // ticks
			if ($data[1] <= 0) {
				unset($this->cooldown[$id]);
			}
		}
	}

	/**
	 * Clear all hotbar slots
	 */
	public function clearHotbar() {
		$this->hotbarItems = null;
		$this->getInventory()->setHotbarSlotIndex(0, -1);
		$this->getInventory()->setHotbarSlotIndex(1, -1);
		$this->getInventory()->setHotbarSlotIndex(2, -1);
		$this->getInventory()->setHotbarSlotIndex(3, -1);
		$this->getInventory()->setHotbarSlotIndex(4, -1);
		$this->getInventory()->setHotbarSlotIndex(5, -1);
		$this->getInventory()->setHotbarSlotIndex(6, -1);
		$this->getInventory()->setHotbarSlotIndex(7, -1);
	}

	/**
	 * Set item in specified hotbar slot
	 * 
	 * @param int $slot
	 * @param Item $item
	 */
	public function setHotbarAction($slot, $item) {
		$this->getInventory()->setItem($slot, $item);
		$this->getInventory()->setHotbarSlotIndex($slot, $slot);
		$this->getInventory()->sendContents($this);
	}

	/**
	 * Set array of hotbar items
	 * 
	 * @param array $items
	 * @param int $resetSlot
	 */
	public function setHotbarActions($items, $resetSlot = -1) {
		//clear all current inventory
		$this->actionItem = null;
		$this->hotbarActionEmptySlot = $resetSlot;
		$this->getInventory()->clearAll();

		$this->clearHotbar();
		//save new inventory items
		$this->hotbarItems = $items;

		foreach ($items as $slot => $item) {
			$this->getInventory()->setItem($slot, $item);
			$this->getInventory()->setHotbarSlotIndex($slot, $slot);
		}

		$this->getInventory()->sendContents($this);
		$this->getInventory()->sendArmorContents($this);
		$this->previousHeldSlot = -1;
		$this->getInventory()->setHeldItemIndex($this->getEmptyHotbarSlot());
	}

	/**
	 * Look for empty clot in player's hotbar
	 * 
	 * @return int
	 */
	public function getEmptyHotbarSlot() {
		if ($this->hotbarActionEmptySlot !== -1) {
			return $this->hotbarActionEmptySlot;
		}
		for ($i = 0; $i < 9; $i++) {
			if ($this->getInventory()->getHotbarSlotIndex($i) === -1) {
				return $i;
			}
		}
		return -1;
	}

	/**
	 * Set player as spectator
	 * 
	 * @return void
	 */
	public function spectate() {		
		$this->isSpectating = true;
		if ($this->currentGame === null){
			return;
		}
		
		$this->setGamemode(Player::SPECTATOR);
		$this->sendTip($this->getTranslatedString("IS_SPECTATOR", TextFormat::BOLD . TextFormat::GRAY));
		if (isset($this->currentGame->players[$this->getName()])) {
			unset($this->currentGame->players[$this->getName()]);
			$this->currentGame->spectators[$this->getName()] = $this;
		}

		// give spectator items
		$this->setHotbarActions([
			0 => new CompassActionItem(),
			1 => new ReturnToLobbyActionItem()
		]);
		$this->hideFromAll();
	}

	/**
	 * Send notification tip to player
	 * 
	 * @param string $str
	 * @param int $time
	 * @return void
	 */
	public function showNotification($str, $time = -1) {
		if ($this instanceof \webplay\WebPlayPlayer) {
			$this->showTitle(null, $str, $time, true, 0, 0, 0, 0, 1, 1, 1, 1);
			return;
		}

		if ($str != null) {
			$this->hasNotification = $str;
			$this->notificationTime = $time;
			$this->sendTip($str);
		} else {
			$this->hasNotification = false;
		}
	}

	/**
	 * Collect items dropped from player inventory
	 * 
	 * @return array
	 */
	public function getDrops() {
		if (!$this->isCreative()) {
			$drops = [];
			if ($this->inventory instanceof PlayerInventory) {
				foreach ($this->inventory->getContents() as $item) {
					if ($item instanceof NamedItem) {
						continue;
					}
					$drops[] = $item;
				}
			}

			return $drops;
		}

		return [];
	}
	
	/**
	 * Move player to lobby, set default spawn options for him, 
	 * delete old tasks and options if needs
	 */
	public function returnToLobby() {
		$this->setGamemode(0);
		$plugin = Skywars::$instance;
		$this->removeAllEffects();
		$this->setAllowFlight(false);
		$this->setAutoJump(true);
		$this->setOnFire(0);
		$this->setFoodEnabled(false);
		$plugin->gadgetManager->applyEffects($this);
		$this->showNotification(null);
		if ($plugin->portalTask != null) {
			$plugin->portalTask->spawnToPlayer($this);
		}
		$this->getInventory()->clearAll();
		$this->getInventory()->sendArmorContents($this);
		$plugin->gadgetManager->addGadgetsToInv($this);
        $this->setStateInLobby();
		if ($this->currentGame !== null) {
			$this->currentGame->leave($this);
			$this->currentGame = null;
		}
		$this->kills = [];
		$this->killAssists = [];
		$this->damageList = [];
		$this->gameStartingPos = -1;
		$this->isSpectating = false;
		
		$plugin->lobbyArea->setAreaFor($this);
		$plugin->npcManager->respawnNPCs($this);
		$this->showToAll();
	}

	/**
	 * Add player to game after verifying process,
	 * then call gameManager analogue method
	 * 
	 * @param string $gameType
	 * @return boolean
	 */
	public function joinGame($gameType) {
		$plugin = Skywars::$instance;
		foreach ($plugin->gameManager->games as $game) {
			if (!$game->private && $game::$type == $gameType && $game->maxPlayers >= count($game->players) + 1) {
				if (!$game->started || $game->countdown > 0) {
					$this->showToAll();
					$plugin->gameManager->joinGame($game->name, $this, true);
					return true;
          }
        }
      }
      		$this->showToAll();
		$plugin->gameManager->joinGame($plugin->gameManager->createGame($gameType), $this, true);
		$this->setGamemode(0);
		return true;
	}
	
	/**
	 * Decrement needSendSettings field, return new value of this field
	 * 
	 * @return boolean
	 */
	public function needSendSettings(){
		if($this->needSendSettings > 0){
			$this->needSendSettings--;
			return true;
		}
		return false;
	}
	
	/**
	 * Change player's gamemode and set suitable options, like:
	 * spectator (creative gamemode) - is invisible, can fly, have teleport item, etc
	 * 
	 * @param int $gm
	 * @return boolean
	 */
	public function setGamemode($gm){
		if($gm < 0 or $gm > 3 or $this->gamemode === $gm){
			return false;
		}

		$this->server->getPluginManager()->callEvent($ev = new PlayerGameModeChangeEvent($this, (int) $gm));
		if($ev->isCancelled()){
			return false;
		}


		$this->gamemode = $gm;
		
		$this->allowFlight = $this->isCreative();
		
		if($this->isSpectator()){
			$this->despawnFromAll();
		}else{
			$this->spawnToAll();
		}

		$this->namedtag->playerGameType = new IntTag("playerGameType", $this->gamemode);
		$pk = new SetPlayerGameTypePacket();
		$pk->gamemode = $this->gamemode & 0x01;
		$this->dataPacket($pk);


		if($this->gamemode === self::SPECTATOR){
			$pk = new ContainerSetContentPacket();
			$pk->windowid = ContainerSetContentPacket::SPECIAL_CREATIVE;
			$this->dataPacket($pk);
		}else{
			$pk = new ContainerSetContentPacket();
			$pk->windowid = ContainerSetContentPacket::SPECIAL_CREATIVE;
			foreach(Item::getCreativeItems() as $item){
				$pk->slots[] = clone $item;
			}
			$this->dataPacket($pk);
		}

		$this->inventory->sendContents($this);
		$this->inventory->sendContents($this->getViewers());
		$this->inventory->sendHeldItem($this->hasSpawned);
		$this->needSendSettings = 3;
		return true;
	}

}
