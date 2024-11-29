<?php

/**
 * Layout a usar por defecto.
 *
 * @author John Mejía
 * @since Noviemre 2024
 */

// Para desarrollo, visualiza valores recibidos
if (count($view_args) > 0) {
	echo miframe_dump($view_args, 'Valores globales recibidos (LAYOUT-DEFAULT)');
}

// Muestra contenido de vistas previas
echo miframe_render()->contentView();