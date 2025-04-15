<?php

/**
 * Clase para manejo de datos de entorno, capturados de archivos .env
 *
 * @author John Mejía
 * @since Marzo 2025
 */

namespace miFrame\Commons\Core;

use miFrame\Commons\Patterns\Singleton;

class EnvData extends Singleton {

	/**
	 * @var array $data Arreglo que contiene los datos del entorno.
	 */
	private array $data = [];

	/**
	 * @var array $basenames Arreglo con el listado de archivos cargados.
	 */
	private array $basenames = [];

	protected function singletonStart()
	{
		$this->load();
	}

	/**
	 * Recupera el valor de una variable de entorno.
	 *
	 * @param string $name El nombre de la variable de entorno a recuperar.
	 * @param mixed $default El valor predeterminado a devolver si la variable de entorno no está configurada. Por defecto, es una cadena vacía.
	 * @return mixed El valor de la variable de entorno o el valor predeterminado.
	 */
	public function get(string $name, mixed $default = ''): mixed
	{
		// $name es "case insensitive".
		$name = strtolower(trim($name));
		if ($name !== '' && array_key_exists($name, $this->data)) {
			return $this->data[$name];
		}
		return $default;
	}

	/**
	 * Recupera el valor de una variable de entorno como número válido.
	 *
	 * @param string $name El nombre de la variable de entorno a recuperar.
	 * @param int|float $default El valor predeterminado a devolver si la variable no está definida o no es numérica. Por defecto es 0.
	 * @return int|float El valor numérico de la variable de entorno o el valor predeterminado.
	 */
	public function getNumber(string $name, int|float $default = 0): int|float
	{
		return $this->get($name, $default) + 0;
	}

	/**
	 * Recupera el valor de una variable de entorno como true o false (boolean).
	 *
	 * @param string $name El nombre de la variable de entorno a recuperar.
	 * @param int|float $default El valor predeterminado a devolver si la variable no está definida o no es numérica. Por defecto es false.
	 * @return int|float true o false según la variable de entorno o el valor predeterminado.
	 */
	public function getBoolean(string $name, bool $default = false): bool
	{
		return boolval($this->get($name, $default));
	}

	public function documentRoot(string $name, string $default = ''): string
	{
		return miframe_server()->documentRoot($this->get($name, $default));
	}

	/**
	 * Carga las variables de entorno.
	 *
	 * Por defecto busca el archivo .env en cada directorio del proyecto, empezando en el
	 * más externo declarado en SCRIPT_NAME y buscando hacia atrás hasta el directorio raíz
	 * (DOCUMENT_ROOT).
	 *
	 * Puede indicar un nombre base para el archivo, de forma que busca "[basename].env".
	 *
	 * @param string $prefix Nombre base para el archivo de variables.
	 * @return bool Devuelve true si las variables de entorno se cargaron correctamente, false en caso contrario.
	 */
	public function load(string $prefix = ''): bool
	{
		$result = false;
		$basename = trim($prefix) . '.env';

		if (!isset($this->basenames[$prefix]))
		{
			// Limpia datos previos (si alguno)
			// $this->data = [];
			$server = miframe_server();
			// Busca archivo
			$path = $server->documentRoot($basename);
			$result = file_exists($path);
			if (!$result) {
				// Busca el archivo hacia atrás en tanto no alcance el root
				$elements = explode(DIRECTORY_SEPARATOR, $server->removeDocumentRoot($server->scriptDirectory()));
				// print_r($elements); echo "<hr>"; $this->server->scriptDirectory(); echo "\n";
				do {
					$path = $server->documentRoot(array_shift($elements) . DIRECTORY_SEPARATOR . $basename);
					$result = file_exists($path);
				}
				while (count($elements) > 0 && !$result);
			}
			if ($result) {
				// Encontró el archivo
				// Registra en variables globales el contenido del .env
				$env_data = parse_ini_file($path);
				// Convierte todas las llaves a minusculas
				if (is_array($env_data) && count($env_data) > 0) {
					$this->save($env_data);
				}
				// Preserva path asociado al prefijo actual
				$this->basenames[$prefix] = $path;
			}
		}

		return $result;
	}

	/**
	 * Establece un valor para una variable de entorno.
	 *
	 * @param string $name Nombre de la variable de entorno.
	 * @param mixed $value Valor que se asignará a la variable.
	 */
	public function set(string $name, mixed $value)
	{
		$name = strtolower(trim($name));
		if ($name !== '' && !is_numeric($name)) {
			$this->data[$name] = $value;
		}
	}

	/**
	 * Establece valores para múltiples variables de entorno.
	 *
	 * @param array $data Datos a guardar en el entorno.
	 */
	public function save(array $data)
	{
		// Elimina llaves numéricas
		// $this->data += array_change_key_case($env_data, CASE_LOWER);
		foreach ($data as $key => $value) {
			$this->set($key, $value);
		}
	}

	/**
	 * Elimina los variables de entorno previamente establecidos.
	 */
	public function reset()
	{
		$this->data = [];
	}

	/**
	 * Recarga los datos del entorno desde su fuente original.
	 *
	 * Este método permite actualizar los valores almacenados en la instancia
	 * con los datos más recientes disponibles en los archivos de declaraciones (.env).
	 * Cualquier valor modificado previamente se perderá.
	 */
	public function reload()
	{
		$basenames = array_keys($this->basenames);
		$this->reset();
		foreach ($basenames as $prefix) {
			$this->load($prefix);
		}
	}

	/**
	 * Devuelve una lista de los archivos .env cargados.
	 *
	 * @return array Lista de archivos .env cargados, con sus rutas completas.
	 */
	public function loadedFiles(): array
	{
		return $this->basenames;
	}
}