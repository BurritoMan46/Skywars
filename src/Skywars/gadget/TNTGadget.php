<?php

namespace Skywars\gadget;

use pocketmine\entity\PrimedTNT;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
use Skywars\player\CustomPlayer;
use Skywars\Skywars;
use pocketmine\Player;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\Listener;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\FloatTag;

/**
 * Describes logic of TNT gadget
 */
class TNTGadget implements Listener {

	const PRODUCT_ID = 14;
	/** @var Skywars */
	private $plugin;

	/**
	 * Set class as event listener
	 * @param Skywars $plugin
	 */
	public function __construct(Skywars $plugin) {
		$this->plugin = $plugin;
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}

	/**
	 * Calls when player interact with TNT
	 * this action is allowed after some checks
	 * 
	 * @param PlayerInteractEvent $event
	 * @return void
	 */
	public function onInteract(PlayerInteractEvent $event) {
		if ($event->getFace() === 0xff) {
			/** @var CustomPlayer $player */
			$player = $event->getPlayer();

			if ($player->currentGame !== null) {
				return;
			}
			if (!$player->isAuthorized()) {
				$event->getPlayer()->sendTip($event->getPlayer()->getTranslatedString("NEEDS_LOGIN"));
				return;
			}
			//check if he use TNT and some amount of this item is bought
			if ($event->getItem()->getID() === Item::TNT) {
				if ($player->getProductAmount(self::PRODUCT_ID) > 0) {
					if (!$player->cooldown(CustomPlayer::COOLDOWN_LOBBY, "gadget_tnt", 100, TextFormat::RED . "TNT")) {
						return;
					}

					$player->removeProduct(self::PRODUCT_ID, 1);
				} else {
					GadgetManager::buyGadget($player, self::PRODUCT_ID, 50);

					return;
				}

				$this->launchTNT($player, $event->getTouchVector());
			}
		}
	}

	/**
	 * Calls when TNT is thrown
	 * 
	 * @param Player $player
	 * @param Vector3 $vec
	 */
	public function launchTNT(Player $player, Vector3 $vec) {
		$nbt = new Compound("", [
			"Pos" => new Enum("Pos", [
				new DoubleTag("", $player->x),
				new DoubleTag("", $player->y + $player->getEyeHeight()),
				new DoubleTag("", $player->z)
					]),
			"Motion" => new Enum("Motion", [
				new DoubleTag("", -sin($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)),
				new DoubleTag("", -sin($player->pitch / 180 * M_PI)),
				new DoubleTag("", cos($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI))
					]),
			"Rotation" => new Enum("Rotation", [
				new FloatTag("", $player->yaw),
				new FloatTag("", $player->pitch)
					]),
		]);

		$f = 1.5;
		$entity = new \Kits\items\TntProjectile($player->chunk, $nbt, $player);
                $entity->shouldExplode = true;
		$entity->setMotion($entity->getMotion()->multiply($f));
		$entity->spawnToAll();
	}

	/**
	 * Calls when item explode
	 * 
	 * @param ExplosionPrimeEvent $event
	 */
	public function onExplode(ExplosionPrimeEvent $event) {
		$area = $this->plugin->lobbyArea;
		$e = $event->getEntity();
		if ($e->x > $area->centerX - $area->kickSize && $e->z > $area->centerZ - $area->kickSize &&
				$e->x < $area->centerX + $area->kickSize && $e->z < $area->centerZ + $area->kickSize) {
			$event->setBlockBreaking(false);
			$event->setForce(8);
		}
	}

}
 
