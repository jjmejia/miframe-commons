<?php

/**
 * Librería de soporte.
 * Facilita acceso a las Clases de uso general.
 *
 * @author John Mejia
 * @since Julio 2024
 */

use miFrame\Commons\Core\HTMLSupport;
use miFrame\Commons\Core\ServerData;
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
 * Realiza volcado de datos en pantalla.
 *
 * Requiere que se encuentre activo tanto el "modo Debug" (miframe_render()->debug = true)
 * como el "modo Desarrollo" (miframe_render()->developerMode = true) o de lo contrario
 * retornará una cadena vacia.
 *
 * @param mixed $var Variable a mostrar contenido.
 * @param string $title Título a usar al mostrar contenido.
 * @param bool $escape_dump TRUE para mostrar información legible (para humanos) sobre
 * 							el contenido de $var. FALSE muestra el contenido tal
 * 							cual sin modificar su formato.
 *
 * @return string Texto formateado.
 */
function miframe_dump(mixed $var, string $title = '', bool $escape_dump = true) {
	return miframe_render()->dump($var, $title, $escape_dump);
}
