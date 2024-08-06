<?php
/**
 * Demo para pruebas de las librerias incluidas en miframe/commons/errors.php
 *
 * @author John Mejía
 * @since Julio 2024
 */

include_once __DIR__ . '/demo-files/lib/testfunctions.php';

// Ruta a los scripts
miframe_test_src_path(__DIR__ . '/../src');

// URL para descargar recursos web
miframe_test_url(dirname($_SERVER['SCRIPT_NAME']) . '/demo-files');

// Cabezote de presentación
miframe_test_start('Demos para miframe-commons');

?>
<ul>
	<li>
		<a href="demo-files/demo-server.php">miframe_autoload() y miframe_server()</a>
	</li>
</ul>

<?php

// Cierre de la página
miframe_test_end();