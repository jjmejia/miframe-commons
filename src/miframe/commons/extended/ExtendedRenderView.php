<?php

/**
 * Genera páginas web mediante el uso de vistas (views).
 *
 * Hace uso de miframe_render() en las vistas sugeridas.
 *
 * @author John Mejía
 * @since Noviembre 2024
 */

namespace miFrame\Commons\Extended;

use miFrame\Commons\Core\RenderView;
use miFrame\Commons\Interfaces\FilterContentInterface;
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
	 * @var FilterContentInterface $filter Filtro para el contenido final (luego de aplicado Layout).
	 */
	private ?FilterContentInterface $contentFilter = null;


	/**
	 * Acciones a ejecutar al crear un objeto para esta clase.
	 */
	protected function singletonStart()
	{
		parent::singletonStart();
		// Adiciona layout por defecto
		$this->layout->config($this->localPathFiles('layout-default'), 'content');
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
	 * La función a usar para realizar el fitrado se define en un objeto
	 * creado a partir de una clase que implemente la interfaz FilterContentInterface
	 * (el filtro de aplica cuando se incluye el Layout al generar la vista).
	 *
	 * @param FilterContentInterface $filter Filtro a aplicar al contenido final.
	 */
	public function addLayoutFilter(FilterContentInterface $filter)
	{
		$this->contentFilter = $filter;
	}

	/**
	 * Redefine método renderLayout() de la clase RenderView.
	 *
	 * Adiciona filtrado al contenido una vez renderizado el Layout.
	 */
	protected function includeLayout(string &$content)
	{
		if ($this->includeLayoutNow()) {
			// Recupera estilos del repositorio de recursos
			$this->exportStyles($content);
			// Ejecuta método original
			parent::includeLayout($content);
			// Remueve Document Root de la salida a pantalla
			$this->removeDocumentRoot($content);
			// Aplica filtros adicionales
			if (!empty($this->filter)) {
				$this->contentFilter->filter($content);
			}
		}
	}

	/**
	 * Adiciona estilos al repositorio de recursos
	 *
	 * @param string $styles Estilos a guardar.
	 * @param string $comment Comentario a incluir en los estilos.
	 */
	public function saveStyles(string $styles, string $comment = '')
	{
		miframe_html()->cssInLine($styles, $comment);
	}

	/**
	 * Recupera estilos del repositorio de recursos y los añade al contenido.
	 *
	 * @param string $content Contenido de la vista a renderizar.
	 */
	public function exportStyles(string &$content)
	{
		$styles = miframe_html()->cssExport(true);
		if ($styles !== '') {
			$content = $styles . PHP_EOL . $content;
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
	 * Redefine el método viewPaths() de la clase RenderView.
	 *
	 * Adiciona path provisto por el método localPathFiles() para la
	 * busqueda de vistas predefinidas.
	 */
	protected function viewPaths(string $viewname): array
	{
		$options = parent::viewPaths($viewname);
		$options[] = $this->localPathFiles($viewname);
		return $options;
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
	 * Redefine método renderLayout() de la clase RenderView.
	 *
	 * Adiciona modificaciones al contenido en modo Debug una vez renderizada la vista.
	 */
	protected function renderView(string $filename, array $params): string
	{
		$content = parent::renderView($filename, $params);
		if ($content != '' && $this->developerMode && $this->debug) {
			$target = '';
			if ($this->currentView != '') {
				$target = $this->views[$this->currentView]['name'];
			}
			$new_content = $this->view(
				'show-frame-content-debug',
				compact('filename', 'target', 'content')
			);
			if ($new_content !== false) {
				// Nueva vista correctamente generada, actualiza contenido.
				return $new_content;
			}
		}

		return $content;
	}

}
