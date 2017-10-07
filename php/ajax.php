<?php
ini_set('html_errors', 0);
date_default_timezone_set("UTC");

require('PlayingCardDeck.php');
new PlayingCardDeck();

session_start();

/**
 * Init blank vars
 */
$ajax_response = '';

/**
 * Session Vars Init
 */
// user id assigned for new users. Int
$_SESSION['user_id'] = (isset($_SESSION['user_id'])) ? $_SESSION['user_id'] : mt_rand(100000,999999);
// user game stage. refer to switch conditions for each id. Int.
$_SESSION['game_stage'] = (isset($_SESSION['game_stage'])) ? $_SESSION['game_stage'] : 0;
// new user get 1000 complement credits. Int.
if(!isset($_SESSION['user_credits'])) {
	$_SESSION['user_credits'] = 1000 ;
}
// last login bonus
if(!isset($_SESSION['last_login_bonus'])) {
	$_SESSION['last_login_bonus'] = date_timestamp_get(date_create());
}
// user hands are recorded. Json string.
if (!isset($_SESSION['user_hands'])) {
	$_SESSION['user_hands'] = '[]';
}
// user_bet. max 3. Int. user_bet can be set only at the stage 1.
if($_SESSION['game_stage'] === 1) {
	$_SESSION['user_bet'] = (isset($_POST['user_bet'])) ? $_POST['user_bet'] : NULL;
}

/**
 * Commands
 */
$command = (isset($_POST['command'])) ? $_POST['command'] : '';

switch ($command) {
	case 'wait_screen': // 0, user credit / id defined
		$ajax_response = wait_screen();
		break;
	case 'deal': // 1, sends out cards
		$ajax_response = deal();
		break;
	case 'draw': // 2, PLAYER choose cards to be held. Sends out card combinations again, with results.
		$ajax_response = draw();
		break;
	case 'double': // 3, PLAYER can ask for double only if PLAYER wins. Otherwise back to wait screen.
		$ajax_response = double();
		break;
	default:
		break;
}

session_write_close();

/**
 * Return response
 */
header('Content-Type: text/html; charset=utf-8');
echo ($ajax_response);
die;

/**
 * Routines
 */



/**
 * Commands functions
 */

/**
 * Returns user id, current credits
 */
function wait_screen () {
	$return_data = array();
	$return_data['user_id'] = $_SESSION['user_id'];
	/** check up for last login bonus */
	$_unixtime_now = date_timestamp_get(date_create());
	if(
		$_SESSION['last_login_bonus'] + 3600 <= $_unixtime_now &&
		$_SESSION['user_credits'] < 1000
	) {
		$_SESSION['last_login_bonus'] = $_unixtime_now;
		$_SESSION['user_credits'] = 1000;
		$return_data['message'] = 'Login Bonus Awarded!';
	}
	/** return credits */
	$return_data['user_credits'] = $_SESSION['user_credits'];
	$_SESSION['game_stage'] = 1;
	$return_data['game_stage'] = $_SESSION['game_stage'];
	return json_encode($return_data);
}

/**
 * Deduct credits, Sends out cards to the player. If bet is not sent, game over immediately.
 */
function deal () {
	/**
	 * Require
	 */
	/**
	 * Var init
	 */
	$return_data = array();
	/**
	 * Update Session data from POST
	 */
	// user_bet is one of the only 2 user inputs accepted. BET is kept in SESSION till gameover for reference.
	if ($_SESSION['user_credits'] < $_SESSION['user_credits'] - $_SESSION['user_bet']) {
		// Treated as an abnormality if bet is not sent. Game Over right away.
		$return_data = gameover();
		$return_data['message'] = 'Insufficient Credits';
		// Rreturn json array in string.
		return json_encode($return_data);
	} else if (isset($_POST['user_bet'])) {
		$_SESSION['user_bet'] = $_POST['user_bet'];
		// deduct credits
		$_SESSION['user_credits'] = $_SESSION['user_credits'] - ($_SESSION['user_bet'] + 1);
		// shuffle cards
		PlayingCardDeck::shuffle_cards();
		// pick 5 cards and save user_hands in SESSION
		$_user_hands = PlayingCardDeck::pick_five_cards();
		// find matches. matches is returned as keyed array. hand_code as matched hand id in string. matched_cards as array of user_hand array index that need to be indicated as matched.
		$_matches = PlayingCardDeck::evaluate_user_hands($_user_hands);
		// assemble return data
		$return_data['user_hands'] = $_user_hands;
		$return_data['user_credits'] = $_SESSION['user_credits'];
		$return_data['current_game_card_sequence_index'] = $_SESSION['current_game_card_sequence_index'];
		$return_data['hand_code'] = $_matches['hand_code'];
		$return_data['game_stage'] = 2;
		$return_data['matched_cards'] = $_matches['matched_cards'];
		// save data into SESSION
		$_SESSION['game_stage'] = $return_data['game_stage'];
		// Rreturn json array in string.
		return json_encode($return_data);
	} else {
		// Treated as an abnormality if bet is not sent. Game Over right away.
		$return_data = gameover();
		$return_data['message'] = 'Error at Deal';
		// Rreturn json array in string.
		return json_encode($return_data);
	}
}

function draw () {
	// making sure that the client has the same user hands as that in the server.
	$_server_hands = json_decode($_SESSION['user_hands'],TRUE);
	$_user_hands = $_POST['user_hands'];
	$_security_check = PlayingCardDeck::identical_values($_user_hands,$_server_hands);

	if($_security_check === TRUE) {
		$_user_bet = $_SESSION['user_bet'];
		// because empty array is not sent out in POST.
		$_cards_to_draw = (isset($_POST['cards_to_draw'])) ? $_POST['cards_to_draw'] : array();

		// pick 5 cards and save user_hands in SESSION
		$_user_hands = PlayingCardDeck::draw_cards($_cards_to_draw);

		// find matches. matches is returned as keyed array. hand_code as matched hand id in string. matched_cards as array of user_hand array index that need to be indicated as matched.
		$_matches = PlayingCardDeck::evaluate_user_hands($_user_hands);
			// assemble return data
		$return_data['user_hands'] = $_user_hands;
		$return_data['user_credits'] = $_SESSION['user_credits'];
		$return_data['current_game_card_sequence_index'] = $_SESSION['current_game_card_sequence_index'];
		$return_data['hand_code'] = $_matches['hand_code'];
		// one pair gets no rewards
		if($return_data['hand_code'] !== 9 && $return_data['hand_code'] !== 8 ) {
			// Awards credits
			$_award_amount = PlayingCardDeck::get_award_amount($return_data['hand_code']);
			$return_data['award_amount'] = $_award_amount;
			// add to the server side data
			$_SESSION['user_credits'] = $_SESSION['user_credits'] + $_award_amount;
		}
		$return_data['game_stage'] = 1;
		$return_data['matched_cards'] = $_matches['matched_cards'];
			// save data into SESSION
		$_SESSION['game_stage'] = $return_data['game_stage'];
			// Rreturn json array in string.
		return json_encode($return_data);
	} else {
		// Treated as an abnormality if bet is not sent. Game Over right away.
		$return_data = gameover();
		$return_data['message'] = 'Error at DRAW';
		// Rreturn json array in string.
		return json_encode($return_data);
	}
}

function double () {
	$_SESSION['game_stage'] = 3;
}

/**
 * Game over.
 *
 * @return     array  game over declaration.
 */
function gameover () {
	$_SESSION['game_stage'] = 1;
	unset($_SESSION['game_card_sequence']);
	unset($_SESSION['user_hands']);
	return array('game_stage' => 1) ;
}

/**
 * Game logic is in the class PlayingCardDeck
 */
