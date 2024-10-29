<?php
/**
 * Librería de soporte para clases usadas para generar texto HTML.
 *
 * @author John Mejia
 * @since Octubre 2024
 */

namespace miFrame\Commons\Core;

use miFrame\Commons\Patterns\Singleton;

class HTMLSupport extends Singleton {

	/**
	 * @var array $resources	Listado de recursos CSS no publicados.
	 */
	private array $resources = [];

	/**
	 * @var array $published	Listado de recursos CSS ya publicados.
	 */
	private array $published = [];

	/**
	 * @var string $last_key Última llave adicionada a recursos.
	 */
	private string $last_key = '';

	/**
	 * Inicialización de la clase Singleton.
	 */
	protected function singletonStart() {

		$this->cssClear();
	}

	/**
	 * Adiciona un archivo CSS existente en disco.
	 *
	 * @param string $filename	Archivo CSS.
	 * @param bool $inline		TRUE para publicar contenido, FALSE para indicar URL al
	 * 							archivo local (solo si es posible, de lo contrario publica
	 * 							el contenido directamente).
	 * @return bool				TRUE si pudo adicionar el recurso, FALSE si hubo error.
	 */
	public function cssLocal(string $filename, bool $inline = false) : bool {

		$filename = trim($filename);
		if ($filename !== '' && is_file($filename)) {
			// Se asegura siempre de registrar correctamente el path fisico
			$filename = realpath($filename);
			$src = 'locals';
			if (!$inline) {
				$server = miframe_server();
				// En este caso, debe intentar incluirlo como remoto
				$path = $server->removeDocumentRoot($filename);
				if ($path !== false) {
					// Pudo obtener la URL
					// Adiciona a listado de publicados el path real
					$key = $this->keyPath($filename, $src);
					$this->published['css'][$key] = true;
					// Asegura formato URL remoto
					$filename = '/' . $server->purgeURLPath($path);
					// Modifica el calificador
					$src = 'remote';
				}
			}
			$this->addResourceCSS($filename, $src);
		}
		elseif ($filename !== '') {
			// No pudo acceder al archivo
			$info = "Recurso CSS \"{$filename}\" no es un archivo valido";
			// Genera mensaje de error
			trigger_error($info, E_USER_WARNING);

			return false;
		}

		return true;
	}

	/**
	 * Adiciona un recurso CSS indicando su URL, se publica
	 * apuntando a su ubicación remota.
	 *
	 * @param string $path URL al archivo de CSS remoto.
	 */
 	public function cssRemote(string $path) {

		$path = trim($path);
		if ($path !== '') {
			$this->addResourceCSS($path, 'remote');
		}
	}

	/**
	 * Adiciona un recurso CSS directamente en línea
	 *
	 * @param string $styles Estilos CSS.
	 */
	public function cssInLine(string $styles) {

		$styles = $this->cleanCode($styles);
		if ($styles !== '') {
			// Se asegura siempre de registrar correctamente el path fisico
			$this->addResourceCSS($styles, 'inline');
		}
	}

	/**
	 * Adiciona recurso CSS
	 *
	 * @param string $filename 	Ubicación del archivo de recurso.
	 * @param string $dest 		Uso del recurso (local, enlínea, etc.)
	 */
	private function addResourceCSS(string $filename, string $dest) {

		$this->addResource($filename, 'css', $dest);
	}

	/**
	 * Adiciona recurso
	 *
	 * @param string $filename 	Ubicación del archivo de recurso.
	 * @param string $type 		Tipo de recurso (css, script, etc.)
	 * @param string $dest 		Uso del recurso (local, enlínea, etc.)
	 */
	private function addResource(string $filename, string $type, string $dest) {

		$key = $this->keyPath($filename, $dest);
		if (!isset($this->published[$type]) ||
			!array_key_exists($key, $this->published[$type])
			) {
			$this->resources[$type][$key] = $filename;
		}
	}

	/**
	 * Genera identificador asociado a un recurso.
	 *
	 * @param string $filename 	Ubicación del archivo de recurso.
	 * @param string $prefix 	Prefijo asociado al recurso.
	 * @return string 			Llave asociada al recurso.
	 */
	private function keyPath(string $filename, string $prefix) : string {

		$this->last_key = $prefix . ':' . md5(strtolower($filename));

		return $this->last_key;
	}

	/**
	 * Último identificador adicionado al listado de recursos.
	 *
	 * @return string Llave.
	 */
	public function lastKey() {

		return $this->last_key;
	}

	/**
	 * Genera código con los estilos CSS no publicados, para su uso en páginas web.
	 *
	 * @param  bool   $inline 	TRUE retorna los estilos, FALSE genera tag link al archivo CSS indicado.
	 * @return string 			HTML con estilos a usar.
	 */
	public function cssExport() : string {

		return $this->cssMake($this->resources['css']);
	}

	/**
	 * Estilos CSS para usar en páginas HTML.
	 *
	 * Una vez procesados, los recursos se remueven del listado $data y
	 * se adicionan al listado de recursos ya publicados ($this->published).
	 *
	 * @param  array $data 	Arreglo con listado de recursos.
	 * @return string 		HTML con estilos a usar.
	 */
	private function cssMake(array &$data) {

		// Codigo remoto se almacena aqui
		$text = '';

		// Estilos en linea se almacenan aqui
		$code = '';

		foreach ($data as $key => $filename) {
			$src = substr($key, 0, 6);
			$local_inline = false;

			switch ($src) {

				case 'locals': // Local, en línea
					$code .= '/* ' . $src . ':' . basename($filename) . ' */' . PHP_EOL .
						$this->cleanCode(file_get_contents($filename)) .
						PHP_EOL;
					break;

				case 'remote': // Remoto siempre
					$text .= '<link rel="stylesheet" href="' . $filename . '" />' . PHP_EOL;
					break;

				case 'inline': // En línea siempre
					$code .= '/* ' . $key . ' */' . PHP_EOL .
							$filename .
							PHP_EOL;
					break;

				default:
			}

			// Adiciona a listado de publicados
			$this->published['css'][$key] = true;
			// Remueve de listado de pendientes
			unset($this->resources['css'][$key]);
		}

		// Da formato decente a la salida final
		if ($text !== '') {
			$text = PHP_EOL . $text;
		}
		if ($code !== '') {
			$code = '<style>' . PHP_EOL . $code . '</style>' . PHP_EOL;
			if ($text === '') {
				$code = PHP_EOL . $code;
			}
		}

		return $text . $code;
	}

	/**
	 * Listado de recursos CSS no publicados.
	 *
	 * @return array Listado de recursos.
	 */
	public function cssUnpublished() : array {

		return $this->resources['css'];
	}


	/**
	 * Limpia código, remueve comentarios y líneas en blanco.
	 *
	 * @param string $content 	Código a depurar.
	 * @return string 			Código depurado.
	 */
	private function cleanCode(string $content) {

		// Remueve comentarios /* ... */
		// Sugerido en https://stackoverflow.com/a/643136
		$content = preg_replace('!/\*.*?\*/!s', '', $content);
		// Remueve lineas en blanco
		$content = preg_replace('/\n\s*\n/', "\n", $content);
		// Remueve lineas en general para exportar una unica linea
		$content = str_replace(["\n", "\r", "\t"], ['', '', ' '], $content);
		// Adiciona espacios antes y después de los parentesis
		$content = str_replace([ '{', '}' ], [ ' { ', ' } ' ], $content);
		// Remueve espacios dobles
		while (strpos($content, '  ') !== false) {
			$content = str_replace('  ', ' ', $content);
		}

		return trim($content);
	}

	/**
	 * Genera código con los estilos CSS contenidos en el archivo indicado, si no se ha publicado previamente.
	 *
	 * @param string $filename 	Ruta de archivo o URL.
	 * @param bool   $inline 	TRUE retorna los estilos, FALSE genera tag link al archivo CSS indicado.
	 * @return string 			HTML con estilos a usar.
	 */
	public function cssExportFrom(string $filename, bool $inline = false) {

		$text = '';
		if ($this->cssLocal($filename, $inline)) {
			// Recupera última llave asociada
			$key = $this->lastKey();
			// Recupara datos
			$data[$key] = $this->resources['css'][$key];
			// Procesa archivo
			$text = $this->cssMake($data);
			// Remueve de la cola original
			unset($this->resources['css'][$key]);
		}

		return $text;
	}

	/**
	 * Limpia el listado de recursos pendientes por publicar.
	 */
	public function cssClear() {

		$this->resources['css'] = [];
	}

}
