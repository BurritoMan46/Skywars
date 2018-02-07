<?php


namespace Skywars\language;

use pocketmine\utils\TextFormat;
use LbCore\language\core\English as LbEnglish;

/**
 * Contains specific plugin language strings
 */
class English extends LbEnglish {
	
	/**
	 * Base class constructor - add plugin custom strings to core english array
	 */
	public function __construct() {
		$this->translates = array_merge($this->translates, $this->swTranslates);
	}
	/** @var array */
	private  $swTranslates = array(
		"ERROR_PREFIX" => "Error> ",
		"GAME_PREFIX" => "Game> ",
		"JOIN_PREFIX" => "Join> ",
		"QUIT_PREFIX" => "Quit> ",
		"WARNING_PREFIX" => TextFormat::RED . "Warning> ",
		"WELCOME_TO_LIFEBOAT" => TextFormat::GREEN . "Welcome to Lifeboat arg1!",	
		"PURCHASED_ITEM" => TextFormat::GREEN . "Purchase> You have purchased the ",
		"ALREADY_PURCHASED" => TextFormat::RED . "You have already purchased this item!",
		"GADGET_FOR_VIP" => "This item is only available to VIPs.",
		"WAITING" => "Please wait...",
		"NOT_PURCHASED_ITEM" => TextFormat::RED . "You haven't purchased this item yet!\n" . TextFormat::GRAY . "To purchase this item, tap it again.",
		"PREP_FOR" => " for ", 
		"CAN_BUY" => "You don't have any arg1!\n You can buy arg2 " . TextFormat::YELLOW . " for arg3 coins\n" . TextFormat::YELLOW . "by pressing the screen again.",
		"SEND_POTATO" => "You potatoed ",
		"GET_POTATO" => "You got potatoed by ",
		"IS_SPECTATOR" => "You are a spectator.",
		"GAME_IN_PROGRESS" => "This game is in progress.",
		"PLAYER_LIMIT_ERROR" => "This game has reached it's max player count.",
		"NO_START_POSITION" => "Cannot find starting position!",
		"PLAYER_WAIT" => TextFormat::YELLOW . "Waiting for players...",
		"YOU_WON" => "You have won the game!",
		"GAME_RESULTS" => "Results:",
		"YOU_DIED" => TextFormat::RED . "You died!",
		"TOTAL_RESULT" => "Total: ",
		"PLAYER_WON" => "arg1 has won a arg2 game!",
		"GAME_WAS_STARTED" => "Game> Game was started.",
		"PLAYER_WAS_KILLED" => TextFormat::RED . "arg1 was killed by arg2",		
		"START_POSITION_ERROR" => "Cannot find your starting position!",
		"GAME_STARTED_GOOD_LUCK" => "Game> The game has started! Good luck!",
		"STARTING_GAME_COUNTDOWN" => "Starting in arg1 seconds...",
		"NO_GAME" => "Game arg1 doesn't exist!",
		"ON_GAME" => "You are already on arg1!",
		"IN_GAME" => "You are now in: arg1",
		"JUST_COINS" => " coins",
		"ACC_NOT_REGISTERED" => TextFormat::GRAY . "This account is not registered.",
		"PLACE_BLOCK_ERROR" => "You can't place blocks here.",
		"BREAK_BLOCK_ERROR" => "You can't break blocks here.",
		"YOU_TELEPORTED" => "You were teleported to: ",
		"BASE_ERROR" => "An error has occurred.",		
		"DISALLOWED_AREA" => TextFormat::RED . TextFormat::BOLD . "You were on disallowed area! You were returned to lobby.",
		"RETURNED_TO_AREA" => TextFormat::BOLD . "Return to allowed area!",				
		"SECONDS_BEFORE" => " seconds before)",
		"GAME_FINDING" => "Finding a game...",
		"PLAYER_WAIT_SECONDS" => TextFormat::YELLOW . TextFormat::BOLD . "You need to wait arg1 seconds"	
	);
	
}
