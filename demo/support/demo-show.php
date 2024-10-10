<?php
/**
 * Demo para pruebas de las funciones miframe_autoload() y miframe_server().
 *
 * @author John Mejía
 * @since Octubre 2024
 */

include_once __DIR__ . '/lib/miCodeTest.php';

$Test = new miCodeTest();

include_once $Test->includePath('/miframe/commons/autoload.php');
include_once $Test->includePath('/miframe/commons/helpers.php');

$Test->start(
	'miframe_show() y miframe_box()',
	'Demos para ilustrar uso del utilitario <code>miframe_show()</code> y <code>miframe_box()</code> de la librería <code>miFrame\\Commons</code>, para visualización de mensajes en pantalla.'
	);

// Asocia clase a una variable para agilizar su uso.
$showme = miframe_show();

$showme->emulateConsole = $Test->choice('emulate-cli', 'Simular vista para Consola', 'Cancelar Simulación de Consola');

if (($ignore_tests_without_renderers = $Test->choice('no-renders', 'Remover renderers incluidos', 'Restablecer renderers incluidos'))) {
	$showme->noRenderers();
}

if ($Test->choice('no-css', 'Remover CSS incluidos', 'Restablecer CSS incluidos')) {
	// Sin estilos (debe hacerse antes del primer render)
	$showme->ignoreStyles();
}

// Visualiza opciones
echo '<p>' . $Test->renderChoices() . '</p>';

// 1. Todos los elementos texto definidos
$showme->title('Bienvenido')
	->body('Hola mundo')
	->footer('En caso de requerir soporte, contacte al <b>administrador</b> del sistema')
	->render();

// 2. Solo body
$showme->body('Hola mundo (solo body)')->render();

// 3. Uso del helper miframe_box()
echo miframe_box('Hola mundo', 'Bienvenido desde miframe_box()', 'En caso de <i>requerir</i> soporte, contacte al administrador del sistema');

echo '<h2>Estilos predefinidos</h2>';

$tipos = [
	'info' => 'Informativo',
	'warning' => 'Warning/Aviso',
	'alert' => 'Alerta por errores',
	'critical' => 'Errores críticos',
	];

// 4. Tipo informativo
// 5. Tipo warning
// 6. Tipo alert
// 7. Tipo critical
foreach ($tipos as $class => $title) {
	if ($class == 'critical') {
		$showme->footer('Ocurrido en ' . basename(__FILE__) . ' línea ' . __LINE__);
	}
	$showme->title('Tipo ' . $title)
	->body("Mensaje creado usando <code>miframe_show()->class('{$class}')</code>.")
	->class($class)
	->render();
}

echo '<h2>Estilos personalizados</h2>';

echo '<p>Esto ocurre al intentar remplazar el archivo principal de estilos cuando ya se ha usado render() (puede ocultarse el mensaje de error usando "@" al inicio de la función):</p>';
// Genera error
miframe_show()->css(__DIR__ . '/demo-show-files/personalizado.css', true);

echo '<p>A continuación, ejemplos funcionales de personalización de estilos:</p>';

// 10. Cambia estilos en línea
$showme->style('.in-line { background-color:#ddd; font-weight:bold; font-style:normal; padding:0 5px; border-radius:4px; }');
echo miframe_box('Usando <i class="in-line">estilos personalizados in-line</i> en el cuerpo de este mensaje usando <code>miframe_show()->style(...)</code> y la función <code>miframe_box()</code>.<br />Recuerde que los estilos se ignoran para salidas tipo Consola.');

// 12. Tipo personalizado simple
$showme->title('Clase Personalizada sin estilos adicionales')
	->body('En este caso se define una clase diferente a las predefinidas.<br />Como no se definen  estilos específicos a aplicar (asociados en este caso a la clase <code>.box-personalizado-simple</code>), se presenta como la caja de diálogo definida por defecto.')
	->class('personalizado-simple')
	->render();

// 13. Tipo personalizado (combinado con estilos propietarios)
$filename = __DIR__ . '/demo-show-files/personalizado.css';
$contenido = file_get_contents($filename);
$showme->css($filename);

$showme->title('Tipo Personalizado con estilos adicionales aplicados')
	->body('Usando estilos declarados en archivo CSS <code>' . basename($filename) . '</code> adicional al predefinido.<br />Aunque se definan en este punto del script, estos estilos afectarán todas las cajas de diálogo en pantalla.')
	->footer('<pre>' . $contenido . '</pre>')
	->class('personalizado')
	->render();

echo "<h2>Repositorio</h2>";
echo '<p style="margin-top:30px"><a href="https://github.com/jjmejia/miframe-commons/tree/01-miframe-server-y-miframe-autoload" target="_blank">Repositorio disponible en <b>github.com/jjmejia</b></a></p>';

// Registra visita
$Test->visitorLog('demo-show');

// Cierre de la página
$Test->end();
