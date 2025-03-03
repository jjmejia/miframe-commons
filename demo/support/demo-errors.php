<?php

/**
 * Demo para pruebas de la clase ErrorHandler aplicada a
 * manejo personalizado de mensajes de error PHP.
 *
 * @author John Mejía
 * @since Diciembre 2024
 */

// Configuración de demo, crea objeto $Test
include_once __DIR__ . '/../demo-config.php';

// Apertura de la página demo
$Test->title = 'Manejo de errores';
$Test->description = 'Esta demo ilustra el uso de la clase <code>ErrorHandler</code> usada para el manejo de errores en PHP.';
$Test->useMiFrameErrorHandler = false;
$Test->start();

// Asocia clase a una variable para agilizar su uso.
$view = miframe_render();

// Habilita modo developer (habilita dumps y uso del modo Debug)
if ($Test->choice('developerMode', 'Modo Desarrollo', 'Habilitar modo Producción')) {
	$Test->copyNextLines();
	$view->developerOn();
}

// Crea manejador de errores
use miFrame\Commons\Core\ErrorHandler;
use miFrame\Commons\Extended\ExtendedRenderError;

$Test->copyNextLines();
$errors = new ErrorHandler();
$errors->sizeErrorLog = 2097152; // 2MB

if (!$Test->choice('userview', 'Remover vista de error personalizada', 'usar vista personalizada')) {
	$Test->copyNextLines(2);
	$render = new ExtendedRenderError();
	$errors->setRenderer($render);
	// Previene terminar al script al ejecutar en modo desarrollo
	if (!$Test->choice('endscript', 'Terminar script (modo Desarrollo)', 'No terminar script')) {
		$Test->copyNextLines();
		$render->inDeveloperModeEndScript = false;
	}
}

// Habilita uso del watch()
if (!$Test->choice('nowatch', 'Deshabilitar personalización de errores', 'No watch')) {
	$Test->copyNextLines();
	$errors->watch();
	$view->errorHandler($errors);
}

// Directorio donde ubicar el layout y las vistas
$view->location(__DIR__ . DIRECTORY_SEPARATOR . 'demo-view-files');

// Lista vistas disponibles
$views_list = [
	'demo-b' => 'Vista con errores',
	'demo-e' => 'Otros ejemplos de uso',
	'novista' => 'Vista no existente'
];
// Adiciona la opción "Vista no existente" solo para Localhost,
// ya que al remover manejo personalizado de errores no existe
// forma de prevenir que muestra paths completos en entornos no seguros.
if (miframe_server()->isLocalhost()) {
	$views_list['demo-x'] = 'PHP Fatal Error';
}

// Crea enlaces para selección de las vistas
$views_links = $Test->multipleLinks('view', $views_list);

// Muestra opciones solamente cuando se tienen múltiples vistas
if (count($views_list) > 1) {
	echo "<p><b>Vistas:</b> {$views_links}</p>";
}

// Recupera vista seleccionada
$post_view = $Test->getParam('view', $views_list);

// Adiciona layout a la vista
$view->layout('demo-layout');

// Valores a usar en layout
$view->globals(['title' => $views_list[$post_view], 'uid' => uniqid(), 'Test' => $Test]);

// Visualiza opciones
echo '<p><b>Opciones:</b> ' . $Test->renderChoices('', true) . '</p>';

// Valores a usar para invocar la vista
$dato1 = 'Esta es la variable *dato1* de la vista ' . strtoupper($post_view);
$dato2 = time();

// Para mostrar en pantalla
$Test->showNextLines(1, ['$view' => 'miframe_render()', '$post_view' => "'{$post_view}'"]);
echo $view->view($post_view, compact('dato1', 'dato2', 'errors'));

// Cierre de la página
$Test->end();
