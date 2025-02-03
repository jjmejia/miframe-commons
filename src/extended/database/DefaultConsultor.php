<?php

/**
 * Interfaz para implementar conexiones a bases de datos MySQL/MariaDB.
 *
 * https://mariadb.com/resources/blog/developer-quickstart-php-data-objects-and-mariadb/
 */

namespace miFrame\Commons\Extended\Database;

use miFrame\Commons\Interfaces\SQLConsultorInterface;
// use miFrame\Commons\Support\DBMotorData;

class DefaultConsultor implements SQLConsultorInterface {

	public function driverName():string
	{
		return 'defaultsql';
	}

	public function changeDatabase(string $database): string
	{
		return '';
	}

	public function splice(string &$query, int $offset = 0, int $limit = 0): bool
	{
		return false;
	}

	public function tablesList(): string
	{
		return '';
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