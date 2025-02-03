<?php

/**
 * Interfaz para implementar conexiones a bases de datos.
 */

namespace miFrame\Commons\Interfaces;

// use miFrame\Commons\Support\DBMotorData;

// use miFrame\Commons\Support\DBMotorData;

interface SQLConsultorInterface {

	// mysql, pgsql, etc. Nombre soportado por PDO
	public function driverName():string;
	// public function connect(string $database, DBMotorData $data):bool;
	public function changeDatabase(string $database):string;
	// Caso especial: Si no existe comando SQL para ejecutar esta acción debe retornar FALSE.
	public function splice(string &$query, int $start = 0, int $limit = 0):bool;
	// public function exec(string $query):array|false;
	public function tablesList(): string;
	// public function first(string &$query, int $count):bool;
	// public function last(string &$query, int $count):bool;
}

/**
 * Listado de bases de datos:
 * sqlite: 		PRAGMA database_list;
 * mysql: 		SHOW DATABASES [LIKE '<yourDatabasePrefixHere>%'];
 * posgresql: 	SELECT current_database();
 *
 * Base de datos actual:
 * sqlite: 		PRAGMA database_list;
 * mysql: 		SELECT DATABASE();
 * posgresql: 	SELECT current_database();
 *
 * sqlite necesita adjuntar manualmente las bases de datos
 * adicionales. La principal se lista como "main".
 * ATTACH DATABASE 'Pets.db' AS Pets;
 *
 * Estructura de tablas:
 * sqlite:		SELECT sql FROM sqlite_schema WHERE name = '[name]';
 * mysql:		SHOW CREATE TABLE [table];
 * 				DESCRIBE [table];
 * pgsql:		select column_name, data_type, character_maximum_length, column_default, is_nullable
 * 				 from INFORMATION_SCHEMA.COLUMNS where table_name = '<name of table>';
 *
 */