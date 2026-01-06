<?php

/**
 * Visualiza volcado de datos en pantalla.
 *
 * @author John Mejía
 * @since Noviemre 2024
 */

$text = PHP_EOL;

if (miframe_render()->once())
{
	// Estilos a usar, se declaran una única vez
	$styles = '
.mfsd {
	padding:0;margin:2px 0;
	.mfsd-title { padding:0 4px;float:left;border-radius:0 0 4px 0;background-color:#555;color:#fff;font-size:9pt;font-family:Consolas,Arial; }
	.mfsd-title b { color:#eee; }
	.mfsd-content { padding:2px 8px;padding-top:18px;margin:0;border:1px dashed #777;border-radius:4px;background-color:#f4f4f4;color:#333; }
	.mfsd-content pre { font-family:Consolas;font-size:9pt;max-width:100%;max-height:300px;overflow:auto; }
	p { margin:0; margin-bottom:5px; }
}
';
	// Adiciona al repositorio de estilos
	miframe_render()->saveStyles($styles, 'showDump');
}

$text .= PHP_EOL . "<div class=\"mfsd\">" .
	"<span class=\"mfsd-title\">" .
	"{$title}" . PHP_EOL .
	"</span>" .
	"<div class=\"mfsd-content\">" . PHP_EOL .
	'<pre>' .
	$var .
	'</pre>' . PHP_EOL .
	"</div>" .
	"</div>" . PHP_EOL;

echo $text;
