<!--
 Tradicionalmente, el layout contiene la apertura y cierre de la página.
 Para efectos de esta demo no es necesario.
-->
<?php if (miframe_render()->once()) { ?>
<style>.view-container { border:1px dashed #999;padding:10px 20px;margin:10px 0; h1 { margin-top:0; } }</style>
<?php } ?>
<div class="view-container">
	<h1><?= $title ?></h1>
	<?= $content_view ?>
	<hr>
	<p>Pie de página contenido en el Layout (<b>UID</b> <?= $uid ?>).
</div>
