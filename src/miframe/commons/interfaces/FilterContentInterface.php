<?php

/**
 * Interface para filtrar contenido a pantalla.
 *
 * @author John Mejía
 * @since Diciembre 2024
 */

namespace miFrame\Commons\Interfaces;

interface FilterContentInterface {

	/**
	 * Modifica contenido renderizado.
	 *
	 * @param string $content Contenido a ser filtrado.
	 */
	public function filter(string &$content);
}