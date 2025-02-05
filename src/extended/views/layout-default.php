<?php

/**
 * Layout a usar por defecto.
 *
 * @author John Mejía
 * @since Noviemre 2024
 */

if (!isset($view_args['contentFromViews'])) {
	$contentFromViews = '<p><b>Aviso:</b> No se recibió contenido a mostrar en la variable "contentFromViews".</p>';
}
$copy_args = $view_args;
if (isset($copy_args['contentFromViews'])) {
	// No elimina directamente en $view_args ya que está vinculado a la
	// variable exportada y la removería automáticamente.
	unset($copy_args['contentFromViews']);
}
// Habilita modo desarrollo para visualizar el dump()
miframe_render()->developerOn();
miframe_dump($copy_args, 'Valores globales recibidos (LAYOUT-DEFAULT)');

// Muestra contenido de vistas previas
echo $contentFromViews;