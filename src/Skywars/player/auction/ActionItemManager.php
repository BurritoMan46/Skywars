<?php

namespace Skywars\player\action;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemHeldEvent;
use Skywars\player\CustomPlayer;
use Skywars\Skywars;

/**
 * Manages action items such as compass and teleport to lobby
 * 
 */
class ActionItemManager implements Listener {

	/**
	 * Register as event listener
	 * 
	 * @param Skywars $plugin
	 */
	public function __construct(Skywars $plugin) {
		$this->plugin = $plugin;
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}

	/**
	 * Call when player hold action item in hand,
	 * set it as selected if item has not been selected before
	 * 
	 * @param PlayerItemHeldEvent $event
	 * @return void
	 */
	public function onHeldItem(PlayerItemHeldEvent $event) {
		$itm = $event->getItem();
		/** @var CustomPlayer $p */
		$p = $event->getPlayer();

		if ($event->getInventorySlot() === $event->getPlayer()->getInventory()->getHeldItemSlot()) {
			if ($event->getSlot() === $p->previousHeldSlot) {
				return;
			}
			$p->previousHeldSlot = $event->getSlot();
		} else if ($event->getItem() instanceof NamedItem) {
			$event->setCancelled(true);
			return;
		}
		
		if ($itm instanceof NamedItem) {
			$itm->selected($p);
		} else {
			$hid = $event->getPlayer()->getInventory()->getHeldItemIndex();
			if (isset($p->hotbarItems[$hid])) {
				$hitm = $p->hotbarItems[$hid];
				if ($hitm->getId() === $itm->getId() && $hitm instanceof NamedItem) {
					$hitm->selected($p);
				}
			}
		}
	}

}
