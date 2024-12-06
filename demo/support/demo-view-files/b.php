<div>
	<p>Fecha: <span><?= date('Y/m/d H:i:s') ?></span></p>
	<p>Texto: <span><?= trim($dato1) ?></span></p>
	<p>Número: <span><?= number_format($dato2) ?></span></p>
	<?php if (!miframe_render()->inDeveloperMode()) { ?>
		<p><i>
			<b>Aviso:</b> El reporte de errores en pantalla se bloquean por defecto al usar vistas.
			Si no se visualiza mensaje de error en pantalla, prueba a habilitar el "modo Desarrollo".
		</i></p>
	<?php } ?>
	<p>Valor de variable no declarada ($invalid_var): <span><?= trim($invalid_var) ?></span></p>
</div>
