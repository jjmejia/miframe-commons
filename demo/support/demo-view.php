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
$Test->start();

// Asocia clase a una variable para agilizar su uso.
$view = miframe_render();

// Habilita modo developer (habilita dumps y uso del modo Debug)
if ($Test->choice('developerMode', 'Modo Desarrollo', 'Habilitar modo Producción')) {
	$Test->copyNextLines();
	$view->developerOn();
}

// Habilita modo Debug (identifica cada view usado en pantalla)
if ($Test->choice('debug', 'Modo Debug ', 'Remover modo Debug')) {
	$Test->copyNextLines();
	$view->debug = true;
}

// Directorio donde ubicar el layout y las vistas
$Test->copyNextLines();
$view->location(__DIR__ . DIRECTORY_SEPARATOR . 'demo-view-files');

// Lista vistas disponibles
$views_list = [
	'demo-a' => 'Vista regular',
	// 'b' => 'Vista con errores',
	'demo-c' => 'Invocando view() dentro de otro view()',
	'multiples' => 'Multiples vistas',
	'novista' => 'Vista no existente'
];

// Crea enlaces para selección de las vistas
$views_links = $Test->multipleLinks('view', $views_list);

// Muestra opciones solamente cuando se tienen múltiples vistas
if (count($views_list) > 1) {
	echo "<p><b>Vistas:</b> {$views_links}</p>";
}

// Remueve layout en uso
if ($Test->choice('nolayout', 'Remover Layout', 'Usar Layout personalizado')) {
	$Test->copyNextLines();
	$view->layoutRemove();
} else {
	$Test->copyNextLines();
	$view->layout('demo-layout');
}

// Recupera vista seleccionada
$post_view = $Test->getParam('view', $views_list);

// Valores a usar en layout
$Test->copyNextLines();
$view->globals(['title' => $views_list[$post_view], 'uid' => uniqid()]);
// Adiciona objeto $Test para manejo en la vista demo (se hace aparte para no visualizarlo en pantalla,
// para efectos de que la vista no la visualiza)
$view->globals(['Test' => $Test]);

// Visualiza opciones
echo '<p><b>Opciones:</b> ' . $Test->renderChoices('', true) . '</p>';

// Valores a usar para invocar la vista
$dato1 = 'Esta es la variable *dato1* de la vista ' . strtoupper($post_view);
$dato2 = time();

// Comando previo
$Test->htmlPasteLines([
	'$views_list[$post_view]' => "'{$views_list[$post_view]}'",
	'$view' => 'miframe_render()'
]);

if ($post_view !== 'multiples') {
	// Para mostrar en pantalla
	$Test->showNextLines(1, ['$post_view' => "'{$post_view}'"]);
	echo miframe_view($post_view, compact('dato1', 'dato2'));
} else {
	// Multiples views
	$Test->showNextLines(4);
	echo miframe_view('demo-a', compact('dato1', 'dato2'));
	echo miframe_view('demo-m', ['index' => 1]);
	$view->layoutReset();
	echo miframe_view('demo-m', ['index' => 2]);
}


// Cierre de la página
$Test->end();
