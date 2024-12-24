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
	 * @var array $viewCache Path de vistas ya encontradas.
	 */
	private array $viewCache = [];

	/**
	 * @var string $currentView Nombre de la vista actualmente en ejecución.
	 */
	protected string $currentView = '';

	/**
	 * @var object $layout Datos asociados al layout.
	 */
	private object $layout;

	/**
	 * Acciones a ejecutar al crear un objeto para esta clase.
	 */
	protected function singletonStart()
	{
		// Crea objeto para almacenar datos del Layout
		// TODO: Mover a una clase en support LayoutData
		// y hacer $layout public!
		$this->layout = new class {
			// Archivo
			public string $filename = '';
			// Valores a usar en el Layout
			public array $params = [];
			// Nombre de la variable con el contenido de vistas previas
			public string $contentViewName = '';
			// TRUE para indicar que el Layout fue usado
			public bool $alreadyUsed = false;
			// Método para actualizar contenido
			public function saveContentView(string &$content) {
				// Marca este Layout como "ya usado"
				$this->alreadyUsed = true;
				if ($this->contentViewName != '') {
					$this->params[$this->contentViewName] =& $content;
				}
			}
			// Método para remover contenido (libera memoria)
			public function removeContentView() {
				if ($this->contentViewName != '') {
					unset($this->params[$this->contentViewName]);
				}
			}
		};
	}

	/**
	 * Asigna vista a usar como layout.
	 *
	 * El "layout" es la vista que habrá de contener todas las vistas
	 * ejecutadas a través de $this->view().
	 *
	 * @param string $viewname Nombre/Path de la vista layout.
	 * @param string $content_view_name Nombre de la variable que va a contener el
	 * 									texto previamente renderizado.
	 */
	public function layout(string $viewname, string $content_view_name): self
	{
		$this->layout->filename = $this->checkFile($viewname);
		$this->layout->contentViewName = trim($content_view_name);
		$this->layout->params = [];
		return $this;
	}

	/**
	 * Remueve el archivo asociado al layout.
	 */
	public function removeLayout()
	{
		$this->layout->filename = '';
	}

	/**
	 * Habilita el layout actual para su uso, incluso después de haber sido usado en la vista actual.
	 */
	public function resetLayout()
	{
		$this->layout->alreadyUsed = false;
	}

	public function layoutUsed(): bool
	{
		return ($this->layout->alreadyUsed && !empty($this->layout->filename));
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
	private function viewPaths(string $viewname): array
	{
		$options = [];
		// Busca en el directorio de archivos
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
	 * Valida existencia del nombre/path dado a una vista.
	 *
	 * @param string $viewname Nombre/Path de la vista.
	 * @param string $filename Path real asociado a la vista. Si se indica este
	 * 						   valor, lo valida antes de buscar en la lista
	 * 						   retornada por $this->viewPaths. (opcional)
	 *
	 * @return string Path completo asociado al $viewname o cadena vacia si no existe la vista.
	 */
	public function findView(string $viewname, string $filename = ''): string
	{
		$viewname = trim($viewname);
		if ($viewname !== '') {
			$key = strtolower($viewname);
			if ($filename != '' && is_file($filename)) {
				$this->viewCache[$key] = realpath($filename);
			}
			// Busca en la caché local (agiliza resultado)
			if (isset($this->viewCache[$key])) {
				return $this->viewCache[$key];
			}
			// Busca en los directorios indicados
			foreach ($this->viewPaths($viewname) as $filename) {
				if (!is_file($filename)) {
					// Intenta el mismo pero todo en minusculas para el nombre base,
					// en caso que el SO diferencia may/min (Linux)
					$filename = dirname($filename) . DIRECTORY_SEPARATOR . strtolower(basename($filename));
				}
				if (is_file($filename)) {
					$this->viewCache[$key] = realpath($filename);
					return $this->viewCache[$key];
				}
			}
		}

		return '';
	}

	/**
	 * Valida nombre/path dado a una vista.
	 *
	 * Si el nombre dado no corresponde a un archivo físico, se genera un error.
	 *
	 * @param string $viewname Nombre/Path de la vista.
	 *
	 * @return string Path completo asociado al $viewname o cadena vacia si no existe la vista.
	 */
	private function checkFile(string $viewname): string
	{
		$filename = $this->findView($viewname);
		if ($filename === '') {
			// Si llega a este punto, dispara un error
			trigger_error(
				"Path para vista solicitada no es valido ({$viewname})",
				E_USER_ERROR
			);
		}
		// Este punto nunca se alcanza por el uso de trigger_error()
		return $filename;
	}

	/**
	 * Registra parámetros (valores) a usar para generar el layout.
	 *
	 * @param array $params Arreglo con valores.
	 *
	 * @return self Este objeto.
	 */
	public function globals(array $params): self
	{
		// Los nuevos valores remplazan los anteriores
		$this->layout->params = $params + $this->layout->params;
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
			array_key_exists($name, $this->layout->params) ?
			$this->layout->params[$name] :
			$default
		);
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
	 * @param string $content Contenido de la vista a renderizar (Valor por referencia).
	 */
	private function includeLayout(string &$content)
	{
		// Ejecuta layout (si alguno) si no hay vistas pendientes.
		if (
			!$this->layoutUsed() &&
			$this->currentView == ''
			) {
			// Preserva el contenido previamente renderizado para su uso en el Layout
			$this->layout->saveContentView($content);
			// Ejecuta vista
			$content = $this->evalTemplate(
				$this->layout->filename,
				$this->layout->params
			);
			// Libera memoria
			$this->layout->removeContentView();
		}
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
	protected function newTemplate(string $viewname, bool $only_validate = false): bool
	{
		$reference = md5($viewname);
		if (isset($this->views[$reference])) {
			return false;
		}

		if (!$only_validate) {
			// No solo valida, debe crear la referencia
			$this->views[$reference] = ['name' => $viewname, 'parent' => $this->currentView];
			// Actualiza identificador de vista actual
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
	 * @return string Contenido renderizado o FALSE si la vista ya está en ejecución.
	 */
	public function view(string $viewname, array $params): false|string
	{
		$content = false;
		if ($this->newTemplate($viewname)) {
			// Valida nombre de la vista y recupera nombre de archivo asociado
			$filename = $this->checkFile($viewname);
			// Ejecuta vista
			$content = $this->evalTemplate($filename, $params);
			// Restablece vista previa
			$this->removeTemplate();
			// Valida si se incluye layout en esta vista
			$this->includeLayout($content);
		}

		return $content;
	}

	/**
	 * Captura el texto enviado a pantalla (o al navegador) por cada vista.
	 *
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
	 *
	 * @param string $filename Archivo que contiene la vista.
	 * @param array $params Arreglo con valores.
	 *
	 * @return string Contenido renderizado.
	 */
	private function evalTemplate(string $filename, array $params): string
	{
		// La define como una función estática para no incluir $this
		$fun = static function (string $view_filename, array &$view_args) {

			if (count($view_args) > 0) {
				// EXTR_SKIP previene use $filename o $args y genere colisión de valores.
				// Se extraen como valores referencia para evitar duplicados.
				extract($view_args, EXTR_SKIP | EXTR_REFS);
			}

			// Previene se invoque un archivo no valido
			if ($view_filename == '' || !is_file($view_filename)) { return; }

			include $view_filename;
		};
		// Bloquea salida a pantalla
		ob_start();
		// Ejecuta
		$fun($filename, $params);
		// Recupera contenido
		// $content = ob_get_clean();
		// De Copilot: The use of ob_get_clean() can be replaced with
		// ob_get_contents() followed by ob_end_clean() for better performance
		// in some cases.
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}
}
