<?php

// Ejemplos de uso manual del objeto errores

// echo '<h2>Otros ejemplos de manejo de errores</h2>';

if (miframe_server()->isLocalhost()) {
	echo '<ul class="dmeo"><li><b>Log de errores:</b> ' . $errors->getErrorLog() . '</li>';
}

if (!empty($render)) {
	$default = $render->warningMessage;
	if ($Test->choice('usermsg', 'Cambiar mensaje de Advertencia', 'Restaurar mensaje de Advertencia')) {
		$render->warningMessage = 'Ups! Houston, tenemos un problema';
	}
	echo "<li><b>" . $Test->renderChoices() . "</b> (Mensaje original: <i>{$default}</i>)</li>";
	// $errors->warningMessage = 'Ups! Houston, tenemos un problema (warning)';
}

echo "<li>Error generado luego de usado el <i>Layout</i> en la vista previa:";
// Variable no declarada, genera error
$Test->showNextLines();
$variable_not_declared++;
echo "</li>";

echo "<li>Error visualizado manualmente:";
$Test->showNextLines();
$errors->showError(E_USER_WARNING, 'Error manualmente generado');
echo "</li>";

echo "<li>Ejemplo del manejo de una <code>Exception</code>:";
$Test->showNextLines(6);
try {
	// Las excepciones pueden manejar cualquier valor entero para código
	throw new Exception('Exception manualmente generada', 30);
} catch (\Exception $e) {
	$errors->showException($e, false);
}

echo "</li></ul>";
