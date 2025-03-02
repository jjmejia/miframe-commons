<?php

/**
 * Librería de soporte para clases usadas para generar texto HTML.
 *
 * Requiere miframe_server().
 *
 * @author John Mejia
 * @since Octubre 2024
 */

namespace miFrame\Commons\Core;

use miFrame\Commons\Patterns\Singleton;

class HTMLSupport extends Singleton
{
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
	 * @var bool $minimizeCSS	TRUE minimiza estilos CSS en línea. FALSE los
	 * 							incluye tal cual estén escritos en el origen.
	 */
	private bool $minimizeCSS = true;

	/**
	 * Inicialización de la clase Singleton.
	 */
	protected function singletonStart()
	{
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
	public function cssLocal(string $filename, bool $inline = false): bool
	{
		$filename = trim($filename);
		if ($filename !== '' && is_file($filename)) {
			// Se asegura siempre de registrar correctamente el path fisico
			$filename = realpath($filename);
			$src = 'local';
			if (!$inline) {
				// En este caso, debe intentar incluirlo como remoto
				$server = miframe_server();
				$path = $server->removeDocumentRoot($filename);
				if ($path !== false) {
					// El archivo está dentro del document root, puede accderse por web.
					// Adiciona a listado de publicados el path real
					// para prevenir lo duplique si lo invoca como no-inline
					// o no continuar si ya fue publicado como inline.
					$key = $this->keyPath($filename);

					if (!$this->isPublished($key)) {
						$this->published[$key] = true;
						// Cambia a formato de URL remoto
						$filename = $server->purgeURLPath($path);
						// Modifica el calificador
						$src = 'remote';
					}
				}
			}
			$this->addResourceCSS($filename, $src);
		} elseif ($filename !== '') {
			// No pudo acceder al archivo
			// Genera mensaje de error enmascarable (warning)
			trigger_error(
				"El archivo de recursos CSS \"{$filename}\" no existe",
				E_USER_WARNING
			);

			return false;
		}

		return true;
	}

	/**
	 * Adiciona un recurso CSS indicando su URL, se publica
	 * apuntando a su ubicación remota.
	 *
	 * @param string $path	URL al archivo de CSS remoto.
	 */
	public function cssRemote(string $path)
	{
		$path = trim($path);
		// Permite referir cualquier recurso, incluso locales
		// que usan por ejemplo directorios virtuales.
		if ($path !== '') {
			$this->addResourceCSS($path, 'remote');
		}
	}

	/**
	 * Adiciona un recurso CSS directamente en línea
	 *
	 * @param string $styles 	Estilos CSS.
	 * @param string $comment 	Comentario asociado al estilo.
	 */
	public function cssInLine(string $styles, string $comment = '')
	{
		$styles = $this->cleanCSSCode($styles);
		if ($styles !== '') {
			// Se asegura siempre de registrar correctamente el path fisico
			$this->addResourceCSS($styles, 'inline', $comment);
		}
	}

	/**
	 * Adiciona recurso CSS
	 *
	 * @param string $content 	Ubicación del archivo de recurso o contenido, según sea el caso.
	 * @param string $prefix 	Prefijo identificador
	 * @param string $comment 	Comentario asociado al estilo.
	 */
	private function addResourceCSS(string $content, string $prefix, string $comment = '')
	{
		$this->addResource('css', $content, $prefix, $comment);
	}

	/**
	 * Adiciona recurso
	 *
	 * @param string $resource 	Tipo de recurso (css, script, etc.)
	 * @param string $content 	Ubicación del archivo de recurso o contenido, según sea el caso.
	 * @param string $type		Prefijo identificador.
	 * @param string $comment 	Comentario asociado al estilo (opcional)
	 */
	private function addResource(string $resource, string $content, string $type, string $comment = '')
	{
		$key = $this->keyPath($content);
		if (!$this->isPublished($key)) {
			$this->resources[$resource][$key] = [
				'type' => $type,
				'value' => $content,
				'comment' => $comment
			];
		}
	}

	/**
	 * Valida si el recurso asociado a la llave indicada ya fue publicado.
	 *
	 * @param string $key 	Llave asociada al recurso.
	 * @return bool 		TRUE si el recurso ya fue asignado.
	 */
	private function isPublished(string $key): bool
	{
		return (array_key_exists($key, $this->published));
	}

	/**
	 * Genera identificador asociado a un recurso.
	 *
	 * @param string $filename 	Ubicación del archivo de recurso o path remoto.
	 * @return string 			Llave asociada al recurso.
	 */
	private function keyPath(string $filename): string
	{
		// Usa urldecode() en caso que se incluyan URLs con caracteres codificados
		$this->last_key = '#' . sha1(strtolower(urldecode(trim(str_replace("\\", '/', $filename)))));

		return $this->last_key;
	}

	/**
	 * Último identificador adicionado al listado de recursos.
	 *
	 * @return string Llave.
	 */
	public function lastKey(): string
	{
		return $this->last_key;
	}

	/**
	 * Genera código con los estilos CSS no publicados, para su uso en páginas web.
	 *
	 * @param  bool $debug_comments	Adiciona comentarios para identificar elementos adicionados.
	 * @return string HTML con estilos a usar.
	 */
	public function cssExport(bool $debug_comments = false): string
	{
		return $this->cssMake($this->resources['css'], $debug_comments);
	}

	/**
	 * Estilos CSS para usar en páginas HTML.
	 *
	 * Una vez procesados, los recursos se remueven del listado $data y
	 * se adicionan al listado de recursos ya publicados ($this->published).
	 *
	 * @param  array $data 			Arreglo con listado de recursos.
	 * @param  bool $debug_comments	Adiciona comentarios para identificar elementos adicionados.
	 * @return string 				HTML con estilos a usar.
	 */
	private function cssMake(array &$data, bool $debug_comments = false)
	{
		// Codigo remoto se almacena aqui
		$text = '';

		// Estilos en linea se almacenan aqui
		$code = '';

		foreach ($data as $key => $infodata) {

			// Comentario asociado
			$comment = trim($infodata['comment']);
			if ($comment !== '') {
				$comment = ' - ' . $comment;
			}

			switch ($infodata['type']) {

				case 'local': // Local, en línea
					if ($debug_comments) {
						$code .= '/* ' . $key . ' (' . basename($infodata['value']) . ')' . $comment . ' */' . PHP_EOL;
					}
					$content = @file_get_contents($infodata['value']);
					$code .= $this->cleanCSSCode($content) . PHP_EOL;
					break;

				case 'remote': // Remoto siempre
					if ($debug_comments) {
						$text .= '<!-- ' . $key . ' (remote)' . $comment . ' -->' . PHP_EOL;
					}
					$text .= '<link rel="stylesheet" href="' . $infodata['value'] . '" />' . PHP_EOL;
					break;

				case 'inline': // En línea siempre
					if ($debug_comments) {
						$code .= '/* ' . $key . ' (inline)' . $comment . ' */' . PHP_EOL;
					}
					$code .= $infodata['value'] . PHP_EOL;
					break;

				default:
			}

			// Adiciona a listado de publicados
			$this->published[$key] = true;
			// Remueve de listado de pendientes
			unset($this->resources['css'][$key]);
		}

		// Da formato decente a la salida final
		if ($text !== '') {
			$text = PHP_EOL . $text;
		}
		if ($code !== '') {
			$code = '<style>' . PHP_EOL . rtrim($code) . PHP_EOL . '</style>' . PHP_EOL;
			if ($text === '') {
				$code = PHP_EOL . $code;
			}
		}

		return $text . $code;
	}

	/**
	 * Total de recursos CSS no publicados.
	 *
	 * @return int Total de recursos CSS no publicados.
	 */
	public function cssUnpublished(): int
	{
		return count($this->resources['css']);
	}

	/**
	 * Define si minimiza o no los estilos CSS a exportar.
	 *
	 * @param bool $value TRUE minimiza estilos (valor por defecto), FALSE los incluye tal cuál sean definidos.
	 */
	public function minimizeCSSCode(bool $value)
	{
		$this->minimizeCSS = $value;
	}

	/**
	 * Limpia código de estilos, remueve comentarios y líneas en blanco.
	 *
	 * @param string $content Código a depurar.
	 * @return string Código depurado.
	 */
	private function cleanCSSCode(string $content): string
	{
		if ($this->minimizeCSS) {
			$this->cleanCode($content);
		}
		// No edita contenido
		return $content;
	}

	/**
	 * Limpia código de recursos, remueve comentarios y líneas en blanco.
	 *
	 * @param string $content Código a depurar.
	 */
	private function cleanCode(string &$content): string
	{
		// Remueve comentarios /* ... */
		// Sugerido en https://stackoverflow.com/a/643136
		$content = preg_replace('!/\*.*?\*/!s', '', $content);
		// Remueve lineas en blanco
		$content = preg_replace('/\n\s*\n/', "\n", $content);
		// Remueve lineas en general para exportar una unica linea
		$content = str_replace(["\n", "\r", "\t"], ['', '', ' '], $content);
		// Adiciona espacios antes y después de los parentesis
		$content = str_replace(['{', '}'], [' { ', ' } '], $content);
		// Remueve espacios dobles
		while (strpos($content, '  ') !== false) {
			$content = str_replace('  ', ' ', $content);
		}

		return trim($content);
	}

	/**
	 * Genera código con los estilos CSS contenidos en el archivo indicado, si no se ha publicado previamente.
	 *
	 * @param string $filename 	Ruta de archivo.
	 * @param bool   $inline 	TRUE retorna los estilos, FALSE genera tag link al archivo CSS indicado.
	 * @return string 			HTML con estilos a usar.
	 */
	public function cssExportFrom(string $filename, bool $inline = false)
	{
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
	public function cssClear()
	{
		$this->resources['css'] = [];
	}
}
