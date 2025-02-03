<?php

/**
 * Gestiona consultas a bases de datos SQL.
 * Similar sintaxis a Eloquent.
 * https://laravel.com/docs/11.x/eloquent
 */

namespace miFrame\Commons\Core;

use Exception;
use miFrame\Commons\Interfaces\SQLConsultorInterface;
use miFrame\Commons\Support\DBMotorData;
use miFrame\Commons\Support\SQLQueryBuilder;

class SQLConsultor {

	private ?SQLConsultorInterface $driver = null;
	private ?DBMotorData $db = null;

	private string $database = ''; // Base de datos por defecto
	private string $currentDatabase = '';
	private bool $connected = false;

	public bool $debug = false;

	public function __construct(string|SQLConsultorInterface $object_or_class)
	{
		// Asocia interfaz a usar (tipo de base de datos)
		$this->loadMotor($object_or_class);
	}

	/**
	 * Asocia interfaz a usar (tipo de base de datos)
	 */
	private function loadMotor(string|SQLConsultorInterface $object_or_class)
	{
		$driver_name = '';
		if (!is_object($object_or_class)) {
			// Si no es objeto, asume es una cadena
			$driver_name = $object_or_class;
			$classname = '\\miFrame\\Commons\\Extended\\Database\\' . ucfirst(strtolower($driver_name)) . 'Consultor';
			if (!class_exists($classname) || !is_subclass_of($classname, SQLConsultorInterface::class)) {
				// Usa un objeto creado por defecto
				$classname = '\\miFrame\\Commons\\Extended\\Database\\DefaultConsultor';
			}
			$this->driver = new $classname();
		}
		else {
			$this->driver = $object_or_class;
			$driver_name = $this->driver->driverName();
		}
		// Crea contenedor para datos
		$this->db = new DBMotorData($driver_name);
	}

	public function host(string $host): self
	{
		$this->db->host = trim($host);
		return $this;
	}

	public function dbFilename(string $filename): self
	{
		$this->db->filename = trim($filename);
		return $this;
	}

	public function user(string $user): self {
		$this->db->user = trim($user);
		return $this;
	}

	public function password(string $password): self {
		$this->db->password = trim($password);
		return $this;
	}

	public function charset(string $charset): self {
		$this->db->charset = trim($charset);
		return $this;
	}

	public function defaultDatabase(string $database): self {
		$this->database = trim($database);
		return $this;
	}

	public function open(string $database = ''): bool
	{
		$database = trim($database);
		if ($database === '') {
			// Si no indica valor, usa la base de datos por defecto
			$database = $this->database;
		}
		// Si el motor ya esá conectado a la base de datos indicada,
		// no hace nada. Se indica primero porque hay motores que requieren
		// que se indique una base de datos para poder conectarse.
		if (
			$this->connected &&
			$database !== '' &&
			$database !== $this->currentDatabase
			) {
			$query = $this->driver->changeDatabase($database);
			if ($query !== '') {
				if ($this->db->query($query)) {
					$this->currentDatabase = $database;
				}
			}
			else {
				// Procede manualmente desconectando la base de datos actual
				// y conectando a la nueva base de datos
				$this->db->release();
				$this->connected = false;
				$this->currentDatabase = '';
			}
		}
		if (!$this->connected) {
			// Abre conexion a la base de datos (si no está abierta ya)
			if ($this->db->connect($database)) {
				$this->connected = true;
				$this->currentDatabase = $database;
			}
			// print_r($this->pdo); echo "--PDO<hr>";
		}

		return $this->connected;
	}

	public function select(array $columns): SQLQueryBuilder
	{
		$builder = new SQLQueryBuilder($this->db, $this->driver);

		return $builder->select($columns);
	}

	public function query(string $query, array $values = []): array|false
	{
		return $this->db->query($query, $values);
	}

	/**
	 * Retorna listado de tablas creadas o false si no le es posible
	 */
	public function tablesList(): array|false
	{
		$query = $this->driver->tablesList();
		if ($query !== '') {
			$data = $this->db->query($query);
			if (is_array($data)) {
				// Construye arreglo de salida
				$tables = [];
				foreach ($data as $row) {
					// Solamente retorna una columna y el nombre contiene
					// el nombre de la base de datos. Ej. "Tables_in_[database]"
					// o "name". También aplica si retorna más de una columna
					// pero es la primera la que contiene el nombre de las tablas.
					$tables[] = array_shift($row);
				}
				// Retorna listado de tablas
				return $tables;
			}
		}

		return false;
	}
}