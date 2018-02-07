<?php


namespace Skywars\language;

use pocketmine\utils\TextFormat;
use LbCore\language\core\Dutch as LbDutch;

/**
 * Contains specific plugin language strings
 */
class Dutch extends LbDutch {
	
	/**
	 * Base class constructor - add plugin custom strings to core dutch array
	 */
	public function __construct() {
		$this->translates = array_merge($this->translates, $this->swTranslates);
	}
	/** @var array */
	private  $swTranslates = array(
		"ERROR_PREFIX" => "Fout> ",
		"GAME_PREFIX" => "Spel> ", 
		"JOIN_PREFIX" => "Toetreden> ",
		"QUIT_PREFIX" => "Verlaten> ",
		"WARNING_PREFIX" => TextFormat::RED . "Waarschuwing> ",		
		"WELCOME_TO_LIFEBOAT" => TextFormat::GREEN . "Welkom bij reddingsboot arg1!",
		"PURCHASED_ITEM" => TextFormat::GREEN . "Aankoop> U hebt gekocht van de ",
		"ALREADY_PURCHASED" => TextFormat::RED . "U hebt gekocht heeft u dit item!",
		"GADGET_FOR_VIP" => "Dit artikel is alleen beschikbaar voor VIP's.",
		"WAITING" => "Wacht alstublieft...",
		"NOT_PURCHASED_ITEM" => TextFormat::RED . "U hebt gekocht nog niet dit item!\n" . TextFormat::GRAY . "Om dit voorwerp te kopen, tik er opnieuw.",
		"PREP_FOR" => " for ", 
		"CAN_BUY" => "Je hebt geen arg1!\n U kunt arg2 kopen " . TextFormat::YELLOW . " voor arg3 coins\n" . TextFormat::YELLOW . "door nogmaals op het scherm.",
		"SEND_POTATO" => "Je potatoed ",
		"GET_POTATO" => "Je hebt potatoed door ",		
		"IS_SPECTATOR" => "Je bent een toeschouwer.",
		"GAME_IN_PROGRESS" => "Dit spel is in volle gang.",
		"PLAYER_LIMIT_ERROR" => "Dit spel heeft bereikt is het maximum aantal spelers.",
		"NO_START_POSITION" => "Kan het niet vinden uitgangspositie!",
		"PLAYER_WAIT" => TextFormat::YELLOW . "Wachtend op spelers...",
		"YOU_WON" => "Je hebt het spel gewonnen!",
		"GAME_RESULTS" => "Resultaten:",
		"YOU_DIED" => TextFormat::RED . "Je stierf!",
		"TOTAL_RESULT" => "Totaal: ",
		"PLAYER_WON" => "arg1 heeft een arg2 gewonnen!",
		"GAME_WAS_STARTED" => "Game> spel werd gestart.",
		"PLAYER_WAS_KILLED" => TextFormat::RED . "arg1 werd gedood door arg2",
		"START_POSITION_ERROR" => "Kan uw uitgangspositie niet te vinden!",
		"GAME_STARTED_GOOD_LUCK" => "Game> Het spel is begonnen! Veel geluk!",
		"STARTING_GAME_COUNTDOWN" => "Vanaf arg1 seconden...",
		"NO_GAME" => "Spel arg1 bestaat niet!",
		"ON_GAME" => "U bent al op arg1!",
		"IN_GAME" => "U bent nu in: arg1",		
		"JUST_COINS" => " munten",
		"ACC_NOT_REGISTERED" => TextFormat::GRAY . "Dit account is niet geregistreerd.",
		"PLACE_BLOCK_ERROR" => "Je kunt hier geen blokken te plaatsen.",
		"BREAK_BLOCK_ERROR" => "Je kunt hier geen blokken breken.",
		"YOU_TELEPORTED" =>  "Je werd geteleporteerd naar: ",
		"BASE_ERROR" => "Er is een fout opgetreden.",
		"DISALLOWED_AREA" => TextFormat::RED . TextFormat::BOLD . "Je was op verworpen gebied! Je was teruggekeerd om te lobbyen.",
		"RETURNED_TO_AREA" => TextFormat::BOLD . "Keer terug naar toegestaan gebied!",				
		"SECONDS_BEFORE" => " seconden voor)",
		"GAME_FINDING" => "Het vinden van een spel...",
		"PLAYER_WAIT_SECONDS" => TextFormat::YELLOW . TextFormat::BOLD . "Je nodig hebt om arg1 seconden wachten"
	);
	
}
