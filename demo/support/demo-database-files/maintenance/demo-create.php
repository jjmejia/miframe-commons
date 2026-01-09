<?php

/**
 * Crea base de datos en SQLite/MySQL
 */

use miFrame\Commons\Core\PDOController;

// Configuración de demo, crea objeto $Test
include_once __DIR__ . '/../../../demo-config.php';

// Recupera driver asociado al mantenimiento
$motor = 'sqlite';
if (array_key_exists('driver', $_GET)) {
	$motor = strtolower(trim($_GET['driver']));
}

// Apertura de la página demo
$Test->title = 'Mantenimiento / Base de datos para soporte';
$Test->description = 'Crea y/o reconstruye la base de datos usada para las demos, asociadas al driver <b>' . ucfirst($motor) . '</b>.';
$Test->start();

// Valida permisos de creación
evalToken('token', 'create-db-token', $motor);

// Carga datos
$filename = __DIR__ . DIRECTORY_SEPARATOR . 'personajes-historicos.csv';

$lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$cabecera = array_shift($lines);

$person = [];
$gender = [];
$nationality = [];
$tags = [];
$person_tags = [];

foreach ($lines as $line) {
	$csv = explode(';', $line);

	$fecha = trim($csv[3]);
	// Ignora fechas negativas (AC)
	if (substr($fecha, 0, 1) == '-') {
		$fecha = 0 - intval($fecha);
		$csv[4] = trim($csv[4]) . ' (Nació en el año ' . $fecha . ' A.C)';
		$fecha = null;
	} elseif (strpos($fecha, '/01/01') !== false) {
		// Fecha de nacimiento no conocida
		$fecha = 0 + intval($fecha);
		$csv[4] = trim($csv[4]) . ' (Nació en el año ' . $fecha . ')';
		$fecha = null;
	}

	$short_name = trim($csv[1]);
	$person[$short_name] = [
		'name' => trim($csv[0]),
		'short_name' => $short_name,
		'birthdate' => $fecha,
		'resume' => trim($csv[4]),
		'url' => trim($csv[9]),
		'refs' => [
			'gender' => trim($csv[5]),
			'nationality' => trim($csv[2]),
			'tags' => [trim($csv[6]), trim($csv[7]), trim($csv[8])]
		]
	];

	saveRef($gender, $csv, 5);
	saveRef($nationality, $csv, 2);
	saveRef($tags, $csv, 6, 8);
}

//**********************************************

// Lee archivo de configuración de bases de datos
$env = parse_ini_file(__DIR__ . '/../.env', true);
// echo "<pre>$motor: "; print_r($env); echo "</pre><hr>";
if (!array_key_exists($motor, $env)) {
	$Test->abort('Error: Soporte no encontrado para bases de datos ' . $motor);
}
$env = $env[$motor];

if (miframe_server()->isLocalhost()) {
	$Test->dump($env);
}

// Inicia conexión a base de datos
$db = new PDOController($motor);
if ($motor === 'sqlite') {
	$db->filename = dirname(__DIR__) . DIRECTORY_SEPARATOR . $env['file'];
} else {
	$db->host = $env['host'];
	$db->user = $env['user'];
	$db->password = $env['password'];
	$db->database = $env['database'];
}

$db->debug(true);

// Archivo con los SQL para crear tablas
$filename = __DIR__ . DIRECTORY_SEPARATOR . $motor . '-create-tables.sql';

// Valida si reconstruye tablas
createTables($Test, $db, $filename);

// Carga valores de referencia
loadDataRef('gender', $gender, $db);
loadDataRef('nationality', $nationality, $db);
loadDataRef('tags', $tags, $db);
// Carga valores principales
loadPerson($person, $gender, $nationality, $db);

// Asocia tags
// $db->rawQuery('delete from person_tags');
$person_tags = $db->query('select * from person_tags');
if (empty($person_tags)) {
	foreach ($person as $v) {
		// print_r($v);
		foreach ($v['refs']['tags'] as $tag_name) {
			$tag_name = strtolower($tag_name);
			if (isset($tags[$tag_name])) {
				// echo "INSERT INTO person_tags (id, tags_id) VALUES ('{$v['id']}', '{$tags[$tag_name]['id']}')\n";
				$db->query("INSERT INTO person_tags (person_id, tags_id) VALUES ('{$v['id']}', '{$tags[$tag_name]['id']}')");
			}
		}
	}
	$person_tags = $db->query('select * from person_tags');
}

$total = count($person_tags);
echo "<p><b>Person_Tags:</b> Encontrados {$total} registros.</p>";

echo "<hr><p>Base de datos <b>{$motor}</b> actualizada.</p>";

// Libera token
unset($_SESSION['create-db-token']);

// echo "<pre>"; print_r([$person, $person_tags, $gender, $nationality, $tags]);

$Test->end(false);

//**********************************************

function createTables(miCodeTest $Test, PDOController $db, string $filename)
{
	if (array_key_exists('reset', $_GET) || !evalTables($db)) {
		echo "Creando tablas... ";
		if (!file_exists($filename)) {
			$Test->abort('Error: No pudo encontrar archivo ' . basename($filename));
		}
		// Elimina todas las tablas contenidas (que no sean propias del motor)
		$tables = getTables($db);
		foreach ($tables as $table_name) {
			$query = 'drop table if exists ' . $table_name;
			$db->query($query);
		}
		// Lee contenido del archivo
		$content = file($filename, FILE_IGNORE_NEW_LINES);
		// Arma querys separados por ";"
		$acum = '';
		foreach ($content as $k => $line) {
			$line = trim($line);
			if (substr($line, -1, 1) == ';') {
				$acum .= substr($line, 0, -1);
				// Ejecuta query
				if ($db->query($acum) === false) {
					$k++;
					$Test->abort("No pudo ejecutar query en línea {$k}: {$acum}");
				}
				// Limpia variable que acumula comandos
				$acum = '';
			} else {
				$acum .= $line . ' ';
			}
		}

		if (!evalTables($db)) {
			$Test->abort('No pudo crear todas las tablas requeridas.');
		}
	}
}

//**********************************************

function getTables(PDOController $db): array
{
	$data = [];
	$query = '';
	if ($db->driver() == 'sqlite') {
		$query = "SELECT name " .
			"FROM sqlite_schema " .
			"WHERE type ='table' AND name NOT LIKE 'sqlite_%'";
		$data = $db->query($query);
		if ($db->getLastError() !== '') {
			// Versiones anteriores a SQLITE3 no usan sqlite_schema
			$query = 'SELECT name FROM sqlite_master';
			$data = $db->query($query);
			// print_r($data); exit;
		}
	} elseif ($db->driver() == 'mysql') {
		// MySQL
		$query = 'show tables';
		$data = $db->query($query);
	} else {
		die('Driver no soportado: ' . $db->driver());
	}
	// print_r($rows);
	$tables = [];
	foreach ($data as $row) {
		// Solamente retorna una columna y el nombre contiene
		// el nombre de la base de datos. Ej. "Tables_in_[database]"
		// o "name". También aplica si retorna más de una columna
		// pero es la primera la que contiene el nombre de las tablas.
		$tables[] = array_shift($row);
	}

	return $tables;
}

//**********************************************

function evalTables(PDOController $db): bool
{
	$list_tables = ['person', 'gender', 'nationality', 'tags', 'person_tags'];
	$tables = getTables($db);
	foreach ($list_tables as $table_name) {
		if (!in_array($table_name, $tables)) {
			// Crea la tabla
			echo "Error: Tabla \"{$table_name}\" no existe<hr>";
			return false;
		}
	}

	$total = count($tables);
	echo "<p>Tablas encontradas: {$total}</p><hr>";

	return true;
}

//**********************************************

function loadPerson(array &$person, array $gender, array $nationality, PDOController $db)
{
	$data = $db->query('select id, short_name from person');
	if (empty($data)) {
		echo "Actualizando tabla... ";
		// Complementa tabla principal
		foreach ($person as $v) {
			$v['gender_id'] = $gender[strtolower($v['refs']['gender'])]['id'];
			$v['nationality_id'] = $nationality[strtolower($v['refs']['nationality'])]['id'];
			// Almacena valores de persona y actualiza listado
			unset($v['refs']);
			$keys = implode(',', array_keys($v));
			// Remplaza cadenas vacias por "null"
			$values = str_replace("''", 'null', implode("','", $v));
			$db->query("INSERT INTO person ({$keys}) VALUES ('{$values}')");
		}
		// Recupera datos ingresados
		$data = $db->query('select id, short_name from person');
	}

	$total = count($data);
	echo "<p><b>Person:</b> Encontrados {$total} registros.</p>";

	// Asocia datos en tabla con los id de person
	foreach ($data as $v) {
		$short_name = $v['short_name'];
		$person[$short_name]['id'] = $v['id'];
	}
}

//**********************************************

function loadDataRef(string $table_name, array &$ref, PDOController $db)
{
	$data = $db->query('select * from ' . $table_name);
	if (empty($data)) {
		echo "Actualizando tabla... ";
		foreach ($ref as $v) {
			$sql = "INSERT INTO {$table_name} (name) VALUES ('{$v['name']}')";
			$data = $db->query($sql);
		}
		// Recupera datos
		$data = $db->query('select * from ' . $table_name);
	}

	$total = count($data);
	echo '<p><b>' . ucfirst($table_name) . ":</b> Encontrados {$total} registros.</p>";

	// Actualiza tabla de referencia
	foreach ($data as $v) {
		$reference = strtolower($v['name']);
		$ref[$reference]['id'] = $v['id'];
	}

	return $data;
}

//**********************************************

function saveRef(array &$ref, array $csv, int $start, int $finish = 0)
{
	if ($finish <= 0) {
		$finish = $start;
	}
	for ($i = $start; $i <= $finish; $i++) {
		$tag = trim($csv[$i]);
		if ($tag !== '') {
			$ref[strtolower($tag)] = [
				'id' => 0,
				'name' => $tag
			];
		}
	}
}

//**********************************************

/**
 * Evalúa un token de seguridad.
 *
 * Si la validación es fallida, aborta la ejecución del script.
 *
 * @param string $param Nombre del parámetro en $_GET a evaluar.
 * @param string $name Nombre del token.
 * @param string $key Complemento del token.
 */
function evalToken(string $param, string $name, string $key)
{
	$user_string = ($_GET[$param] ?? '');
	if (!session()->csrfCheck($user_string)) {
		throw new Exception('Acceso no autorizado');
	}
}
