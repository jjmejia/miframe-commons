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
use miFrame\Commons\Core\ShowMe;

/**
 * Retorna Clase para manejo de valores registrados en $_SERVER y  funcionalidades asociadas.
 *
 * @return object Objeto miFrame\Commons\Core\ServerData.
 */
function miframe_server(): ServerData
{
	return ServerData::getInstance();
}

/**
 * HTML Support
 */
function miframe_html(): HTMLSupport
{
	return HTMLSupport::getInstance();
}

/**
 * ShowMe
 */
function miframe_show(): ShowMe
{
	return ShowMe::getInstance();
}

/**
 * Simplifica uso de ShowMe
 */
function miframe_box(string $body, string $title = '', string $footnote = '', string $class = '')
{
	return miframe_show()->title($title)
		->body($body)
		->footer($footnote)
		->class($class)
		->render(true);
}
