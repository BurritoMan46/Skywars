<?php

namespace Skywars\gadget;

use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\network\protocol\TakeItemEntityPacket;
use pocketmine\Server;
use Skywars\player\CustomPlayer;
use Skywars\Skywars;
use pocketmine\event\Listener;

/**
 * Handle coin bomb throwing options, create as event listener
 */
class CoinBombGadget implements Listener {
	/** @var Skywars */
	private $plugin;

	/**
	 * Base class constructor, start to listen events
	 * @param Skywars $plugin
	 */
	public function __construct(Skywars $plugin) {
		$this->plugin = $plugin;
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}

	/**
	 * Drop item after hit
	 * 
	 * @param ProjectileHitEvent $event
	 */
	public function onProjectileHit(ProjectileHitEvent $event) {
		if ($event->getEntity() instanceof CoinBombProjectile) {
			$e = $event->getEntity();
			$l = $e->getLevel();
			$item = Item::get(175, 0, 1);
			for ($i = 0; $i < 100 / 5; $i++) {
				$this->dropItem($l, $e, $item, new Vector3(0.5 * (-0.5 + lcg_value()), 0.1, 0.5 * (-0.5 + lcg_value())));
			}
		}
	}

	/**
	 * Calls when player pickup coin bomb - add to inventory
	 * 
	 * @param InventoryPickupItemEvent $event
	 */
	public function onPickup(InventoryPickupItemEvent $event) {
		if ($event->getItem() instanceof CoinBombItemEntity) {
			$h = $event->getInventory()->getHolder();
			if ($h instanceof CustomPlayer) {
				$h->addCoins($event->getItem()->value);

				$pk = new TakeItemEntityPacket();
				$pk->eid = $h->getId();
				$pk->target = $event->getItem()->getId();
				Server::broadcastPacket($event->getItem()->getViewers(), $pk);
				$event->getItem()->kill();

				$h->getInventory()->sendContents($h);

				$event->setCancelled(true);
			}
		}
	}

	/**
	 * Calls when player drop coin bomb - throw it with delay
	 * 
	 * @param Level $level
	 * @param Vector3 $source
	 * @param Item $item
	 * @param Vector3 $motion
	 * @param int $delay
	 * @return \Skywars\gadget\CoinBombItemEntity
	 */
	private function dropItem(Level $level, Vector3 $source, Item $item, Vector3 $motion = null, $delay = 10) {
		$motion = $motion === null ? new Vector3(lcg_value() * 0.2 - 0.1, 0.2, lcg_value() * 0.2 - 0.1) : $motion;
		if ($item->getId() > 0 and $item->getCount() > 0) {
			$itemEntity = new CoinBombItemEntity($level->getChunk($source->getX() >> 4, $source->getZ() >> 4), new Compound("", [
				"Pos" => new Enum("Pos", [
					new DoubleTag("", $source->getX()),
					new DoubleTag("", $source->getY()),
					new DoubleTag("", $source->getZ())
						]),
				"Motion" => new Enum("Motion", [
					new DoubleTag("", 0),
					new DoubleTag("", 0),
					new DoubleTag("", 0)
						]),
				"Rotation" => new Enum("Rotation", [
					new FloatTag("", lcg_value() * 360),
					new FloatTag("", 0)
						]),
				"Health" => new ShortTag("Health", 5),
				"Item" => new Compound("Item", [
					"id" => new ShortTag("id", $item->getId()),
					"Damage" => new ShortTag("Damage", $item->getDamage()),
					"Count" => new ByteTag("Count", $item->getCount())
						]),
				"PickupDelay" => new ShortTag("PickupDelay", $delay)
			]));

			$itemEntity->spawnToAll();
			$itemEntity->setMotion($motion);
			return $itemEntity;
		}
		return null;
	}

}
