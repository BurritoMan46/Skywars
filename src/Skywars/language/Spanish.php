<?php


namespace Skywars\language;

use pocketmine\utils\TextFormat;
use LbCore\language\core\Spanish as LbSpanish;

/**
 * Contains specific plugin language strings
 */
class Spanish extends LbSpanish {
	
	/**
	 * Base class constructor - add plugin custom strings to core spanish array
	 */
	public function __construct() {
		$this->translates = array_merge($this->translates, $this->swTranslates);
	}
	/** @var array */
	private  $swTranslates = array(
		"ERROR_PREFIX" => "Error> ",
		"GAME_PREFIX" => "Juego> ",
		"JOIN_PREFIX" => "Unirse> ",
		"QUIT_PREFIX" => "Dejar> ",
		"WARNING_PREFIX" => TextFormat::RED . "Advertencia> ",		
		"WELCOME_TO_LIFEBOAT" => TextFormat::GREEN . "Bienvenido a bote salvavidas arg1!",
		"PURCHASED_ITEM" => TextFormat::GREEN . "Compra> Ha comprado ",
		"ALREADY_PURCHASED" =>  TextFormat::RED . "Ya has comprado este producto!",
		"GADGET_FOR_VIP" => "Este artículo sólo está disponible para los VIPs.",
		"WAITING" => "Por favor espera...",
		"NOT_PURCHASED_ITEM" => TextFormat::RED . "No ha comprado este producto todavía!\n" . TextFormat::GRAY . "Para comprar este artículo, toque de nuevo.",
		"PREP_FOR" => " for ", 
		"CAN_BUY" => "Usted no tiene ningún arg1!\n Usted puede comprar arg2 " . TextFormat::YELLOW . " para arg3 monedas\n" . TextFormat::YELLOW . "pulsando la pantalla de nuevo..",
		"SEND_POTATO" => "Usted potatoed ",
		"GET_POTATO" => "Usted consiguió potatoed por ",		
		"IS_SPECTATOR" => "Usted es un espectador.",
		"GAME_IN_PROGRESS" => "Este juego está en curso.",
		"PLAYER_LIMIT_ERROR" => "Este juego ha alcanzado su recuento máximo jugador.",
		"NO_START_POSITION" => "No se puede encontrar la posición de partida!",
		"PLAYER_WAIT" => TextFormat::YELLOW . "A la espera de los jugadores...",
		"YOU_WON" => "Has ganado el juego!",
		"GAME_RESULTS" => "Resultados:",
		"YOU_DIED" => TextFormat::RED . "Moriste!",
		"TOTAL_RESULT" => "Total: ",
		"PLAYER_WON" => "arg1 ha ganado un partido arg2!",
		"GAME_WAS_STARTED" => "Juego> Juego se inició.",
		"PLAYER_WAS_KILLED" => TextFormat::RED . "arg1 fue asesinado por arg2",
		"START_POSITION_ERROR" => "No puede encontrar su posición de partida!",
		"GAME_STARTED_GOOD_LUCK" => "Juego> El juego ha comenzado! ¡Buena suerte!",
		"STARTING_GAME_COUNTDOWN" => "A partir de arg1 segundos...",
		"NO_GAME" => "arg1 juego no existe!",
		"ON_GAME" => "Usted ya está en arg1!",
		"IN_GAME" => "Ahora se encuentra en: arg1",		
		"JUST_COINS" => " monedas",
		"ACC_NOT_REGISTERED" => TextFormat::GRAY . "Esta cuenta no está registrado.",
		"PLACE_BLOCK_ERROR" => "No se puede colocar bloques aquí.",
		"BREAK_BLOCK_ERROR" => "No se puede romper bloques aquí.",
		"YOU_TELEPORTED" => "Usted fue teletransportado a:: ",
		"BASE_ERROR" => "Se ha producido un error.",
		"DISALLOWED_AREA" => TextFormat::RED . TextFormat::BOLD . "Estabas en el área anulado! Estabas volverá a ejercer presión.",
		"RETURNED_TO_AREA" => TextFormat::BOLD . "Volver a la zona permitida!",				
		"SECONDS_BEFORE" => " segundos antes)",
		"GAME_FINDING" => "Encontrar un juego...",
		"PLAYER_WAIT_SECONDS" => TextFormat::YELLOW . TextFormat::BOLD . "Tienes que esperar arg1 segundos"
	);
	
}
