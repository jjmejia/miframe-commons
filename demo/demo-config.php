<?php

// Carga librerías de miFrame (si fue cargado desde otro entorno, no repite)
require_once $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'miframe-bootstrap.php';

$dir_commons = __DIR__ . '/../src';

// Carga librerías
include_once $dir_commons . '/autoload.php';
include_once $dir_commons . '/helpers.php';
include_once $dir_commons . '/tools/debug.php';

include_once __DIR__ . '/lib/miCodeTest.php';

// Define directorio temporal
miframe_server()->tempDir(config('temp_dir', ''));

// Crea objeto para asistencia en las demos
$Test = new miCodeTest();
