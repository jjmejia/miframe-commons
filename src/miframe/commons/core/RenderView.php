<?php

/**
 * Genera páginas web mediante el uso de vistas (views).
 *
 * @author John Mejía
 * @since Noviemre 2024
 */

namespace miFrame\Commons\Core;

use miFrame\Commons\Patterns\Singleton;

class RenderView extends Singleton
{
	/**
	 * @var array $views Listado de vistas en ejecución.
	 */
	private array $views = [];

	/**
	 * @var array $once Listado de control para habilitar ejecuciones de código
	 * 					una única vez por sesión web.
	 */
	private array $once = [];

	/**
	 * @var array $filters Filtros a aplicar al código luego de incluir el Layout.
	 */
	private array $filters = [];

	/**
	 * @var string $pathFiles Path a buscar los scripts asociados a las vistas.
	 */
	private string $pathFiles = '';

	/**
	 * @var string $currentView Nombre de la vista actualmente en ejecución.
	 */
	private string $currentView = '';

	/**
	 * @var string $content Contenido acumulado de las diferentes vistas
	 * 						ejecutadas, a usar usualmente para incluirlo en el layout.
	 */
	private string $content = '';

	/**
	 * @var int $viewsCounter Contador de vistas ejecutadas, forma parte del nombre
	 * 						  dado a cada vista nueva.
	 */
	private int $viewsCounter = 0;

	/**
	 * @var bool $renderingLayout TRUE para incluir el layout al finalizar
	 * 							  la ejecución de la vista actual.
	 */
	private bool $renderingLayout = false;

	/**
	 * @var bool $developerMode TRUE para habilitar el modo Desarrollo. Permite
	 *                          habilitar o bloquear características.
	 */
	public bool $developerMode = false;

	/**
	 * @var bool $debug TRUE para habilitar mensajes de depuración en pantalla.
	 */
	public bool $debug = false;

	/**
	 * Acciones a ejecutar al crear un objeto para esta clase.
	 *
	 * Se inicializan atributos y se definen funciones a usar
	 * para el manejo de errores y excepciones, así como las
	 * acciones a ejecutar al terminar de ejecutar el script.
	 */
	public function singletonStart()
	{
		$this->newTemplate('layout');
		$this->layout('layout-default');
	}

	/**
	 * Asigna vista a usar como layout.
	 *
	 * El "layout" es la vista que habrá de contener todas las vistas
	 * ejecutadas a través de $this->view().
	 *
	 * @param string $viewname Nombre/Path de la vista layout.
	 */
	public function layout(string $viewname): self
	{
		$this->checkFile($viewname, 'layout');
		$this->resetParams('layout');
		return $this;
	}

	/**
	 * Remueve layout.
	 *
	 * No destruye el elemento, solamente inicializa el archivo asociado.
	 */
	public function removeLayout()
	{
		$this->views['layout']['file'] = '';
	}

	/**
	 * Valida nombre/path dado a una vista.
	 *
	 * Busca algún archivo que cumpla con alguna de estas condiciones:
	 *
	 * - Se encuentre en el directorio $this->pathFiles (si se ha configurado).
	 * - Se encuentre tal cual como ha sido indicado.
	 * - Sea una de las vistas incluidas por defecto.
	 *
	 * El sistema asume que el archivo que contiene la vista es un script PHP y
	 * por tanto no es necesario indicar la extensión ".php" en $viewname (hacerlo
	 * tampoco genera error, en caso que se requiera referirlo completo).
	 *
	 * Si el nombre dado no corresponde a un archivo físico, se generará un error.
	 *
	 * @param string $viewname Nombre/Path de la vista.
	 * @param string $reference Referencia asociada a la vista que usará el archivo.
	 */
	private function checkFile(string $viewname, string $reference)
	{
		// Limpia valor previo
		$this->views[$reference]['file'] = '';

		$viewname = trim($viewname);
		if ($viewname !== '') {
			$options = [];
			if ($this->pathFiles !== '') {
				// $filename no contiene path ni extensión (comportamiento por defecto)
				$options[] = $this->pathFiles . $viewname . '.php';
				// $filename no contiene path pero si la extensión
				$options[] = $this->pathFiles . $viewname;
			}
			// $filename contiene path pero no la extensión
			$options[] = $viewname . '.php';
			// Path completo dado por el usuario
			$options[] = $viewname;
			// Para views, busca en la librería de soporte local
			$options[] = __DIR__ . '/views/' . $viewname . '.php';

			foreach ($options as $path) {
				if (!is_file($path)) {
					// Intenta el mismo pero todo en minusculas para el nombre base,
					// en caso que el SO diferencia may/min (Linux)
					$path = dirname($path) . DIRECTORY_SEPARATOR . strtolower(basename($path));
				}
				if (is_file($path)) {
					$this->views[$reference]['file'] = realpath($path);
					return;
				}
			}
		}

		// Si llega a este punto, dispara un error
		trigger_error(
			"Path para vista solicitada no es valido ({$viewname})",
			E_USER_ERROR
		);
	}

	/**
	 * Registra parámetros (valores) a usar para generar el layout.
	 *
	 * @param array $params Arreglo con valores.
	 *
	 * @return
	 */
	public function globals(array $params): self
	{
		$this->saveParams($params, 'layout');
		return $this;
	}

	/**
	 * Recupera valor de parámetro asignado al layout.
	 *
	 * Se provee este método para su uso en vistas. Cuando se genera el
	 * layout, igual que ocurre con las vistas, el valor de cada parámetro
	 * se exporta para su uso directo en la vista. No es necesario usar
	 * este método en el script usado para el layout.
	 *
	 * @param string $name Nombre del parámetro a recuperar.
	 * @param mixed $default Valor a retornar si el parámetro no existe.
	 *
	 * @return mixed Valor del parámetro solicitado.
	 */
	public function global(string $name, mixed $default = ''): mixed
	{
		return (
			array_key_exists($name, $this->views['layout']['params']) ?
			$this->views['layout']['params'][$name] :
			$default
		);
	}

	/**
	 * Registra arreglo con los valores a usar para generar las páginas.
	 *
	 * @param array $params Arreglo con valores.
	 * @param string $reference Referencia asociada a la vista que usará los valores.
	 */
	private function saveParams(array &$params, string $reference)
	{
		// Los nuevos valores remplazan los anteriores
		if (isset($this->views[$reference]['params'])) {
			$this->views[$reference]['params'] = $params + $this->views[$reference]['params'];
		} else {
			$this->views[$reference]['params'] = $params;
		}
	}

	/**
	 * Limpia arreglo de valoers asociado a una vista.
	 *
	 * @param string $reference Referencia asociada a la vista que desea limpiar.
	 */
	private function resetParams(string $reference)
	{
		$this->views[$reference]['params'] = [];
	}

	/**
	 * Indica el directorio que contiene los archivos a usar para generar las vistas.
	 *
	 * Si el directorio indicado no existe, genera un error.
	 *
	 * @param string $path Path
	 */
	public function location(string $path)
	{
		if ($path == '' || !is_dir($path)) {
			trigger_error(
				'El path indicado para buscar las vistas no es valido (' . $path . ')',
				E_USER_ERROR
			);
		}
		// Registra valor
		$this->pathFiles = realpath($path) . DIRECTORY_SEPARATOR;
	}

	/**
	 * Incluye el contenido del layout en la vista.
	 *
	 * Se encarga de ejecutar el layout solo si no hay views pendientes.
	 * La ejecución del layout se hace solo una vez, justo antes de cerrar la vista.
	 * Las vistas ejecutadas pueden modificar el archivo de layout a usar.
	 * Una vez renderizado el layout, aplica los filtros programados al contenido
	 * generado.
	 *
	 * @param string $content Contenido de la vista a renderizar.
	 *
	 * @return string Contenido renderizado.
	 */
	private function includeLayout(string $content): string
	{
		// Ejecuta layout (si alguno)
		// Nota: Al ejecutar "view" puede modificar el layout
		// Solamente ejecuta si no hay views pendientes.
		if (
			!$this->renderingLayout &&
			$this->currentView == 'layout'
		) {
			if (!empty($this->currentFile())) {
				// Restablece control para prevenir se use al evaluar template
				$this->renderingLayout = true;
				// Asigna a propiedad para permitir su uso en las vistas
				$this->content = $content;
				// Ejecuta vista
				$content = $this->evalTemplate();
				// Libera memoria
				$this->content = '';
				// Restablece
				$this->renderingLayout = false;
			}

			// Aplica filtros
			$content = $this->filterContent($content);
		}

		return $content;
	}

	/**
	 * Crea espacio para una nueva vista a ejecutar.
	 *
	 * Si se indica valor de referencia, valida que no exista uno ya asignado
	 * a ese nombre.
	 *
	 * @param string $reference Referencia deseada.
	 *
	 * @return bool TRUE si crea el espacio para la nueva vista, FALSE si la referencia
	 * 				deseada ya existe.
	 */
	private function newTemplate(string $reference = ''): bool
	{
		if ($reference == '') {
			$this->viewsCounter++;
			$reference = 'view' . $this->viewsCounter;
		} elseif (isset($this->views[$reference])) {
			// Ya existe referencia
			return false;
		}
		if ($reference !== false) {
			$this->views[$reference] = ['pre' => $this->currentView, 'file' => '', 'params' => []];
			$this->currentView = $reference;
		}

		return true;
	}

	/**
	 * Remueve espacio para la vista actual y restablece control a aquella que la invocó (si alguna).
	 */
	private function removeTemplate()
	{
		$reference = $this->currentView;
		if (isset($this->views[$reference])) {
			// Restablece la vista anterior (o false si no existe)
			$this->currentView = $this->views[$reference]['pre'];
			unset($this->views[$reference]);
		}
	}

	/**
	 * Ejecuta la vista indicada.
	 *
	 * Permite que esta misma vista pueda invocarse de forma recursiva si así
	 * lo requiere.
	 *
	 * @param string $viewname Nombre/Path de la vista.
	 * @param array $params Arreglo con valores.
	 *
	 * @return string Contenido renderizado.
	 */
	public function view(string $viewname, array $params): string
	{
		// Adiciona control de views (para prevenir se superpongan valores)
		$this->newTemplate();
		return $this->show($viewname, $params);
	}

	/**
	 * Ejecuta vista actual.
	 *
	 * @param string $viewname Nombre/Path de la vista.
	 * @param array $params Arreglo con valores (por referencia).
	 *
	 * @return string Contenido renderizado.
	 */
	private function show(string $viewname, array &$params)
	{
		// Valida nombre y asigna parámetros (variables de la vista)
		$this->checkFile($viewname, $this->currentView);
		// Preserva argumentos asociados a la referencia para uso
		$this->saveParams($params, $this->currentView);
		// Ejecuta vista
		$content = $this->evalTemplate();
		// Restablece target previo y elimina último "view" de la lista
		$this->removeTemplate();

		// Valida si se incluye layout en esta vista
		$content = $this->includeLayout($content);

		return $content;
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
		$key = $this->generateKey($viewname);
		if ($key !== '') {
			$reference = $this->newTemplate($key);
			// $reference será vacio si ya existe asociada la llave
			if ($reference !== false) {
				$content = $this->show($viewname, $params);
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
		}

		return $content;
	}

	/**
	 * Retorna archivo asociado a la vista actual.
	 *
	 * @return string Archivo.
	 */
	private function currentFile()
	{
		return $this->views[$this->currentView]['file'];
	}

	/**
	 * Realiza la inclusión de los scripts de vistas.
	 *
	 * Para prevenir que pueda modificarse directamente esta clase, en lugar de
	 * simplemente usar "include" para incluir (y por consiguiente ejecutar) el
	 * script de vista, este se incluye usando una función "static" que lo aisla del
	 * entorno actual. En consecuencia, el nombre de los parámetros esperados por
	 * esta función ($view_filename y $view_args) pueden ser invocados en cada vista
	 * si así lo requiere.
	 *
	 * El contenido del arreglo $view_args se exporta al entorno de la
	 * función para facilitar su uso en los scripts de vistas. Por tanto y para prevenir
	 * colisiones (en cuyo caso el valor del arreglo será ignorado) se sugiere no usar
	 * dichos nombres en los valores asignados a la vista.
	 */
	private function includeView()
	{
		// La define como una función estática para no incluir $this
		$fun = static function (string $view_filename, array &$view_args) {

			if (count($view_args) > 0) {
				// EXTR_SKIP previene use $filename o $args y genere colisión de valores.
				// Se extraen como valores referencia para evitar duplicados.
				extract($view_args, EXTR_SKIP | EXTR_REFS);
			}

			// Libera memoria? (No, se requieren porque se registran como referencias)
			// unset($include_args);

			include $view_filename;
		};

		// Ejecuta
		$fun($this->currentFile(), $this->views[$this->currentView]['params']);
	}

	/**
	 * Captura el texto enviado a pantalla (o al navegador) por cada vista.
	 *
	 * Cuando se habilita el "modo Debug", se enmarca la salida capturada para
	 * facilitar su identificación en pantalla.
	 *
	 * @return string Contenido renderizado.
	 */
	private function evalTemplate(): string
	{
		$content = '';

		if ($this->currentFile() !== '') {
			// Inicia captura de texto
			ob_start();
			// Ejecuta
			$this->includeView();
			// Captura contenido
			$content = ob_get_clean();

			// Enmarca respuesta (si aplica)
			$content = $this->frameContentDebug($content);
		}

		return $content;
	}

	/**
	 * Enmarca el contenido renderizado para facilitar su identificación en pantalla.
	 *
	 * Requiere que se encuentre activo tanto el "modo Debug" ($this->debug = true)
	 * como el "modo Desarrollo" ($this->developerMode = true).
	 *
	 * @param string $content Contenido previamente renderizado.
	 *
	 * @return string Contenido renderizado.
	 */
	private function frameContentDebug(string $content)
	{
		$target = $this->currentView;
		if ($this->developerMode && $this->debug) {
			$filename = $this->currentFile();
			if ($filename == '') {
				$filename = '(Layout no asignado)';
			}
			$content = @$this->eview(
				'show-frame-content-debug',
				compact('content', 'filename', 'target'),
				'content'
			);
		}

		return $content;
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
		if (!isset($this->once[$reference])) {
			$this->once[$reference] = $source;
			// print_r($this->once);
			return true;
		}

		return false;
	}

	/**
	 * Retorna contenido renderizado en vistas previas.
	 *
	 * Este método solamente retorna contenido valido cuando se invoca desde el
	 * Layout o desde una vista contenida en el Layout.
	 *
	 * @return string Contenido renderizado en vistas anteriores.
	 */
	public function contentView(): string
	{
		return $this->content;
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
	 * Genera llave para arreglos.
	 *
	 * @param string $name Nombre base a usar para generar la llave.
	 *
	 * @return string Llave.
	 */
	private function generateKey(string $name): string
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
	 * Aplica al contenido los filtros previamente configurados.
	 *
	 * @param string $content Contenido renderizado previamente.
	 *
	 * @return string Contenido renderizado.
	 */
	private function filterContent(string $content): string
	{
		// Aplica filtros adicionales
		foreach ($this->filters as $data) {
			// print_r($data); echo "<hr>";
			$content = call_user_func($data['fun'], $content);
		}

		return $content;
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
}
