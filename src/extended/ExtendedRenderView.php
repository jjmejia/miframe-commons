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

use miFrame\Commons\Core\ErrorHandler;
use miFrame\Commons\Core\RenderView;
use miFrame\Commons\Interfaces\FilterContentInterface;
use miFrame\Commons\Traits\SanitizeRenderContent;

class ExtendedRenderView extends RenderView
{
	use SanitizeRenderContent;

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
	private FilterContentInterface $contentFilter;

	/**
	 * @var string $layoutViewname Nombre de la vista a usar como Layout.
	 */
	private string $layoutViewname = '';

	/**
	 * @var string $theMainView Nombre de la vista principal (capturada automáticamente).
	 */
	private string $theMainView = '';

	/**
	 * @var ErrorHandler $errors Manejador de errores.
	 */
	private ErrorHandler $errors;

	/**
	 * Acciones a ejecutar al crear un objeto para esta clase.
	 */
	protected function singletonStart()
	{
		parent::singletonStart();
		// Adiciona layout por defecto
		$this->layout('layout');
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
	 * Captura el texto enviado a pantalla (o al navegador) por cada vista.
	 *
	 * @param string $filename Archivo que contiene la vista.
	 * @param array $params Arreglo con valores.
	 *
	 * @return string Contenido renderizado.
	 */
	protected function renderView(string $filename, array $params = []): string
	{
		// Registra primera vista como la vista principal
		// (a menos que se registre como vista sin layout)
		if ($this->theMainView === '' && $this->layoutExists()) {
			$this->theMainView = $filename;
		}

		// Renderiza vista en la forma tradicional
		$content = parent::renderView($filename, $params);

		if (!empty($content) && $this->developerMode && $this->debug) {
			$this->showFrameContentDebug($content, $filename);
		}

		// Validación posterior (cuando termina la ejecución del vista principal)
		if ($content !== false && $filename === $this->theMainView) {
			$this->validateMainContent($content);
		}

		return $content;
	}

	/**
	 * Valida el contenido de la vista principal.
	 *
	 * Esta función es llamada justo después de renderizar la vista principal
	 * (la primera vista renderizada). Su función es:
	 *
	 * 1. Incluir el contenido del layout en la vista (si se ha definido
	 *    un layout).
	 * 2. Recuperar los estilos definidos en el layout y los incluye en
	 *    la vista.
	 * 3. Remueve el Document Root de la salida a pantalla.
	 * 4. Aplica filtros adicionales definidos en el atributo $filter.
	 *
	 * @param string $content Contenido renderizado de la vista principal.
	 *
	 * @return string Contenido renderizado de la vista principal.
	 *                Se actualiza para incluir el contenido del layout.
	 */
	private function validateMainContent(string &$content)
	{

		$unique_mark = '';

		// Incluye layout
		if ($this->layoutExists()) {
			// Adiciona marca para incluir estilos
			// (no los adiciona directamente por si se adicionan nuevos
			// estilos durante la visualización del layout)
			// Se marca previamente para garantizar que se incluyan aunque
			// ocurra algun evento de error durante la inclusión del layout.
			$unique_mark = uniqid('@styles:', true) . PHP_EOL;
			$content = $unique_mark . $content;
			$this->includeLayout($content);
		}

		// Recupera estilos
		$this->exportStyles($content, $unique_mark);
		// Remueve Document Root de la salida a pantalla
		$this->sanitizeDocumentRoot($content);
		// Aplica filtros adicionales
		if (!empty($this->filter)) {
			$this->contentFilter->filter($content);
		}
	}

	/**
	 * Incluye el contenido del layout en la vista.
	 *
	 * @param string $content Contenido renderizado de la vista principal.
	 *                        Se actualiza para incluir el contenido del layout.
	 */
	private function includeLayout(string &$content)
	{
		// Recupera path real del layout
		// Nota: Al recuperarlo justo antes de invocar el layout, permite a la aplicación
		// poder cambiarlo en algún momento.
		$filename = $this->checkFile($this->layoutViewname);
		if ($filename !== '') {
			// Asigna contenido a una variabe que pueda ser invocada en la vista
			$params = ['RenderViewContent' => $content];
			// Ejecuta vista y recupera nuevo contenido
			$content = $this->renderView(
				$filename,
				$params
			);
		}
	}

	/**
	 * Adiciona estilos al repositorio de recursos
	 *
	 * Si es invocado dentro del Layout, lo exporta de inmediato.
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
	 * @param string $replaceMark Texto a buscar para remplazar con los estilos.
	 */
	public function exportStyles(string &$content, string $replaceMark = '')
	{
		$styles = miframe_html()->cssExport(true);
		if ($replaceMark !== '') {
			$content = str_replace($replaceMark, $styles, $content);
		}
		elseif ($styles !== '') {
			$content = $styles . PHP_EOL . $content;
		}
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
			// Carga vista respectiva
			$content = $this->capture('show-dump', compact('var', 'title'));
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
			return true;
		}

		return false;
	}

	/**
	 * Adiciona marcas en pantalla para identificar la vista que lo genera.
	 *
	 * Solamente funciona cuando se usa la opció de "modo Debug".
	 *
	 * @param string $content Contenido de la vista a renderizar.
	 * @param string $filename Archivo de la vista a renderizar.
	 */
	private function showFrameContentDebug(string &$content, string $filename)
	{
		$target = '';
		if ($this->currentView != '') {
			$target = $this->views[$this->currentView]['name'];
		}
		$content = $this->capture(
			'show-frame-content-debug',
			compact('filename', 'target', 'content'),
			$content
		);
	}

	/**
	 * Establece o recupera el nombre de la vista a usar como Layout.
	 *
	 * El layout es una vista adicional que se invoca automáticamente luego de
	 * rederizar la vista principal (primer vista invocada).
	 *
	 * Si se invoca sin parámetros, retorna el nombre actual.
	 *
	 * @param string $viewname [optional] Nombre de la vista a usar como Layout.
	 * @return string Nombre actual de la vista Layout.
	 */
	public function layout(string $viewname = ''): string
	{
		$viewname = trim($viewname);
		if ($viewname !== '') {
			$this->layoutViewname = $viewname;
		}
		return $this->layoutViewname;
	}

	/**
	 * Elimina el uso de Layout en la vista actual.
	 */
	public function layoutRemove()
	{
		$this->layoutViewname = '';
	}

	/**
	 * Verifica si se ha definido un nombre para la vista Layout actual.
	 *
	 * @return bool TRUE si se ha definido un nombre de vista Layout, FALSE en otro caso.
	 */
	private function layoutExists(): bool
	{
		return ($this->layoutViewname !== '');
	}

	/**
	 * Habilita la captura de una nueva vista principal a usar con Layout.
	 *
	 * Reinicia el nombre de la vista principal, permitiendo que una vista
	 * posterior pueda ser considerada como la vista principal y así renderizarla
	 * con Layout. El Layout solamente se aplica a la vista principal, tantas
	 * veces como sea invocada.
	 */
	public function layoutReset()
	{
		$this->theMainView = '';
	}

	/**
	 * Captura contenido de una vista sin aplicar Layout.
	 *
	 * La vista a capturar se ejecuta sin considerar el Layout actual. Luego de
	 * ejecutar la vista, el Layout se restablece a su valor original.
	 *
	 * Si la vista a capturar no existe, se devuelve el valor de $default.
	 *
	 * @param string $viewname Nombre de la vista a capturar.
	 * @param array $params Arreglo de parámetros a enviar a la vista.
	 * @param string $default Valor a devolver si la vista no existe.
	 *
	 * @return string Contenido devuelto por la vista o $default si no existe.
	 */
	public function capture(string $viewname, array $params = [], string $default = ''): string
	{
		// Preserva layout actual y luego lo remueve
		$layout_back = $this->layout();
		$this->layoutRemove();
		// Ejecuta vista sin layout (no marca "mainView" si esta es la primer invocación)
		$content = $this->view($viewname, $params);
		// Restablece el layput
		$this->layout($layout_back);
		// Valida respuesta
		if ($content === false) {
			return $default;
		}
		return $content;
	}

	/**
	 * Asigna un objeto ErrorHandler para reporte de errores.
	 *
	 * @param ErrorHandler $errors Objeto que reportará los errores.
	 */
	public function errorHandler(ErrorHandler $errors) {
		$this->errors = $errors;
	}

	/**
	 * Genera evento de error y termina la ejecución del script.
	 *
	 * Si se ha asignado un objeto ErrorHandler, se utiliza para reportar el error.
	 * Si no se ha asignado, se utiliza el método error() de la clase padre.
	 *
	 * @param string $message Mensaje de error.
	 * @param string $file [optional] Archivo en el que se produjo el error.
	 * @param int $line [optional] Número de línea en el que se produjo el error.
	 */
	public function error(string $message, string $file = '', int $line = 0)
	{
		if (!empty($this->errors)) {
			$this->errors->showError(E_USER_ERROR, $message, $file, $line);
		}
		parent::error($message, $file, $line);
	}
}
