<?php

/**
 * Demo para pruebas de las funciones miframe_view().
 *
 * @author John Mejía
 * @since Noviembre 2024
 */

// Configuración de demo, crea objeto $Test
include_once __DIR__ . '/../demo-config.php';

include_once $Test->includePath('/miframe/commons/autoload.php');
include_once $Test->includePath('/miframe/commons/helpers.php');

// Apertura de la página demo
$Test->start(
	'miframe_render() y miframe_view()',
	'Demos para ilustrar uso del utilitario <code>miframe_render()</code> y <code>miframe_view()</code> de la librería <code>miFrame\\Commons</code>, para visualización de páginas en pantalla.'
);

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

// Adiciona filtros para proteger paths.
// Remueve referencias al DOCUMENT ROOT para no revelar
// su ubicación en entornos no seguros.
// Para removerlo, usar $view->removeFilter('hideDocumentRoot')
$view->addLayoutFilter('hideDocumentRoot', function (string $content) {
	if (!miframe_server()->isLocalhost()) {
		$content = str_replace(
			[$_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR, $_SERVER['DOCUMENT_ROOT']],
			['', '[..]'],
			$content
		);
	}
	return $content;
});

// Directorio donde ubicar el layout y las vistas
$view->location(__DIR__ . DIRECTORY_SEPARATOR . 'demo-view-files');

// Lista vistas disponibles
$views_list = [
	'a' => 'Vista regular',
	'b' => 'Vista con errores',
	'c' => 'Invocando view() dentro de otro view()',
	'd' => 'Multiples views()',
];
// Adiciona la opción "Vista no existente" solo para Localhost,
// ya que al remover manejo personalizado de errores no existe
// forma de prevenir que muestra paths completos en entornos no seguros.
if (miframe_server()->isLocalhost()) {
	$views_list['e'] = 'Vista no existente';
}
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
	$view->layout('layout', 'content_view');
}
// Remueve layout personalizado o por defecto
if ($Test->choice('nolayout', 'Remover Layout', 'Usar Layout personalizado')) {
	$Test->copyNextLines();
	$view->removeLayout();
}

// Recupera vista seleccionada
$post_view = $Test->getParam('view', $views_list);

// Valores a usar en layout
$view->globals(['title' => $views_list[$post_view], 'uid' => uniqid()]);

// Visualiza opciones
echo '<p><b>Opciones:</b> ' . $Test->renderChoices('', true) . '</p>';

// Valores a usar para invocar la vista
$dato1 = 'Esta es la variable *dato1* de la vista ' . strtoupper($post_view);
$dato2 = time();

if ($post_view !== 'd') {
	// Visualiza comando
	$Test->htmlPre(
		"miframe_render()->globals(['title' => '{$views_list[$post_view]}', 'uid' => uniqid()]);" .
		PHP_EOL .
		str_replace('$view', 'miframe_render()', $Test->pasteLines()) .
		"echo miframe_view('{$post_view}', compact('dato1', 'dato2'));");

	// Para mostrar en pantalla
	echo miframe_view($post_view, compact('dato1', 'dato2'));

} else {

	// Comando previo
	$Test->htmlPre(
		"miframe_render()->globals(['uid' => uniqid()]);" .
		PHP_EOL .
		str_replace('$view', 'miframe_render()', $Test->pasteLines())
	);
	// Multiples views
	foreach ($views_list as $p => $ptitle) {
		if ($p == 'd') {
			break;
		}
		// Redefine valores
		$view->globals(['title' => $ptitle]);
		// Visualiza comando
		$Test->htmlPre(
			"miframe_render()->globals(['title' => '{$ptitle}']);" .
			PHP_EOL .
			"echo miframe_view('{$p}', compact('dato1', 'dato2'));"
		);
		// Habilita layout (se inhabilita luego de su primer uso)
		$view->resetLayout();
		// Para mostrar en pantalla
		echo miframe_view($p, compact('dato1', 'dato2'));
	}
}


// Cierre de la página
$Test->end();
