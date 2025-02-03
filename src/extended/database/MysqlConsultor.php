<?php

/**
 * Interfaz para implementar conexiones a bases de datos MySQL/MariaDB.
 *
 * https://mariadb.com/resources/blog/developer-quickstart-php-data-objects-and-mariadb/
 */

namespace miFrame\Commons\Extended\Database;

use miFrame\Commons\Interfaces\SQLConsultorInterface;
// use miFrame\Commons\Support\DBMotorData;
// use miFrame\Commons\Support\DBMotorData;

class MysqlConsultor implements SQLConsultorInterface {

	public function driverName():string
	{
		return 'mysql';
	}

	public function changeDatabase(string $database): string
	{
		return 'use ' . $database;
	}

	public function splice(string &$query, int $offset = 0, int $limit = 0): bool
	{
		// Nota: MySQL trabaja con offset base 0
		$query = rtrim($query) . PHP_EOL . ' limit ' . $offset;
		if ($limit > 0) {
			$query .= ',' . $limit;
		}

		return true;
	}

	public function tablesList(): string
	{
		// https://database.guide/4-ways-to-list-all-tables-in-a-mysql-database/
		return 'show tables';
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