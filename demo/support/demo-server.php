<?php
/**
 * Demo para pruebas de las funciones miframe_autoload() y miframe_server().
 *
 * @author John Mejía
 * @since Julio 2024
 */

require_once __DIR__ . '/lib/testfunctions.php';
require_once miframe_test_src_path() . '/miframe/commons/autoload.php';
require_once miframe_test_src_path() . '/miframe/commons/helpers.php';

miframe_test_start('miframe_server() y miframe_autoload()');

// Asocia clase a una variable para agilizar su uso.
$server = miframe_server();

$path_dummy = '../path/to/other/script/ignora/..';

$data = array(
	// Limpieza
	'miframe_server()->purgePath($path_dummy)' => $server->purgePath($path_dummy),
	'miframe_server()->purgeFilename($path_dummy)' => $server->purgeFilename($path_dummy),
	// 'miframe_server()->purgePath(\'/../path1\\path2\\./ignorar/..//path3/\', \'|\')' => $server->purgePath('/../path1\\path2\\./ignorar/..//path3/', '|'),
	// 'miframe_server()->purgeFilename(\'/../path1\\path2\\./ignorar/..//path3/\', \'|\')' => $server->purgeFilename('/../path1\\path2\\./ignorar/..//path3/', '|'),
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
	'miframe_server()->local()' => $server->local(),
	'miframe_server()->local($path_dummy)' => $server->local($path_dummy),
	'miframe_server()->documentRoot()' => $server->documentRoot(),
	'miframe_server()->documentRoot($path_dummy)' => $server->documentRoot($path_dummy),
	'miframe_server()->documentRootSpace()' => $server->documentRootSpace(),
	'miframe_server()->script()' => $server->script(),
	'miframe_server()->localScript()' => $server->localScript(),
	'miframe_server()->localScript($path_dummy)' => $server->localScript($path_dummy),
	'miframe_server()->tempDir()' => $server->tempDir(),
	'miframe_server()->tempDirSpace()' => $server->tempDirSpace(),
	'miframe_server()->startAt()' => $server->startAt() . ' (' . date('Y/m/d H:i:s', $server->startAt()). ')',
	'miframe_server()->executionTime()' => $server->executionTime(),
	// Validaciones
	'miframe_server()->inDocumentRoot($path_dummy)' => $server->inDocumentRoot($path_dummy),
	'miframe_server()->inDocumentRoot($this->script())' => $server->inDocumentRoot($server->script()),
	'miframe_server()->inTempDir($path_dummy)' => $server->inTempDir($path_dummy),
	// 'miframe_server()->inTempDir($this->script())' => $server->inTempDir($server->script()),
	// Acciones sobre el servidor
	'miframe_server()->mkdir($path_dummy)' => $server->mkdir($path_dummy),
	'miframe_server()->createTempSubdir($path_dummy)' => $server->createTempSubdir($path_dummy),
);

$aviso_ocultar = '';
// Por razones de seguridad, se ocultan algunos valores cuando no se ejecuta desde localhost
if (!$server->isLocalhost()) {
	$ocultar = '[Restringido]';
	// Información sensible
	$data['miframe_server()->name()'] = $ocultar;
	$data['miframe_server()->browser()'] = $ocultar;
	$data['miframe_server()->ip()'] = $ocultar;
	$data['miframe_server()->software()'] = $ocultar;
	// Oculta directorios
	$que = [ $server->tempDir(), $server->script(false), $server->documentRoot() ];
	$con = [ '[TEMP_DIR] ', 	 '[SCRIPT_FILENAME] ',	 '[DOCUMENT_ROOT] ' ];
	foreach ($data as $k => $v) {
		$data[$k] = trim(str_replace($que, $con, $v));
	}
	// Mensaje informativo
	$aviso_ocultar = '<p><b>Importante:</b> Algunos valores se han ocultado por seguridad, pero son visibles para consultas dede Localhost.</p>';
}

// ksort($data);

echo "<h2>miframe_server()</h2>";
echo "<p>Ejemplos de uso:</p>" . $aviso_ocultar;

miframe_test_dump([ '$path_dummy' => $path_dummy ]);
miframe_test_dump($data);

echo "<h2>miframe_autoload()</h2>";
echo "<p>Respecto al uso de la librería <code>autoload.php</code>, " .
	"estas son las Clases evaluadas durante esta presentación:</p>";

miframe_test_dump(miframe_autoload()->matches(), 'miframe_autoload()->matches()');

echo "<p>Y estos son los <i>namespaces</i> registrados:</p>";

miframe_test_dump(miframe_autoload()->namespaces(), 'miframe_autoload()->namespaces()');