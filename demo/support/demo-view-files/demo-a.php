<div>
	<p><b>Variables disponibles:</b></p>
	<?php

	// Muestra variables recibidas, elimina referencias a $Test
	$arr = get_defined_vars();
	unset($arr['Test']);
	unset($arr['layout_args']['Test']);
	$Test->dump($arr, true);

	?>
	<?php if (!miframe_render()->inDeveloperMode()) { ?>
	<p><b>Nota:</b> Cuando habilita "modo Desarrollo" puede visualizar a continuación el listado de las variables locales en ejecución.</p>
	<?php } ?>
	<p><?= miframe_render()->dump($view_args) ?>
</div>
