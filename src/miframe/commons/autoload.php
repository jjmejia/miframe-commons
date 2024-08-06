<?php
/**
 * Autoload de clases para uso en proyectos de PHP.
 *
 * Notese que por su forma de operar (buscar path a clases) no puede
 * usarse una clase dentro de la función registrada en "miframe_autoload_classes".
 *
 * @author John Mejia
 * @since Julio 2024
 */

use miFrame\Commons\Classes\AutoLoader;

// Carga directamente las librerias requeridas
// (Recuerde que esta es la librería que implementa el autoload,
// a esta altura deben cargarse manualmente cada librería requerida).
include_once __DIR__ . '/patterns/Singleton.php';
include_once __DIR__ . '/classes/AutoLoader.php';

/**
 * Retorna Clase para para la gestión de la autocarga de scripts requeridos para la creación
 * de Clases PHP.
 *
 * @return object Objeto miFrame\Commons\Classes\AutoLoader.
 */
function miframe_autoload() : AutoLoader {
	return AutoLoader::getInstance();
}

// Registra función para carga de Clases.
spl_autoload_register(function ($className) {
		miframe_autoload()->get($className);
	});
