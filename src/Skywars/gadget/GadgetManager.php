<?php

namespace Skywars\gadget;

use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\network\protocol\StartGamePacket;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;
use Skywars\particle\effects\LavaParticleEffect;
use Skywars\particle\effects\PortalParticleEffect;
use Skywars\particle\effects\RainbowParticleEffect;
use Skywars\particle\effects\RedstoneParticleEffect;
use Skywars\particle\ParticleManager;
use Skywars\player\CustomPlayer;
use Skywars\Skywars;
use pocketmine\item\Item;
use Skywars\db\lifeboat\LifeboatDatabase;

/**
 * Manages the gadgets (potato gun, TNT, teleport)
 */
class GadgetManager implements Listener {
	/** @var Skywars */
	private $plugin;
	/** @var TreasureChestManager */
	public $chestManager;
	/** @var array */
	private $gadgets = [];
	/** @var array */
	private $items = [];
	/** @var array */
	public static $morphProductIds = [
		15 => 10,
		10 => 3,
		14 => 4,
		38 => 5,
		36 => 6,
		33 => 11
	];
	/** @var array */
	public static $productNames = [];
	/** @var array */
	public static $productPrices = [];
	/** @var array */
	private static $mobHeights = [
		15 => 1.8,
		10 => 0.7,
		14 => 0.8,
		38 => 2.9,
		36 => 1.8,
		33 => 1.8
	];

	/**
	 * Base manager constructor, create TreasureChestManager,
	 * prepare array of gadget objects and items (such as morphing eggs)
	 * 
	 * @param Skywars $plugin
	 */
	public function __construct(Skywars $plugin) {
		$this->plugin = $plugin;
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);

		$this->chestManager = new TreasureChestManager($plugin);

		// I disabled potatoes because those can really make the server lag :(
		array_push($this->gadgets, new PotatoGun($plugin));
		array_push($this->gadgets, new TNTGadget($plugin));
		array_push($this->gadgets, new CoinBombGadget($plugin));
		$this->items[1] = new GadgetItem(Item::POTATO, 0, 1, PotatoGun::PRODUCT_ID, "Potato", TextFormat::GOLD);
		$this->items[2] = new GadgetItem(Item::TNT, 0, 1, TNTGadget::PRODUCT_ID, "TNT", TextFormat::RED);

		$this->items[10] = Item::get(Item::SPAWN_EGG, 15); // villager morph
		$this->items[11] = Item::get(Item::SPAWN_EGG, 10); // chicken morph
		$this->items[12] = Item::get(Item::SPAWN_EGG, 14); // wolf morph
		$this->items[13] = Item::get(Item::SPAWN_EGG, 38); // enderman morph
		$this->items[14] = Item::get(Item::SPAWN_EGG, 36); // zombie pigman morph
		$this->items[15] = Item::get(Item::SPAWN_EGG, 33); // creeper morph

		//collect allowed bonuses
		$products = array_merge(self::$morphProductIds, [PotatoGun::PRODUCT_ID, TNTGadget::PRODUCT_ID]);
		$iterator = 0;
		while ($iterator < 5) {
			$iterator++;
			$result = Utils::postURL('http://data.lbsg.net/apiv3/database2.php', array(
						'auth' => LifeboatDatabase::AUTH_STRING,
						'return' => true,
						'cmd' => "SELECT id,title,price FROM virtual_products WHERE id IN (" . implode(",", $products) . ")"
							), 1);
			if ($result && !stristr($result, "fail")) {
				$raw_data = json_decode($result, true);
				if ($raw_data && is_array($raw_data)) {
					foreach ($raw_data as $row) {
						self::$productPrices[$row["id"]] = intval($row["price"]);
						self::$productNames[$row["id"]] = str_replace("&", "§", $row["title"]);
					}
					break;
				}
			}
		}
	}

	/**
	 * Save bought gadget in player's inventory:
	 * clear hotbar, then move items there
	 * 
	 * @param CustomPlayer $player
	 */
	public function addGadgetsToInv(CustomPlayer $player) {
		$player->clearHotbar();
		foreach ($this->items as $id => $item) {
			$player->getInventory()->setItem($id, $item);
		}
		$player->getInventory()->setHotbarSlotIndex(1, 1);
		$player->getInventory()->setHotbarSlotIndex(2, 2);
		$player->getInventory()->setHeldItemIndex(0);
		$player->getInventory()->sendContents($player);
	}

	/**
	 * Check if player can use specified bonus item
	 * 
	 * @param CustomPlayer $player
	 * @param int $prodId
	 * @return boolean
	 */
	private function checkItem(CustomPlayer $player, $prodId) {
		//if not bought - offer to buy
		if (!$player->hasBought($prodId)) {
			if (isset(self::$productPrices[$prodId]) && self::$productPrices[$prodId] <= 0) {
				if ($player->isVip()) {
					return true;
				}
				$player->sendPopup($player->getTranslatedString("GADGET_FOR_VIP", TextFormat::RED));
				return false;
			}

			if ($player->buyingItem === $prodId) {
				$player->sendPopup($player->getTranslatedString("WAITING", TextFormat::YELLOW));
				$player->buyProduct($prodId);
				$player->buyingItem = null;
				return false;
			}

			$name = "Unknown";
			$price = 0;

			if (isset(self::$productNames[$prodId])) {
				$name = self::$productNames[$prodId];
			}
			if (isset(self::$productPrices[$prodId])) {
				$price = self::$productPrices[$prodId];
			}
			//send info about checked bonus
			$lines = TextFormat::GREEN . TextFormat::BOLD . $name . " " 
				. TextFormat::RESET . TextFormat::GOLD . "[" . $price . " coins]" 
				. TextFormat::RESET . "\n" . $player->getTranslatedString("NOT_PURCHASED_ITEM", TextFormat::GRAY);
			$player->sendPopup($lines);
			$player->buyingItem = $prodId;
			return false;
		}
		if (isset(self::$productNames[$prodId])) {
			$player->sendPopup(TextFormat::GREEN . TextFormat::BOLD . self::$productNames[$prodId]);
		}
		return true;
	}

	/**
	 * used for potatoes and TNT
	 * 
	 * @param CustomPlayer $player
	 * @param int $prodId
	 * @param int $amount
	 */
	public static function buyGadget(CustomPlayer $player, $prodId, $amount) {
		if ($player->buyingItem === $prodId) {
			$player->buyProduct($prodId, $amount);
			$player->buyingItem = -1;
		} else {
			if(isset(self::$productNames[$prodId]) && isset(self::$productPrices[$prodId])){
				$buyDetails = array(self::$productNames[$prodId], ($amount . " " . self::$productNames[$prodId]), (self::$productPrices[$prodId] * $amount));
				$player->sendMessage($player->getTranslatedString("CAN_BUY", "", $buyDetails));
				$player->buyingItem = $prodId;
			}
		}
	}

	/**
	 * Calls when player check item from inventory - set effects and other options
	 * 
	 * @param PlayerItemHeldEvent $event
	 * @return void
	 */
	public function switchItem(PlayerItemHeldEvent $event) {
		/** @var CustomPlayer $player */
		$player = $event->getPlayer();

		if (!$player->isAuthorized()) {
			$player->sendTip($player->getTranslatedString("NEEDS_LOGIN", TextFormat::RED));
			return;
		}
		//check if player is in lobby
		if ($player->currentArea === $this->plugin->lobbyArea) {
			if ($event->getInventorySlot() === $event->getPlayer()->getInventory()->getHeldItemSlot() &&
					$event->getItem()->getId() !== 0) {
				$player->buyingItem = -1;
				return;
			}

			$event->setCancelled(true);
			if ($event->getItem()->getId() === Item::SPAWN_EGG) {
				// morph effects
				$type = $event->getItem()->getDamage();

				$prodId = self::$morphProductIds[$type];
				if ($this->checkItem($player, $prodId)) {
					if ($player->getAllowFlight()) {
						$spawnPosition = $player->getSpawn();
						$pk = new StartGamePacket();
						$pk->seed = -1;
						$pk->x = $player->x;
						$pk->y = $player->y;
						$pk->z = $player->z;
						$pk->spawnX = (int) $spawnPosition->x;
						$pk->spawnY = (int) $spawnPosition->y;
						$pk->spawnZ = (int) $spawnPosition->z;
						$pk->generator = 1;
						$pk->gamemode = $player->gamemode & 0x01;
						$pk->eid = $player->getId();
						$player->dataPacket($pk);
					}

					$meta = [];

					if ($event->getPlayer()->getName() == "Sean_M") {
						$meta[12] = [Entity::DATA_TYPE_BYTE, 1];
					}
					if ($type != 10) { // chicken should have ai
						$meta[Entity::DATA_NO_AI] = [Entity::DATA_TYPE_BYTE, 1];
					}
					$player->morphInto($type, $meta, self::$mobHeights[$type]);
					$player->getInventory()->setItem($player->getInventory()->getSize() - 1, Item::get(Item::AIR));
					$player->removeAllEffects();
					$player->setAllowFlight(false);
					$player->setAutoJump(true);
					$this->applyEffects($player);
				}
				//particle effects
			} else if ($event->getItem()->getId() === Item::BUCKET) {
				if ($this->checkItem($player, LavaParticleEffect::PRODUCT_ID)) {
					$this->plugin->particleManager->setPlayerParticleEffect($player, ParticleManager::$lava);
				}
			} else if ($event->getItem()->getId() === Item::REDSTONE) {
				if ($this->checkItem($player, RedstoneParticleEffect::PRODUCT_ID)) {
					$this->plugin->particleManager->setPlayerParticleEffect($player, ParticleManager::$redstone);
				}
			} else if ($event->getItem()->getId() === 120) {
				if ($this->checkItem($player, PortalParticleEffect::PRODUCT_ID)) {
					$this->plugin->particleManager->setPlayerParticleEffect($player, ParticleManager::$portal);
				}
			} else if ($event->getItem()->getId() === 378) {
				if ($this->checkItem($player, RainbowParticleEffect::PRODUCT_ID)) {
					$this->plugin->particleManager->setPlayerParticleEffect($player, ParticleManager::$rainbow);
				}
			}
		}
	}

	/**
	 * Apply morph effects for player
	 * 
	 * @param CustomPlayer $player
	 */
	public function applyEffects(CustomPlayer $player) {
		$player->addEffect(Effect::getEffect(Effect::SPEED)->setVisible(false)->setAmplifier(0.5)->setDuration(0x7fffffff));

		if ($player->morphedInto === 14) { // wolf
			$player->addEffect(Effect::getEffect(Effect::SPEED)->setVisible(false)->setAmplifier(0.8)->setDuration(0x7fffffff));
			$player->addEffect(Effect::getEffect(Effect::JUMP)->setVisible(false)->setAmplifier(0)->setDuration(0x7fffffff));
		}
		if ($player->morphedInto === 10) { // chicken
			$player->addEffect(Effect::getEffect(Effect::JUMP)->setVisible(false)->setAmplifier(3)->setDuration(0x7fffffff));
			$player->setAllowFlight(true);
			$player->setAutoJump(false);
		}
		if ($player->morphedInto === 15) { // villager
			$player->getInventory()->setItem($player->getInventory()->getSize() - 1, new CoinBombItem());
		}
		if ($player->morphedInto === 38) { // enderman
			$player->getInventory()->setItem($player->getInventory()->getSize() - 1, new TeleportItem());
		}
		if ($player->morphedInto === 33) { // creeper
			$player->getInventory()->setItem($player->getInventory()->getSize() - 1, new ExplodeItem());
		}
		if ($player->morphedInto === 36) { // zombie pigman
			$player->addEffect(Effect::getEffect(Effect::SPEED)->setVisible(false)->setAmplifier(1)->setDuration(0x7fffffff));

			if ($player->getName() == "DaCoolKid") {
				$player->addEffect(Effect::getEffect(Effect::SPEED)->setVisible(false)->setAmplifier(1.5)->setDuration(0x7fffffff));
			}
		}
	}

}
