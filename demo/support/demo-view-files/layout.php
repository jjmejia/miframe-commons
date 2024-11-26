<!--
 Tradicionalmente, el layout contiene la apertura y cierre de la página.
 Para efectos de esta demo no es necesario.
<html>
	<head>
		<title><?= $title ?></title>
	</head>
	<body>
-->
		<h1><?= $title ?></h1>
		<?= miframe_render()->contentView() ?>
		<hr>
		<p>Pie de página contenido en el Layout (<b>UID</b> <?= $uid ?>).
<!--
	</body>
</html>
-->