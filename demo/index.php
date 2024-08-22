<?php
/**
 * Demo para pruebas de las librerias incluidas en miframe/commons/errors.php
 *
 * @author John Mejía
 * @since Julio 2024
 */

include_once __DIR__ . '/support/lib/miCodeTest.php';

$Test = new miCodeTest();

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
$Test->start('miFrame\\Commons', 'Demos para ilustrar uso de la librería <code>miFrame\\Commons</code>.');

?>
<ul>
	<li>
		<a href="support/demo-server.php">miframe_server() y miframe_autoload()</a>
	</li>
</ul>

<?php

$Test->visitorLog('demo-index');

// Cierre de la página
$Test->end();
