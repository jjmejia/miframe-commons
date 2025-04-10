<?php

/**
 * Funciones de soporte para depuración de código.
 *
 * Requiere helpers.php
 *
 * @author John Mejia
 * @since Marzo 2025
 */

/**
 * Muestra en pantalla el tiempo transcurrido.
 *
 * Se calcula el tiempo desde el inicio del script y entre cada invocación a esta función.
 * Función documentada en:
 * https://medium.com/@jjmejia_dev/depurando-la-duraci%C3%B3n-de-un-script-en-php-e089f627e382
 *
 * @param string $text (Opcional) Texto para identificar el punto de chequeo.
 * @param int $precision (Opcional) Indica cuantos decimales mostrar.
 */
function timecheck(string $text = '', int $precision = 4)
{
	static $last_check = '';

	$server = miframe_server();
	// Obtiene el script y línea desde donde se invoca.
	$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
	// Usa el primer elemento del arreglo $trace para obtener el origen.
	$source = $server->removeDocumentRoot($trace[0]['file']);
	// Tiempo desde el anterior checkpoint (o desde el inicio)
	$time = $server->executionTime($precision);
	// Obtiene el tiempo total transcurrido.
	$partial = $server->checkPoint($precision);
	// Fecha actual
	$date = date('Y-m-d H:i:s');

	// Complementa info
	$info_text = '';
	if ($time !== $partial) {
		if ($last_check !== '') {
			$last_check = " ({$last_check})";
		}
		$info_text = " / Desde el anterior check{$last_check}: <b>{$partial}</b>";
	}
	// Preserva marca (si alguna)
	$last_check = $text;

	// Adiciona etiquetas a mostrar en pantalla (opcional)
	if ($text != '') {
		$text = "<b style=\"float:right;padding-left:5px;color:#b4ffff\">{$text}</b>";
	}

	// Muestra el mensaje, adiciona algunos estilos.
	echo PHP_EOL .
		"<div style=\"font-family:Calibri;background:#000;color:#fefefe;padding:5px 10px;margin:5px 0;font-size:14px\">" .
		"{$text}<b style=\"color:yellow\">TIME/CHECK</b> Tiempo transcurrido: <b>{$time}</b>{$info_text}" .
		"<div style=\"color:#ccc;font-size:12px;padding-top:3px\"><span style=\"color:yellow\">{$date}</span> {$source}:{$trace[0]['line']}</div>".
		"</div>" . PHP_EOL;
}
