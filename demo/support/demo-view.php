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

// Estilo adicional para esta demo
echo '<style>.view-container { border:1px dashed #999;padding:10px 20px;margin:10px 0; h1 { margin-top:0; } }</style>' . PHP_EOL;

// Asocia clase a una variable para agilizar su uso.
$view = miframe_render();

// Habilita modo developer (habilita dumps y uso del modo Debug)
if ($Test->choice('developerMode', 'Modo Desarrollo', 'Habilitar modo Producción')) {
	$view->developerOn();
	$Test->context('miframe_render()->developerOn();');
}

// Habilita modo Debug (identifica cada view usado en pantalla)
if ($Test->choice('debug', 'Modo Debug ', 'Remover modo Debug')) {
	$view->debug = true;
	$Test->context('miframe_render()->debug = true;');
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

// Captura vista elegida por el usuario
$post_view = '';
if (isset($_GET['view'])) {
	$post_view = strtolower(trim($_GET['view']));
}
// Lista vistas disponibles
$views_list = [
	'a' => 'Vista regular',
	'b' => 'Vista con errores',
	'c' => 'Invocando view() dentro de otro view()',
	'd' => 'Multiples views()',
	'e' => 'Vista no existente'
];
// Proceso por defecto
if (!isset($views_list[$post_view])) {
	$post_view = 'a';
}
// Crea enlaces para selección de las vistas
$views_links = '';
foreach ($views_list as $k => $view_title) {
	if ($views_links != '') {
		$views_links .= ' | ';
	}
	if ($post_view == $k) {
		$views_links .= $view_title;
	} else {
		$data =  ['view' => $k] + $_GET;
		$views_links .= $Test->link($view_title, $data);
	}
}

echo "<p><b>Vistas:</b> {$views_links}</p>";

// Opciones para configuración de layout:
// Usar layout personalizado
if (!$Test->choice('uselayoutdef', 'Usar Layout por defecto', 'Usar Layout personalizado')) {
	$view->layout('layout');
	$Test->context("miframe_render()->layout('layout');");
}
// Remueve layout personalizado o por defecto
if ($Test->choice('nolayout', 'Remover Layout', 'Usar Layout personalizado')) {
	$view->removeLayout();
	$Test->context('miframe_render()->removeLayout();');
}

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
		$Test->getContext() .
		"echo miframe_view('{$post_view}', compact('dato1', 'dato2'));");

	// Contenedor
	echo '<div class="view-container">';
	// Para mostrar en pantalla
	echo miframe_view($post_view, compact('dato1', 'dato2'));
	// Cierra contenedor
	echo '</div>';
} else {

	// Comando previo
	$Test->htmlPre(
		"miframe_render()->globals(['uid' => uniqid()]);" .
		PHP_EOL .
		$Test->getContext()
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
		// Contenedor
		echo '<div class="view-container">';
		// Para mostrar en pantalla
		echo miframe_view($p, compact('dato1', 'dato2'));
		// Cierra contenedor
		echo '</div>';
	}
}


// Cierre de la página
$Test->end();
