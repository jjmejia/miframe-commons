<?php

/**
 * Demo para pruebas de las librerias incluidas en miframe/commons/errors.php
 *
 * @author John Mejía
 * @since Julio 2024
 */

include_once __DIR__ . '/demo-config.php';

// Define home principal y nombde de log de visitas
$Test->config(['home' => '/', 'visitor-log' => 'demo-index']);

// Cabezote de presentación
$Test->start('miFrame\\Commons', 'Demos para ilustrar uso de la librería <code>miFrame\\Commons</code>.');

$base = dirname($_SERVER['SCRIPT_NAME']) . '/';

$links = [
	'support/demo-server.php' => 'Uso de miframe_server() y miframe_autoload()',
	'support/demo-html.php' => 'Gestión de recursos HTML con miframe_html()',
	'support/demo-view.php' => 'Uso de miframe_render() y miframe_view()',
	'support/demo-errors.php' => 'Manejo de errores (clase ErrorHandler)',
	'support/demo-database-pdo.php' => 'Conexión y consulta a base de datos (clase PDOController)',
];

echo '<ul>' . PHP_EOL;
foreach ($links as $href => $title) {
	echo "<li><a href=\"{$base}{$href}\">{$title}</a></li>" . PHP_EOL;
}
echo '</ul>' . PHP_EOL;

// Cierre de la página
$Test->end(false);
