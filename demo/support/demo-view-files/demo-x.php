<?php

echo "<p>A continuación se genera un <i>PHP Fatal Error</i>.</p>";

if (!miframe_render()->inDeveloperMode()) {
	?>
	<p><i>
		<b>Aviso:</b> El reporte de errores en pantalla se bloquean por defecto al usar vistas.
		Si no se visualiza mensaje de error en pantalla, prueba a habilitar el "modo Desarrollo".
	</i></p>
<?php
}

// La siguiente línea causa un PHP Fatal Error (división por cero)
$x = 5 / 0;
