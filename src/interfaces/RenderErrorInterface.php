<?php

/**
 * Interface para manejo de errores.
 *
 * @author John Mejía
 * @since Diciembre 2024
 */

namespace miFrame\Commons\Interfaces;

use miFrame\Commons\Components\ErrorData;

interface RenderErrorInterface {

	/**
	 * Genera salida a pantalla con la información de error capturada.
	 *
	 * @param ErrorData $error Objeto que contiene detalles del error.
	 * @param string $html_default Texto a mostrar por defecto en caso de que
	 * 							   no tenga otra información para mostrar.
	 *
	 * @return false|string	Texto renderizado con base en el arreglo de datos o
	 * 						FALSE si no fue posible generar el texto.
	 */
	public function show(ErrorData $error, string $html_default): string;
}