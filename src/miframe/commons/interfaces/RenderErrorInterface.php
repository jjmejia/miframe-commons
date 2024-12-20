<?php

/**
 * Interface para manejo de errores.
 */

namespace miFrame\Commons\Interfaces;

use miFrame\Commons\Support\DataError;

interface RenderErrorInterface {

	/**
	 * Genera salida a pantalla con la información de error capturada.
	 *
	 * El arreglo $data_error contiene al menos los siguientes elementos:
	 *
	 * - 'class'  : Nombre de la clase que generó el error.
	 * - 'type'   : Nivel de error de PHP (E_USER_ERROR, E_ERROR, ...).
	 * - 'message': Descripción del error.
	 * - 'file'   : Archivo donde se generó el error.
	 * - 'line'   : Línea del archivo donde se generó el error.
	 * - 'trace'  : Información de backtrace.
	 * - 'type_name': Nombre amigable del nivel de error.
	 *
	 * @param DataError $error Arreglo de datos.
	 * @return false|string	Texto renderizado con base en el arreglo de datos o
	 * 						FALSE si no fue posible generar el texto.
	 */
	public function show(DataError $error): string|false;
}