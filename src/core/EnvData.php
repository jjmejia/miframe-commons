<?php

/**
 * Clase para manejo de datos de entorno, capturados de archivos .env
 *
 * @author John Mejía
 * @since Marzo 2025
 */

namespace miFrame\Commons\Core;

use miFrame\Commons\Patterns\Singleton;

class EnvData extends Singleton
{

	/**
	 * @var array $data Arreglo que contiene los datos del entorno.
	 */
	private array $data = [];

	/**
	 * @var array $basenames Arreglo con el listado de archivos cargados.
	 */
	private array $basenames = [];

	// Nada por realizar al iniciar esta clase
	protected function singletonStart() {}

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
			$this->autoload();
			return $this->data[$name];
		}
		return $default;
	}

	/**
	 * Autocarga los datos de entorno si no ha cargado ningún dato previamente.
	 */
	private function autoload()
	{
		// Si no ha cargado ningún dato, carga los datos de entorno por defecto esperados.
		if (count($this->basenames) === 0) {
			$this->load();
		}
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

	/**
	 * Recupera el valor de una variable de entorno y lo devuelve como ruta absoluta dentro del directorio Web.
	 *
	 * @param string $name El nombre de la variable de entorno a recuperar.
	 * @param string $default El valor predeterminado a devolver si la variable no está definida. Por defecto es una cadena vacía.
	 * @return string La ruta absoluta del valor de la variable de entorno o el valor predeterminado.
	 */
	public function documentRoot(string $name, string $default = ''): string
	{
		$server = miframe_server();
		return $server->documentRoot($this->get($name, $default));
	}

	/**
	 * Recupera el valor de una variable de entorno y lo devuelve como ruta para acceder a través de HTTP.
	 *
	 * @param string $path El nombre de la variable de entorno a recuperar.
	 * @param string $default El valor predeterminado a devolver si la variable no está definida. Por defecto es una cadena vacía.
	 * @param array $args (Opcional) Arreglo de pares clave-valor para agregar a la ruta como parámetros GET. Por defecto es un arreglo vacío.
	 * @return string La ruta para acceder a través de HTTP del valor de la variable de entorno o el valor predeterminado.
	 */
	public function url(string $path = '', array $args = [], string $default = ''): string
	{
		$server = miframe_server();
		return $server->url($this->get($path, $default), $args);
	}

	/**
	 * Carga las variables de entorno.
	 *
	 * Por defecto busca el archivo .env en cada directorio del proyecto, empezando en el
	 * más externo declarado en SCRIPT_NAME y buscando hacia atrás hasta el directorio raíz
	 * (DOCUMENT_ROOT).
	 *
	 * Puede indicar un nombre base para el archivo, de forma que busca "[basename].env". En este
	 * caso, puede indicar el path a partir de DOCUMENT_ROOT (sin la extensión) para buscar un
	 * archivo particular.
	 *
	 * @param string $basename Nombre base para el archivo de variables.
	 * @return bool Devuelve true si las variables de entorno se cargaron correctamente, false en caso contrario.
	 */
	public function load(string $basename = ''): bool
	{
		$result = false;
		$server = miframe_server();
		// Remueve posibles ".." del $basename para prevenir salga del DOCUMENT_ROOT
		$basename = $server->purgeFilename($basename);
		// Estandariza formato del nombre para registro
		$key = strtolower($basename);

		if (!isset($this->basenames[$key])) {
			// Registra uso de $basename aunque no encuentre archivo alguno
			$this->basenames[$key] = '?';

			$filename = $basename . '.env';
			// Busca archivo en DOCUMENT_ROOT (donde espera encontrarlo siempre)
			$documentRoot = $server->documentRoot();
			$path = $documentRoot . $filename;
			$result = file_exists($path);
			if (!$result) {
				// Busca el archivo hacia atrás en tanto no alcance el root
				$dirname = $server->scriptDirectory();
				do {
					$path = $dirname . $filename;
					$result = file_exists($path);
					$dirname = dirname($dirname) . DIRECTORY_SEPARATOR;
					// echo "$key : $path -- $result / $documentRoot<hr>";
				} while (!$result && $dirname !== '.' && $dirname !== $documentRoot);
			}
			if ($result) {
				// Encontró el archivo
				$this->read($path);
				// Preserva path asociado al prefijo actual
				$this->basenames[$key] = realpath($path);
			}
		}

		return $result;
	}

	/**
	 * Toma las variables de entorno de un archivo.
	 *
	 * @param string $path Path del archivo a cargar.
	 */
	private function read(string $path)
	{
		// Registra en variables globales el contenido del .env
		$env_data = @parse_ini_file($path);
		// Convierte todas las llaves a minusculas
		if (is_array($env_data)) {
			$this->save($env_data);
		}
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
		$this->reset();
		foreach ($this->basenames as $path) {
			$this->read($path);
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
