<?php
/**
 * Librería de soporte.
 * Facilita acceso a las Clases de uso general.
 *
 * @author John Mejia
 * @since Julio 2024
 */

use miFrame\Commons\Core\ServerData;

/**
 * Retorna Clase para manejo de valores registrados en $_SERVER y  funcionalidades asociadas.
 *
 * @return object Objeto miFrame\Commons\Core\ServerData.
 */
function miframe_server() : ServerData {

	return ServerData::getInstance();
}
