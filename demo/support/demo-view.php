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

// Opciones para configuración de layout:
// Usar layout personalizado
if (!$Test->choice('uselayoutdef', 'Usar Layout por defecto', 'Usar Layout personalizado')) {
	$Test->copyNextLines();
	$view->layout->set('demo-layout');
}
// Remueve layout personalizado o por defecto
if ($Test->choice('nolayout', 'Remover Layout', 'Usar Layout personalizado')) {
	$Test->copyNextLines();
	$view->layout->remove();
}

// Recupera vista seleccionada
$post_view = $Test->getParam('view', $views_list);

// Valores a usar en layout
$Test->copyNextLines();
$view->layout->values(['title' => $views_list[$post_view], 'uid' => uniqid()]);
// Adiciona objeto $Test para manejo en la vista demo (se hace aparte para no visualizarlo en pantalla,
// para efectos de que la vista no la visualiza)
$view->layout->values(['Test' => $Test]);

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
	// Multiples views(replica la vista "a" varias veces)
	$Test->showNextLines(4);
	echo miframe_view('demo-m', ['index' => 1]);
	echo miframe_view('demo-m', ['index' => 2]);
	$view->layout->reset();
	echo miframe_view('demo-m', ['index' => 3]);


	/*for ($num_view = 1; $num_view <=3; $num_view++) {
		// Redefine valores para el layout
		$view->layout->values(['title' => "Vista regular #{$num_view}"]);
		// Habilita visualización de layout (se inhabilita luego de su primer uso)
		$view->layout->reset();
		// Actualiza valores para $dato1
		$dato1 = "Esta es la variable *dato1* de la copia #{$num_view}";
		// Visualiza cada vista con los nuevos valores
		echo miframe_view('a', compact('dato1', 'dato2'));
	}*/
}


// Cierre de la página
$Test->end();
