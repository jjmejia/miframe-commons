<?php
/**
 * Clase de soporte para mostrar contenido de datos.
 *
 * @author John Mejía
 * @since Septiembre 2024
 */

namespace miFrame\Commons\Traits;

trait VarDumpData {

	private int $dumpExportMax = 0;

	private int $export_count = 0;

	private array $recursive_dump = array();

	private int $recursive_count = 0;

	private function varDumpFormat(mixed $data, bool $first = true, string $prefix = '') {

		$info = array();

		if ($first) {
			$this->export_count = 0;
		}
		// Limita cantidad máxima en caso que haya recursión
		elseif ($this->stopDumpExport()) {
			return false;
		}

		// Determina tipo asociado al dato a mostrar
		$type = gettype($data);
		$size = 0;
		// Booleanos: Modifica valor para indicar visualmente "true" o "false"
		if (is_bool($data)) {
			$data = ($data === true) ? 'true' : 'false';
		}
		// Texto: Modifica tipo para incluir longitud
		elseif (is_string($data) && strlen($data) > 0) {
			$size = strlen($data);
		}
		// Arreglos: Modifica tipo para incluir tamaño
		elseif (is_array($data) && count($data) > 0) {
			$size = count($data);
		}

		// Items para manejo de recursividad
		$key_name = '';
		$key_value = '';

		// Evalua dato a mostrar para determinar si es:
		// 1. Arreglo de datos (muestra la información de forma recursiva)
		if (is_array($data)) {

			$key = $this->recursiveDump($data);
			$sub = array();
			if ($key === '' || $this->recursive_dump[$key] == 0) {
				// Ordena por llave
				// ksort($data);
				// Lista elementos
				foreach ($data as $k => $v) {
					$sub[$k] = $this->varDumpFormat($v, false);
					// Limita cantidad máxima en caso que haya recursión
					if ($this->stopDumpExport()) {
						// $sub[] = '...'; // Suspende
						break;
					}
				}

				// Actualiza valor de la llave
				// (pudo verse modificada en alguno de los ciclos internos)
				if ($key !== '' && $this->recursive_dump[$key] > 0) {
					$key_value = $this->recursive_dump[$key];
					$key_name = 'recursive-id';
				}
			}
			else {
				$sub = false;
				$key_name = 'recursive-parent';
				$key_value = $this->recursive_dump[$key];
			}

			$info = [ 'key' => $key, 'type' => $prefix . $type, 'size' => $size, 'value' => $sub ];
		}
		// 2. Un objeto o clase
		elseif (is_object($data)) {
			$classname = get_class($data);
			$key = $this->recursiveDump($data);
			$sub = array();
			if ($key === '' || $this->recursive_dump[$key] == 0) {
				// Recupera información del objeto.
				// https://www.slingacademy.com/article/php-how-to-list-all-properties-and-methods-of-an-object/
				$reflector = new \ReflectionClass($classname);
				$properties = $reflector->getProperties();
				$size = count($properties);

				foreach($properties as $property) {
					$prefix_property = '';
					if ($property->isStatic()) {
						$prefix_property .= 'static';
					}
					if ($property->isPrivate()) {
						if ($prefix_property != '') {
							$prefix_property .= ':';
						}
						$prefix_property .= 'private';
					}
					if ($prefix_property != '') {
						$prefix_property .= ':'; // "[{$prefix_property}] ";
					}
					$k = $property->getName();
					$sub[$k] = $this->varDumpFormat($property->getValue($data), false, $prefix_property);
					// Limita cantidad máxima en caso que haya recursión
					if ($this->stopDumpExport()) {
						// $sub[] = '...'; // Suspende
						break;
					}
					// echo $property->getName() . ($property->isPrivate() ? ' (private)' : '') . ' = ' . print_r($property->getValue($data), true) . '<hr>';
				}

				// Actualiza valor de la llave
				// (pudo verse modificada en alguno de los ciclos internos)
				if ($key != '' && $this->recursive_dump[$key] > 0) {
					$key_value = $this->recursive_dump[$key];
					$key_name = 'recursive-id';
				}
			}
			else {
				$sub = false;
				$key_name = 'recursive-parent';
				$key_value = $this->recursive_dump[$key];
			}

			$info = [ 'type' => $prefix . 'object', 'class' => $classname, 'size' => $size, 'value' => $sub ];

			// $info = trim(str_replace($classname, '', print_r($data, true)));
			// // Suprime "Object"
			// if (substr($info, 0, 6) == 'Object') { $info = trim(substr($info, 8)); }
			// // Suprime parentesis al inicio y final
			// if (substr($info, 0, 1) == '(') { $info = trim(substr($info, 1)); }
			// if (substr($info, -1, 1) == ')') { $info = trim(substr($info, 0, -1)); }

			// $info = [ 'type' => 'object', 'class' => $classname, 'value' => PHP_EOL . $info ];
			// $this->export_count ++;
		}
		// 3. Cualquier otro tipo (string, integer, etc.)
		else {
			$info = [ 'type' => $prefix . $type, 'size' => $size, 'value' => trim($data) ];
			$this->export_count ++;
		}

		// Complementa con datos de recursividad
		if ($key_name != '') {
			$info[$key_name] = $key_value;
		}

		if ($first) {
			// Limita cantidad máxima en caso que haya recursión
			if ($this->stopDumpExport()) {
				$info['error'] = 'Suspende muestreo de variable porque alcanzó tope máximo de items a mostrar (' . $this->dumpExportMax . ')';
			}
		}

		return $info;
	}

	private function recursiveDump(mixed &$data) : string {

		$key = '';
		if (!is_object($data)) {
			return $key;
		}

		// Solo aplica para objetos.
		// Caso especial: Está evaluando ESTE mismo objeto
		// porque mientras lo evalua va cambiando y dificulta el
		// control de recursividad.
		if ($data === $this) {
			$key = 'this';
		}
		else {
			$key .= get_class($data) . ':' . md5(spl_object_hash($data));
		}

		if (!isset($this->recursive_dump[$key])) {
			// Registra lectura
			$this->recursive_dump[$key] = 0;
		}
		// Indica al control que debe indicar uso
		elseif ($this->recursive_dump[$key] == 0) {
			$this->recursive_count ++;
			$this->recursive_dump[$key] = $this->recursive_count;
		}

		// echo "$key : {$this->recursive_dump[$key]}<hr>";

		return $key;
	}

	/**
	 * Indica que debe suspender la exportación del dump
	 */
	private function stopDumpExport() {

		return ($this->dumpExportMax > 0 && $this->export_count >= $this->dumpExportMax);
	}

}