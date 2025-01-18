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
	'mysql' => 'MySQL'
];
// Recupera vista seleccionada
$type = $Test->getParam('type', $drivers_list);

// Carga driver seleccionado
$class = '\\miFrame\\Commons\\Extended\\Database\\' . ucfirst(strtolower($type)) . 'Consultor';
// Valida selección de motor
if (!isset($env[$type]) || !class_exists($class)) {
	$type = 'sqlite';
	$class = '\\miFrame\\Commons\\Extended\\Database\\SqliteConsultor';
}

// Crea enlaces para selección de las vistas
$views_links = $Test->multipleLinks('type', $drivers_list);

// Muestra opciones solamente cuando se tienen múltiples vistas
if (count($drivers_list) > 1) {
	echo "<p><b>Drivers:</b> {$views_links}</p>";
}

$env = $env[$type];
// Asocia clase a una variable para agilizar su uso.
$db = new PDOController($type);

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
$Test->dump($db->stats());

echo "<h3>Consulta SQL parcial</h3>";

// Recupera 5 registros
$Test->showNextLines(4);
$query = 'select person.id, person.name, gender.name as gender
from person
    left join gender on gender.id = person.gender_id';
$rows = $db->query($query, offset: 8, limit: 5);
echo showTable($rows);
$Test->dump($db->stats());

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
$Test->dump($db->stats());

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
	$salida = '<table cellspacing="0" class="demo-table-db">' . PHP_EOL;
	$headers = true;
	$count = 10;
	$total = count($values);

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
		$salida .= "<p>Mostrados 10 registros de un total de {$total} recuperados.</p>";
	}
	return $salida;
}
