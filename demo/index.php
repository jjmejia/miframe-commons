<?php
/**
 * Demo para pruebas de las librerias incluidas en miframe/commons/errors.php
 *
 * @author John Mejía
 * @since Julio 2024
 */

include_once __DIR__ . '/support/lib/miCodeTest.php';

$Test = new miCodeTest(false);

// Ruta a los scripts
$Test->config([
	// Path con el código fuente
	'src-path' => __DIR__ . '/../src',
	// URL para descargar recursos web
	'url-resources' => dirname($_SERVER['SCRIPT_NAME']) . '/support',
	// Registrar página de inicio
	'home' => $_SERVER['SCRIPT_NAME'],
	// Pie de página adicional (si existe)
	'footer' => __DIR__ . '/footer.htm',
	// Visitors log
	'logs-path' => __DIR__ . '/logs',
	// Temporal
	'tmp-path' => __DIR__ . '/tmp'
	]);

// Cabezote de presentación
$Test->start('miFrame\\Commons', 'Demos para ilustrar uso de la librería <code>miFrame\\Commons</code>.', '../../../index.php');

$base = dirname($_SERVER['SCRIPT_NAME']) . '/';

$links = [
	'support/demo-server.php' => 'miframe_server() y miframe_autoload()',
	'support/demo-show.php' => 'miframe_show() y miframe_box()'
];

echo '<ul>' . PHP_EOL;
foreach ($links as $href => $title) {
	echo "<li><a href=\"{$base}{$href}\">{$title}</a></li>" . PHP_EOL;
}
echo '</ul>' . PHP_EOL;

$Test->visitorLog('demo-index');

// Cierre de la página
$Test->end();
