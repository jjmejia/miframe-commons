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
$Test->start(
	'Manejo de errores',
	'Esta demo ilustra el uso de la clase <code>ErrorHandler</code> usada para el manejo de errores en PHP.'
);

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
}

// Directorio donde ubicar el layout y las vistas
$view->location(__DIR__ . DIRECTORY_SEPARATOR . 'demo-view-files');

// Lista vistas disponibles
$views_list = [
	'b' => 'Vista con errores',
	'e' => 'Otros ejemplos de uso',
];
// Adiciona la opción "Vista no existente" solo para Localhost,
// ya que al remover manejo personalizado de errores no existe
// forma de prevenir que muestra paths completos en entornos no seguros.
if (miframe_server()->isLocalhost()) {
	$views_list['x'] = 'PHP Fatal Error';
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
$view->layout->config('layout', 'content_view');

// Valores a usar en layout
$view->layout->values(['title' => $views_list[$post_view], 'uid' => uniqid()]);

// Visualiza opciones
echo '<p><b>Opciones:</b> ' . $Test->renderChoices('', true) . '</p>';

// Valores a usar para invocar la vista
$dato1 = 'Esta es la variable *dato1* de la vista ' . strtoupper($post_view);
$dato2 = time();

// Visualiza comando
$Test->htmlPre(
	"miframe_render()->layout->values(['title' => '{$views_list[$post_view]}', 'uid' => uniqid()]);" .
	PHP_EOL .
	str_replace('$view', 'miframe_render()', $Test->pasteLines()) .
	// "\$errors->watch();" . PHP_EOL .
	"echo miframe_view('{$post_view}', compact('dato1', 'dato2', 'errors', 'Test'));");

// throw new Exception('Exception manualmente generada', 300);

// Para mostrar en pantalla
echo $view->view($post_view, compact('dato1', 'dato2', 'errors', 'Test'));

// Cierre de la página
$Test->end();
