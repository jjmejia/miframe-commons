<?php
/**
 * Librería de soporte para montaje de scripts para test de módulos.
 *
 * @author John Mejia
 * @since Mayo 2022
 */

// Inicializa manejo de sesión PHP
if (empty($_SESSION)) {
	session_start();
}

/**
 * Define el path a usar para buscar los scripts (directorio "src").
 */
function miframe_test_src_path(string $path = '') {

	if ($path !== '') {
		// Registra valor
		$_SESSION['MICODE_DEMO_INCLUDE_PATH'] = realpath($path);
	}
	elseif (empty($_SESSION['MICODE_DEMO_INCLUDE_PATH'])) {
		// Asigna el path usado por el script actual
		$_SESSION['MICODE_DEMO_INCLUDE_PATH'] = __DIR__;
	}

	return $_SESSION['MICODE_DEMO_INCLUDE_PATH'];
}

/**
 * Define el path a usar para buscar recursos, relativo a la URL actual.
 * Puede definirse previamente para acceder a un directorio diferente
 * al auto-detectado.
 */
function miframe_test_url(string $path = '') {

	if ($path !== '') {
		// Remueve ultimo caracter "/"
		if (substr($path, -1, 1) == '/') {
			$path = substr($path, 0, -1);
		}
		// Registra valor
		$_SESSION['MICODE_DEMO_URL'] = $path;
	}
	elseif (empty($_SESSION['MICODE_DEMO_URL'])) {
		// Asigna el path usado por el script actual
		$_SESSION['MICODE_DEMO_URL'] = dirname($_SERVER['SCRIPT_NAME']);
	}

	return $_SESSION['MICODE_DEMO_URL'];
}

/**
 * Presenta encabezado para la salida a pantalla.
 */
function miframe_test_start(string $title) {

	$estilos = miframe_test_url() . '/resources/css/tests.css';

?>
<!DOCTYPE html>
<html>
<head>
	<title><?= htmlentities($title) ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link rel="stylesheet" href="<?= $estilos ?>">
</head>
<body>
	<h1>
		<?= htmlentities($title) ?>
		<small>miCode-Manager</small>
	</h1>
	<div class="content-test">

<?php

}

function miframe_test_pre(string $text) {

	echo PHP_EOL . '<pre class="code">' . htmlentities(trim($text)) . '</pre>' . PHP_EOL;
}

function miframe_test_end() {

	echo "</div></body></html>";
}

function miframe_test_link(string $name, array $data) {

	// $enlace_base = basename(miframe_server_get('SCRIPT_FILENAME'));
	$enlace_base = '';
	if (count($data) > 0) {
		$enlace_base .= '?' . http_build_query($data);
	}
	$enlace_base = '<a href="' . $enlace_base . '">' . $name . '</a>';

	return $enlace_base;
}

function miframe_test_option(string $option, string $text_ok, string $text_nok, string &$link) {

	$retornar = false;

	$data = $_REQUEST;
	$info = $text_ok;
	if (array_key_exists($option, $_REQUEST)) {
		$retornar = true;
		unset($data[$option]);
		$info = $text_nok;
	}
	else {
		$data[$option] = '';
	}

	if ($link != '') { $link .= ' | '; }
	$link .= miframe_test_link($info, $data);

	return $retornar;
}

function miframe_test_dump(mixed $data) {

	$info = '';
	if (is_array($data)) {
		foreach ($data as $k => $v) {
			if ($info != '') {
				$info .= PHP_EOL;
			}
			$info .= $k . ' => ' . var_export($v, true);
		}
	}
	else {
		$info = var_export($data, true);
	}

	return miframe_test_pre($info);
}