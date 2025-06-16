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

// Cierre de la página
$Test->end();

// Funciones de soporte

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