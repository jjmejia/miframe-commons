<?php

/**
 * Librería de soporte.
 *
 * Facilita acceso a las Clases de uso general.
 *
 * @author John Mejia
 * @since Julio 2024
 */

use miFrame\Commons\Core\EnvData;
use miFrame\Commons\Core\ErrorHandler;
use miFrame\Commons\Core\HTMLSupport;
use miFrame\Commons\Core\ServerData;
use miFrame\Commons\Extended\ExtendedRenderError;
use miFrame\Commons\Extended\ExtendedRenderView;

/**
 * Retorna objeto Singleton para manejo de valores registrados en $_SERVER y  funcionalidades asociadas.
 *
 * @return object Objeto miFrame\Commons\Core\ServerData.
 */
function miframe_server(): ServerData
{
	return ServerData::getInstance();
}

/**
 * Retorna objeto Singleton para manejo de recursos HTML.
 *
 * @return object miFrame\Commons\Core\HTMLSupport
 */
function miframe_html(): HTMLSupport
{
	return HTMLSupport::getInstance();
}

/**
 * Retorna objeto Singleton para manejo de vistas.
 *
 * @return object miFrame\Commons\Extended\ExtendedRenderView
 */
function miframe_render(): ExtendedRenderView
{
	return ExtendedRenderView::getInstance();
}

/**
 * Ejecuta la vista indicada.
 *
 * @param string $viewname Nombre/Path de la vista.
 * @param array $params Arreglo con valores.
 *
 * @return string Contenido renderizado.
 */
function miframe_view(string $viewname, array $params = []): string
{
	return miframe_render()->view($viewname, $params);
}

/**
 * Habilita manejo de errores personalizados
 */
function miframe_errors(bool $use_extended_render = false)
{
	/**
	 * @var ErrorHandler $errors
	 */
	$errors = new ErrorHandler();
	if ($use_extended_render) {
		// Hablita render predefinido
		$render = new ExtendedRenderError();
		// No termina script al ejecutar en modo desarrollo
		$render->inDeveloperModeEndScript = false;
		// Registra render
		$errors->setRenderer($render);
	} else {
		// Remueve render si ya tenía uno asignado
		$errors->removeRenderer();
	}
	// Observa errores
	$errors->watch();

	return $errors;
}

/**
 * Manejo de datos de configuración.
 *
 * @return object miFrame\Commons\Core\EnvData
 */
function miframe_env(): EnvData
{
	return EnvData::getInstance();
}
