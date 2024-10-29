<?php

include_once __DIR__ . '/support/lib/miCodeTest.php';

$Test = new miCodeTest();

// Configuración general
$Test->config([
	// Path con el código fuente
	'src-path' => __DIR__ . '/../src',
	// URL para descargar recursos web
	'url-resources' => '/software/miframe-commons/demo/support',
	// Registrar página de inicio
	'home' => '/software/miframe-commons/demo/',
	// Pie de página adicional (si existe)
	'footer' => __DIR__ . '/footer.htm',
	// Visitors log
	'logs-path' => __DIR__ . '/logs',
	// Temporal
	'tmp-path' => __DIR__ . '/tmp',
	// Repositorio
	'github-repo' => 'https://github.com/jjmejia/miframe-commons/'
	]);

// Configuraciones adicionales
if (file_exists(__DIR__ . '/demo-config-dev.php')) {
	include_once __DIR__ . '/demo-config-dev.php';
}