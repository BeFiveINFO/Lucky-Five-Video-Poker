//creates 4 instances of the Tone.Synth
var polySynth = new Tone.PolySynth(4, Tone.Synth).toMaster();
polySynth.volume.value = -23;
var noiseSynth = new Tone.NoiseSynth({
	noise: {
		type:"white"
	},
	envelope: {
		attack:0.01,
		decay:0.2,
		sustain:0
	}}).toMaster();
noiseSynth.volume.value = -15;
var melody;

//   for i in *.svg; do convert -background none -size 210x304 "$i" "${i%.svg}.png"; done
// vars
var registers = {
	'user_credits': 0,
	'user_id': 0,
	'game_stage': 0,
	'user_hands': [],
	'current_game_card_index': 0,
	'user_bet': 0,
}

// Parameter
var bet_rates_multiplier = [1,2,3,9,10];

// Melodies
var melodyBanks = {
	'good_news': {
		'bpm': 128,
		'melody':[
			['0:0:0', ['8n',['C4', 'E4', 'F4','A4']]],
			['0:0:1', ['16n',['D6', 'F5', 'A5', 'C5']]],
			['0:0:2', ['16n',['D6', 'F5', 'A5', 'C5']]],
		]
	},
	'bet': {
		'bpm': 128,
		'melody':[
			['0:0:0', ['8n',['C4','E4']]],
			['0:0:1', ['16n',['C5']]],
		]
	},
	'hold': {
		'bpm': 400,
		'melody':[
			['0:0:0', ['16n',['C4','E4']]],
			['0:0:1', ['16n',['C6','E5']]],
			['0:0:2', ['16n',['C7']]],
		]
	},
	'unhold': {
		'bpm': 400,
		'melody':[
			['0:0:0', ['8n',['C6']]],
			['0:0:1', ['16n',['C6','E5']]],
			['0:0:2', ['16n',['C5','E4']]],
		]
	},
	'lets_start': {
		'bpm': 128,
		'melody':[
			['0:0:0', ['16n',['C4', 'E4', 'F4','A4']]],
			['0:0:1', ['32n',['C4', 'A3']]],
		]
	},
	'you_win': {
		'bpm': 128,
		'melody':[
			['0:0:0', ['16n',['G4','E5']]],
			['0:0:4', ['16n',['G4','E5']]],
			['0:0:2', ['16n',['C4','C5']]],
			['0:1:1', ['8n',['C4','E5','B5']]],
			['0:1:2', ['8n',['G4','E5','C6']]],
			['0:2:0', ['8n',['G4','B4','G5']]],
			['0:2:8', ['8n',['E4','B4','E6']]],
			['0:2:2', ['4n',['C4','G6','C5']]],
		]
	}
};

/**
 * Input
 */
$('#bet').on('mouseup', function(e){
	var $_currentTarget = $(e.currentTarget);
	if($_currentTarget.hasClass('inactive')) {
		return false;
	}
	process_bet_rate_change(true);
});



document.querySelector('#start').addEventListener('mouseup', function(e){
	var $_currentTarget = $(e.currentTarget);
	if($_currentTarget.hasClass('inactive')) {
		return false;
	}
	switch(registers.game_stage) {
		case 1:
			deal();
			break;
		case 2:
			draw();
			break;
	}
});

/** hold button click event */
$('.hold-button').on('mouseup', function(e){
	var $_currentTarget = $(e.currentTarget);
	if($_currentTarget.hasClass('inactive')) {
		return false;
	}
	var _id = $_currentTarget.attr('id').replace('hold-','');
	toggleCardToHold(_id);
});

/** clicking on card also works as hold button */
$('.playing-card').on('mouseup', function(e){
	var $_currentTarget = $(e.currentTarget);
	var _id = $_currentTarget.attr('id').replace('playing-card-','');
	var $_targetBetButton = $('#hold-'+_id);
	if($_targetBetButton.hasClass('inactive')) {
		return false;
	}
	toggleCardToHold(_id);
});

/**
 * Begin
 */
process_bet_rate_change();
wait_screen();

/**
 * Routines
 */

function wait_screen () {
	changeStartButtonState(0);
	showCardBack();
	updateWinDisplay();
	changeHoldButtonState(false);
	/** update the message */
	updateMessage('LUCKY FIVE',true);
	/* send request */
	$.ajax({
		url: 'php/ajax.php',
		data: { command: 'wait_screen' },
		type: 'POST',
		dataType: 'json',
		success: function (data) {
			// console.log(data);
			registers.user_credits = data.user_credits;
			registers.user_id = data.user_id;
			registers.game_stage = data.game_stage;
			$('#console').val('Credits: ' + registers.user_credits + '\nUser ID: ' + registers.user_id + '\nGame Stage: ' + registers.game_stage);
			updateCreditDisplay();
			changeStartButtonState(1);
			if(data.message) {
				updateMessage(data.message);
			}
		},
		error: function () {
			console.log();
			alert('Error');
		}
	});
}

function deal () {
	changeBetButtonState(false);
	changeStartButtonState(0);
	updateWinDisplay();
	showCardBack();
	mark_matched_cards();
	process_bet_rate_change();
	changeHoldButtonState(false);
	play_a_melody('lets_start');
	updateMessage('GOOD LUCK!',true);
	$.ajax({
		url: 'php/ajax.php',
		data: { command: 'deal', user_bet: registers.user_bet },
		type: 'POST',
		dataType: 'json',
		success: function (data) {
			registers.user_credits = data.user_credits;
			registers.user_hands = data.user_hands;
			registers.game_stage = data.game_stage;
			registers.current_game_card_index = data.current_game_card_index;
			$('#console').val('Credits: ' + registers.user_credits + '\nUser Hands: ' + registers.user_hands + '\nGame Stage: ' + registers.game_stage + '\nCards Index: ' + registers.current_game_card_index);

			updateHands(registers.user_hands);
			setTimeout(
				function()
				{
					mark_matched_cards(data.matched_cards);
					process_winning_hands(data.hand_code,true);
					updateCreditDisplay();
					if(data.hand_code == 9) {
						changeBetButtonState(true);
					}
					if(data.message) {
						updateMessage(data.message);
					}
				}, 500);
		},
		error: function () {
			console.log();
			// alert('Error');
		}
	});
}

function draw () {
	var _cards_to_draw = findCardsToDraw();
	changeStartButtonState(0);
	mark_matched_cards();
	process_bet_rate_change();
	changeHoldButtonState(false);
	play_a_melody('lets_start');
	updateMessage('GOOD LUCK!',true);
	$.ajax({
		url: 'php/ajax.php',
		data: { 'command': 'draw', 'cards_to_draw': _cards_to_draw, 'user_hands': registers.user_hands },
		type: 'POST',
		dataType: 'json',
		success: function (data) {
			registers.user_credits = data.user_credits;
			registers.user_hands = data.user_hands;
			registers.game_stage = data.game_stage;
			registers.current_game_card_index = data.current_game_card_index;
			$('#console').val('Credits: ' + registers.user_credits + '\nUser Hands: ' + registers.user_hands + '\nGame Stage: ' + registers.game_stage + '\nCards Index: ' + registers.current_game_card_index);
			var _handCode = (data.hand_code == 8) ? 9 : data.hand_code;
			var _matched_cards = (_handCode == 9) ? [] : data.matched_cards;
			// post process
			updateHands(registers.user_hands,_cards_to_draw);
			setTimeout(
				function()
				{
					if(data.award_amount) {
						updateWinDisplay(data.award_amount);
						registers.user_credits += data.award_amount;
					}
					mark_matched_cards(_matched_cards);
					process_winning_hands(_handCode);
					updateCreditDisplay();
					changeBetButtonState(true);
					if(data.message) {
						updateMessage(data.message);
					}
				}, 550);
		},
		error: function () {
			console.log();
			// alert('Error');
		}
	});
}

function findCardsToDraw () {
	var _cards_to_draw = [];
	var $_betButton = $('.hold-button');
	// scan through 5 cards
	$('.hold-button').each(function(index, value) {
		var $_targetHoldButton = $(value);
		var _hasHeld = $_targetHoldButton.hasClass('held');
		if(_hasHeld === false) {
			var _targetID = $_targetHoldButton.attr('id');
			_targetID = _targetID.replace('hold-','');
			_cards_to_draw.push(Number(_targetID) - 1);
		}
	});
	return _cards_to_draw;
}

/**
 * Card display
 */

function showCardBack (cardIndex) {
	if(!cardIndex) {
		for (var _i = 1; _i < 6; _i++ ){
			$('#hand-' + _i).attr('src','cards/cardBack.png');
		}
	} else {
		$('#hand-' + cardIndex).attr('src','cards/cardBack.png');
	}
}

function updateHands (hands,cards_to_draw) {
	if(!cards_to_draw) {
		$('.card-face').hide();
		for (var _i = 1; _i < 6; _i++ ){
			$('#hand-' + _i).attr('src','cards/'+hands[_i-1]+'.png');
			$('#hand-' + _i).hide();
			$('#hand-' + _i).delay(90 * _i).queue(function(next) {
				noiseSynth.triggerAttackRelease("8n");
				next();
			}).slideDown(30);
		}
	} else {
		for (var _key in cards_to_draw ){
			var _index = cards_to_draw[_key] + 1;
			$('#hand-' + _index).attr('src','cards/'+hands[_index-1]+'.png');
			$('#hand-' + _index).hide();
			$('#hand-' + _index).delay(90 * _index).queue(function(next) {
				noiseSynth.triggerAttackRelease("8n");
				next();
			}).slideDown(30);
		}
	}
}

/**
 * Process winning hands
 *
 * @param      {number}  handCode  The hand code
 *
 * Hand Code Chart
 * Rank / ID : Name			Examples
 * 0 : Five of a kind		A A A A JJ
 * 1 : Straight flush		Q J 10 9 8
 * 2 : Four of a kind		5 5 5 5 2
 * 3 : Full house			6 6 6 K K
 * 4 : Flush				Five cards of the same suit
 * 5 : Straight				7 6 5 4 3
 * 6 : Three of a kind		2 2 2 K 6
 * 7 : Two pair				J J 4 4 9
 * 8 : One pair				4 4 K 10 5 << skipped
 * 9 : High card			No Pairs
 * 10 : Royal Straight
 *
 * PAY TABLE
 * Five of a kind		250
 * Royal Straight		100
 * Straight flush		40
 * Four of a kind		20
 * Full house		8
 * Flush			5
 * Straight		4
 * Three of a kind		2
 * Two pair		1
 */

function process_winning_hands (handCode,isDraw) {
	if(handCode == 9 && !isDraw) {
		updateMessage('GAME OVER',true);
		changeHoldButtonState(false);
		changeStartButtonState(1);
		return false;
	}
	/** init vars */
	var _bet_to_col_conversion_table = [2,3,4,5,6];
	var _handCode_className = '.hand-code-' + handCode;
	var _paytableCell_className = '.col-' + _bet_to_col_conversion_table[registers.user_bet];
	var $_paytable = $('div#paytable table');
	/** target tr */
	$_targetTr = $_paytable.find(_handCode_className);
	/** reset display */
	$_paytable.find('td').removeClass('win');
	/** mark */
	$_targetTr.find('.hand-name').addClass('win');
	/** payout table */
	$_targetTr.find(_paytableCell_className).addClass('win');

	/** DEAL / DRAW Conditional */
	if(isDraw) {
		/** enable hold button */
		changeHoldButtonState(true);
		/** update the message */
		updateMessage('HOLD AND DRAW');
		/** change the start button to DRAW */
		changeStartButtonState(2);
		// play sound
		play_a_melody('good_news');
	} else {
		// probably DRAW result received
		/** disable hold button */
		changeHoldButtonState(false);
		/** update the message */
		updateMessage('WINNER!',true);
		/** change the start button back to DEAL */
		changeStartButtonState(1);
		/** play sound */
		play_a_melody('you_win');
	}
}

 /**
  * Change bet
  *
  * BET Multiplier : 1, 2, 3, 9 , 10 stored in bet_rates array
  */
function process_bet_rate_change(changeBet) {
	var $_paytable = $('div#paytable table');
	$_paytable.find('td').removeClass('current');
	$_paytable.find('td').removeClass('win');
	/** change bet */
	if(changeBet) {
		registers.user_bet = (registers.user_bet < 4) ? registers.user_bet + 1 : 0;
		play_a_melody('bet');
	}
	/** handle according to the current bet */
	switch(registers.user_bet) {
		case 4:
			$_paytable.find('.col-6').addClass('current');
			break;
		case 3:
			$_paytable.find('.col-5').addClass('current');
			break;
		case 2:
			$_paytable.find('.col-4').addClass('current');
			break;
		case 1:
			$_paytable.find('.col-3').addClass('current');
			break;
		case 0:
		default:
			$_paytable.find('.col-2').addClass('current');
			break;
	}
	updateWagerDisplay();
}



/**
 * Put red border line around matched cards.
 *
 * @param      {array}  matchedCards  The index for matched cards (range 0 ~ 4)
 */
function mark_matched_cards (matchedCards) {
	// console.log(matchedCards);
	$('.playing-card').removeClass('hit');
	if(matchedCards){
		for(var _key in matchedCards) {
			var _cardIndexOnScreen = Number(matchedCards[_key])+1;
			// console.log(_cardIndexOnScreen);
			var $_targetCard = $('#hand-'+_cardIndexOnScreen);
			// console.log($_targetCard);
			$_targetCard.parent().addClass('hit');
		}
	}
}

/**
 * Display Control
 */
function updateCreditDisplay () {
	$('span#credit_counter').text(registers.user_credits);
}

function updateWagerDisplay () {
	$('span#wager_counter').text(bet_rates_multiplier[registers.user_bet]);
}

function updateWinDisplay (winAmount) {
	if(!winAmount) winAmount = 0;
	$('span#win_counter').text(winAmount);
}

function updateMessage(messageString,isGoodNews){
	var $_gameMessage = $('#game-message');
	$_gameMessage.html(messageString);
	if(isGoodNews) {
		$_gameMessage.addClass('good-news');
	} else {
		$_gameMessage.removeClass('good-news');
	}

}

/**
 * deal / draw button
 * @param      {integer} state 0: disabled, 1: DEAL, 2: DRAW
 */
function changeStartButtonState (state) {
	var $_startButton = $('button#start');
	switch(state) {
		case 2:
			$_startButton.removeClass('inactive');
			$_startButton.text('DRAW');
			$_startButton.addClass('held');
			break;
		case 1:
			$_startButton.removeClass('inactive');
			$_startButton.text('DEAL');
			break;
		case 0:
		default:
			$_startButton.addClass('inactive');
			$_startButton.text('WAIT');
			break;
	}
}

/** bet button */
function changeBetButtonState (enable) {
	var $_betButton = $('#bet');
	if(enable) {
		$_betButton.removeClass('inactive');
	} else {
		$_betButton.addClass('inactive');
	}
}

/** hold button control */
function changeHoldButtonState (enable) {
	var $_holdButton = $('.hold-button');
	$_holdButton.removeClass('held');
	if(enable) {
		$_holdButton.removeClass('inactive');
		$_holdButton.text('HELD');
		$_holdButton.addClass('held');
	} else {
		$_holdButton.removeClass('held');
		$_holdButton.addClass('inactive');
		$_holdButton.text('HOLD');
	}
}

function toggleCardToHold(holdButtonID) {
	var $_targetHoldButton = $('#hold-'+holdButtonID);
	if($_targetHoldButton.length){
		var $_targetCard = $('#hand-'+holdButtonID);
		if($_targetHoldButton.hasClass('held')) {
			$_targetHoldButton.removeClass('held');
			$_targetHoldButton.text('HOLD');
			$_targetCard.slideUp(50);
			play_a_melody('unhold');
		} else {
			$_targetHoldButton.addClass('held');
			$_targetHoldButton.text('HELD');
			$_targetCard.slideDown(50);
			play_a_melody('hold');
		}
	}
}

/**
 * Music
 */
function play_a_melody (melodyID) {
	var _melodyScore = melodyBanks[melodyID];
	if(melody) melody.stop();
	melody = new Tone.Part(function(time, note) {polySynth.triggerAttackRelease(note[1], note[0], time);}, _melodyScore.melody).start();
	melody.loop = 1;
	Tone.Transport.bpm.value = _melodyScore.bpm;
	Tone.Transport.start();
}
