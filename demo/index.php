<?php
/**
 * Demo para pruebas de las librerias incluidas en miframe/commons/errors.php
 *
 * @author John Mejía
 * @since Julio 2024
 */

include_once __DIR__ . '/demo-config.php';

// Define home principal
$Test->config([ 'home' => '/' ]);

// Cabezote de presentación
$Test->start('miFrame\\Commons', 'Demos para ilustrar uso de la librería <code>miFrame\\Commons</code>.');

$base = dirname($_SERVER['SCRIPT_NAME']) . '/';

$links = [
	'support/demo-server.php' => 'miframe_server() y miframe_autoload()',
	'support/demo-html.php' => 'miframe_html()',
	// 'support/demo-show.php' => 'miframe_show() y miframe_box()'
];

echo '<ul>' . PHP_EOL;
foreach ($links as $href => $title) {
	echo "<li><a href=\"{$base}{$href}\">{$title}</a></li>" . PHP_EOL;
}
echo '</ul>' . PHP_EOL;

$Test->visitorLog('demo-index');

// Cierre de la página
$Test->end(false);
