<?php

/**
 * Demo para pruebas de las funciones miframe_autoload() y miframe_server().
 *
 * @author John Mejía
 * @since Julio 2024
 */

require_once __DIR__ . '/../demo-config.php';

$Test->start(
	'miframe_server() y miframe_autoload()',
	'Demos para ilustrar uso de los utilitarios <code>miframe_server()</code> y <code>miframe_autoload()</code> de la librería <code>miFrame\\Commons</code>'
);

// Asocia clase a una variable para agilizar su uso.
$server = miframe_server();
$path_dummy = '../path/to/other/any/ .. /./script/ignora/..';
$script = $server->script();

// Fija directorio temporal
$server->tempDir($Test->tmpDir());

$force_no_localhost = false;
// Retorna TRUE si simula consulta no localhost
if ($server->isLocalhost()) {
	$force_no_localhost = $Test->choice('emulate-nolocal', 'Simular vista para hosting remoto (no Localhost)', 'Restaurar vista para Localhost');
	// Visualiza opciones
	echo '<p class="test-aviso"><b>Sólo para Localhost:</b> ' . $Test->renderChoices() . '</p>';
}

echo "<h2>miframe_server()</h2>";

// Arreglo de muestras
$data = array(
	'miframe_server()->startAt()' => $server->startAt(),
	'miframe_server()->startAt(\'Y/m/d H:i:s\')' => $server->startAt('Y/m/d H:i:s'),
	'miframe_server()->checkPoint() [Inicio]' => $server->checkPoint(),
	// Limpieza
	'miframe_server()->purgeURLPath($path_dummy)' => $server->purgeURLPath($path_dummy),
	'miframe_server()->purgeFilename($path_dummy)' => $server->purgeFilename($path_dummy),
	// Consultas
	'miframe_server()->get(\'REQUEST_METHOD\')' => $server->get('REQUEST_METHOD'),
	'miframe_server()->client()' => $server->client(),
	'miframe_server()->isWeb()' => $server->isWeb(),
	'miframe_server()->useHTTPSecure()' => $server->useHTTPSecure(),
	'miframe_server()->isLocalhost()' => $server->isLocalhost(),
	'miframe_server()->software()' => $server->software(),
	'miframe_server()->browser()' => $server->browser(),
	'miframe_server()->rawInput()' => $server->rawInput(),
	// Datos del servidor
	'miframe_server()->name()' => $server->name(),
	'miframe_server()->ip()' => $server->ip(),
	'miframe_server()->path()' => $server->path(),
	'miframe_server()->pathInfo()' => $server->pathInfo(),
	'miframe_server()->host()' => $server->host(),
	'miframe_server()->host(true)' => $server->host(true),
	'miframe_server()->self()' => $server->self(),
	'miframe_server()->relativePath()' => $server->relativePath(),
	'miframe_server()->relativePath($path_dummy)' => $server->relativePath($path_dummy),
	'miframe_server()->documentRoot()' => $server->documentRoot(),
	'miframe_server()->documentRoot($path_dummy)' => $server->documentRoot($path_dummy),
	'miframe_server()->documentRootSpace()' => $server->documentRootSpace(),
	'miframe_server()->script()' => $server->script(),
	'miframe_server()->scriptDirectory()' => $server->scriptDirectory(),
	'miframe_server()->scriptDirectory($path_dummy)' => $server->scriptDirectory($path_dummy),
	'miframe_server()->removeDocumentRoot($script)' => $server->removeDocumentRoot($script),
	'miframe_server()->removeDocumentRoot($path_dummy)' => $server->removeDocumentRoot($path_dummy),
	'miframe_server()->tempDir()' => $server->tempDir(),
	'miframe_server()->tempDirSpace()' => $server->tempDirSpace(),
	// Validaciones
	'miframe_server()->hasAccessTo($path_dummy)' => $server->hasAccessTo($path_dummy),
	'miframe_server()->hasAccessTo($script)' => $server->hasAccessTo($script),
	// Acciones sobre el servidor
	'miframe_server()->mkdir($path_dummy)' => $server->mkdir($path_dummy),
	'miframe_server()->createTempSubdir($path_dummy)' => $server->createTempSubdir($path_dummy),
	// punto de chequeo
	'miframe_server()->executionTime()' => $server->executionTime(),
	'miframe_server()->checkPoint() [Fin]' => $server->checkPoint(),
);

$variables = array('$path_dummy' => $path_dummy, '$script' => $script);
$matches = miframe_autoload()->matches();
$namespaces = miframe_autoload()->namespaces();

$aviso_ocultar = '';
// Por razones de seguridad, se ocultan algunos valores cuando no se ejecuta desde localhost
if (!$server->isLocalhost() || $force_no_localhost) {
	$ocultar = '[Restringido]';
	// Información sensible
	$data['miframe_server()->name()'] = $ocultar;
	$data['miframe_server()->browser()'] = $ocultar;
	// $data['miframe_server()->ip()'] = $ocultar;
	$data['miframe_server()->software()'] = $ocultar;
	// Oculta directorios sensibles
	$que = [$server->tempDir(), $server->documentRoot()];
	$con = ['[TEMP_DIR]' . DIRECTORY_SEPARATOR, '[DOCUMENT_ROOT]' . DIRECTORY_SEPARATOR];
	foreach ($data as $k => $v) {
		if (is_string($v)) {
			$data[$k] = trim(str_replace($que, $con, $v));
		}
	}
	foreach ($matches as $k => $v) {
		$matches[$k] = trim(str_replace($que, $con, $v));
	}
	foreach ($namespaces as $k => $v) {
		$namespaces[$k] = trim(str_replace($que, $con, $v));
	}
	// Oculta variables sensibles
	foreach ($variables as $k => $v) {
		$variables[$k] = trim(str_replace($que, $con, $v));
	}
	// Mensaje informando de estos valores protegidos
	$aviso_ocultar = '<p class="test-aviso"><b>Importante:</b> Algunos valores se han ocultado por seguridad.</p>';
}

echo "<p>Variables usadas para crear el arreglo de muestra:</p>" . $aviso_ocultar;

$Test->dump($variables);

echo "<p>Arreglo de muestra:</p>";

$Test->dump($data);

echo "<h2>miframe_autoload()</h2>";
echo "<p>Respecto al uso de la librería <code>autoload.php</code>, " .
	"estas son las Clases evaluadas durante esta presentación:</p>";

$Test->dump($matches, 'miframe_autoload()->matches()');

echo "<p>Y estos son los <i>namespaces</i> registrados:</p>";

$Test->dump($namespaces, 'miframe_autoload()->namespaces()');

// Cierre de la página
$Test->end();
