<?php

/**
 * Vista para demostrar el comportamiento del Layout al usarse múltiples
 * vistas de primer nivel.
 */

$message = [
	1 => 'Segunda vista. Ya no incluye el Layout (no se visualiza título) porque fue usado en la primera, que es marcada como la "vista Principal". El layout se incluye entonces solamente para cada iteración de esa vista.',
	2 => 'Esta tercera vista incluye de nuevo el Layout porque previamente se ha habilitado su uso con <code>$view->layoutReset()</code>, de forma que ahora esta es la "vista Principal".'
];

?>
<div style="margin:10px 0; padding:20px; border:1px solid #ccc;">
	<p><b>Fecha:</b> <span><?= date('Y/m/d H:i:s') ?></span></p>
	<p><?= $message[$index] ?></p>
</div>
