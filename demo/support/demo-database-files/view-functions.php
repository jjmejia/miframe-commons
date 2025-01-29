<?php

/**
 * Funciones de soporte para demos de base de datos.
 *
 * @author John Mejía
 * @since Enero 2025
 */

use miFrame\Commons\Core\PDOController;

/**
 * Genera una fila de tabla HTML a partir de un array de datos.
 *
 * @param array $data Los datos que se mostrarán en la fila de la tabla.
 * @param bool $is_title Opcional. Si es true, la fila corresponde a una fila de títulos (cabecera).
 * @return string La fila de tabla HTML generada.
 */
function showRecord(array $data, bool $is_title = false): string
{
	$tag = 'td';
	if ($is_title) {
		$tag = 'th';
	}
	return "<tr><{$tag}>" . implode("</{$tag}><{$tag}>", $data) . "</{$tag}></tr>" . PHP_EOL;
}

/**
 * Genera una tabla HTML a partir de un array de datos.
 *
 * @param array $values Los datos que se mostrarán en la tabla.
 * @return string La tabla HTML generada.
 */
function showTable(array $values): string
{
	$total = count($values);
	if ($total <= 0) {
		return '<p>&bull; <i>No hay datos para mostrar.</i></p>';
	}

	$headers = true;
	$count = 10;
	$salida = '<table cellspacing="0" class="demo-table-db">' . PHP_EOL;
	foreach ($values as $key => $data) {
		if (!is_numeric($key)) {
			$data = ['Nombre' => $key, 'Valor' => $data];
		}
		if (!is_array($data)) { continue; }
		if ($headers) {
			$salida .= showRecord(array_keys($data), true);
			$headers = false;
		}
		$salida .= showRecord($data);
		$count --;
		if ($count < 1) { break; } // Limita a 10 valores
	}
	$salida .= '</table>' . PHP_EOL;
	if ($total > 10) {
		$salida .= "<p>&bull; Muestra limitada a 10 registros de un total de {$total}.</p>";
	}
	return $salida;
}

/**
 * Muestra estadísticas de la base de datos.
 *
 * @param PDOController $db El controlador de la base de datos.
 */
function showStats(PDOController $db)
{
	if ($db->inDebug()) {
		miframe_dump($db->stats(), 'Estadísticas de la Consulta');
	}
	else {
		$error = $db->getLastError();
		if ($error !== '') {
			echo '<p><i><b>Aviso:</b> ' . $error . '</i></p>';
		}
	}
}