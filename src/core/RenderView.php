<?php

/**
 * Genera páginas web mediante el uso de vistas (views).
 *
 * Entiéndase como "vista" el script usado como frontend o interfaz
 * gráfica de usuario (GUI) para una aplicación o página Web. Este
 * es uno de los pilares de los modelos MVC (Modelo-Vista-Controlador).
 *
 * @author John Mejía
 * @since Noviembre 2024
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
	 * @var array $globalParams Valores disponibles para todas las vistas a usar.
	 */
	private array $globalParams = [];

	/**
	 * Acciones a ejecutar al crear un objeto para esta clase.
	 */
	protected function singletonStart()
	{
		// Nada por hacer, se incluye por requerimiento de la clase Singleton
	}

	/**
	 * Registra valores globales a usar por todas las vistas a mostrar.
	 *
	 * @param array $params Arreglo con valores a adicionar (opcional).
	 *
	 * @return array Arreglo con todos los valores registrados.
	 */
	public function globals(array $params = []): array
	{
		// Los nuevos valores remplazan los anteriores
		if (count($params) > 0) {
			$this->globalParams = $params + $this->globalParams;
		}
		return $this->globalParams;
	}

	/**
	 * Recupera valor de una variable global.
	 *
	 * Cuando se usan en las vistas, las variables globales son exportadas
	 * al contexto de la vista, pero no remplaza el valor con el mismo nombre
	 * en caso que sea definido uno entre los argumentos pasados a la vista.
	 * En esta caso, puede usar este método para recuperar el valor real de la
	 * variable global.
	 *
	 * @param string $name Nombre del parámetro a recuperar.
	 * @param mixed $default Valor a retornar si el parámetro no existe.
	 *
	 * @return mixed Valor del parámetro solicitado.
	 */
	public function getGlobal(string $name, mixed $default = ''): mixed
	{
		return (
			array_key_exists($name, $this->globalParams) ?
				$this->globalParams[$name] :
				$default
			);
	}

	/**
	 * Elimina los valores globales registrados.
	 */
	public function removeGlobals()
	{
		$this->globalParams = [];
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
		// Busca en el directorio de archivos
		if ($this->pathFiles !== '') {
			// $filename no contiene path ni extensión (comportamiento por defecto)
			$options[] = $this->pathFiles . $viewname . '.php';
			// $filename no contiene path pero si la extensión
			$options[] = $this->pathFiles . $viewname;
		}
		// En caso que el path contenga una ruta completa (o realtivamente completa)
		if (strpos($viewname, DIRECTORY_SEPARATOR) !== false || strpos($viewname, '/') !== false) {
			// $filename contiene path pero no la extensión
			$options[] = $viewname . '.php';
			// Path completo dado por el usuario
			$options[] = $viewname;
		}

		return $options;
	}

	/**
	 * Valida existencia del nombre/path dado a una vista.
	 *
	 * @param string $viewname Nombre/Path de la vista.
	 *
	 * @return string Path completo asociado al $viewname o cadena vacia si no existe la vista.
	 */
	public function findView(string $viewname): string
	{
		$viewname = trim($viewname);
		if ($viewname !== '') {
			$key = md5(strtolower($viewname));
			// Valida si no está registrado en la caché local
			if (!isset($this->viewCache[$key])) {
				$options = $this->viewPaths($viewname);
				// Busca en los directorios indicados
				foreach ($options as $filename) {
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
			} else {
				return $this->viewCache[$key];
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
	protected function checkFile(string $viewname): string
	{
		$filename = $this->findView($viewname);
		if ($filename === '') {
			// Si llega a este punto, dispara un error
			$this->error("La vista \"{$viewname}\" no pudo ser encontrada", __FILE__, __LINE__);
		}
		return $filename;
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
			$this->error("El path indicado para buscar vistas ({$path}) no es valido", __FILE__, __LINE__);
		}
		// Registra valor
		$this->pathFiles = realpath($path) . DIRECTORY_SEPARATOR;
	}

	/**
	 * Crea espacio para una nueva vista a ejecutar.
	 *
	 * Se valida que no exista un espacio ya asignado a ese nombre, para
	 * prevenir referencias ciclicas (por ejemplo, una vista que se invoca a
	 * si misma crearía un bucle infinito de una misma vista).
	 *
	 * @param string $viewname Nombre/Path de la vista.
	 *
	 * @return bool TRUE si crea el espacio para la nueva vista, FALSE si la referencia
	 * 				deseada ya existe.
	 */
	private function newTemplate(string $viewname): bool
	{
		$reference = md5($viewname);
		if (isset($this->views[$reference])) {
			return false;
		}

		$this->views[$reference] = ['name' => $viewname, 'parent' => $this->currentView];
		// Actualiza identificador de vista actual
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
	 * @return string Contenido renderizado o FALSE si la vista ya está en ejecución.
	 */
	public function view(string $viewname, array $params = []): false|string
	{
		if ($this->newTemplate($viewname)) {
			// Valida nombre de la vista y recupera nombre de archivo asociado
			$filename = $this->checkFile($viewname);
			// Ejecuta vista
			$content = $this->renderView($filename, $params);
			// Restablece vista previa
			$this->removeTemplate();

			return $content;
		}

		return false;
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
	protected function renderView(string $filename, array $params): string
	{
		// La define como una función estática para no incluir $this
		$fun = static function (string $view_filename, array &$view_args) {

			// Previene se invoque un archivo no valido
			if ($view_filename == '' || !is_file($view_filename)) { return; }

			if (count($view_args) > 0) {
				// EXTR_SKIP previene use $filename o $args y genere colisión de valores.
				// Se extraen como valores referencia para evitar duplicados.
				extract($view_args, EXTR_SKIP | EXTR_REFS);
			}

			include $view_filename;
		};

		// Bloquea salida a pantalla
		ob_start();
		// Adiciona variables globales pero tienen prioridad las locales
		$params = $params + $this->globalParams;
		// Ejecuta include
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

	/**
	 * Genera evento de error y termina la ejecución del script.
	 *
	 * Se recomienda usar en remplazo de trigger_error() o Exception, para prevenir
	 * posibles ciclos infinitos si el error ocurre mientras se visualiza una
	 * personalización de la función que muestra errores en pantalla.
	 *
	 * @param string $message Mensaje de error.
	 * @param string $file [optional] Archivo en el que se produjo el error.
	 * @param int $line [optional] Número de línea en el que se produjo el error.
	 */
	public function error(string $message, string $file = '', int $line = 0)
	{
		if ($file !== '' && $line > 0) {
			$message .= " en \"{$file}\" línea {$line}";
		}
		// Redefine mensaje para ambientes de producción?
		echo "<div style=\"background: #fadbd8; padding: 15px; margin: 5px 0\">" .
			"<b>Error:</b> {$message}" .
			"</div>";

		exit;
	}
}
