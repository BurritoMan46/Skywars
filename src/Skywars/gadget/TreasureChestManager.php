<?php

namespace Skywars\gadget;

use pocketmine\block\Block;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\inventory\ChestInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\SimpleTransactionGroup;
use pocketmine\inventory\Transaction;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\tile\Tile;
use Skywars\particle\effects\LavaParticleEffect;
use Skywars\particle\effects\PortalParticleEffect;
use Skywars\particle\effects\RedstoneParticleEffect;
use Skywars\player\CustomPlayer;
use Skywars\Skywars;

/**
 * Manages treasure chests
 */
class TreasureChestManager implements Listener {

	/** @var Skywars */
	private $plugin;
	/** @var array */
	private $positions;

	/**
	 * Base class constructor, prepare positions for treasure chests,
	 * create spawnTreasureChest task, register events
	 * 
	 * @param Skywars $plugin
	 */
	public function __construct(Skywars $plugin) {
		$this->plugin = $plugin;
		$positions = [];
		$posConfig = $plugin->config->get("chests");
		foreach ($posConfig as $id => $data) {
			$positions[] = [new Vector3($data["posX"], $data["posY"], $data["posZ"]), $data["chances"]];
		}
		$this->positions = $positions;
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);

		if (count($this->positions) > 0) {
			$plugin->getServer()->getScheduler()->scheduleDelayedTask(new SpawnTreasureChestTask($plugin, $this), 20 * rand(60 * 60 * 2, 60 * 60 * 6));
		}
	}

	/**
	 * Used to randomly spawn chest
	 */
	public function spawnChestAtRandomPosition() {
		$id = $this->randomItem($this->positions);
		$this->spawnChestAt($this->positions[$id][0]);
	}

	/**
	 * Logic to fill chest with items
	 * 
	 * @param Vector3 $pos
	 * @param float $chanceForRareItem
	 * @param float $chanceForSecondRareItem
	 * @return Tile
	 */
	public function spawnChestAt(Vector3 $pos, $chanceForRareItem = 0.025, $chanceForSecondRareItem = 0.001) {
		$this->plugin->level->setBlock($pos, Block::get(Block::CHEST));
		$nbt = new Compound(false, [
			new Enum("Items", []),
			new StringTag("id", Tile::CHEST),
			new IntTag("x", $pos->x),
			new IntTag("y", $pos->y),
			new IntTag("z", $pos->z)
		]);
		$nbt->Items->setTagType(NBT::TAG_Compound);
		$chest = Tile::createTile("Chest", $this->plugin->level->getChunk($pos->x >> 4, $pos->z >> 4), $nbt);
		$inv = $chest->getInventory();
		for ($i = 0; $i < $inv->getSize(); $i++) {
			$item = null;
			$r = rand(0, 6);
			if ($r <= 1) { // potatoes
				$item = Item::get(Item::POTATO, 0, rand(45, 64));
			} else if ($r <= 3) { // coins
				$item = Item::get(175, 0, rand(40, 64));
			} else if ($r <= 5) { // tnt
				$item = Item::get(Item::TNT, 0, rand(10, 64));
			}
			$inv->setItem($i, $item);
		}

		// rare items
		if (lcg_value() <= $chanceForRareItem) {
			$rareItems = [
				[Item::get(120, 4), 100], // portal particles [2k]
				[Item::get(Item::BUCKET, 10), 25], // lava particles [5k]
				[Item::get(Item::REDSTONE), 25], // redstone particles [5k]
				[Item::get(Item::SPAWN_EGG, 10), 25], // chicken morph [5k]
				[Item::get(Item::SPAWN_EGG, 14), 25], // wolf morph [5k]
				[Item::get(Item::SPAWN_EGG, 38), 100], // enderman morph [2k]
				[Item::get(Item::SPAWN_EGG, 36), 100], // zombie pigman morph [2k]
				[Item::get(Item::SPAWN_EGG, 33), 1], // creeper morph [vip]
				[Item::get(Item::SPAWN_EGG, 15), 1], // villager morph [vip]
			];

			$slots = range(0, $inv->getSize());

			$slotId = array_rand($slots);
			$itemId = $this->randomItem($rareItems);
			$inv->setItem($slots[$slotId], $rareItems[$itemId][0]);

			if (lcg_value() <= $chanceForSecondRareItem) {
				unset($rareItems[$itemId]);
				unset($slots[$slotId]);

				$slotId = array_rand($slots);
				$itemId = $this->randomItem($rareItems);
				$inv->setItem($slots[$slotId], $rareItems[$itemId][0]);
			}
		}
		return $chest;
	}

	/**
	 * Calls when player take items from a chest,
	 * mostly used to send messages about got weapon items (potato, tnt)
	 * 
	 * @param InventoryTransactionEvent $event
	 * @return void
	 */
	public function onInventoryTransaction(InventoryTransactionEvent $event) {
		if ($event->getTransaction() instanceof SimpleTransactionGroup && count($event->getTransaction()->getTransactions()) >= 2) {
			$t = $event->getTransaction()->getTransactions();
			/** @var Transaction $t1 */
			$t1 = reset($t);
			/** @var Transaction $t2 */
			$t2 = end($t);
			if ($t1->getInventory() instanceof ChestInventory &&
					$t2->getInventory() instanceof PlayerInventory
			) {
				if ($t2->getInventory()->getHolder() instanceof CustomPlayer &&
						$t2->getInventory()->getHolder()->currentArea != $this->plugin->lobbyArea
				) {
					return;
				}
				// chest -> player
				/** @var CustomPlayer $p */
				$p = $event->getTransaction()->getSource();
				$t1->getInventory()->setItem($t1->getSlot(), $t1->getTargetItem(), $p);
				$event->setCancelled();

				$id = $t1->getSourceItem()->getId();
				$amount = $t1->getSourceItem()->getCount() - $t1->getTargetItem()->getCount();
				if ($id === Item::POTATO) {
					$this->plugin->getLogger()->info($p->getName() . " received " . $amount . " poatoes");
					$p->buyProduct(13, $amount);
				} else if ($id === Item::TNT) {
					$this->plugin->getLogger()->info($p->getName() . " received " . $amount . " TNT");
					$p->buyProduct(14, $amount);
				} else if ($id === 175) {
					$this->plugin->getLogger()->info($p->getName() . " received " . $amount . " coins");
					$p->addCoins($amount);
				} else if ($id === Item::SPAWN_EGG) {
					$pid = GadgetManager::$morphProductIds[$id];
					$this->plugin->getLogger()->info($p->getName() . " received " . GadgetManager::$productNames[$pid]);
					$this->giveItem($p, $pid);
				} else if ($id === 120) {
					$this->plugin->getLogger()->info($p->getName() . " received Portal particles");
					$this->giveItem($p, PortalParticleEffect::PRODUCT_ID);
				} else if ($id === Item::BUCKET) {
					$this->plugin->getLogger()->info($p->getName() . " received Lava particles");
					$this->giveItem($p, LavaParticleEffect::PRODUCT_ID);
				} else if ($id === Item::REDSTONE) {
					$this->plugin->getLogger()->info($p->getName() . " received Redstone particles");
					$this->giveItem($p, RedstoneParticleEffect::PRODUCT_ID);
				}
			}
		}
	}

	/**
	 * Give bought item to player
	 * 
	 * @param CustomPlayer $player
	 * @param int $prodId
	 * @return void
	 */
	private function giveItem(CustomPlayer $player, $prodId) {
		$price = GadgetManager::$productPrices[$prodId];
		if ($price === -1) {
			$price = 10000;

			if ($player->isVip()) {
				$player->addCoins($price);
				return;
			}
		}

		if ($player->hasBought($prodId)) {
			$player->addCoins($price);
			return;
		}
		$player->buyProduct($prodId);
	}

	/**
	 * Used to get random item from specified array
	 * 
	 * @param array $array
	 * @return int
	 */
	private function randomItem($array) {
		$total = 0;
		foreach ($array as $val) {
			$total += $val[1];
		}
		$n = rand(0, $total - 1);
		$total = 0;
		foreach ($array as $id => $val) {
			$total += $val[1];

			if ($n < $total) {
				return $id;
			}
		}
	}

}
