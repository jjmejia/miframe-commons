<?php

/**
 * Genera páginas web mediante el uso de vistas (views).
 *
 * @author John Mejía
 * @since Noviemre 2024
 */

namespace miFrame\Commons\Extended;

use miFrame\Commons\Core\RenderView;

class ExtendedRenderView extends RenderView
{
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
	public function singletonStart()
	{
		parent::singletonStart();
		// Adiciona layout por defecto
		$this->layout('layout-default');
	}

	/**
	 * Retorna arreglo con opciones de dónde buscar los archivos de vistas.
	 *
	 * Adiciona path para vistas predefinidas.
	 *
	 * @param string $viewname Nombre/Path de la vista.
	 *
	 * @return array Opciones de busqueda.
	 */
	protected function viewPaths(string $viewname): array
	{
		$options = parent::viewPaths($viewname);
		// Para views, busca en la librería de soporte local
		$options[] = __DIR__ . '/views/' . $viewname . '.php';

		return $options;
	}

	/**
	 * Captura el texto enviado a pantalla (o al navegador) por cada vista.
	 *
	 * Cuando se habilita el "modo Debug", se enmarca la salida capturada para
	 * facilitar su identificación en pantalla.
	 *
	 * @param string $filename Archivo que contiene la vista.
	 * @param array $params Arreglo con valores.
	 *
	 * @return string Contenido renderizado.
	 */
	protected function evalTemplate(string $filename, array $params): string
	{
		$content = parent::evalTemplate($filename, $params);
		return $this->frameContentDebug($filename, $content);
	}

	/**
	 * Incluye el contenido del layout en la vista.
	 *
	 * Una vez renderizado el layout, aplica los filtros programados al contenido
	 * generado.
	 *
	 * @param string $content Contenido de la vista a renderizar.
	 *
	 * @return string Contenido renderizado.
	 */
	protected function includeLayout(string &$content): bool
	{
		$result = parent::includeLayout($content);
		if ($result) {
			// Aplica filtros
			$this->filterContent($content);
		}

		return $result;
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
		return $this->dump($this->views, 'Views', true);
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
	 *
	 * @return string Texto formateado.
	 */
	public function dump(mixed $var, string $title = '', bool $escape_dump = true): string
	{
		$content = '';
		if ($this->developerMode) {
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
	 * Ejecuta la vista indicada de forma exclusiva.
	 *
	 * No permite que esta misma vista pueda invocarse de forma recursiva, si se
	 * invoca retorna el valor indicado por defecto sin ejecutar la vista. Si no
	 * se indica valor por defecto se genera un error capturable.
	 *
	 * Para prevenir replicar contenido de variables, en $default puede indicar el
	 * nombre de la variable en $params que desea usar en caso que ya esté en
	 * ejecución esta vista.
	 *
	 * @param string $viewname Nombre/Path de la vista.
	 * @param array $params Arreglo con valores.
	 * @param string $default Valor a retornar si esta misma vista ya está en ejecución.
	 *
	 * @return string Contenido renderizado.
	 */
	public function eview(string $viewname, array $params, string $default = ''): string
	{
		$content = '';
		if ($this->newTemplate($viewname, true)) {
			$content = $this->view($viewname, $params);
		} elseif ($default !== '') {
			// $default contiene la llave de uno de los $params
			// (esto para evitar duplicar contenido) o
			// una cadena texto a retornar
			$content = $default;
			if (array_key_exists($default, $params)) {
				$content = $params[$default];
			}
		}
		else {
			// Error capturable, use get_last_error()
			trigger_error("Vista exclusiva ya en ejecución ($viewname)", E_USER_NOTICE);
		}

		return $content;
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
	private function frameContentDebug(string $filename, string $content)
	{
		$target = $this->currentView;
		if ($content != '' && $this->developerMode && $this->debug) {
			$content = @$this->eview(
				'show-frame-content-debug',
				compact('content', 'filename', 'target'),
				'content'
			);
		}

		return $content;
	}

}
