<div>
	<p>Fecha: <span><?= date('Y/m/d H:i:s') ?></span></p>
	<p>Texto: <span><?= trim($dato1) ?></span></p>
	<p>Número: <span><?= number_format($dato2) ?></span></p>
	<p>
		<i><b>Aviso:</b> El reporte de errores en pantalla puede que esté bloqueado en el servidor.
		Si no se visualiza mensaje de error en pantalla, prueba a habilitar el "modo Desarrollo".</i>
	</p>
	<p>Valor no asignado: <span><?= trim($dato3) ?></span></p>
</div>
