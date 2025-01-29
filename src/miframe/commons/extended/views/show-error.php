<?php

/**
 * showError
 *
 * Visualiza mensajes de error.
 *
 * Puede personalizarse extendiendo esta clase y redefiniendo este método.
 */

$styles = PHP_EOL;

// Captura referencia al render
$render = miframe_render();

if ($render->once()) {
  	// Estilos a usar, se declaran una única vez.
	// Sin embargo, deben publicarse SI O SI, aunque exista un
	// Fatal Error posteriormente.
	$styles = '
<style>
/* showError */
.mvse {
	background-color:#fadbd8;border:2px solid #e74c3c;margin:4px 1px;padding:10px;font-size:10pt;font-family:Segoe UI,Arial;
	p { margin:0; margin-bottom:8px; }
	.mvse-partial { background-color:#555;color:#f4f4f4;margin:0;margin-top:10px;padding:5px; }
	.mvse-pre { background-color:#f4f4f4;color:#333;border:1px dashed #777;border-top:none;margin:0;padding:5px;font-family:Consolas;max-width:100%;overflow:auto; }
	.mvse-label { background:#e74c3c;color:#fff;font-weight:bold;padding:5px;margin:0;margin-bottom:10px; }
}
</style>' . PHP_EOL;
}

// echo "<pre>"; print_r($view_args); echo "</pre><hr>";

$info = '';

// Etiquetas especiales
if ($end_script && $render->inDeveloperMode()) {
	$label = 'Script interrumpido. Modo Desarrollo activo (developerMode)';
	$info = '<div class="mvse-label">' . $label . '</div>';
}

// Mensaje de error
$info .= "<p><b>{$type_name}<!--$type--></b></p><p>" . nl2br($message) . "</p>";

// Adiciona archivo referido
if ($file != '' && $line > 0) {
	$info .= "<p>Reportado en <b>{$file}</b> línea <b>$line</b></p>";
}
// Da formato a contenido capturado (si alguno)
if ($buffer != '') {
	$buffer = htmlspecialchars($buffer);
	$buffer = $render->dump($buffer, 'Contenido parcial', false);
}
// Backtrace
$infotrace = '';
if (is_array($trace)) {
	foreach ($trace as $data) {
		// No reporta file por ejemplo cuando invoca trigger_error() y ejecuta una función
		// personalizada, como "localError".
		if (!empty($data['file'])) {
			$function = $data['function'];
			if (!empty($data['class'])) {
				$function = "{$data['class']}</b>{$data['type']}<b>{$function}";
			}
			$infotrace .= "<p>{$data['file']} : {$data['line']} - <b>{$function}</b></p>";
		}
	}
}

if ($infotrace != '') {
	// Enmarca valores
	$infotrace = $render->dump($infotrace, 'Backtrace', false);
}

// Adiciona valor del $_SERVER (solamente para localhost y al primer error presente)
if ($end_script && miframe_server()->isLocalhost()) {
	$infotrace .= $render->dump($_SERVER, '$_SERVER');
	$infotrace .= $render->dump($_REQUEST, '$_REQUEST', ignore_empty: true);
	$infotrace .= $render->dump($_FILES, '$_FILES', ignore_empty: true);
	$infotrace .= $render->dump($_SESSION, '$_SESSION', ignore_empty: true);
}

$info = $styles .
	'<div class="mvse">' .
	$info .
	$buffer .
	$infotrace .
	'</div>';

echo $info;
