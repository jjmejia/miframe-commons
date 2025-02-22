<div>
	<p><b>PHP Versión:</b> <span><?= PHP_VERSION ?></span></p>
	<p><b>Archivo vista:</b> <?= $view_filename ?></p>
	<p><b>Variables:</b> [<?= implode(', ', array_keys($view_args)) ?>]</p>
	<p><b>Variable #1:</b> ($dato1 - Texto) <?= trim($dato1) ?></p>
	<p><b>Variable #2:</b> ($dato2 - Número) <?= number_format($dato2) ?></p>
	<p><b>UID:</b> <span>(asignado al layout)</span> <?= miframe_render()->layout->get('uid', 'NA') ?></p>
	<?php if (!miframe_render()->inDeveloperMode()) { ?>
	<p><b>Nota:</b> Cuando habilita "modo Desarrollo" puede visualizar a continuación el listado de las vistas en ejecución.</p>
	<?php } else { ?>
	<p><b>Nota:</b> Cuando quita el "modo Desarrollo" no se visualiza el contenido mostrado a continuación.</p>
	<?php } ?>
	<p><?= miframe_render()->dumpViews() ?>
</div>
