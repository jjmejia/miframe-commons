<?php

include_once __DIR__ . '/support/lib/miCodeTest.php';

$Test = new miCodeTest();

// Los recursos se encuentran asociados al directorio /demo/
$arreglo = explode('/demo/', $_SERVER['SCRIPT_NAME']);

// Ruta a los scripts
$Test->config([
	// Path con el código fuente
	'src-path' => __DIR__ . '/../src',
	// URL para descargar recursos web
	'url-resources' => $arreglo[0] . '/demo/support',
	// Registrar página de inicio
	'home' => $arreglo[0] . '/demo/',
	// Pie de página adicional (si existe)
	'footer' => __DIR__ . '/footer.htm',
	// Visitors log
	'logs-path' => __DIR__ . '/logs',
	// Temporal
	'tmp-path' => __DIR__ . '/tmp',
	// Repositorio
	'github-repo' => 'https://github.com/jjmejia/miframe-commons/'
	]);

unset($arreglo);