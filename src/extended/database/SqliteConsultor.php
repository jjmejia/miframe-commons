<?php

/**
 * Interfaz para implementar conexiones a bases de datos MySQL/MariaDB.
 *
 * https://www.sqlitetutorial.net/sqlite-php/connect/
 */

namespace miFrame\Commons\Extended\Database;

use miFrame\Commons\Interfaces\SQLConsultorInterface;
// use miFrame\Commons\Support\DBMotorData;
// use miFrame\Commons\Support\DBMotorData;

class SqliteConsultor implements SQLConsultorInterface
{

	public function driverName(): string
	{
		return 'sqlite';
	}

	public function changeDatabase(string $database): string
	{
		return '';
	}

	public function splice(string &$query, int $offset = 0, int $limit = 0): bool
	{
		$add_sql = '';
		if ($limit > 0) {
			$add_sql .= 'limit ' . $limit . ' ';
		}
		if ($offset > 0 || $limit > 0) {
			$add_sql .= 'offset ' . $offset;
		}
		$query = rtrim($query) . PHP_EOL . $add_sql;

		return true;
	}

	public function tablesList(): string
	{
		// https://www.sqlitetutorial.net/sqlite-show-tables/
		return "SELECT name " .
			"FROM sqlite_schema " .
			"WHERE type ='table' AND name NOT LIKE 'sqlite_%'";
	}


	// public function first(string &$query, int $count):bool
	// {
	// 	return false;
	// }

	// public function last(string &$query, int $count):bool
	// {
	// 	return false;
	// }

}
