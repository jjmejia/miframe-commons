<?php

/**
 * Adiciona marcas en pantalla para identificar la vista que lo genera.
 *
 * @author John Mejía
 * @since Noviemre 2024
 */

$styles = PHP_EOL;

if (miframe_render()->once()) {
	// Estilos a usar, se declaran una única vez
	$styles .= '
<style>
/* showFrameContentDebug */
.mvsfod {
padding:0;margin:2px 0;
.mvsfod-title { padding:0 4px;float:left;border-radius:0 0 4px 0;background:#777;color:#fff;font-size:9pt;font-family:Consolas,Arial }
.mvsfod-title b { color:#eee; }
.mvsfod-content { padding:2px;padding-top:18px;margin:0;border:2px solid #ccc; }
.mvsfod-title-layout { background-color:darkred; }
.mvsfod-title-view { background-color:#135f9d; }
.mvsfod-content-layout { border-color:darkred; }
.mvsfod-content-view { border-color:#135f9d; }
}
</style>' . PHP_EOL;
}

$model = 'layout';
// Valida si está mostrando views o layout
if ($target !== md5('layout')) {
	$model = 'view';
}
$utarget = strtoupper($model);

$content = $styles .
	"<!-- Inicia {$filename} -->" . PHP_EOL .
	"<div class=\"mvsfod\">" .
	"<span class=\"mvsfod-title mvsfod-title-{$model}\">" . PHP_EOL .
	"<b>{$utarget}</b> {$filename}" . PHP_EOL .
	"</span>" .
	"<div class=\"mvsfod-content mvsfod-content-{$model}\">" . PHP_EOL .
	$content . PHP_EOL .
	"</div>" .
	"</div>" . PHP_EOL .
	"<!-- Finaliza {$filename} -->" . PHP_EOL;

echo $content;