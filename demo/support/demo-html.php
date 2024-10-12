<?php
/**
 * Demo para pruebas de las funciones miframe_html().
 *
 * @author John Mejía
 * @since Octubre 2024
 */

include_once __DIR__ . '/lib/miCodeTest.php';

$Test = new miCodeTest();

include_once $Test->includePath('/miframe/commons/autoload.php');
include_once $Test->includePath('/miframe/commons/helpers.php');

$Test->start(
	'miframe_html()',
	'Demos para ilustrar uso del utilitario <code>miframe_html()</code> de la librería <code>miFrame\\Commons</code>, para visualización de mensajes en pantalla.'
	);

// Asocia clase a una variable para agilizar su uso.
$html = miframe_html();

// En el caso de los estilos remotos, cuando se indique su descarga,
// el sistema lo intentará y generará un archivo de control para
// prevenir múltiples descargas del mismo recurso. Este caché se preservará
// el número de segundos indicado como parámetro o cómo configuración
// global, usando el método:

// $html->cacheTimeOut(3600);

// Por defecto, se usará el directorio temporal para registro del caché,
// pero puede indicarse un path alterno, esto mediante el método:

// $html->cachePath($path);

// Adiciona un archivo CSS existente en disco
$html->cssLocal(__DIR__ . '/demo-html-files/uno.css');

// Adiciona contenido en linea
$html->cssLocal(__DIR__ . '/demo-html-files/dos.css', true);

// Adiciona un recurso CSS indicando su URL, se publica
// apuntando a su ubicación remota.
$url = miframe_server()->relativePath('demo-html-files/tres.css');
$html->cssRemote($url);

// Adiciona un recurso CSS indicando su URL, se publica
// capturando su contenido e incluyendolo en línea. Si no
// es posible, lo registra remoto.
// $url = miframe_server()->relativePath('demo-html-files/tres.css');
// $html->cssRemote($url, 3600);

// Adiciona un recurso CSS directamente en línea
$html->cssInLine('
.miframe-cuatro {
	background:darkred;
	color:lightcoral;
}');
// Otro bloque en linea
$html->cssInLine('.demo-div { margin:10px 0; padding:20px; border-radius:4px; }');

// Duplica para validar que no repita
$html->cssLocal(__DIR__ . '/demo-html-files/uno.css');
$html->cssLocal(__DIR__ . '/demo-html-files/uno.css', true);

// Recupera de archivo puntual
$styles = $html->cssExportFrom(__DIR__ . '/demo-html-files/cinco.css', true);

echo '<p>Listado de estilos pendientes:</p>';
$Test->htmlPre(print_r($html->cssUnpublished(), true));

// Descarga estilos
$code = $html->cssExport();

echo '<p>Resultado al procesar pendientes:</p>';
$Test->htmlPre(htmlentities($code));

echo '<p>Resultado al usar cssExportFrom():</p>';
$Test->htmlPre(htmlentities($styles));

echo $code . $styles;

// Ejemplos de estilos
echo '<p>Ejemplo de los estilos cargados:</p>';

echo '<div class="demo-div miframe-uno"><b>miframe-uno:</b> Estilos de cssLocal() como URL</div>' . PHP_EOL;
echo '<div class="demo-div miframe-dos"><b>miframe-dos:</b> Estilos de cssLocal() en línea</div>' . PHP_EOL;
echo '<div class="demo-div miframe-tres"><b>miframe-tres:</b> Estilos de cssRemoto()</div>' . PHP_EOL;
echo '<div class="demo-div miframe-cuatro"><b>miframe-cuatro:</b> Estilos de cssInLine()</div>' . PHP_EOL;
echo '<div class="demo-div miframe-cinco"><b>miframe-cinco:</b> Estilos de cssExportFrom()</div>' . PHP_EOL;

echo '<p>Ejemplo al adicionar un recurso no valido</p>';
$html->cssLocal(__DIR__ . '/demo-html-files/nn.css');

echo "<h2>Repositorio</h2>";
echo '<p style="margin-top:30px"><a href="https://github.com/jjmejia/miframe-commons/tree/01-miframe-server-y-miframe-autoload" target="_blank">Repositorio disponible en <b>github.com/jjmejia</b></a></p>';

// Registra visita
$Test->visitorLog('demo-html');

// Cierre de la página
$Test->end();
