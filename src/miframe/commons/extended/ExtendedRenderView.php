<?php

/**
 * Genera páginas web mediante el uso de vistas (views).
 *
 * Hace uso de miframe_render() en las vistas sugeridas.
 *
 * @author John Mejía
 * @since Noviemre 2024
 */

namespace miFrame\Commons\Extended;

use Exception;
use miFrame\Commons\Core\RenderView;
use miFrame\Commons\Traits\RemoveDocumentRootContent;

class ExtendedRenderView extends RenderView
{
	use RemoveDocumentRootContent;

	/**
	 * @var bool $developerMode TRUE para habilitar el modo Desarrollo. Permite
	 *                          habilitar o bloquear características.
	 */
	private bool $developerMode = false;

	/**
	 * @var bool $debug TRUE para habilitar mensajes de depuración en pantalla.
	 */
	public bool $debug = false;

	/**
	 * @var array $ctlOnce Listado de control para habilitar ejecuciones de código
	 * 					   una única vez por sesión web.
	 */
	private array $ctlOnce = [];

	/**
	 * @var array $filters Filtros a aplicar al código luego de incluir el Layout.
	 */
	private array $filters = [];

	/**
	 * Acciones a ejecutar al crear un objeto para esta clase.
	 */
	protected function singletonStart()
	{
		parent::singletonStart();
		// Adiciona layout por defecto
		$this->layout($this->localPathFiles('layout-default'), 'content');
		// Deshabilita salida a pantalla de mensajes de error
		// (se habilita solo para modo Desarrollo)
		ini_set("display_errors", "off");
	}

	/**
	 * Path para buscar vistas predefinidas.
	 *
	 * @param string $viewname Nombre/Path de la vista.
	 *
	 * @return string Path.
	 */
	private function localPathFiles(string $viewname): string
	{
		return __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $viewname . '.php';
	}

	/**
	 * Habilita modo Desarrollo (DeveloperMode)
	 */
	public function developerOn()
	{
		$this->developerMode = true;
		// Habilita reporte de todos los errores
		error_reporting(E_ALL);
		// Habilita salida a pantalla de mensajes de error
		ini_set("display_errors", "on");
	}

	/**
	 * Indica si está en modo Desarrollo (DeveloperMode)}
	 *
	 * @return bool TRUE si está en modo Desarrollo, FALSE en otro caso
	 * 				(también referido como modo Producción)
	 */
	public function inDeveloperMode(): bool
	{
		return $this->developerMode;
	}

	/**
	 * Genera llave para arreglos.
	 *
	 * @param string $name Nombre base a usar para generar la llave.
	 *
	 * @return string Llave.
	 */
	protected function generateKey(string $name): string
	{
		$key = trim($name);
		if ($key !== '') {
			// Adiciona "#" para evitar que el valor retornado
			// sea interpretado como número y llegue a generar errores
			// o falsos positivos si se usa en validaciones del tipo
			// ($key1 == $key2)
			$key = '#' . sha1(strtolower($name));
		}

		return $key;
	}

	/**
	 * Adiciona filtro a usar para depurar el contenido renderizado.
	 *
	 * La función a usar para realizar el fitrado debe ser del tipo:
	 *
	 *     function (string $content)
	 *     {
	 *         // Procesa contenido y lo retorna en la misma variable
	 *         // u otra de su preferencia.
	 *         return $content;
	 *     }
	 *
	 * @param string $name Nombre del filtro.
	 * @param callable $fun Función a ejecutar.
	 */
	public function addLayoutFilter(string $name, callable $fun)
	{
		$key = $this->generateKey($name);
		if ($key !== '') {
			$this->filters[$key] = ['name' => $name, 'fun' => $fun];
		}
	}

	/**
	 * Remueve filtro previamente asignado.
	 *
	 * @param string $name Nombre del filtro.
	 */
	public function removeLayoutFilter(string $name)
	{
		$key = $this->generateKey($name);
		if ($key !== '' && isset($this->filters[$key])) {
			unset($this->filters[$key]);
		}
	}

	/**
	 * Retorna el listado de filtros configurados.
	 *
	 * @return array Listado de filtros.
	 */
	public function getFilters()
	{
		return $this->filters;
	}

	/**
	 * Aplica al contenido los filtros previamente configurados.
	 *
	 * @param string $content Contenido renderizado previamente.
	 */
	private function filterContent(string &$content)
	{
		// Remueve Document Root de la salida a pantalla
		$this->removeDocumentRoot($content);

		// Aplica filtros adicionales
		foreach ($this->filters as $data) {
			$content = call_user_func($data['fun'], $content);
		}
	}

	/**
	 * Retorna a pantalla información de las vistas actuales.
	 *
	 * @return string Texto formateado.
	 */
	public function dumpViews()
	{
		return $this->dump($this->views, 'Views');
	}

	/**
	 * Realiza volcado de datos en pantalla.
	 *
	 * La información a mostrar se enmarca usando la vista "show-dump". Se usa un
	 * modelo predefinido para esta vista, aunque puede ser personalizada creando una
	 * vista con el mismo nombre en el directorio que contiene las vistas de usuario.
	 *
	 * Requiere que se encuentre activo tanto el "modo Debug" ($this->debug = true)
	 * como el "modo Desarrollo" ($this->developerMode = true) o de lo contrario
	 * retornará una cadena vacia.
	 *
	 * @param mixed $var Variable a mostrar contenido.
	 * @param string $title Título a usar al mostrar contenido.
	 * @param bool $escape_dump TRUE para mostrar información legible (para humanos) sobre
	 * 							el contenido de $var. FALSE muestra el contenido tal
	 * 							cual sin modificar su formato.
	 * @param bool $ignore_empty TRUE para no generar texto alguno si la variable está vacia.
	 *
	 * @return string Texto formateado.
	 */
	public function dump(mixed $var, string $title = '', bool $escape_dump = true, bool $ignore_empty = false): string
	{
		$content = '';
		if ($this->developerMode && (!$ignore_empty || !empty($var))) {
			if ($escape_dump) {
				// Convierte en texto protegido
				$var = htmlspecialchars(print_r($var, true));
				// Complementa titulo
				$title = trim('<b>DUMP</b> ' . $title);
			}

			$content = $this->view('show-dump', compact('var', 'title'));
		}

		return $content;
	}

	/**
	 * Busca vista predefinida.
	 *
	 * Primero busca el archivo en el directorio de vistas asignado en
	 * $this->pathFiles. Si no lo encuentra, intenta en el directorio
	 * de vistas predefinidas usadas por esta clase.
	 *
	 * @param string $viewname Nombre/Path de la vista.
	 * @param array $params Arreglo con valores.
	 *
	 * @return string Contenido renderizado o FALSE si la vista ya está en ejecución.
	 */
	public function view(string $viewname, array $params): false|string
	{
		$filename = $this->findView($viewname);
		if ($filename === '') {
			// Busca en el directorio actual
			$filename = $this->findView($viewname, $this->localPathFiles($viewname));
			if ($filename === '') {
				// Si llega a este punto, dispara un error?
				// No, porque si ocurre en una atención a errores, bloquea salida.
				// En su lugar, produce una salida estándar.
				// trigger_error($message, E_USER_ERROR);
				return $this->contentError("Vista predefinida no encontrada ({$viewname})");
			}
		}

		$content = parent::view($viewname, $params);

		if ($content !== false) {
			$content = $this->frameContentDebug($filename, $content);
			if ($this->layoutUsed()) {
				// Aplica filtros
				$this->filterContent($content);
			}
		}

		return $content;
	}

	/**
	 * Registra error al procesar vistas predefinidas.
	 *
	 * Si se encuentra en Producción, enmascara el mensaje pero lo registra
	 * debidamente en el log de errores.
	 *
	 * @param sstring $message Mensaje de error.
	 *
	 * @return string Texto renderizado en formato HTML.
	 */
	private function contentError(string $message): string
	{
		// Recupera línea de dónde se solicita la vista
		// $message = "Vista predefinida no encontrada ({$viewname})";
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		// Si se invoca directo, usaría [0]. Validar caso si ocurre.
		if (isset($trace[1])) {
			$message .= " en \"{$trace[1]['file']}\" línea {$trace[1]['line']}";
		}
		// Registra mensaje en el log de errores
		error_log('VIEW/ERROR: ' . $message);
		// Si no está en desarrollo, enmascara mensaje
		if (!$this->developerMode) {
			// Redefine mensaje para ambientes de producción
			$message = 'No pudo mostrar contenido, favor revisar el log de errores';
		}

		return "<div style=\"background: #fadbd8; padding: 15px; margin: 5px 0\"><b>Error:</b> {$message}</div>";
	}

	/**
	 * Control para permitir una única ejecución de contenidos.
	 *
	 * @return bool TRUE para cuando se invoca la primera vez. FALSE en otro caso.
	 */
	public function once()
	{
		// print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)); echo "<hr>";
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		// Elemento "0" corresponde a la linea de donde se invoca este método
		$source = $trace[0]['file'] . ':' . $trace[0]['line'];
		$reference = $this->generateKey($source);
		if (!isset($this->ctlOnce[$reference])) {
			$this->ctlOnce[$reference] = $source;
			// print_r($this->once);
			return true;
		}

		return false;
	}

	/**
	 * Valida si una vista se encuentra en ejecución.
	 */
	public function isRendering(string $viewname)
	{
		return (!$this->newTemplate($viewname, true));
	}

	/**
	 * Enmarca el contenido renderizado para facilitar su identificación en pantalla.
	 *
	 * Requiere que se encuentre activo tanto el "modo Debug" ($this->debug = true)
	 * como el "modo Desarrollo" ($this->developerMode = true).
	 *
	 * @param string $filename Archivo que contiene la vista.
	 * @param string $content Contenido previamente renderizado.
	 *
	 * @return string Contenido renderizado.
	 */
	private function frameContentDebug(string $filename, string $content): string
	{
		if ($content != '' && $this->developerMode && $this->debug) {
			$target = $this->currentView;
			$new_content = $this->view(
				'show-frame-content-debug',
				compact('filename', 'target', 'content')
			);
			if ($new_content !== false) {
				return $new_content;
			}
		}

		return $content;
	}

}
