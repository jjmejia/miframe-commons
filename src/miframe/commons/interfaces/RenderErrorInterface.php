<?php

/**
 * Interface para manejo de errores.
 */

namespace miFrame\Commons\Interfaces;

use miFrame\Commons\Support\ErrorData;

interface RenderErrorInterface {

	/**
	 * Genera salida a pantalla con la información de error capturada.
	 *
	 * @param ErrorData $error Objeto que contiene detalles del error.
	 * @return false|string	Texto renderizado con base en el arreglo de datos o
	 * 						FALSE si no fue posible generar el texto.
	 */
	public function show(ErrorData $error): string|false;
}