<?php

namespace Skywars\game;

use pocketmine\math\AxisAlignedBB;
use Skywars\player\CustomPlayer;
use Skywars\Skywars;
use pocketmine\scheduler\PluginTask;

/**
 * An task, that finds a game for an player
 */
class JoinGameTask extends PluginTask {
	/** @var SkyWars */
	private $plugin;
	/** @var array */
	private $vipQueue;
	/** @var array */
	private $queue;
	/** @var int */
	private $msgTick;

	/**
	 * Base class constructor, create fields
	 * 
	 * @param Skywars $plugin
	 */
	public function __construct(Skywars $plugin) {
		parent::__construct($plugin);
		$this->plugin = $plugin;
		$this->queue = [];
		$this->vipQueue = [];
		$this->msgTick = 0;
	}

	/**
	 * Save player in queue to join game
	 * 
	 * @param CustomPlayer $player
	 * @param string $gameType
	 * @param AxisAlignedBB $aabb
	 */
	public function queuePlayer(CustomPlayer $player, $gameType, AxisAlignedBB $aabb = null) {
		if ($player->isVip()) {
			$this->vipQueue[] = [$player, true, $gameType, $aabb];
		} else {
			$this->queue[] = [$player, true, $gameType, $aabb];
		}
	}

	/**
	 * Repeatable method, calls joinGames submethod
	 * 
	 * @param int $currentTick
	 */
	public function onRun($currentTick) {
		$this->msgTick++;
		if ($this->msgTick > 10) {
			$this->msgTick = 0;
		}

		$this->joinGames($this->queue, $this->joinGames($this->vipQueue));
	}

	/**
	 * Used to join each player to game
	 * 
	 * @param array $players
	 * @param boolean $noGames
	 * @return boolean
	 */
	private function joinGames(&$players, $noGames = false) {
		foreach ($players as $id => $entry) {
			/** @var CustomPlayer $player */
			$player = $entry[0];
			if ($player === null || !$player->isOnline() || $player->currentGame !== null) {
				unset($players[$id]);
				continue;
			}

			/** @var AxisAlignedBB $aabb */
			$aabb = $entry[3];
			if ($aabb !== null && !$aabb->intersectsWith($player->getBoundingBox())) {
				$player->joining = false;				
				unset($players[$id]);
				$player->showNotification(null);
				continue;
			}

			if (!$noGames && $player->joinGame($entry[2])) {
				unset($players[$id]);
			} else {
				$player->removeAllEffects();
				$noGames = true;
				if ($entry[1] === true) {
					$players[$id][1] = false;
				}
			}
		}
		return $noGames;
	}

}
