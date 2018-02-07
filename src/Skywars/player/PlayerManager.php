<?php

namespace Skywars\player;

use pocketmine\entity\Entity;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\utils\TextFormat;
use ReflectionClass;
use Skywars\player\action\ActionItemManager;
use Skywars\Skywars;
use pocketmine\Player;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

/**
 * Manages what players can do.
 *
 */
class PlayerManager implements Listener {
	/** @var SkyWars */
	private $plugin;
	/** @var ActionItemManager */
	private $actionItemManager;
	/** @var ReflectionProperty */
	private $attackTimeProperty;
	/** @var int */
	private $notifyId;

	/**
	 * Base class constructor, create events registration,
	 * calls subclass ActionItemManager and dependable tasks
	 * 
	 * @param Skywars $plugin
	 */
	public function __construct(Skywars $plugin) {
		$this->plugin = $plugin;
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);

		$this->actionItemManager = new ActionItemManager($plugin);



		$plugin->getServer()->getScheduler()->scheduleRepeatingTask(new CooldownTickTask($plugin), 1);
		$plugin->getServer()->getScheduler()->scheduleRepeatingTask(new NotificationTickTask($plugin), 20);

		// heh, maybe this is a bit hacky, but why not do it like this?
		$livingClass = new ReflectionClass("\\pocketmine\\entity\\Living");
		$this->attackTimeProperty = $livingClass->getProperty("attackTime");
		$this->attackTimeProperty->setAccessible(true);

		$this->notifyId = Entity::$entityCount++;
		;
	}

	/**
	 * Get current value of attack time
	 * 
	 * @param Player $player
	 * @return int
	 */
	public function getAttackTime(Player $player) {
		return $this->attackTimeProperty->getValue($player);
	}

	/**
	 * Set new attack time value for player
	 * 
	 * @param Player $player
	 * @param int $newVal
	 */
	public function setAttackTime(Player $player, $newVal) {
		$this->attackTimeProperty->setValue($player, $newVal);
	}

	/**
	 * Handle event of player moving to save his last action and last move
	 * 
	 * @param PlayerMoveEvent $event
	 */
	public function onPlayerMove(PlayerMoveEvent $event) {
		$player = $event->getPlayer();
		$player->lastAction = $this->plugin->getServer()->getTick();
		if (!$event->getTo()->equals($event->getFrom())) {
			$player->lastMove = $this->plugin->getServer()->getTick();
		}
	}

	/**
	 * Custom subevent of EntityDamageEvent, 
	 * cancel lobby attacks from players 
	 * 
	 * @param EntityDamageEvent $event
	 * @return void
	 */
	public function onPlayerHurt(EntityDamageEvent $event) {
		if ($event->isCancelled()) {
			return;
		}
		if ($event->getDamage() <= 0) {
			return;
		}

		if ($event instanceof EntityDamageByEntityEvent) {
			if ($event->getDamager() instanceof Player) {
				$player = $event->getDamager();
				$area = $player->currentArea;
				if ($area === null) {
					$event->setCancelled(true);
					return;
				}
			}
		}
		if ($event->getEntity() instanceof Player) {
			$area = $event->getEntity()->currentArea;
			if ($area === null) {
				$event->setCancelled(true);
				return;
			}
			if ($area->noDamage) {
				$event->setCancelled(true);
			}
		}
	}

	/**
	 * Calls when player join game, create default settings for him,
	 * clear welcome message
	 * 
	 * @param PlayerJoinEvent $event
	 */
	public function onPlayerJoin(PlayerJoinEvent $event) {
		$event->setJoinMessage("");
		$event->getPlayer()->lastAction = $this->plugin->getServer()->getTick();
	}

	/**
	 * Calls when player quit server,
	 * set quit message as empty, remove player data
	 * 
	 * @param PlayerQuitEvent $event
	 */
	public function onPlayerQuit(PlayerQuitEvent $event) {
		$event->setQuitMessage("");
		$event->getPlayer()->removeQueued();
	}
  
  	/**
	 * Calls when player try to set block,
	 * check if he is allowed to do this,
	 * also check if block is inside allowed area
	 * 
	 * @param BlockPlaceEvent $event
	 * @return void
	 */
	public function onPlaceBlock(BlockPlaceEvent $event) {
		if (!$event->getPlayer()->canPlaceBreakBlocks()) {
			$event->setCancelled(true);
			return;
		}
		$b = $event->getBlock();
		$area = $event->getPlayer()->currentArea;
		if ($b->x < $area->centerX - $area->kickSize || $b->z < $area->centerZ - $area->kickSize ||
				$b->x > $area->centerX + $area->kickSize || $b->z > $area->centerZ + $area->kickSize) {
			$event->setCancelled(true);
			$event->getPlayer()->sendLocalizedMessage("PLACE_BLOCK_ERROR", array(), TextFormat::RED);		
			return;
		}
	}

	/**
	 * Calls when player try to break block
	 * Check if he is allowed to do this,
	 * also check if block is inside allowed area
	 * 
	 * @param BlockBreakEvent $event
	 * @return void
	 */
	public function onBreakBlock(BlockBreakEvent $event) {
		if (!$event->getPlayer()->canPlaceBreakBlocks()) {
			$event->setCancelled(true);
		}
		$b = $event->getBlock();
		$area = $event->getPlayer()->currentArea;
		if ($b->x < $area->centerX - $area->kickSize || $b->z < $area->centerZ - $area->kickSize ||
				$b->x > $area->centerX + $area->kickSize || $b->z > $area->centerZ + $area->kickSize) {
			$event->setCancelled(true);
			$event->getPlayer()->sendLocalizedMessage("BREAK_BLOCK_ERROR", array(), TextFormat::RED);		
			return;
		}
	}

	/**
	 * Calls when player try to interact with item,
	 * check if he is allowed to do this,
	 * also check if block is inside allowed area
	 * 
	 * @param PlayerInteractEvent $event
	 * @return void
	 */
	public function onUseItem(PlayerInteractEvent $event) {
		if (!$event->getPlayer()->canPlaceBreakBlocks()) {
			$event->setCancelled(true);
		}
		if ($event->getFace() === 0xff) {
			return;
		}
		$b = $event->getBlock();
		$area = $event->getPlayer()->currentArea;
		if ($b->x < $area->centerX - $area->kickSize || $b->z < $area->centerZ - $area->kickSize ||
				$b->x > $area->centerX + $area->kickSize || $b->z > $area->centerZ + $area->kickSize) {
			$event->setCancelled(true);
			$event->getPlayer()->sendLocalizedMessage("PLACE_BLOCK_ERROR", array(), TextFormat::RED);		
			return;
		}
	}

}
