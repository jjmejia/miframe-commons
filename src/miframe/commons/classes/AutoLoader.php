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

namespace miFrame\Commons\Classes;

use \miFrame\Commons\Patterns\Singleton;

class AutoLoader extends Singleton {

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
	 *     miFrame\Common\Classes\* = C:\classes\*.php
	 *
	 * Cuando se solicita la Clase "miFrame\Common\Classes\ServerData" la busca en el archivo
	 * "C:\classes\ServerData.php".
	 *
	 * Nota: No es necesario registrar las clases contenidas en el mismo directorio de esta
	 * Clase (AutoLoader) ya que se adicionan automáticamente.
	 *
	 * @param string $className	Patrón de la Clase
	 * @param string $path		Path del script que define la clase
	 */
	public function register(string $className, string $path) {

		$this->namespaces[strtolower(trim($className))] = trim($path);
	}

	/**
	 * Realiza el cargue del archivo que contiene la Clase indicada.
	 *
	 * @param string $className	Nombre de Clase.
	 */
	public function load(string $className) {

		$class = strtolower($className);
		$path = '';

		if (isset($namespaces[$class])) {
			// Valor exacto
			$path = $namespaces[$class];
		}
		else {
			// Registra este modelo
			if (count($this->namespaces) <= 0) {
				$class_pattern = dirname(dirname(__CLASS__));
				$file_path = dirname(dirname(__FILE__));
				$this->register($class_pattern . '\*', $file_path . DIRECTORY_SEPARATOR . '*.php');
			}
			// Busca parciales
			foreach ($this->namespaces as $nameclass => $namepath) {
				if (substr($nameclass, -1) == '*') {
					// Valida directorio parcial
					$pattern = '#^' . str_replace('\\', '\\\\', substr($nameclass, 0, -1)) . '(.+)$#';
					// Ejemplo: '#^DesignPatterns\\\\(.+)$#'
					if (preg_match($pattern, $class, $match)) {
						$path = str_replace('*', $match[1], $namepath);
						break;
					}
				}
			}
		}

		// Mensaje a pantalla
		// echo '[' . __CLASS__ . "] $className : $path (" . (file_exists($path) ? 'true' : 'false') . ")<hr>" . PHP_EOL;

		if ($path !== '' && file_exists($path)) {

			// Registra uso
			$this->matches[$className] = realpath($path);

			require_once $this->matches[$className];
		}
	}

	/**
	 * Listado de namespaces correctamente identificados.
	 *
	 * @return array Arreglo asociativo del tipo (Clase) => (Path del script que define la Clase).
	 */
	public function matches() : array {

		return $this->matches;
	}

	/**
	 * Listado de namespaces registrados.
	 *
	 * @return array Arreglo asociativo del tipo (Patrón) => (Path del script que define la Clase).
	 */
	public function namespaces() : array {

		return $this->namespaces;
	}
}
