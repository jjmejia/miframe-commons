<div>
	<p><b>Variables disponibles:</b></p>
	<?php

	// Muestra variables recibidas, elimina referencias a $Test
	$arr = get_defined_vars();
	unset($arr['Test']);
	unset($arr['view_args']['Test']);
	$Test->dump($arr, true);

	?>
	<?php if (!miframe_render()->inDeveloperMode()) { ?>
	<p><b>Nota:</b> Cuando habilita "modo Desarrollo" puede visualizar a continuación el listado de las vistas en ejecución.</p>
	<?php } else { ?>
	<p><b>Nota:</b> Cuando quita el "modo Desarrollo" no se visualiza el contenido mostrado a continuación.</p>
	<?php } ?>
	<p><?= miframe_render()->dumpViews() ?>
</div>
