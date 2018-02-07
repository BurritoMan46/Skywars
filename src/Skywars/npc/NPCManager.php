<?php

namespace Skywars\npc;

use pocketmine\entity\Entity;
use Skywars\Skywars;
use pocketmine\event\Listener;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use Kits\KitData;
use pocketmine\utils\TextFormat;
use Kits\task\SaveKitsTask;

/**
 * Manages the NPCs (portal, kit giving statues)
 */
class NPCManager implements Listener {
	/** @var Skywars */
	private $plugin;
	/** @var array */
	public $npcs = [];

	/**
	 * Base class constructor, set as event listener
	 * 
	 * @param Skywars $plugin
	 */
	public function __construct(Skywars $plugin) {
		$this->plugin = $plugin;
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}

	/**
	 * Add NPC to level, put on armor and give items if needs,
	 * save new entity in manager field
	 * 
	 * @param string $name
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $yaw
	 * @param int $pitch
	 * @param int $heldItem
	 * @param int $boots
	 * @param int $leggings
	 * @param int $chestplate
	 * @param int $helmet
	 * @param string $kitName
	 * @return \Skywars\npc\NPCEntity
	 */
	public function addNPC(
			$name, 
			$x, 
			$y, 
			$z, 
			$yaw, 
			$pitch, 
			$heldItem = 0, 
			$boots = 0, 
			$leggings = 0, 
			$chestplate = 0, 
			$helmet = 0, 
			$kitName = "") {
		$entity = new NPCEntity($this->plugin->level->getChunk($x >> 4, $z >> 4, true), new Compound("", [
			"Pos" => new Enum("Pos", [
				new DoubleTag("", $x),
				new DoubleTag("", $y),
				new DoubleTag("", $z)
					]),
			"Motion" => new Enum("Motion", [
				new DoubleTag("", 0),
				new DoubleTag("", 0),
				new DoubleTag("", 0)
					]),
			"Rotation" => new Enum("Rotation", [
				new FloatTag("", $yaw),
				new FloatTag("", $pitch)
					]),
			"Inventory" => new Enum("Inventory", []),
			"NameTag" => new StringTag("NameTag", $name)
		]));
		$entity->kitName = $kitName;
		$entity->setSkin(file_get_contents("steve.mcskin"), false);
		if ($heldItem !== 0) {
			$entity->getInventory()->setItem(0, Item::get($heldItem));
			$entity->getInventory()->setHeldItemSlot(0);
		}
		if ($boots !== 0) {
			$entity->getInventory()->setBoots(Item::get($boots));
		}
		if ($leggings !== 0) {
			$entity->getInventory()->setLeggings(Item::get($leggings));
		}
		if ($chestplate !== 0) {
			$entity->getInventory()->setChestplate(Item::get($chestplate));
		}
		if ($helmet !== 0) {
			$entity->getInventory()->setHelmet(Item::get($helmet));
		}
		$entity->setDataProperty(Entity::DATA_NO_AI, Entity::DATA_TYPE_BYTE, 1);
		$entity->spawnToAll();
		array_push($this->npcs, $entity);
		$this->plugin->level->getChunk($x >> 4, $z >> 4)->allowUnload = false;
		return $entity;
	}

	/**
	 * Calls when somebody try to attack NPC
	 * if it is a kit - buy it
	 * 
	 * @param EntityDamageEvent $event
	 * @return void
	 */
	public function onEntityDamage(EntityDamageEvent $event) {
		if ($event->getEntity() instanceof NPCEntity) {
			$event->setCancelled(true);
			if ($event instanceof EntityDamageByEntityEvent) {
				if ($event->getDamager() instanceof Player) {
					$name = $event->getEntity()->kitName;
					$player = $event->getDamager();
					//buy kit logic
					if (!empty($name)) {
						if ($kitId = KitData::getKitIdByName($name)) {
							$kit = KitData::getKit($kitId);
							if (!isset($kit->name)) {
								return;
							}
							// If they tapped the same sign twice then give them the kit! Or if they tap once give kit info
							if ($player->kitSignLastTapped === $kitId) {
								if ($player->haveKit($kitId)) {
									$player->sendLocalizedMessage("HAVE_KIT");
									return;
								}
								if (!$player->isAuthorized() || !$player->isVip()) {
									$player->sendLocalizedMessage("ONLY_FOR_VIP");
									return;
								}
								try {
									$player->addKit($kitId);
								} catch (PlayerBaseException $e) {
									$this->plugin->getLogger()->warning($e->getMessage());
									return;
								}
								// save kits into db
								$task = new SaveKitsTask($player->getName(), $player->getKits());
								$this->plugin->getServer()->getScheduler()->scheduleAsyncTask($task);
								$player->sendLocalizedMessage("VIP_SELECT_KIT", array($kit->name));
							} else {
								$player->kitSignLastTapped = $kitId;
								$player->sendMessage(TextFormat::YELLOW . 'The ' . TextFormat::DARK_PURPLE . $kit->name . TextFormat::YELLOW . ' kit:');
								$player->sendMessage(TextFormat::YELLOW . $kit->description);
								$player->sendMessage(TextFormat::AQUA . "Tap again to choose kit.");
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Logic to respawn all NPC to player again
	 * @param Player $player
	 */
	public function respawnNPCs(Player $player) {
		foreach ($this->npcs as $npc) {
			$npc->despawnFrom($player);
			$npc->spawnTo($player);
		}
	}

}
