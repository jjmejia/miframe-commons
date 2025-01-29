<?php

/**
 * Demo para pruebas de la clase xxx.
 *
 * @author John Mejía
 * @since Diciembre 2024
 */

use miFrame\Commons\Core\PDOController;

// Configuración de demo, crea objeto $Test
include_once __DIR__ . '/../demo-config.php';

include_once $Test->includePath('/miframe/commons/autoload.php');
include_once $Test->includePath('/miframe/commons/helpers.php');

// Apertura de la página demo
$Test->start(
	'Conexión y consultas a bases de datos',
	'Esta demo ilustra el uso de la clase <code>PDOController</code> usada para consultas a bases de datos en PHP.'
);

$env = parse_ini_file(__DIR__ . DIRECTORY_SEPARATOR . '.env', true);

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

$env = $env[$type];
// Asocia clase a una variable para agilizar su uso.
$db = new PDOController($type);

// Habilita modo developer (habilita dumps y uso del modo Debug)
if ($Test->choice('debugMode', 'Habilitar modo Debug', 'Ocultar Debug')) {
	$db->debug(true);
}

// Visualiza opciones
echo '<p><b>Opciones:</b> ' . $Test->renderChoices('', true) . '</p>';

// Valida opciones de configuración
if (isset($env['file'])) {
	$db->filename = __DIR__ . DIRECTORY_SEPARATOR . 'demo-database-files' . DIRECTORY_SEPARATOR . $env['file'];
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
// consulta a realizar
// $db->connect();

echo '<style>
.demo-table-db {
	border:1px solid #ccc;
	margin-bottom:10px;
	th { background:#f2f2f2; }
	td, th { border:1px solid #ccc; padding:5px; }
}
</style>';

echo "<h3>Consulta SQL</h3>";

// Recupera registros
$Test->showNextLines(4);
$query = 'select person.id, person.name, gender.name as gender
from person
    left join gender on gender.id = person.gender_id';
$rows = $db->query($query);
echo showTable($rows);
showStats($db, $Test);

echo "<h3>Consulta SQL parcial</h3>";

// Recupera 5 registros
$Test->showNextLines(4);
$query = 'select person.id, person.name, gender.name as gender
from person
    left join gender on gender.id = person.gender_id';
$rows = $db->query($query, offset: 8, limit: 5);
echo showTable($rows);
showStats($db, $Test);

echo "<h3>Consulta SQL usando sentencias preparadas</h3>";

// Busqueda por valores
$Test->showNextLines(6);
$query = 'select person.id, person.name, gender.name as gender
from person
    left join gender on gender.id = person.gender_id
where gender.name = ?';
$values = ['Femenino'];
$rows = $db->query($query, $values);
echo showTable($rows);
showStats($db, $Test);

echo "<h3>Consulta SQL con recuperación manual de datos</h3>";

// Recupera 5 registros
$Test->showNextLines(6);
$query = 'select person.id, person.name, gender.name as gender
from person
    left join gender on gender.id = person.gender_id
where person.id > ?';
$result = $db->execute($query, [20]);
$rows = $result->fetchAll();
echo showTable($rows);
showStats($db, $Test);

echo "<h3>Consulta con errores</h3>";

// Recupera registros
$Test->showNextLines(4);
$query = 'select person.id, name, gender.name as gender
from person
    left join gender on gender.id = person.gender_id';
$rows = $db->query($query);
echo showTable($rows);
showStats($db, $Test);

if (!$db->inDebug()) {
	echo "<p><b>Sugerencia:</b> Habilite el modo Debug para visualizar errores directamente en pantalla.</p>";
}

// Cierre de la página
$Test->end();

function showRecord(array $data, bool $is_title = false): string
{
	$tag = 'td';
	if ($is_title) {
		$tag = 'th';
	}
	return "<tr><{$tag}>" . implode("</{$tag}><{$tag}>", $data) . "</{$tag}}></tr>" . PHP_EOL;
}

function showTable(array $values): string
{
	$total = count($values);
	if ($total <= 0) {
		return '<p>&bull; <i>No hay datos para mostrar.</i></p>';
	}

	$headers = true;
	$count = 10;
	$salida = '<table cellspacing="0" class="demo-table-db">' . PHP_EOL;
	foreach ($values as $key => $data) {
		if (!is_numeric($key)) {
			$data = ['Nombre' => $key, 'Valor' => $data];
		}
		if (!is_array($data)) { continue; }
		if ($headers) {
			$salida .= showRecord(array_keys($data), true);
			$headers = false;
		}
		$salida .= showRecord($data);
		$count --;
		if ($count < 1) { break; } // Limita a 10 valores
	}
	$salida .= '</table>' . PHP_EOL;
	if ($total > 10) {
		$salida .= "<p>&bull; Muestra limitada a 10 registros de un total de {$total}.</p>";
	}
	return $salida;
}

function showStats(PDOController $db, miCodeTest $Test)
{
	if ($db->inDebug()) {
		$Test->dump($db->stats());
	}
	else {
		$error = $db->getLastError();
		if ($error !== '') {
			echo '<p><i><b>Aviso:</b> ' . $error . '</i></p>';
		}
	}
}