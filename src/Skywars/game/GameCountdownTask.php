<?php

namespace Skywars\game;

use pocketmine\level\Level;
use pocketmine\utils\TextFormat;
use Skywars\Skywars;
use pocketmine\scheduler\PluginTask;

/**
 * This class manages the game's countdown and a few other things.
 *
 */
class GameCountdownTask extends PluginTask {
	/** @var Skywars */
	private $plugin;
	/** @var GameManager */
	private $gameManager;

	/**
	 * Base class constructor
	 * 
	 * @param Skywars $plugin
	 * @param GameManager $gameManager
	 */
	public function __construct(Skywars $plugin, GameManager $gameManager) {
		parent::__construct($plugin);

		$this->plugin = $plugin;
		$this->gameManager = $gameManager;
	}

	/**
	 * Repeatable method, send broadcast notifications about game start,
	 * when countdown is finished change player's status
	 * 
	 * @param $currentTick
	 */
	public function onRun($currentTick) {
		foreach ($this->gameManager->games as $game) {
			if ($game->started) {
				if ($game->countdown >= 0) {
					$color = TextFormat::GREEN;
					if ($game->countdown < 5) {
						$color = TextFormat::RED;
					} else if ($game->countdown < 10) {
						$color = TextFormat::GOLD;
					}
					$game->broadcastNotification("STARTING_GAME_COUNTDOWN", -1, array($game->countdown), $color);
					if ($game->countdown <= 0) {
						$game->_noDamageTime = $game->noDamageTime;
						if ($game->countdown == 0) {
							$game->broadcastMessageLocalized("GAME_STARTED_GOOD_LUCK", array(), TextFormat::GOLD);
							$game->broadcastNotification("GAME_WAS_STARTED", 3, array(), TextFormat::GREEN);
						}
						foreach ($game->players as $player) {
							if (!$player->isSpectating) {
								$posId = $player->gameStartingPos;
								if ($posId === -1) {
									$this->plugin->getLogger()->error("Player " . $player->getName() . " is in game and isn't spectating, but doesn't have the starting position id saved.");
									$player->sendMessage($player->getTranslatedString($player, "ERROR_PREFIX", TextFormat::RED) . $player->getTranslatedString($player, "START_POSITION_ERROR", TextFormat::BOLD));
								} else {
									$player->getInventory()->clearAll();
									$player->setStateInGame();
								}
							}
						}
						$game->countdownFinished();
					}
					$game->countdown--;
				}
			}

			if ($game->_noDamageTime > 0) {
				$game->_noDamageTime--;
			}
		}	
	}

}
