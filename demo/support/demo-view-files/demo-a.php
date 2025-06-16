<div>
	<p>
		Esta es un script PHP usado para presentación de información al usuario
		y/o solicitar datos a través de formularios HTML.
	</p>
	<p><b>Variables disponibles en este script:</b></p>
	<?php

	// Muestra variables recibidas, elimina referencias a $Test
	$arr = get_defined_vars();
	if (isset($arr['Test'])) {
		unset($arr['Test']);
		unset($arr['view_args']['Test']);
		$Test->dump($arr, true);
	}

	?>
	<?php if (!miframe_render()->inDeveloperMode()) { ?>
	<p><b>Nota:</b> Cuando habilita "modo Desarrollo" puede visualizar a continuación el listado de las variables locales en ejecución.</p>
	<?php } else { ?>
		<p><b>Nota:</b> El siguiente listado de las variables locales es visible porque está habilitado el "modo Desarrollo".
	<?php } ?>
	<p><?= miframe_dump($view_args) ?>
</div>
