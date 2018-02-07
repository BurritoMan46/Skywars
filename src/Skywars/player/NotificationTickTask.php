<?php

namespace Skywars\player;

use pocketmine\utils\TextFormat;
use Skywars\Skywars;
use pocketmine\scheduler\PluginTask;

/**
 * Created in PlayerManager class, check for notification time
 */
class NotificationTickTask extends PluginTask {
	/** @var SkyWars */
	private $plugin;

	/**
	 * Base task constructor
	 * @param Skywars $plugin
	 */
	public function __construct(Skywars $plugin) {
		parent::__construct($plugin);
		$this->plugin = $plugin;
	}

	/**
	 * Main repeatable task, decrement notification time,
	 * send welcome message and other notifications
	 * 
	 * @param $currentTick
	 */
	public function onRun($currentTick) {
		foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
			if ($player->spawned && $player instanceof CustomPlayer) {
				if ($player->hasNotification !== false) {
					$player->sendTip($player->hasNotification);
					if ($player->notificationTime >= 0) {
						$player->notificationTime--;

						if ($player->notificationTime === 0) {
							$player->showNotification(null);
						}
					}
				} else if ($player->currentGame === null) {
					//send welcome message
					$welcome = $player->getTranslatedString("WELCOME_TO_AVERSIONPE", "", array($this->plugin->serverGameType));
					$account_status = "";
					//send popup with player's coin balance or message need registration
					if ($player->isAuthorized()) {
						$account_status = TextFormat::YELLOW . $player->getDisplayName() 
								. TextFormat::GRAY . " | " 
								. TextFormat::GOLD . number_format($player->coinsNum) . $player->getTranslatedString("JUST_COINS");
					} else if (!$player->isRegistered()) {
						$account_status = $player->getTranslatedString("ACC_NOT_REGISTERED");
					}					
					$player->sendTip($welcome . "\n" . $account_status);
				}
			}
		}
	}

}
