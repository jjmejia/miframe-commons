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
	 * @var string $currentView Nombre de la vista actualmente en ejecución.
	 */
	protected string $currentView = '';

	/**
	 * @var array $globalParams Valores disponibles para todas las vistas a usar.
	 */
	private array $globalParams = [];

	/**
	 * Rutas predeterminadas asociadas a vistas, usualmente cuando se encuentra en un path diferente a $pathFiles.
	 *
	 * @var array $defaults Arreglo asociativo de valores predeterminados.
	 */
	private array $defaults = [];

	public bool $useExceptionForErrors = false;

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
			// Valida si no está registrado en la caché local
			// Busca en el directorio de archivos
			if ($this->pathFiles !== '') {
				// $filename no contiene path ni extensión (comportamiento por defecto)
				// Nota: No valida $viewname sin el path indicado para prevenir incluya
				// por accidente archivos de rutas no habilitadas.
				$options = [
					$this->pathFiles . $viewname . '.php',
					// $filename no contiene path pero si la extensión
					$this->pathFiles . $viewname
				];

				// Busca en los directorios indicados
				foreach ($options as $filename) {
					if (is_file($filename)) {
						return realpath($filename);
					}
				}
			}
			// Llegado a este punto, valida en la lista de defaults
			if (isset($this->defaults[$viewname])) {
				return $this->defaults[$viewname];
			}
		}

		return '';
	}


	/**
	 * Establece una ruta de archivo predeterminada para un nombre de vista específico.
	 *
	 * @param string $viewname El nombre de la vista a asociar con el archivo predeterminado.
	 * @param string $filename Ruta del archivo que se establecerá como predeterminada para la vista especificada.
	 */
	public function defaultFor(string $viewname, string $filename)
	{
		$viewname = trim($viewname);
		$filename = trim($filename);
		if ($viewname !== '' && $filename !== '') {
			if (file_exists($filename)) {
				// Registra path por defecto
				$this->defaults[$viewname] = realpath($filename);
			} else {
				// Genera error ya que el archivo no existe
				$this->error("El archivo por defecto para la vista {$viewname} no es valido ({$filename})", __FILE__, __LINE__);
			}
		}
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
	 * @param string $filename Path de la vista.
	 *
	 * @return bool TRUE si crea el espacio para la nueva vista, FALSE si la referencia
	 * 				deseada ya existe.
	 */
	private function newTemplate(string $filename): bool
	{
		$reference = md5($filename);
		if (isset($this->views[$reference])) {
			return false;
		}

		$this->views[$reference] = ['file' => $filename, 'parent' => $this->currentView];
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
	 * @param string $default Valor a devolver si la vista no existe.
	 *
	 * @return string Contenido renderizado.
	 */
	public function view(string $viewname, array $params = [], string $default = ''): string
	{
		// Valida nombre de la vista y recupera nombre de archivo asociado
		$filename = $this->checkFile($viewname);
		if ($this->newTemplate($filename)) {
			// Bloquea salida a pantalla
			ob_start();
			// Ejecuta vista
			$this->includeFile($filename, $params);
			// Recupera contenido
			// $content = ob_get_clean();
			// De Copilot: The use of ob_get_clean() can be replaced with
			// ob_get_contents() followed by ob_end_clean() for better performance
			// in some cases.
			$default = ob_get_contents();
			ob_end_clean();
			// Restablece vista previa
			$this->removeTemplate();
		}

		return $default;
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
	 *
	 * @param string $filename Archivo que contiene la vista.
	 * @param array $params Arreglo con valores.
	 *
	 * @return mixed Valor retornado por el script, si alguno.
	 */
	private function includeFile(string $filename, array $params): mixed
	{
		// La define como una función estática para no incluir $this
		$fun = static function (string $view_filename, array &$view_args) {

			// Previene se invoque un archivo no valido
			if ($view_filename == '' || !is_file($view_filename)) {
				return false;
			}

			if (count($view_args) > 0) {
				// EXTR_SKIP previene use $filename o $args y genere colisión de valores.
				// Se extraen como valores referencia para evitar duplicados.
				extract($view_args, EXTR_SKIP | EXTR_REFS);
			}

			return include($view_filename);
		};

		// Adiciona variables globales pero tienen prioridad las locales
		$params = $params + $this->globalParams;

		// Ejecuta include
		return $fun($filename, $params);
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
		$source = '';
		if ($file !== '') {
			$server = miframe_server();
			if (!$server->isLocalhost()) {
				// Remueve root para entornos no locales
				$file = $server->removeDocumentRoot($file);
			}
			$source .= "\"{$file}\"";
		}
		if ($line > 0) {
			$source .= " línea {$line}";
		}

		// Valida si usa una Excepción para errores
		if ($this->useExceptionForErrors) {
			// El mensaje de excepción adiciona su propio path y línea
			throw new \Exception("{$message} ({$source})");
		}

		if ($source !== '') {
			$message .= " en {$source}";
		}

		echo "<div style=\"background: #fadbd8; padding: 15px; margin: 5px 0\">" .
			"<b>Error:</b> " .
			nl2br($message) .
			"</div>";

		exit;
	}
}
