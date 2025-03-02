<?php

/**
 * Vista para demostrar el comportamiento del Layout al usarse múltiples
 * vistas de primer nivel.
 */

$message = [
	1 => 'Primera vista, incluye por defecto el Layout. Este es el comportamiento estándar.',
	2 => 'Segunda vista, ya no incluye el Layout porque fue usado en la primera.',
	3 => 'Esta tercera vista incluye de nuevo el Layout porque previamente se ha habilitado con <code>$view->layout->reset()</code>.'
];

?>
<div style="margin:10px 0; padding:20px; border:1px solid #ccc;">
	<p><b>Fecha:</b> <span><?= date('Y/m/d H:i:s') ?></span></p>
	<p><b>[<?= $index ?>]</b> <?= $message[$index] ?></p>
</div>
