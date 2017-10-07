<?php
class PlayingCardDeck {
	private static $decks = array (
		0 => 'AD',
		1 => '2D',
		2 => '3D',
		3 => '4D',
		4 => '5D',
		5 => '6D',
		6 => '7D',
		7 => '8D',
		8 => '9D',
		9 => 'TD',
		10 => 'JD',
		11 => 'QD',
		12 => 'KD',
		13 => 'AH',
		14 => '2H',
		15 => '3H',
		16 => '4H',
		17 => '5H',
		18 => '6H',
		19 => '7H',
		20 => '8H',
		21 => '9H',
		22 => 'TH',
		23 => 'JH',
		24 => 'QH',
		25 => 'KH',
		26 => 'AC',
		27 => '2C',
		28 => '3C',
		29 => '4C',
		30 => '5C',
		31 => '6C',
		32 => '7C',
		33 => '8C',
		34 => '9C',
		35 => 'TC',
		36 => 'JC',
		37 => 'QC',
		38 => 'KC',
		39 => 'AS',
		40 => '2S',
		41 => '3S',
		42 => '4S',
		43 => '5S',
		44 => '6S',
		45 => '7S',
		46 => '8S',
		47 => '9S',
		48 => 'TS',
		49 => 'JS',
		50 => 'QS',
		51 => 'KS',
		52 => 'JJ',
		);

	private static $card_ranks_translation_table = array(
		'A' => 1,
		'2' => 2,
		'3' => 3,
		'4' => 4,
		'5' => 5,
		'6' => 6,
		'7' => 7,
		'8' => 8,
		'9' => 9,
		'T' => 10,
		'J' => 11,
		'Q' => 12,
		'K' => 13,
		);

	/**
	 * Parameters for payout
	 */
	private static $bet_rates_multiplier = array(1,2,3,9,10);
	private static $hand_primary_rates = array(
		'0' => 250,
		'1' => 40,
		'2' => 20,
		'3' => 8,
		'4' => 5,
		'5' => 4,
		'6' => 2,
		'7' => 1,
		'10' => 100
		);

	/**
	 * Runtime registers
	 */
	private static $card_num = 5;
	private static $ranks_stat = array('A'=>0,'2'=>0,'3'=>0,'4'=>0,'5'=>0,'6'=>0,'7'=>0,'8'=>0,'9'=>0,'T'=>0,'J'=>0,'Q'=>0,'K'=>0);
	private static $suits_stat = array('D'=>0,'H'=>0,'C'=>0,'S'=>0,'J'=>0);
	private static $ranks = array();
	private static $suits = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		// var_export(self::$decks);
	}

	/**
	 * Routines
	 */

	/**
	 * Calculates credits to be awarded to user.
	 *
	 * @return     int Amount to be awarded
	 *
	 * Hand Code
	 * Rank / ID : Name			Examples
	 * 0 : Five of a kind		A A A A JJ
	 * 1 : Straight flush		Q J 10 9 8
	 * 2 : Four of a kind		5 5 5 5 2
	 * 3 : Full house			6 6 6 K K
	 * 4 : Flush				Five cards of the same suit
	 * 5 : Straight				7 6 5 4 3
	 * 6 : Three of a kind		2 2 2 K 6
	 * 7 : Two pair				J J 4 4 9
	 * 8 : One pair				4 4 K 10 5
	 * 9 : High card			No Pairs
	 * 10 : Royal Straight
	 *
	 *
	 */
	public static function get_award_amount ($hand_code) {
		$_user_bet = $_SESSION['user_bet'];
		$_multiplier = self::$bet_rates_multiplier[$_user_bet];
		$_award_amount = self::$hand_primary_rates[$hand_code] * $_multiplier;
		return $_award_amount;
	}

	/**
	 * Shuffle cards and set data in _SESSION
	 */
	public static function shuffle_cards () {
		// shuffle
		$_random_sequential_numbers = range(0,52);
		shuffle($_random_sequential_numbers);
		$_random_sequential_numbers = json_encode($_random_sequential_numbers);
		$_SESSION['game_card_sequence'] = $_random_sequential_numbers;
		// set the card index to zero
		$_SESSION['current_game_card_sequence_index'] = 0;
	}

	/**
	 * Pick five cards into array and return the array and saves the result into SESSION
	 *
	 * @return     array  five card id in array
	 */
	public static function pick_five_cards () {
		$_user_hands = array();
		for($_i = 0; $_i < 5; $_i ++) {
			array_push($_user_hands,self::pick_one_card());
		}
		$_SESSION['user_hands'] = json_encode($_user_hands);
		return $_user_hands;
	}

	/**
	 * Draws cards. Current user hands saved to session automatically.
	 *
	 * @param      <array>  $cards_to_draw  The card index to draw. Range 0 ~ 4. max 5 counts.
	 *
	 * @return     <array>  five card id in array
	 */
	public static function draw_cards ($cards_to_draw = array()) {
		$_user_hands = json_decode($_SESSION['user_hands'],TRUE);
		foreach ($cards_to_draw as $_key => $_value) {
			$_user_hands[$_value] = self::pick_one_card();
		}
		$_SESSION['user_hands'] = $_user_hands;
		return $_user_hands;
	}

	/**
	 * Pick a card and advance the current_game_card_sequence_index.
	 *
	 * @return     boolean|string  Returns card code in string. FALSE if the index number reached out over 52.
	 */
	private static function pick_one_card () {
		$_game_card_sequence = json_decode($_SESSION['game_card_sequence'],TRUE);
		$_current_game_card_sequence_index = $_SESSION['current_game_card_sequence_index'];
		if($_current_game_card_sequence_index > 52) {
			return FALSE;
		} else {
			$_current_game_card_id = $_game_card_sequence[$_current_game_card_sequence_index];
			$_SESSION['current_game_card_sequence_index'] = $_current_game_card_sequence_index + 1;
			return self::$decks[$_current_game_card_id];
		}
	}

	/**
	 * Find matches.
	 *
	 * @return    hand_code => integer hand id, matched_cards array matched cards to indicate in the client screen
	 *
	 * Hand Code
	 * Rank / ID : Name			Examples
	 * 0 : Five of a kind		A A A A JJ
	 * 1 : Straight flush		Q J 10 9 8
	 * 2 : Four of a kind		5 5 5 5 2
	 * 3 : Full house			6 6 6 K K
	 * 4 : Flush				Five cards of the same suit
	 * 5 : Straight				7 6 5 4 3
	 * 6 : Three of a kind		2 2 2 K 6
	 * 7 : Two pair				J J 4 4 9
	 * 8 : One pair				4 4 K 10 5
	 * 9 : High card			No Pairs
	 * 10 : Royal Straight
	 */
	public static function evaluate_user_hands ($user_hands) {
		// $user_hands = array('JD','QD','KD','AD','TD'); // royal straight flush
		// $user_hands = array('JJ','JJ','4D','5D','6D'); // straight
		// $user_hands = array('JJ','JJ','JJ','JJ','JJ'); // five of a kind
		// $user_hands = array('JJ','JJ','JJ','JJ','JJ'); //
		// $user_hands = array('7C','8D','4D','5D','6D'); // straight
		// $user_hands = array("8D", "JJ", "JD", "4D", "TD"); // Flush with joker
		// $user_hands = array('7D','8D','4D','5D','6D'); // straight flush
		// $user_hands = array('7D','JD','7D','7D','7C'); // 2 : Four of a kind
		// $user_hands = array('7D','JD','7D','4D','7C'); // 6 : 3 of a kind
		// $user_hands = array('7D','JD','7D','JD','KC'); // 7 two pair
		// $user_hands = array('7D','3D','7D','JD','KC'); // 7 two pair
		// $user_hands = array('JJ','3D','8D','QD','KC'); // 8 One pair with joker
		// $user_hands = array('7D','7C','7H','JD','JC'); // Full House
		// $user_hands = array('JJ','7H','7D','JD','JC'); // Full House with 1 joker
		// $user_hands = array('JJ','7H','7D','JJ','JC'); // Full House with joker
		// init var
		$_matches = array('hand_code' => 9, 'matched_cards' => array());
		// preprocessing
		self::$card_num = count($user_hands);
		self::set_ranks_stat($user_hands);
		self::set_suits_stat($user_hands);
		self::set_ranks($user_hands);
		self::set_suits($user_hands);
		// var_dump($user_hands,self::$ranks,self::$suits);
		// detection flags
		// FALSE - 0, TRUE - 1, Royal Straight - 2
		$_is_five_of_a_kind = self::is_any_of_a_kind(5);
		$_is_straight = self::is_straight();
		$_is_flush = self::is_flush();
		// Pairs evaluation
		$_is_full_house = self::is_full_house(4);
		$_is_four_of_a_kind = self::is_any_of_a_kind(4);
		$_is_three_of_a_kind = self::is_any_of_a_kind(3);
		$_is_two_pair = self::is_two_pair($user_hands);
		$_is_one_pair = self::is_one_pair($user_hands);

		// translation table is in self::$card_ranks_translation_table
		if($_is_five_of_a_kind !== FALSE ) {
			// Five of a kind
			$_matches['hand_code'] = 0;
			$_matches['matched_cards'] = range(0,self::$card_num-1);
		} else if ($_is_straight === 2 && $_is_flush === TRUE) {
			// Royal Straight (Flush)
			$_matches['hand_code'] = 10;
			$_matches['matched_cards'] = range(0,self::$card_num-1);
		} else if ($_is_straight === 1 && $_is_flush === TRUE) {
			// Straight flush
			$_matches['hand_code'] = 1;
			$_matches['matched_cards'] = range(0,self::$card_num-1);
		} else if ($_is_flush === TRUE) {
			// Royal Straight
			$_matches['hand_code'] = 4;
			$_matches['matched_cards'] = range(0,self::$card_num-1);
		} else if ($_is_straight === 1) {
			// Straight
			$_matches['hand_code'] = 5;
			$_matches['matched_cards'] = range(0,self::$card_num-1);
			// that is for 5 cards hands
			// pair evaluation results continue.
		} else if ($_is_full_house !== FALSE) {
			$_matches['hand_code'] = 3;
			$_matches['matched_cards'] = self::find_macthes($_is_full_house);
		} else if ($_is_four_of_a_kind !== FALSE) {
			// 2 Four of a kind
			$_matches['hand_code'] = 2;
			$_matches['matched_cards'] = self::find_macthes($_is_four_of_a_kind);
		} else if ($_is_three_of_a_kind !== FALSE) {
			// 3
			$_matches['hand_code'] = 6;
			$_matches['matched_cards'] = self::find_macthes($_is_three_of_a_kind);
		} else if ($_is_two_pair !== FALSE) {
			// 7 Two pair
			$_matches['hand_code'] = 7;
			$_matches['matched_cards'] = self::find_macthes($_is_two_pair);
		} else if ($_is_one_pair !== FALSE) {
			// 8 One Pair
			$_matches['hand_code'] = 8;
			$_matches['matched_cards'] = self::find_macthes($_is_one_pair);
		} else {
			// High Card
			$_matches['hand_code'] = 9;
		}
		return $_matches;
	}

	/**
	 * Data preprocessing
	 */

	private static function set_ranks_stat ($user_hands) {
		foreach($user_hands as $_value) {
			if($_value !== 'JJ') {
				$_rank = substr($_value,0,1);
				self::$ranks_stat[$_rank] = self::$ranks_stat[$_rank] + 1;
			}
		}
		return self::$ranks_stat;
	}

	private static function set_ranks ($user_hands) {
		foreach($user_hands as $_value) {
			if($_value !== 'JJ') {
				$_rank = substr($_value,0,1);
				array_push(self::$ranks,$_rank);
			} else {
				array_push(self::$ranks,'JJ');
			}
		}
		return self::$ranks;
	}

	private static function set_suits_stat ($user_hands) {
		// init var
		self::$suits_stat = array('D'=>0,'H'=>0,'C'=>0,'S'=>0,'J'=>0);
		foreach($user_hands as $_value) {
			$_suit = substr($_value,1,1);
			self::$suits_stat[$_suit] = self::$suits_stat[$_suit] + 1;
		}
		return self::$suits_stat;
	}

	private static function set_suits ($user_hands) {
		foreach($user_hands as $_value) {
			$_suit = substr($_value,1,1);
			array_push(self::$suits,$_suit);
		}
		return self::$suits;
	}

	/**
	 * Evaluation
	 */

	private static function is_straight () {
		// flags
		$_is_royal = FALSE;
		$_is_straight = FALSE;
		// table. Begin with 0 and ends with 9. 13 loops.
		$_scan_sequence_table = array('T','J','Q','K','A','2','3','4','5','6','7','8','9','T','J','Q','K');
		$_joker_count = self::$suits_stat['J'];
		// scan
		if($_joker_count < 1) {
			// without any joker
			for ($_i = 0; $_i < 13; $_i ++) {
				// var_dump(array($_scan_sequence_table[$_i],$_scan_sequence_table[$_i + 1],$_scan_sequence_table[$_i + 2],$_scan_sequence_table[$_i + 3],$_scan_sequence_table[$_i + 4]));
				$_hands_eval = self::identical_values(self::$ranks,array($_scan_sequence_table[$_i],$_scan_sequence_table[$_i + 1],$_scan_sequence_table[$_i + 2],$_scan_sequence_table[$_i + 3],$_scan_sequence_table[$_i + 4]));
				// var_dump($_hands_eval);
				if(
					$_hands_eval === TRUE
				) {
					$_is_straight = TRUE;
					if($_i === 0) {
						$_is_royal = TRUE;
					}
					break;
				}
			}
		} else {
			// var_dump(implode(self::$ranks,' - '));
			$_regular_card_num = self::$card_num - $_joker_count;
			// with joker
			for ($_i = 0; $_i < 13; $_i ++) {
				$_hands_eval = array_intersect(array($_scan_sequence_table[$_i],$_scan_sequence_table[$_i + 1],$_scan_sequence_table[$_i + 2],$_scan_sequence_table[$_i + 3],$_scan_sequence_table[$_i + 4]),self::$ranks);
				// print($_scan_sequence_table[$_i].','.$_scan_sequence_table[$_i + 1].','.$_scan_sequence_table[$_i + 2].','.$_scan_sequence_table[$_i + 3].','.$_scan_sequence_table[$_i + 4]." - ");
				// print_r(implode($_hands_eval,'-')."\n");
				// print(count($_hands_eval). ' / ' . $_regular_card_num."\n");
				if(count($_hands_eval) == $_regular_card_num) {
					$_is_straight = TRUE;
					if($_i === 0) {
						$_is_royal = TRUE;
					}
					break;
				}
			}
		}
		if($_is_straight === FALSE ) {
			return 0;
		} else if ($_is_royal === TRUE) {
			return 2;
		} else {
			return 1;
		}
	}

	private static function is_flush () {
		$_is_flush = FALSE;
		$_joker_count = self::$suits_stat['J'];
		if($_joker_count < 1) {
			foreach (self::$suits_stat as $_value) {
				if($_value === self::$card_num) {
					$_is_flush = TRUE;
					break;
				}
			}
		} else {
			$_necessary_suits_num = self::$card_num - $_joker_count;
			foreach (self::$suits_stat as $_value) {
				if($_value === $_necessary_suits_num) {
					$_is_flush = TRUE;
					break;
				}
			}
		}
		return $_is_flush;
	}

	private static function is_full_house () {
		$_is_full_house = FALSE;
		$_pair = array();
		$_tripple = array();
		$_joker_count = self::$suits_stat['J'];
		$_max_value = max(self::$ranks_stat);
		$_min_value = min(array_filter(self::$ranks_stat)); // exclude 0
		$_max_key = array_keys(self::$ranks_stat, $_max_value);
		$_min_key = array_keys(self::$ranks_stat, $_min_value);
		if($_joker_count < 1 && $_max_key === 3 && $_min_key === 2) {
			$_is_full_house = array_merge($_max_key,$_min_key);
		} else if($_joker_count === 1 && $_max_value === 2 && $_min_value === 2) {
			$_is_full_house = array_merge($_max_key,array('JJ'));
		} else if($_joker_count === 2 && $_max_value === 2 && $_min_value === 1) {
			$_is_full_house = array_merge($_max_key,$_min_key,array('JJ'));
		} else if($_joker_count === 2 && $_max_value === 1 && $_min_value === 2) {
			$_is_full_house = array_merge($_max_key,$_min_key,array('JJ'));
		}

		return $_is_full_house;
	}

	private static function is_any_of_a_kind ($card_num) {
		$_is_any_of_a_kind = FALSE;
		$_joker_count = self::$suits_stat['J'];
		if($_joker_count < 1) {
			foreach (self::$ranks_stat as $_key => $_value) {
				if($_value === $card_num) {
					$_is_any_of_a_kind = $_key;
					break;
				}
			}
		} else {
			// with joker
			$_joker_threshold = $card_num - $_joker_count;
			foreach (self::$ranks_stat as $_key => $_value) {
				if($_value >= $_joker_threshold) {
					$_is_any_of_a_kind = array($_key,'JJ');
					break;
				}
			}
		}
		return $_is_any_of_a_kind;
	}

	private static function is_two_pair () {
		$_is_two_pair = FALSE;
		$_pair = array();
		$_single = array();
		$_joker_count = self::$suits_stat['J'];
		if($_joker_count < 1) {
			foreach (self::$ranks_stat as $_key => $_value) {
				if($_value == 2) {
					array_push($_pair,$_key);
				}
				if($_value == 1) {
					array_push($_single,$_key);
				}
			}
			if(count($_pair) === 2 && count($_single) === 1) {
				$_is_two_pair = $_pair;
			}
		}
		return $_is_two_pair;
	}

	private static function is_one_pair () {
		$_is_one_pair = FALSE;
		$_pair = array();
		$_joker_count = self::$suits_stat['J'];
		if($_joker_count < 1) {
			foreach (self::$ranks_stat as $_key => $_value) {
				if($_value == 2) {
					array_push($_pair,$_key);
					break;
				}
			}
			if(count($_pair) === 1) {
				$_is_one_pair = $_pair;
			}
		} else {
			foreach (self::$ranks_stat as $_key => $_value) {
				if($_value == 1) {
					array_push($_pair,$_key);
					break;
				}
			}
			if(count($_pair) === 1) {
				$_is_one_pair = $_pair;
				array_push($_is_one_pair,'JJ');
			}
		}
		return $_is_one_pair;
	}


	/**
	 * Utilities
	 */

	/**
	 * Compare two series of hands.
	 *
	 * @param      <array>   $arrayA  The array a
	 * @param      <array>   $arrayB  The array b
	 *
	 * @return     boolean  TRUE is identical otherwise FALSE
	 */
	public static function identical_values( $arrayA , $arrayB ) {
		sort( $arrayA );
		sort( $arrayB );
		return $arrayA == $arrayB;
	}

	private static function find_macthes ( $matches ) {
		if(is_array($matches)) {
			$_matched_cards = array();
			foreach ($matches as $key => $value) {
				$_matched_cards = array_merge($_matched_cards, self::find_index_of_value_in_hand($value));
			}
			return $_matched_cards;
		} else {
			return self::find_index_of_value_in_hand($matches);
		}
	}

	private static function find_index_of_value_in_hand ( $needle ) {
		// var_dump($needle);
		// var_dump(self::$ranks);
		$_found_index = array();
		foreach (self::$ranks as $_key => $_value) {
			// print($_key.' / '.$_value."\n");
			if($needle == $_value) {
				array_push($_found_index,$_key);
			}
		}
		return $_found_index;
	}
}
