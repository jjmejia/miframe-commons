<?php

// Ejecuta autoload de clases y funciones provistas por "miframe-commons"
// El archivo debe personalizarse de acuerdo a la configuración del sitio web.
// Debe contener como mínimo las siguientes líneas o similares:
// * include_once '/miframe-commons/src/autoload.php';
// * include_once '/miframe-commons/src/helpers.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/miframe-autoload.php';

include_once __DIR__ . '/lib/miCodeTest.php';

$Test = new miCodeTest();

// Valida haya cargado correctamente librerias "main-function.php"
if (function_exists('miframe_env')) {
	// Datos de entorno
	$env = miframe_env();
	// Configuración general
	/** @disregard P1010 Funciones del main */
	$Test->config([
		// Página de inicio principal (si aplica)
		'root' => $env->get('ROOT'),
		// Pie de página adicional (si existe)
		'footer' => $env->get('FOOTER'),
		// Temporal
		'tmp-path' => $env->documentRoot('TEMP-PATH'),
		// Repositorio Github
		'github-repo' => $env->get('MIFRAME-COMMONS-GITHUB-REPO')
	]);
}
