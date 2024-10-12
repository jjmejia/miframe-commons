<?php
/**
 * Librería de soporte para clases usadas para generar texto HTML.
 *
 * @author John Mejia
 * @since Febrero 2023
 */

namespace miFrame\Commons\Support\WebResponse;

class HTMLSupport {

	/**
	 * @var array $files	Listado de recursos CSS no publicados.
	 */
	private array $files = [];

	/**
	 * @var array $published	Listado de recursos CSS ya publicados.
	 */
	private array $published = [];

	/**
	 * Adiciona recurso con estilos CSS a usar.
	 *
	 * Debe corresponder a un archivo físico en disco o puede indicarse una URL adicionando el
	 * prefijo "url:"(en este caso no se validará su existencia).
	 * Si el recurso ya fue publicado previamente, no se adiciona al listado de pendientes.
	 *
	 * @param string $filename Ubicación del archivo CSS.
	 */
	public function addFilenameCSS(string $filename) {

		$filename = trim($filename);
		if ($filename !== '' && !$this->isURL($filename)) {
			if (is_file($filename)) {
				// Se asegura siempre de registrar correctamente el path fisico
				$filename = realpath($filename);
			}
		}

		if ($filename !== '') {
			$key = $this->keyPath($filename, 'css');
			if (!array_key_exists($key, $this->published)) {
				$this->files[$key] = $filename;
			}
		}
	}

	/**
	 * Llave usada para identificar un archivo de recurso.
	 *
	 * @param string $filename 	Ubicación del archivo de recurso.
	 * @param string $type 		Tipo de recurso (css, script, etc.)
	 * @return string 			Llave asociada al recurso.
	 */
	private function keyPath(string $filename, string $type) : string {

		return $type . ':' . md5(strtolower($filename));
	}

	/**
	 * Valida si el path contiene el prefijo "url:", indicando que corresponde a un
	 * recurso remoto.
	 *
	 * Por ejemplo: "url:https://fonts.googleapis.com/css2?family=Roboto..."
	 *
	 * @param string $path 	Path a evaluar.
	 * @return bool			TRUE si el path contiene "url:", FALSE en otro caso.
	 */
	private function isURL(string $path) : bool {

		return ($path != '' && strtolower(substr($path, 0, 4)) == 'url:');
	}

	/**
	 * Retorna segmento del path de un archivo sin incluir DOCUMENT ROOT, para usar como URL.
	 * Si el path comienza con "url:", simplemente remueve este prefijo.
	 *
	 * @param string $path Ruta de archivo o directorio a validar.
	 * @return string URL.
	 */
	private function getURLFromPath(string $path) : string {

		$real = '';
		$path = trim($path);
		if ($path !== '') {
			// Maneja todos los separadores como "/"
			// Si el $path inicia con "url:" no valida el archivo como tal, asume es correcta la URL indicada.
			if ($this->isURL($path)) {
				$real = trim(substr($path, 4));
			}
			else {
				// Valida SIEMPRE contra el DOCUMENT_ROOT
				$document_root = str_replace("\\", '/', realpath($_SERVER['DOCUMENT_ROOT']));
				$base = str_replace("\\", '/', realpath($path));
				$len = strlen($document_root);
				if ($base != '' && substr($base, 0, $len) == $document_root) {
					$real = substr($base, $len);
					if (is_dir($base)) {
						// Es directorio, adiciona separador al final. Sino, corresponde a un archivo.
						$real .= '/';
					}
				}
			}
		}

		return $real;
	}

	/**
	 * Retorna recursos CSS pendientes de publicar.
	 *
	 * @param  bool   $return TRUE retorna los estilos, FALSE genera tag link al archivo CSS indicado.
	 * @return string HTML con estilos a usar.
	 */
	public function getStylesCSS(bool $return = false) : string {

		$text = '';

		foreach ($this->files as $key => $filename) {
			$text .= $this->createCSSCode($key, $filename, $return);
			// Remueve de listado de pendientes
			unset($this->files[$key]);
		}

		return $text;
	}

	/**
	 * Crea código a usar para incluir estilos HTML, sea por referencia (link) o en línea (tag "style").
	 *
	 * Si el recurso indicado no corresponde a uno valido, incluye un comentario en el código y genera
	 * un mensaje de PHP Warning para su rastreo.
	 *
	 * @param string $key 		Identificador asociado al recurso a procesar.
	 * @param string $filename 	Ruta de archivo o URL.
	 * @param bool	 $return 	TRUE retorna los estilos, FALSE genera tag link al archivo CSS indicado.
	 * @return string 			HTML con estilos a usar.
	 */
	private function createCSSCode(string $key, string $filename, bool $return = false) : string {

		$text = '';

		// Si ya fue publicado, ignora nuevo contenido
		if (array_key_exists($key, $this->published)) { return $text; }

		if (!$return || $this->isURL($filename)) {
			// REmueve el DOCUMENT_ROOT. Si no existe, no tiene nada que retornar pues
			// el archivo no sería accequible al navegador.
			$resource = $this->getURLFromPath($filename);
			if ($resource != '') {
				$text .= PHP_EOL . '<link rel="stylesheet" href="' . $resource . '">' . PHP_EOL;
			}
		}
		elseif ($filename !== '' && is_file($filename)) {
			// Retorna contenido de archivo
			$content = file_get_contents($filename);
			// Adiciona tags
			$text .= $this->stylesCode($content, basename($filename));
		}
		else {
			$info = "Recurso CSS \"{$filename}\" no encontrado";
			// Mensaje en pantalla
			$text .= '<!-- ' . $info . ' -->' . PHP_EOL;
			// Genera mensaje de error
			trigger_error($info, E_USER_WARNING);
		}

		// Reporta como publicado
		$this->published[$key] = $filename;

		return $text;
	}

	/**
	 * Limpia código de comentarios y líneas en blanco.
	 *
	 * @param string $content 	Código a depurar.
	 * @return string 			Código depurado.
	 */
	private function cleanCode(string &$content) {

		// Remueve comentarios /* ... */
		// https://stackoverflow.com/a/643136
		$content = preg_replace('!/\*.*?\*/!s', '', $content);
		// Remueve lineas en blanco
		$content = preg_replace('/\n\s*\n/', "\n", $content);
		// Remueve lineas en general para exportar una unica linea
		$content = str_replace(["\n", "\r", "\t"], ['', '', ' '], $content);
		// Remueve espacios dobles
		while (strpos($content, '  ') !== false) {
			$content = str_replace('  ', ' ', $content);
		}

		return trim($content);
	}

	/**
	 * Retorna recursos CSS asociado al path indicado, si no se ha publicado previamente.
	 *
	 * @param string $filename 	Ruta de archivo o URL.
	 * @param bool   $return 	TRUE retorna los estilos, FALSE genera tag link al archivo CSS indicado.
	 * @return string 			HTML con estilos a usar.
	 */
	public function getStylesFrom(string $filename, bool $return = false) {

		$text = '';
		if ($filename != '') {
			$key = $this->keyPath($filename, 'css');
			$text = $this->createCSSCode($key, $filename, $return);
		}

		return $text;
	}

	/**
	 * Da formato HTML al listado de estilos en línea.
	 *
	 * @param string $styles 	Estilos.
	 * @param string $comment	(Opcional) Comentario asociado a los estilos, usualmente el recurso de origen.
	 * @return string 			HTML con estilos a usar.
	 */
	public function stylesCode(string $styles, string $comment = '') {

		$styles = $this->cleanCode($styles);

		if ($styles !== '') {
			// Da formato al comentario
			$comment = trim($comment);
			if ($comment != '') {
				$comment = '/* ' . $comment . ' */' . PHP_EOL;
			}
			// Adiciona tags
			$styles = PHP_EOL .
				'<style>' . PHP_EOL .
				$comment .
				trim($styles) . PHP_EOL .
				'</style>' . PHP_EOL;
		}

		return $styles;
	}

	/**
	 * Limpia el listado de recursos pendientes por publicar.
	 */
	public function ignoreUnpublishedStyles() {

		$this->files = [];
	}

	/**
	 * Da formato HTML a código Javascript en línea.
	 *
	 * @param string $code 		Código Javascript.
	 * @param string $comment	(Opcional) Comentario asociado a los estilos, usualmente el recurso de origen.
	 * @return string 			HTML con código Javascript.
	 */
	public function scriptCode(string $code, string $comment = '') {

		$code = $this->cleanCode($code);

		if ($code !== '') {
			// Da formato al comentario
			$comment = trim($comment);
			if ($comment != '') {
				$comment = '/* ' . $comment . ' */' . PHP_EOL;
			}
			// Código
			$code = PHP_EOL . '<script>' . PHP_EOL .
				$comment .
				$code . PHP_EOL .
				'</script>' . PHP_EOL;
		}

		return $code;
	}
}
