<?php

/**
 * Clase PDOController para manejo de conexiones y consultas a bases de datos utilizando PDO.
 *
 * Referencias:
 * - https://mariadb.com/resources/blog/developer-quickstart-php-data-objects-and-mariadb/
 * - https://www.sqlitetutorial.net/sqlite-php/connect/
 */

namespace miFrame\Commons\Core;

use PDO;
use PDOException;

class PDOController
{
	/**
	 * @var string $host El nombre del servidor de la base de datos.
	 */
	public string $host = '';

	/**
	 * @var string $filename El nombre del archivo para la conexión a la base de datos.
	 */
	public string $filename = '';

	/**
	 * @var string $user El nombre de usuario para la conexión a la base de datos.
	 */
	public string $user = '';

	/**
	 * @var string $password La contraseña para la conexión a la base de datos.
	 */
	public string $password = '';

	/**
	 * @var string $database El nombre de la base de datos.
	 */
	public string $database = '';

	/**
	 * @var string $charset El conjunto de caracteres a utilizar para la conexión a la base de datos.
	 */
	public string $charset = '';

	/**
	 * @property string $driverName Nombre del driver de la base de datos, compatible con PDO (mysql, pgsql, sqlite, etc.).
	 */
	private string $driverName = '';

	/**
	 * @property string $lastQuery Última consulta ejecutada.
	 */
	private string $lastQuery = '';

	/**
	 * @property string $lastError Último error ocurrido.
	 */
	private string $lastError = '';

	/**
	 * @property bool $debug Modo de depuración.
	 */
	private bool $debug = false;

	/**
	 * @property float $timeQuery Tiempo de inicio de la consulta.
	 */
	private float $timeQuery = 0;

	/**
	 * @property float $durationExec Duración de la ejecución de la consulta SQL.
	 */
	private float $durationExec = 0;

	/**
	 * @property float $durationFetch Duración de la recuperación de filas.
	 */
	private float $durationFetch = 0;

	/**
	 * @property int $rowsFetched Número de filas recuperadas.
	 */
	private int $rowsFetched = 0;

	/**
	 * @property PDO|null $pdo Instancia de PDO.
	 */
	private ?PDO $pdo = null;

	/**
	 * Valida que el driver deseado esté habilitado en el sistema.
	 */
	public function __construct(string $driver)
	{
		$this->driverName = strtolower(trim($driver));
		// Drivers disponibles en el sistema
		if (
			$this->driverName == '' ||
			!in_array($this->driverName, PDO::getAvailableDrivers())
		) {
			trigger_error(
				"El driver indicado no es soportado para PDO ({$driver})",
				E_USER_ERROR
			);
		}
	}

	/**
	 * Nombre del driver en uso, asignado al crear el objeto.
	 *
	 * @return string Nombre del driver en uso
	 */
	public function driver(): string
	{
		return $this->driverName;
	}

	/**
	 * Activa o desactiva el modo de depuración.
	 *
	 * Cuando el modo de depuración está activado, la notificación de errores de PHP se configura
	 * para reportar todos los errores.
	 *
	 * @param bool $value True para activar el modo de depuración, false para desactivarlo.
	 */
	public function debug(bool $value)
	{
		$this->debug = $value;
		if ($value) {
			// Habilita reporte de todos los errores
			error_reporting(E_ALL);
			// Habilita salida a pantalla de mensajes de error
			ini_set("display_errors", "on");
		}
	}

	/**
	 * Establece una conexión a la base de datos usando PDO.
	 *
	 * Este método intenta crear una nueva instancia de PDO con los parámetros
	 * de conexión y opciones proporcionadas. Si la conexión es exitosa,
	 * devuelve true. Si la conexión falla devuelve false y captura la PDOException,
	 * estableciendo el último mensaje de error. Opcionalmente dispara una advertencia
	 * si la depuración está habilitada ($this->debug = true).
	 *
	 * @return bool True si la conexión es exitosa, false en caso contrario.
	 */
	public function connect(): bool
	{
		// Limpia último error
		$this->clearErrors();

		try {
			$options = [
				PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Enable errors in the form of exceptions
				PDO::ATTR_EMULATE_PREPARES   => false, // Disable emulation mode for "real" prepared statements
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Make the default fetch be an associative array
			];
			// Construye cadena de conexión
			$elements = [];
			if ($this->filename !== '') {
				$elements[] = $this->filename;
			}
			if ($this->host !== '') {
				$elements[] = 'host=' . $this->host;
			}
			if ($this->database !== '') {
				// Base de datos por defecto, previamente configurada
				$elements[] = 'dbname=' . $this->database;
			}
			if ($this->charset !== '') {
				$elements[] = 'charset=' . $this->charset;
			}
			$dsn = $this->driverName . ':' . implode(';', $elements);

			$this->pdo = new PDO($dsn, $this->user, $this->password, $options);
		} catch (PDOException $ex) {
			// Aviso de error capturable
			$this->lastError = 'No pudo realizar conexión a la base de datos: ' . $ex->getMessage();
			if ($this->debug) {
				trigger_error($this->lastError, E_USER_WARNING);
			}
			return false;
		}

		return true;
	}

	/**
	 * Libera el objeto PDO actual forzando la creación de uno nuevo en la siguiente consulta.
	 */
	public function release()
	{
		$this->pdo = null;
	}

	/**
	 * Realiza consulta SQL.
	 */
	/**
	 * Ejecuta una consulta SQL y devuelve el resultado como un arreglo.
	 *
	 * @param string $query La consulta SQL a ejecutar.
	 * @param array $values Opcional. Un arreglo de valores para enlazar a la consulta. Por defecto es un array vacío.
	 * @param int $offset Opcional. El número de filas a omitir antes de comenzar a recuperar el resultado. Por defecto es 0.
	 * @param int $limit Opcional. El número máximo de filas a recuperar. Por defecto es 0, lo que significa sin límite.
	 * @return array Arreglo con el resultado de la consulta.
	 */
	public function query(string $query, array $values = [], int $offset = 0, int $limit = 0): array
	{
		$data = [];

		// Si no está previamente conectado, levanta la conexión
		if (empty($this->pdo) && !$this->connect()) {
			return $data;
		}

		$this->clearErrors();
		$this->startStats();

		try {
			$result = false;
			if ($query !== '') {
				// Preserva query a ejecutar para debug
				$this->lastQuery = $query;
				if (count($values) <= 0 || strpos($query, '?') === false) {
					// No está formateada para usar prepare()
					$result = $this->pdo->query($query);
				} else {
					// El arreglo de valores no puede tener llaves asociativas
					$values = array_values($values);
					$result = $this->pdo->prepare($query);
					if ($result !== false) {
						if ($result->execute($values) === false) {
							$result = false;
						}
					}
				}
			}

			// Calcula tiempo que tarda en ejecutar la consulta
			$this->durationExec = microtime(true) - $this->timeQuery;

			if ($result !== false) {
				$time = microtime(true);
				// Recupera datos
				if ($offset <= 0 && $limit <= 0) {
					$data = $result->fetchAll();
				}
				else {
					// Ignora todos los registros antes del $offset indicado.
					// De esta forma solamente almacena los registros indicados.
					// Usado para el caso que no se pueda limitar el resultado
					// directamente en el query.
					// Recordar que el prime elemento es index=0
					while ($offset > 0 && $result->fetch() !== false) {
						$offset --;
					}
					// Recupera los indicados por $limit o todos si es cero
					$count = 0;
					while ($row = $result->fetch()) {
						$data[] = $row;
						$count ++;
						if ($limit > 0 && $count == $limit) {
							break;
						}
					}
				}
				$this->rowsFetched = count($data);
				// Calcula tiempo que tarda en recuperar los datos
				$this->durationFetch = microtime(true) - $time;
			}
		} catch (PDOException $ex) {
			// Aviso de error capturable
			$this->lastError = 'No pudo realizar la consulta SQL solicitada: ' . $ex->getMessage();
			if ($this->debug) {
				trigger_error($this->lastError, E_USER_WARNING);
			}
		}

		return $data;
	}

	/**
	 * Inicializa registro de errores
	 */
	private function clearErrors()
	{
		$this->lastError = '';
		// Limpia último error
		error_clear_last();
	}

	/**
	 * Inicializa registro de estadisticas.
	 */
	private function startStats()
	{
		$this->durationExec = 0;
		$this->durationFetch = 0;
		$this->timeQuery = microtime(true);
		$this->rowsFetched = 0;
	}

	/**
	 * Recupera la última consulta SQL ejecutada.
	 *
	 * @return string La última consulta SQL ejecutada.
	 */
	public function lastQuery(): string
	{
		return $this->lastQuery;
	}

	/**
	 * Recupera estadísticas sobre la última consulta ejecutada.
	 *
	 * @return array Un arreglo asociativo que contiene las siguientes claves:
	 * - 'startDate': La fecha y hora de inicio de la ejecución de la consulta en formato 'Y/m/d H:i:s'.
	 * - 'start': La marca de tiempo cuando comenzó la ejecución de la consulta.
	 * - 'durationExec': La duración de la ejecución de la consulta.
	 * - 'durationFetch': La duración de la recuperación de los resultados.
	 * - 'rowsFetched': El número de filas recuperadas por la consulta.
	 * - 'cmdSuccessful': Un booleano que indica si el último comando fue exitoso.
	 * - 'lastError': El último mensaje de error, si existe.
	 */
	public function stats(): array
	{
		return [
			// 'query' => $this->lastQuery,
			'startDate' => date('Y/m/d H:i:s', intval($this->timeQuery)),
			'start' => $this->timeQuery,
			'durationExec' => $this->durationExec,
			'durationFetch' => $this->durationFetch,
			'rowsFetched' => $this->rowsFetched,
			'cmdSuccessful' => ($this->lastError === ''),
			'lastError' => $this->lastError
		];
	}

	/**
	 * Recupera el último mensaje de error.
	 *
	 * @return string El último mensaje de error.
	 */
	public function getLastError(): string
	{
		return $this->lastError;
	}
}
