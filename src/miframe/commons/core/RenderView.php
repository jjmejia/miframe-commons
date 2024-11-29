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
	protected array $views = [];

	/**
	 * @var string $pathFiles Path a buscar los scripts asociados a las vistas.
	 */
	private string $pathFiles = '';

	/**
	 * @var string $currentView Nombre de la vista actualmente en ejecución.
	 */
	protected string $currentView = '';

	/**
	 * @var string $content Contenido acumulado de las diferentes vistas
	 * 						ejecutadas, a usar usualmente para incluirlo en el layout.
	 */
	private string $content = '';

	/**
	 * @var string $idLayout Identificador asociado al layout.
	 */
	private string $idLayout = '';

	/**
	 * @var bool $renderingLayout TRUE para incluir el layout al finalizar
	 * 							  la ejecución de la vista actual.
	 */
	private bool $renderingLayout = false;

	/**
	 * Acciones a ejecutar al crear un objeto para esta clase.
	 */
	public function singletonStart()
	{
		$this->newTemplate('layout');
		// Preserva identificador
		$this->idLayout = $this->currentView;
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
		$this->checkFile($viewname, $this->idLayout);
		$this->resetParams($this->idLayout);
		return $this;
	}

	/**
	 * Remueve layout.
	 *
	 * No destruye el elemento, solamente inicializa el archivo asociado.
	 */
	public function removeLayout()
	{
		$this->views[$this->idLayout]['file'] = '';
	}

	/**
	 * Retorna arreglo con opciones de dónde buscar los archivos de vistas.
	 *
	 * Busca algún archivo que cumpla con alguna de estas condiciones:
	 *
	 * - Se encuentre en el directorio $this->pathFiles (si se ha configurado).
	 * - Se encuentre tal cual como ha sido indicado.
	 *
	 * El sistema asume que el archivo que contiene la vista es un script PHP y
	 * por tanto no es necesario indicar la extensión ".php" en $viewname (hacerlo
	 * tampoco genera error, en caso que se requiera referirlo completo).
	 *
	 * @param string $viewname Nombre/Path de la vista.
	 *
	 * @return array Opciones de busqueda.
	 */
	protected function viewPaths(string $viewname): array
	{
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

		return $options;
	}

	/**
	 * Valida nombre/path dado a una vista.
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
			foreach ($this->viewPaths($viewname) as $path) {
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
		$this->saveParams($params, $this->idLayout);
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
			array_key_exists($name, $this->views[$this->idLayout]['params']) ?
			$this->views[$this->idLayout]['params'][$name] :
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
	 *
	 * @param string $content Contenido de la vista a renderizar.
	 *
	 * @return bool TRUE si debe incluir layout, FALSE en otro caso.
	 */
	protected function includeLayout(string &$content): bool
	{
		// Ejecuta layout (si alguno)
		// Nota: Al ejecutar "view" puede modificar el layout
		// Solamente ejecuta si no hay views pendientes.
		$result = (
			!$this->renderingLayout &&
			$this->currentView == $this->idLayout
		);
		if ($result && !empty($this->currentFile())) {
			// Protege la ejecución del Layout
			$this->renderingLayout = true;
			// Preserva el contenido previamente renderizado para su uso en el Layout
			$this->content = $content;
			// Ejecuta vista
			$content = $this->evalTemplate();
			// Libera memoria
			$this->content = '';
			// Habilita de nuevo la ejecución del Layout
			$this->renderingLayout = false;
		}

		return $result;
	}

	/**
	 * Valida si una referencia dada ya existe.
	 *
	 * @param string $viewname Nombre/Path de la vista.
	 *
	 * @return bool TRUE si la vista ya está en ejecución, FALSE en otro caso.
	 */
	protected function isRunning(string $viewname): bool
	{
		$reference = md5($viewname);
		return isset($this->views[$reference]);
	}

	/**
	 * Crea espacio para una nueva vista a ejecutar.
	 *
	 * Si se indica valor de referencia, valida que no exista uno ya asignado
	 * a ese nombre.
	 *
	 * @param string $viewname Nombre/Path de la vista.
	 *
	 * @return bool TRUE si crea el espacio para la nueva vista, FALSE si la referencia
	 * 				deseada ya existe.
	 */
	private function newTemplate(string $viewname): bool
	{
		$reference = md5($viewname);
		$this->views[$reference] = ['parent' => $this->currentView, 'file' => '', 'params' => []];
		// Actualiza vista actual
		$this->currentView = $reference;

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
			$this->currentView = $this->views[$reference]['parent'];
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
		$content = '';
		if (!$this->isRunning($viewname)) {
			// Adiciona control de views (para prevenir se superpongan valores)
			$this->newTemplate($viewname);
			// Valida nombre y asigna parámetros (variables de la vista)
			$this->checkFile($viewname, $this->currentView);
			// Preserva argumentos asociados a la referencia para uso
			$this->saveParams($params, $this->currentView);
			// Ejecuta vista
			$content = $this->evalTemplate();
			// Restablece target previo y elimina último "view" de la lista
			$this->removeTemplate();
			// Valida si se incluye layout en esta vista
			$this->includeLayout($content);
		}

		return $content;
	}

	/**
	 * Retorna archivo asociado a la vista actual.
	 *
	 * @return string Archivo.
	 */
	protected function currentFile()
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
	protected function evalTemplate(): string
	{
		$content = '';

		if ($this->currentFile() !== '') {
			// Inicia captura de texto
			ob_start();
			// Ejecuta
			$this->includeView();
			// Captura contenido
			$content = ob_get_clean();
		}

		return $content;
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
}
