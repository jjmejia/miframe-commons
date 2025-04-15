<?php

/**
 * Demo para pruebas de las funciones miframe_html().
 *
 * @author John Mejía
 * @since Octubre 2024
 */

// Configuración de demo, crea objeto $Test
include_once __DIR__ . '/../demo-config.php';

$Test->title = 'miframe_html()';
$Test->description = 'Demos para ilustrar uso del utilitario <code>miframe_html()</code> de la librería <code>miFrame\\Commons</code>, para visualización de mensajes en pantalla.';
$Test->start();

// Asocia clase a una variable para agilizar su uso.
$Test->copyNextLines();
$html = miframe_html();

// Captura opciones
if ($Test->choice('css-nominimize', 'No minimizar estilos en línea', 'Minimizar estilos en línea')) {
	$Test->copyNextLines();
	// Por defecto el valor es "true"
	$html->minimizeCSSCode(false);
}

// Adiciona un archivo CSS existente en disco
$Test->copyNextLines();
$html->cssLocal(__DIR__ . '/demo-html-files/uno.css');

// Adiciona contenido en linea
$Test->copyNextLines();
$html->cssLocal(__DIR__ . '/demo-html-files/dos.css', true);

// Adiciona un recurso CSS indicando su URL, se publica
// apuntando a su ubicación remota.
$Test->copyNextLines();
$html->cssRemote(miframe_server()->relativePath('demo-html-files/tres.css'));

// Adiciona un recurso CSS directamente en línea
$Test->copyNextLines(5);
$html->cssInLine('
.miframe-cuatro {
	background:darkred;
	color:lightcoral;
}');

// Otro bloque en linea
$Test->copyNextLines(4);
$html->cssInLine(
	'.demo-div { margin:10px 0; padding:20px; border-radius:4px; }',
	'Comentario en línea'
);

$Test->copyNextLines(3);
// Nota: Estos elementos duplicados, serán ignorados
$html->cssLocal(__DIR__ . '/demo-html-files/uno.css');
$html->cssLocal(__DIR__ . '/demo-html-files/uno.css', true);

// Muestra líneas capturadas
$Test->htmlPasteLines();

// Descarga estilos
echo '<h3>HTML generado al procesar los ' . $html->cssUnpublished() . ' recursos previamente configurados</h3>';
$nocomentar = $Test->choice('no-comments', 'Ocultar comentarios', 'Incluir comentarios');
$Test->showNextLines(1, ['!$nocomentar' => ($nocomentar ? 'false' : 'true')]);
$code = $html->cssExport(!$nocomentar);
echo $code;

$Test->htmlPre(
	htmlentities($code).
	'<div class="separator"></div>' .
	$Test->renderChoices()
	// '</div>'
);

// Recupera de archivo puntual
echo '<h3>Exportar estilos tomados de un archivo</h3>';
$Test->showNextLines();
$styles = $html->cssExportFrom(__DIR__ . '/demo-html-files/cinco.css', true);
echo $styles;

$Test->htmlPre(htmlentities($styles));

// Ejemplos de estilos
echo '<h3>Ejemplo de los estilos cargados</h3>';
echo '<div class="demo-div miframe-uno"><b>miframe-uno:</b> Estilos de cssLocal() como URL</div>' . PHP_EOL;
echo '<div class="demo-div miframe-dos"><b>miframe-dos:</b> Estilos de cssLocal() en línea</div>' . PHP_EOL;
echo '<div class="demo-div miframe-tres"><b>miframe-tres:</b> Estilos de cssRemoto()</div>' . PHP_EOL;
echo '<div class="demo-div miframe-cuatro"><b>miframe-cuatro:</b> Estilos de cssInLine()</div>' . PHP_EOL;
echo '<div class="demo-div miframe-cinco"><b>miframe-cinco:</b> Estilos de cssExportFrom()</div>' . PHP_EOL;

// Este demo solamente funciona bien para localhost, en remoto pueden estar
// inactivos los mensajes de error y habilitarlos implica visualizar el path real
// de los scripts.
echo '<h3>Ejemplo al adicionar un recurso no valido</h3>';
$Test->showNextLines();
$html->cssLocal(__DIR__ . '/demo-html-files/nn.css');

// Cierre de la página
$Test->end();
