<?php

/**
 * Funciones de soporte para demo.
 */

use miFrame\Commons\Core\PDOController;

function showRecord(array $data, bool $is_title = false): string
{
	$tag = 'td';
	if ($is_title) {
		$tag = 'th';
	}
	return "<tr><{$tag}>" . implode("</{$tag}><{$tag}>", $data) . "</{$tag}}></tr>" . PHP_EOL;
}

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

function showStats(PDOController $db)
{
	if ($db->inDebug()) {
		// $Test->dump($db->stats());
		miframe_dump($db->stats(), 'Estadísticas de la Consulta');
	}
	else {
		$error = $db->getLastError();
		if ($error !== '') {
			echo '<p><i><b>Aviso:</b> ' . $error . '</i></p>';
		}
	}
}