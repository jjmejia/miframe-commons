<?php

/**
 * Funciones de soporta para arranque de las demos de base de datos.
 *
 * @author John Mejía
 * @since Enero 2025
 */

use miFrame\Commons\Core\PDOController;
use miFrame\Commons\Extended\ExtendedRenderView;

// Funciones de soporte para vistas de base de datos
include_once __DIR__ . '/view-functions.php';

// Salida a pantalla usando vistas.
/**
 * @var ExtendedRenderView $view
 */
$view = miframe_render();
$view->location(__DIR__ . DIRECTORY_SEPARATOR . 'views');
$view->layout->config('layout', 'viewContent');

$debug = false;
// Habilita modo developer (habilita dumps y uso del modo Debug)
if ($Test->choice('debugMode', 'Habilitar modo Debug', 'Ocultar Debug')) {
	$debug = true;
	$view->developerOn();
}

// Recupera datos de configuración de bases de datos
$filename = __DIR__ . DIRECTORY_SEPARATOR . 'lekosdev.env';
if (!file_exists($filename)) {
	$filename = __DIR__ . DIRECTORY_SEPARATOR . '.env';
}
$env = parse_ini_file($filename, true);

// Drivers disponibles
$drivers_list = [
	'sqlite' => 'SQLite',
	'mysql' => 'MySQL',
	'nodb' => '(Driver no existente)'
];

// Recupera vista seleccionada
$type = $Test->getParam('type', $drivers_list);

// Crea enlaces para selección de las vistas
$views_links = $Test->multipleLinks('type', $drivers_list);

// Muestra opciones solamente cuando se tienen múltiples vistas
if (count($drivers_list) > 1) {
	echo "<p><b>Drivers:</b> {$views_links}</p>";
}

// Visualiza opciones
echo '<p><b>Opciones:</b> ' . $Test->renderChoices('', true) . '</p>';

$env = $env[$type];
// Asocia clase a una variable para agilizar su uso.
$db = new PDOController($type);
// Habilita modo debug
$db->debug($debug);
// Valida opciones de configuración
if (isset($env['file'])) {
	$db->filename = __DIR__ . DIRECTORY_SEPARATOR . $env['file'];
	// Ddefine valor para compatibilidad
	$env['database'] = '';
}
else {
	$db->host = $env['host'];
	$db->user = $env['user'];
	$db->password = $env['password'];
	$db->database = $env['database'];
}

// Puede conectarse manualmente o dejar que lo haga la primer
// consulta a realizar. Se realiza aquí para validar exista conexión.
if (!$db->connect()) {
	// Cierre de la página
	$Test->abort($db->getLastError());
}

// Valida si ya existe la tabla "person"
$query = 'select count(id) as TOTAL from person';
$rows = @$db->query($query);
if (miframe_server()->isLocalhost() || empty($rows)) {
	// Crea enlace para creación de bases de datos
	$driver = strtolower($type);
	$token = $Test->tokenizer('create-db-token', $driver);
	$link = "demo-database-files/maintenance/demo-create.php?token={$token}&driver={$driver}";
	// Visualiza enlace
	echo "<p class=\"test-aviso\"><b>Mantenimiento:</b> <a href=\"$link\">Crear/reconstruir base de datos <b>{$drivers_list[$type]}</b></a></p>";
}

$viewName = str_replace(['.php', 'demo-database-'], '', basename(miframe_server()->script()));
// Ejecuta vista de esta demo
echo miframe_view($viewName, compact('db', 'Test'));
