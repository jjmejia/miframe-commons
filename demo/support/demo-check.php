<?php

/**
 * Proyecto de demostración uso de chequeo de tiempos.
 * Referencias:
 * - https://www.php.net/manual/en/function.debug-backtrace.php
 * - 'REQUEST_TIME_FLOAT'
 *   The timestamp of the start of the request, with microsecond precision.
 *   https://www.php.net/manual/en/reserved.variables.server.php
 * - https://www.tooltester.com/en/blog/website-loading-time-statistics/
 */

require_once __DIR__ . '/../demo-config.php';

$Test->title = 'timecheck()';
$Test->description = 'Demostración uso de chequeo de tiempos usando <code>timecheck()</code>';
$Test->start();


// Starting code...
timecheck('START');

// Code block #1
bloque(100,200);

timecheck('1');

// Code block #2
bloque(200,300);

timecheck('2');

// Code block #3
bloque(3000,4000);

timecheck('3');

// Code block #4
bloque(100,200);

timecheck('4');

// Closing code
bloque(100,150, 'Closing...');

timecheck('END');

// $rustart = getrusage();
// print_r($rustart);

/*
function timecheck(string $text = '') {

	$now = microtime(true);

	// Check if was setting the starting time.
	// Usually, $_SERVER['REQUEST_TIME_FLOAT'] is setting by the web server.
	if (!isset($_SERVER['REQUEST_TIME_FLOAT'])) {
		$_SERVER['REQUEST_TIME_FLOAT'] = $now;
	}

	// Check if was setting value for checking point
	if (!isset($GLOBALS['TIMETRIAL_PARTIAL_TIME'])) {
		$GLOBALS['TIMETRIAL_PARTIAL_TIME'] = $now;
	}

	// Get time in seconds and 2 decimals
	$partial = number_format(($now - $GLOBALS['TIMETRIAL_PARTIAL_TIME']), 3);

	// Update checking point
	$GLOBALS['TIMETRIAL_PARTIAL_TIME'] = $now;

	// Get script source
	$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

	// Use tThe first element in the $trace array to get the script source
	$source = str_replace($_SERVER['DOCUMENT_ROOT'] . '\\', '', $trace[0]['file']);

	// Get time in seconds and 2 decimals
	$time = number_format(($now - $_SERVER['REQUEST_TIME_FLOAT']), 3);

	// Add label (optional)
	if ($text != '') {
		$text = "<div style=\"float:right;padding-left:5px;color:cyan\">{$text}</div>";
	}

	// Show message, add some styles
	echo PHP_EOL . "<div style=\"font-family:Calibri;background:#000;color:#fefefe;padding:5px;margin:5px 0;font-size:14px\">" .
		"<b style=\"color:yellow\">TIME/TRIAL</b> Ellapse time: <b>{$time}</b> / Since last check-point: <b>{$partial}</b>{$text}" .
		"<div style=\"color:#ccc;font-size:12px;padding-top:3px\">{$source}:{$trace[0]['line']}</div>".
		"</div>" . PHP_EOL;

}
*/

function bloque($min, $max, $text = '') {
global $b;

	if (!isset($b)) { $b = 0; }
	$b++;
	$t = rand($min, $max) * 1000;
	$t2 = number_format($t / 1000000, 3);
	if ($text == '') { $text = "Executed code block #$b"; }
	echo "<pre>&lt;-- {$text} -->\n</pre>";
	usleep($t);
}