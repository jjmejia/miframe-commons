<?php

/**
 * Demo para pruebas de las funciones miframe_html().
 *
 * @author John Mejía
 * @since Octubre 2024
 */

// Configuración de demo, crea objeto $Test
include_once __DIR__ . '/../demo-config.php';

include_once $Test->includePath('/miframe/commons/autoload.php');
include_once $Test->includePath('/miframe/commons/helpers.php');

$Test->start(
	'miframe_html()',
	'Demos para ilustrar uso del utilitario <code>miframe_html()</code> de la librería <code>miFrame\\Commons</code>, para visualización de mensajes en pantalla.'
);

// Asocia clase a una variable para agilizar su uso.
$html = miframe_html();

// Captura opciones
$nominimizar = $Test->choice('css-nominimize', 'No minimizar estilos en línea', 'Minimizar estilos en línea');
$html->minimizeCSSCode(!$nominimizar);

// Adiciona un archivo CSS existente en disco
$html->cssLocal(__DIR__ . '/demo-html-files/uno.css');

// Adiciona contenido en linea
$html->cssLocal(__DIR__ . '/demo-html-files/dos.css', true);

// Adiciona un recurso CSS indicando su URL, se publica
// apuntando a su ubicación remota.
$url = miframe_server()->relativePath('demo-html-files/tres.css');
$html->cssRemote($url);

// Adiciona un recurso CSS directamente en línea
$html->cssInLine('
.miframe-cuatro {
	background:darkred;
	color:lightcoral;
}');

// Otro bloque en linea
$html->cssInLine(
	'.demo-div { margin:10px 0; padding:20px; border-radius:4px; }',
	'Comentario en línea'
);

// Duplica para validar que no repita
$html->cssLocal(__DIR__ . '/demo-html-files/uno.css');
$html->cssLocal(__DIR__ . '/demo-html-files/uno.css', true);

// Recupera de archivo puntual
$styles = $html->cssExportFrom(__DIR__ . '/demo-html-files/cinco.css', true);

/*
echo '<p>Listado de estilos pendientes: (' . $Test->renderChoices() . ')</p>';
$Test->dump($html->cssUnpublished());
*/

// Descarga estilos
$nocomentar = $Test->choice('no-comments', 'Ocultar comentarios', 'Incluir comentarios');
echo '<p>HTML generado al procesar los ' . $html->cssUnpublished() . ' recursos pendientes:</p>';
$code = $html->cssExport(!$nocomentar);
$Test->htmlPre(
	htmlentities($code).
	'<div style="margin-top:10px;border-top:1px solid #999;padding-top:10px">' .
	$Test->renderChoices() .
	'</div>'
);

echo '<p>Resultado al usar <code>cssExportFrom()</code>:</p>';
$Test->htmlPre(htmlentities($styles));

echo $code . $styles;

// Ejemplos de estilos
echo '<p>Ejemplo de los estilos cargados:</p>';

echo '<div class="demo-div miframe-uno"><b>miframe-uno:</b> Estilos de cssLocal() como URL</div>' . PHP_EOL;
echo '<div class="demo-div miframe-dos"><b>miframe-dos:</b> Estilos de cssLocal() en línea</div>' . PHP_EOL;
echo '<div class="demo-div miframe-tres"><b>miframe-tres:</b> Estilos de cssRemoto()</div>' . PHP_EOL;
echo '<div class="demo-div miframe-cuatro"><b>miframe-cuatro:</b> Estilos de cssInLine()</div>' . PHP_EOL;
echo '<div class="demo-div miframe-cinco"><b>miframe-cinco:</b> Estilos de cssExportFrom()</div>' . PHP_EOL;

// Este demo solamente funciona bien para localhost, en remoto pueden estar
// inactivos los mensajes de error y habilitarlos implica visualizar el path real
// de los scripts.
if (miframe_server()->isLocalhost()) {
	echo '<p>Ejemplo al adicionar un recurso no valido (habilitado sólo para Localhost)</p>';
	$html->cssLocal(__DIR__ . '/demo-html-files/nn.css');
}

// Cierre de la página
$Test->end();
