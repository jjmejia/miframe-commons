<?php

/**
 * Funciones de soporte para depuración de código.
 *
 * Requiere helpers.php (funciones miframe_xxx)
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


/**
 * Realiza volcado de datos en pantalla.
 *
 * Requiere que se encuentre activo tanto el "modo Debug" (miframe_render()->debug = true)
 * como el "modo Desarrollo" (miframe_render()->developerMode = true) o de lo contrario
 * retornará una cadena vacia.
 *
 * @param mixed $var Variable a mostrar contenido.
 * @param string $title Título a usar al mostrar contenido.
 * @param bool $escape_dump TRUE para mostrar información legible (para humanos) sobre
 * 							el contenido de $var. FALSE muestra el contenido tal
 * 							cual sin modificar su formato.
 */
function miframe_dump(mixed $var, string $title = '', bool $escape_dump = true, bool $ignore_empty = false)
{
	// echo miframe_render()->dump($var, $title, $escape_dump, $ignore_empty);
	echo miframe_export_dump($var, $title, $escape_dump, $ignore_empty);
}

/**
 * Realiza volcado de datos en pantalla.
 *
 * La información a mostrar se enmarca usando la vista "show-dump". Se usa un
 * modelo predefinido para esta vista, aunque puede ser personalizada creando una
 * vista con el mismo nombre en el directorio que contiene las vistas de usuario.
 *
 * Requiere que se encuentre activo tanto el "modo Debug" ($this->debug = true)
 * como el "modo Desarrollo" ($this->developerMode = true) o de lo contrario
 * retornará una cadena vacia.
 *
 * @param mixed $var Variable a mostrar contenido.
 * @param string $title Título a usar al mostrar contenido.
 * @param bool $escape_dump TRUE para mostrar información legible (para humanos) sobre
 * 							el contenido de $var. FALSE muestra el contenido tal
 * 							cual sin modificar su formato.
 * @param bool $ignore_empty TRUE para no generar texto alguno si la variable está vacia.
 *
 * @return string Texto formateado.
 */
function miframe_export_dump(mixed $var, string $title = '', bool $escape_dump = true, bool $ignore_empty = false): string
{
	$content = '';
	$render = miframe_render();
	if ($render->inDeveloperMode() && (!$ignore_empty || !empty($var))) {
		if ($escape_dump) {
			// Convierte en texto protegido
			$var = htmlspecialchars(print_r($var, true));
			// Complementa titulo
			$title = trim('<b>DUMP</b> ' . $title);
		}
		// Carga vista respectiva
		$content = $render->capture('show-dump', compact('var', 'title'));
	}

	return $content;
}
