<?php

/**
 * Demo para pruebas de las funciones miframe_autoload() y miframe_server().
 *
 * @author John Mejía
 * @since Julio 2024
 */

require_once __DIR__ . '/../demo-config.php';

$Test->title = 'miframe_server() y miframe_autoload()';
$Test->description = 'Demos para ilustrar uso de los utilitarios <code>miframe_server()</code> y <code>miframe_autoload()</code> de la librería <code>miFrame\\Commons</code>';
$Test->start();

// Asocia clase a una variable para agilizar su uso.
$server = miframe_server();

// Variables para uso en la demo
$path_dummy = '/../path/to/other/any/ .. /./script/ignora/../';
$script = $server->script();
$args = [ 'name1' => 'value1', 'name2' => 'value2'];

$force_no_localhost = false;
// Retorna TRUE si simula consulta no localhost
if ($server->isLocalhost()) {
	$force_no_localhost = $Test->choice('emulate-nolocal', 'Simular vista para hosting remoto (no Localhost)', 'Restaurar vista para Localhost');
	// Visualiza opciones
	echo '<p class="test-aviso"><b>Sólo para Localhost:</b> ' . $Test->renderChoices() . '</p>';
}

echo "<h2>miframe_server()</h2>";

timecheck('INICIO');

// Arreglo de muestras
$data = array(
	'miframe_server()->startAt()' => $server->startAt(),
	// 'miframe_server()->startAt(\'Y/m/d H:i:s\')' => $server->startAt('Y/m/d H:i:s'),
	'miframe_server()->checkPoint()' => $server->checkPoint(),
	// Limpieza
	'miframe_server()->purgeURLPath($path_dummy)' => $server->purgeURLPath($path_dummy),
	'miframe_server()->purgeFilename($path_dummy)' => $server->purgeFilename($path_dummy),
	// Consultas
	'miframe_server()->get(\'REQUEST_METHOD\')' => $server->get('REQUEST_METHOD'),
	'miframe_server()->ipClient()' => $server->ipClient(),
	'miframe_server()->isWeb()' => $server->isWeb(),
	'miframe_server()->useHTTPSecure()' => $server->useHTTPSecure(),
	'miframe_server()->isLocalhost()' => $server->isLocalhost(),
	'miframe_server()->webserver()' => $server->webserver(),
	'miframe_server()->browser()' => $server->browser(),
	// Datos del servidor
	'miframe_server()->name()' => $server->name(),
	'miframe_server()->scheme()' => $server->scheme(),
	'miframe_server()->domain()' => $server->domain(),
	'miframe_server()->port()' => $server->port(),
	'miframe_server()->ip()' => $server->ip(),
	'miframe_server()->url()' => $server->url(),
	'miframe_server()->url($path_dummy)' => $server->url($path_dummy),
	'miframe_server()->url($path_dummy, $args)' => $server->url($path_dummy, $args),
	// 'miframe_server()->urlPath()' => $server->urlPath(),
	'miframe_server()->urlPath($path_dummy)' => $server->urlPath($path_dummy),
	'miframe_server()->urlPath($path_dummy, $args)' => $server->urlPath($path_dummy, $args),
	// 'miframe_server()->requestUri()' => $server->requestUri(),
	'miframe_server()->pathInfo()' => $server->pathInfo(),
	'miframe_server()->self()' => $server->self(),
	'miframe_server()->self(true)' => $server->self(true),
	'miframe_server()->relativePath()' => $server->relativePath(),
	'miframe_server()->relativePath($path_dummy)' => $server->relativePath($path_dummy),
	// 'miframe_server()->relativePath("", true)' => $server->relativePath('', true),
	// 'miframe_server()->relativePath($path_dummy, true)' => $server->relativePath($path_dummy, true),
	'miframe_server()->documentRoot()' => $server->documentRoot(),
	'miframe_server()->documentRoot($path_dummy)' => $server->documentRoot($path_dummy),
	'miframe_server()->documentRootSpace()' => $server->documentRootSpace(),
	'miframe_server()->inDocumentRoot($path_dummy)' => $server->inDocumentRoot($path_dummy),
	'miframe_server()->inDocumentRoot($script)' => $server->inDocumentRoot($script),
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
	'miframe_server()->tempSubdir($path_dummy)' => $server->tempSubdir($path_dummy),
);

// Captura información para mostrar
$matches = miframe_autoload()->matches();
$namespaces = miframe_autoload()->namespaces();

// Recupera variables a mostrar
$variables_data = compact('path_dummy', 'script', 'args');

$aviso_ocultar = '';
// Por razones de seguridad, se ocultan algunos valores cuando no se ejecuta desde localhost
if (!$server->isLocalhost() || $force_no_localhost) {
	$ocultar = '[Restringido]';
	// Información sensible
	$data['miframe_server()->name()'] = $ocultar;
	// $data['miframe_server()->browser()'] = $ocultar;
	// $data['miframe_server()->ip()'] = $ocultar;
	$data['miframe_server()->webserver()'] = $ocultar;
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
	foreach ($variables_data as $k => $v) {
		if (!is_array($v)) {
			$variables_data[$k] = trim(str_replace($que, $con, $v));
		}
	}
	// Mensaje informando de estos valores protegidos
	$aviso_ocultar = '<p class="test-aviso"><b>Importante:</b> Algunos valores se han ocultado por seguridad.</p>';
}

echo "<p>Variables usadas para crear el arreglo de muestra:</p>" . $aviso_ocultar;

$Test->dump($variables_data, true);

echo "<p>Métodos disponibles:</p>";

ksort($data);
// Adiciona puntos de chequeo
$data['miframe_server()->executionTime()'] = $server->executionTime();
// $data['miframe_server()->checkPoint() [Fin]'] = $server->checkPoint();

$Test->dump($data, true);

echo "<h2>miframe_autoload()</h2>";
echo "<p>Respecto al uso de la librería <code>autoload.php</code>, " .
	"estas son las Clases evaluadas durante esta presentación:</p>";

$Test->dump($matches);

echo "<p>Y estos son los <i>namespaces</i> registrados:</p>";

$Test->dump($namespaces);

timecheck('FIN');

// Cierre de la página
$Test->end();
