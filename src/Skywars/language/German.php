<?php


namespace Skywars\language;

use pocketmine\utils\TextFormat;
use LbCore\language\core\German as LbGerman;

/**
 * Contains specific plugin language strings
 */
class German extends LbGerman {
	
	/**
	 * Base class constructor - add plugin custom strings to core german array
	 */
	public function __construct() {
		$this->translates = array_merge($this->translates, $this->swTranslates);
	}
	/** @var array */
	private  $swTranslates = array(
		"ERROR_PREFIX" => "Fehler> ",
		"GAME_PREFIX" => "Spiel> ", 
		"JOIN_PREFIX" => "Beitreten> ",
		"QUIT_PREFIX" => "Verlassen> ",
		"WARNING_PREFIX" => TextFormat::RED . "Warnung> ",		
		"WELCOME_TO_LIFEBOAT" => TextFormat::GREEN . "Willkommen auf arg1 Rettungsboot!",
		"PURCHASED_ITEM" => TextFormat::GREEN . "Kaufen> Sie haben gekauft den ",
		"ALREADY_PURCHASED" => TextFormat::RED . "Du hast diesen Artikel gekauft!",
		"GADGET_FOR_VIP" => "Dieser Artikel ist nur für VIPs zur Verfügung.",
		"WAITING" => "Warten Sie mal...",
		"NOT_PURCHASED_ITEM" => TextFormat::RED . "Sie haben noch keine dieses Produkt noch gekauft:!\n" . TextFormat::GRAY . "Um diesen Artikel zu kaufen, tippen Sie es noch einmal.",
		"PREP_FOR" =>" for ", 
		"CAN_BUY" => "Sie haben noch keine arg1!\n Sie können arg2 kaufen" . TextFormat::YELLOW . " fur arg3 Münzen,\n" . TextFormat::YELLOW . "indem Sie den Bildschirm erneut.",
		"SEND_POTATO" => "Sie potatoed ",
		"GET_POTATO" => "Du von potatoed ",		
		"IS_SPECTATOR" => "Sie sind ein Zuschauer.",
		"GAME_IN_PROGRESS" => "Dieses Spiel ist im Gange.",
		"PLAYER_LIMIT_ERROR" => "Dieses Spiel wurde erreicht, es ist max Spieleranzahl.",
		"NO_START_POSITION" => "Kann nicht finden, Startposition!",
		"PLAYER_WAIT" => TextFormat::YELLOW . "Warten auf Spieler...",
		"YOU_WON" => "Sie haben das Spiel gewonnen!",
		"GAME_RESULTS" => "Ergebnisse:",
		"YOU_DIED" => TextFormat::RED . "Du bist gestorben!",
		"TOTAL_RESULT" => "Gesamt: ",
		"PLAYER_WON" => "arg1 hat arg2 Spiel gewonnen!",
		"GAME_WAS_STARTED" =>"Spiel> Spiel fand Gestartet.",
		"PLAYER_WAS_KILLED" => TextFormat::RED . "arg1 wurde von arg2 getötet",
		"START_POSITION_ERROR" => "Können Sie Ihre Ausgangsposition nicht gefunden!",
		"GAME_STARTED_GOOD_LUCK" => "Game> Das Spiel hat begonnen! Viel Glück!",
		"STARTING_GAME_COUNTDOWN" => "Beginnend in arg1 Sekunden...",
		"NO_GAME" => "Spiel arg1 existiert nicht!",
		"ON_GAME" => "Sie sind bereits auf arg1!",
		"IN_GAME" => "Sie befinden sich in: arg1",		
		"JUST_COINS" => " Münzen",
		"ACC_NOT_REGISTERED" => TextFormat::GRAY . "Dieses Konto ist nicht registriert.",
		"PLACE_BLOCK_ERROR" => "Sie können Blöcke nicht an diese Stelle.",
		"BREAK_BLOCK_ERROR" => "Sie können Blöcke hier nicht brechen.",
		"YOU_TELEPORTED" =>"Sie wurden zu teleportiert: ",
		"BASE_ERROR" => "Ein Fehler ist aufgetreten.",
		"DISALLOWED_AREA" => TextFormat::RED . TextFormat::BOLD . "Sie waren nicht zugelassen Bereich! Sie wurden wieder in die Lobby.",
		"RETURNED_TO_AREA" => TextFormat::BOLD . "Zurück zu dürfen Bereich!",				
		"SECONDS_BEFORE" => " Sekunden vor)",
		"GAME_FINDING" => "Suche nach einem Spiel...",
		"PLAYER_WAIT_SECONDS" => TextFormat::YELLOW . TextFormat::BOLD . "Sie müssen bis arg1 Sekunden warten"	
	);
	
}
