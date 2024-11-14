<?php

/**
 * Clase para la gestión de la autocarga de scripts requeridos para la creación
 * de Clases PHP.
 *
 * Se sugiere minimizar el uso de funciones externas para evitar errores de carga al
 * ser usualmente de las primeras librerías en cargar.
 *
 * Lectura sugerida: https://www.php-fig.org/psr/psr-4/
 *
 * @author John Mejía
 * @since Julio 2024
 */

namespace miFrame\Commons\Core;

use \miFrame\Commons\Patterns\Singleton;

class AutoLoader extends Singleton
{

	/**
	 * @var array $namespaces	Registra las listas de busqueda. Arreglo asociativo del tipo:
	 * 							(Patrón de la Clase) => (Path en disco del script que define la Clase)
	 */
	private $namespaces = array();

	/**
	 * @var array $matches Registro de namespaces correctamente identificados.
	 */
	private array $matches = array();

	/**
	 * Inicialización de la clase Singleton.
	 */
	protected function singletonStart()
	{
		// Registra este modelo
		// __CLASS__ usa "\" como separador de segmentos. En Linux, usar
		// dirname() con ese separador retorna ".". Por tanto, empleamos
		// una alternativa diferente, a saber:
		$class_pattern = 'miFrame\\Commons\\*';
		$file_path = dirname(__DIR__); // Apunta al directorio "commons"
		$this->register($class_pattern, $file_path . DIRECTORY_SEPARATOR . '*.php');
	}

	/**
	 * Registra los namespaces que identifican las Clases PHP y los archivos donde se definen.
	 *
	 * Los namespaces a registrar son del tipo:
	 *
	 *    (Patrón de la Clase) => (Path del script que define la Clase)
	 *
	 * Se puede usar el caracter "*" al final de la cadena usada en el patrón de Clase para
	 * indicar que el resto del path asociado a la clase puede ser buscado en el Path de los
	 * scripts. Por ejemplo, si define:
	 *
	 *     miFrame\Common\Core\* = C:\core\*.php
	 *
	 * Cuando se solicita la Clase "miFrame\Common\Core\ServerData" la busca en el archivo
	 * "C:\core\ServerData.php".
	 *
	 * Nota: No es necesario registrar las clases contenidas en el mismo directorio de esta
	 * Clase (AutoLoader) ya que se adicionan automáticamente.
	 *
	 * @param string $className	Patrón de la Clase
	 * @param string $path		Path del script que define la clase
	 */
	public function register(string $className, string $path)
	{

		// Usa como llave el nombre en minusculas para prevenir errores al
		// escribir el nombre. Si empieza con "\" lo remueve.
		$className = strtolower(trim($className));
		if ($className !== '' && $className[0] == '\\') {
			$className = substr($className, 1);
		}
		if ($className !== '' && $path !== '') {
			// Registra path
			$this->namespaces[$className] = trim($path);
		}
	}

	/**
	 * Realiza el cargue del archivo que contiene la Clase indicada.
	 *
	 * @param string $className	Nombre de Clase.
	 */
	public function load(string $className)
	{

		if ($className !== '' && $className[0] == '\\') {
			$className = substr($className, 1);
		}
		$class = strtolower($className);
		$path = '';

		if (isset($namespaces[$class])) {
			// Valor exacto
			$path = $namespaces[$class];
		} else {
			// Busca parciales
			foreach ($this->namespaces as $nameclass => $pathclass) {
				if (substr($nameclass, -1) == '*') {
					// Valida nombre de clase parcial
					$pattern = substr($nameclass, 0, -1);
					$len = strlen($pattern);
					if (substr($class, 0, $len) === $pattern) {
						// Genera path conservando sintaxis de $className
						$subpath = substr($className, $len);
						$path = str_replace('*', $subpath, $pathclass);
						break;
					}
				}
			}
		}

		// Mensaje a pantalla
		// echo '[' . __CLASS__ . "] $className : $path (" . (file_exists($path) ? 'true' : 'false') . ")<hr>" . PHP_EOL;

		if ($path !== '') {
			// Valida que exista. Normaliza path.
			$path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
			if (!file_exists($path)) {
				// Genera path usando todo el path de directorios en minusculas
				$path = strtolower(dirname($path)) . DIRECTORY_SEPARATOR . basename($path);
				if (!file_exists($path)) {
					// Genera todo el path en minusculas
					$path = strtolower($path);
				}
			}
			if (is_file($path)) {
				// Registra uso
				$this->matches[$className] = realpath($path);

				require_once $this->matches[$className];
			}
		}
	}

	/**
	 * Listado de namespaces correctamente identificados.
	 *
	 * @return array Arreglo asociativo del tipo (Clase) => (Path del script que define la Clase).
	 */
	public function matches(): array
	{

		return $this->matches;
	}

	/**
	 * Listado de namespaces registrados.
	 *
	 * @return array Arreglo asociativo del tipo (Patrón) => (Path del script que define la Clase).
	 */
	public function namespaces(): array
	{

		return $this->namespaces;
	}
}
