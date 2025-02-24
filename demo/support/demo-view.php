<?php

/**
 * Demo para pruebas de las funciones miframe_view().
 *
 * @author John Mejía
 * @since Noviembre 2024
 */

// Configuración de demo, crea objeto $Test
include_once __DIR__ . '/../demo-config.php';

// Apertura de la página demo
$Test->title = 'miframe_render() y miframe_view()';
$Test->description = 'Demos para ilustrar uso del utilitario <code>miframe_render()</code> y <code>miframe_view()</code> de la librería <code>miFrame\\Commons</code>, para visualización de páginas en pantalla.';
$Test->useMiFrameErrorHandler = false;
$Test->start();

// Asocia clase a una variable para agilizar su uso.
$view = miframe_render();

// Habilita modo developer (habilita dumps y uso del modo Debug)
if ($Test->choice('developerMode', 'Modo Desarrollo', 'Habilitar modo Producción')) {
	$Test->copyNextLines();
	$view->developerOn();
	// Habilita errores personalizados
	miframe_errors();
}

// Habilita modo Debug (identifica cada view usado en pantalla)
if ($Test->choice('debug', 'Modo Debug ', 'Remover modo Debug')) {
	$Test->copyNextLines();
	$view->debug = true;
}

// Directorio donde ubicar el layout y las vistas
$view->location(__DIR__ . DIRECTORY_SEPARATOR . 'demo-view-files');

// Lista vistas disponibles
$views_list = [
	'a' => 'Vista regular',
	// 'b' => 'Vista con errores',
	'c' => 'Invocando view() dentro de otro view()',
	'd' => 'Multiples views()',
	'novista' => 'Vista no existente'
];

// Crea enlaces para selección de las vistas
$views_links = $Test->multipleLinks('view', $views_list);

// Muestra opciones solamente cuando se tienen múltiples vistas
if (count($views_list) > 1) {
	echo "<p><b>Vistas:</b> {$views_links}</p>";
}

// Opciones para configuración de layout:
// Usar layout personalizado
if (!$Test->choice('uselayoutdef', 'Usar Layout por defecto', 'Usar Layout personalizado')) {
	$Test->copyNextLines();
	$view->layout->config('layout', 'content_view');
}
// Remueve layout personalizado o por defecto
if ($Test->choice('nolayout', 'Remover Layout', 'Usar Layout personalizado')) {
	$Test->copyNextLines();
	$view->layout->remove();
}

// Recupera vista seleccionada
$post_view = $Test->getParam('view', $views_list);

// Valores a usar en layout
$view->layout->values(['title' => $views_list[$post_view], 'uid' => uniqid()]);

// Visualiza opciones
echo '<p><b>Opciones:</b> ' . $Test->renderChoices('', true) . '</p>';

// Valores a usar para invocar la vista
$dato1 = 'Esta es la variable *dato1* de la vista ' . strtoupper($post_view);
$dato2 = time();

if ($post_view !== 'd') {
	// Visualiza comando
	$Test->htmlCode(
		"miframe_render()->layout->values(['title' => '{$views_list[$post_view]}', 'uid' => uniqid()]);" .
		PHP_EOL .
		$Test->pasteLines(['$view' => 'miframe_render()']) .
		"echo miframe_view('{$post_view}', compact('dato1', 'dato2'));");

	// Para mostrar en pantalla
	echo miframe_view($post_view, compact('dato1', 'dato2', 'Test'));

} else {

	// Comando previo
	$Test->htmlCode(
		"miframe_render()->layout->values(['uid' => uniqid()]);" .
		PHP_EOL .
		$Test->pasteLines(['$view' => 'miframe_render()'])
	);
	// Multiples views(replica la vista "a" varias veces)
	$dato1_pre = $dato1;
	for ($p = 1; $p <=3; $p++) {
		// Redefine valores
		$ptitle = 'Vista regular #' . $p;
		$view->layout->values(['title' => $ptitle]);
		// Visualiza comando
		$Test->htmlCode(
			"miframe_render()->layout->values(['title' => '{$ptitle}']);" .
			PHP_EOL .
			"echo miframe_view('a', compact('dato1', 'dato2'));"
		);
		// Habilita layout (se inhabilita luego de su primer uso)
		$view->layout->reset();
		// Para mostrar en pantalla
		$dato1 = $dato1_pre . ' [En copia #' . strtoupper($p) . ']';
		echo miframe_view('a', compact('dato1', 'dato2', 'Test'));
	}
}


// Cierre de la página
$Test->end();
