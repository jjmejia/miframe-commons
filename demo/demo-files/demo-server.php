<?php
/**
 * Demo para pruebas de las funciones miframe_autoload() y miframe_server().
 *
 * @author John Mejía
 * @since Julio 2024
 */

include_once __DIR__ . '/lib/testfunctions.php';
include_once miframe_test_src_path() . '/miframe/commons/autoload.php';
include_once miframe_test_src_path() . '/miframe/commons/helpers.php';

miframe_test_start('miframe_autoload() y miframe_server()');

echo "<p>Ejemplos de uso para <code>miframe_server()</code>:</p>";

// Asocia clase a una variable para agilizar su uso.
$server = miframe_server();

$path_dummy = 'path/to/other/script';

$data = array(
	'$path_dummy' => $path_dummy,
	'miframe_server()->get(\'REQUEST_METHOD\')' => $server->get('REQUEST_METHOD'),
	'miframe_server()->client()' => $server->client(),
	'miframe_server()->isWeb()' => $server->isWeb(),
	'miframe_server()->isLocalhost()' => $server->isLocalhost(),
	'miframe_server()->software()' => $server->software(),
	'miframe_server()->name()' => $server->name(),
	'miframe_server()->ip()' => $server->ip(),
	'miframe_server()->path()' => $server->path(),
	'miframe_server()->pathInfo()' => $server->pathInfo(),
	'miframe_server()->host()' => $server->host(),
	'miframe_server()->useHTTPSecure()' => $server->useHTTPSecure(),
	'miframe_server()->host($path_dummy)' => $server->host($path_dummy),
	'miframe_server()->host($path_dummy, true)' => $server->host($path_dummy, true),
	'miframe_server()->self()' => $server->self(),
	'miframe_server()->self($path_dummy)' => $server->self($path_dummy),
	'miframe_server()->cleanPath(\'/../path1\\path2\\./ignorar/..//path3/\', \'|\')' => $server->cleanPath('/../path1\\path2\\./ignorar/..//path3/', '|'),
	'miframe_server()->cleanFilePath(\'/../path1\\path2\\./ignorar/..//path3/\', \'|\')' => $server->cleanFilePath('/../path1\\path2\\./ignorar/..//path3/', '|'),
	'miframe_server()->mkdir($path_dummy)' => $server->mkdir($path_dummy),
	'miframe_server()->documentRoot()' => $server->documentRoot(),
	'miframe_server()->documentRoot($path_dummy)' => $server->documentRoot($path_dummy),
	'miframe_server()->script()' => $server->script(),
	'miframe_server()->script($path_dummy)' => $server->script($path_dummy),
	'miframe_server()->tempDir()' => $server->tempDir(),
	'miframe_server()->createTempSubdir($path_dummy)' => $server->createTempSubdir($path_dummy),
	'miframe_server()->browser()' => $server->browser(),
	'miframe_server()->rawInput()' => $server->rawInput(),
	'miframe_server()->inDocumentRoot($path_dummy)' => $server->inDocumentRoot($path_dummy),
	'miframe_server()->inDocumentRoot($this->script())' => $server->inDocumentRoot($server->script()),
	'miframe_server()->inTempDir($path_dummy)' => $server->inTempDir($path_dummy),
	'miframe_server()->inTempDir($this->script())' => $server->inTempDir($server->script()),
	'miframe_server()->documentRootSpace()' => $server->documentRootSpace(),
	'miframe_server()->tempDirSpace()' => $server->tempDirSpace(),
);

ksort($data);

miframe_test_dump($data);

echo "<p>Y respecto al uso de la librería <code>autoload.php</code>, estas son las Clases evaluadas durante esta presentación:</p>";

miframe_test_dump(miframe_autoload()->matches());

echo "<p>y estos los <i>namespaces</i> registrados:</p>";

miframe_test_dump(miframe_autoload()->namespaces());