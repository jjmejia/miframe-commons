<?php

/**
 * Demo para pruebas de las librerias incluidas en "miframe-commons"
 *
 * @author John Mejía
 * @since Julio 2024
 */

include_once __DIR__ . '/demo-config.php';

// Cabezote de presentación
$Test->title = 'miFrame\\Commons';
$Test->description = 'Demos para ilustrar uso de la librería <code>miFrame\\Commons</code>.';
$Test->start();

$base = $Test->home(true) . '/';

$links = [
	'demo-check' => 'Uso de timecheck()',
	'demo-server' => 'Uso de miframe_server() y miframe_autoload()',
	'demo-html' => 'Gestión de recursos HTML con miframe_html()',
	'demo-view' => 'Uso de miframe_render() y miframe_view()',
	'demo-errors' => 'Manejo de errores (clase ErrorHandler)',
	'demo-database-pdo' => 'Conexión y consulta a base de datos (clase PDOController)',
	// 'demo-inputs' => 'Validación de datos de entrada',
];

echo '<ul>' . PHP_EOL;
// Si no define valores asociados a LEKOSDEV (usa acceso directo)
// emplea un enlace diferente
$direct_access = !empty(config('apps_miframe_commons_script', false));

foreach ($links as $href => $title) {
	if (!$direct_access) {
		$href = "support/{$href}.php";
	}
	echo "<li><a href=\"{$base}{$href}\">{$title}</a></li>" . PHP_EOL;
}
echo '</ul>' . PHP_EOL;

// Cierre de la página
$Test->end(false);
