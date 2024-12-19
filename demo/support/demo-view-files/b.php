
<div>
	<p><b>Fecha:</b> <span><?= date('Y/m/d H:i:s') ?></span></p>
	<p><b>Variable #1:</b> ($dato1 - Texto) <span><?= trim($dato1) ?></span></p>
	<p><b>Variable #2:</b> ($dato2 - Número) <span><?= number_format($dato2) ?></span></p>
	<?php if (!miframe_render()->inDeveloperMode()) { ?>
		<p><i>
			<b>Aviso:</b> El reporte de errores en pantalla se bloquean por defecto al usar vistas.
			Si no se visualiza mensaje de error en pantalla, prueba a habilitar el "modo Desarrollo".
		</i></p>
	<?php } ?>
	<p><b>Variable no declarada:</b> ($invalid_var) <span><?= trim($invalid_var) ?></span></p>
</div>
