<?php

/**
 * Manejador de errores PHP y de usuario.
 *
 * @author John Mejía
 * @since Diciembre 2024
 */

namespace miFrame\Commons\Core;

use Exception;
use miFrame\Commons\Interfaces\RenderErrorInterface;
use miFrame\Commons\Support\DataError;

class ErrorHandler
{
	/**
	 * @var RenderError $render Objeto usado para generar el mensaje a pantalla.
	 */
	private ?RenderErrorInterface $render = null;

	/**
	 * @var bool $shuttingDown TRUE cuando está ejecutando la rutina de shutdown.
	 */
	private bool $shuttingDown = false;

	/**
	 * @var array $errors Lista de errores ya reportados.
	 */
	private array $errors = [];

	/**
	 * @var string $firstEntryLog TRUE cuando no ha realizado entrada alguna al log de errores.
	 */
	private bool $firstEntryLog = true;

	/**
	 * @var bool $hideDocumentRoot Indica si se oculta el DOCUMENT_ROOT en los mensajes de error.
	 */
	public bool $hideDocumentRoot = true;

	/**
	 * @var int $sizeErrorLog Tamaño máximo en bytes permitido para el log de errores. En cero (0) no
	 * 						  realiza control de tamaño.
	 */
	public int $sizeErrorLog = 0;

	/**
	 * @var string $oldErrorLogSufix Sufijo a usar al guardar una copia del log de errores (rotación del log de errores).
	 * 								 Ver método rotateLog() para más información sobre uso y opciones de este sufijo.
	 */
	public string $oldErrorLogSufix = '(old)';

	/**
	 * @var bool $rotatingLog TRUE cuando está renombrando el log de errores (previene ciclos)
	 */
	private bool $rotatingLog = false;

	/**
	 * Creación del objeto, inicializa atributos.
	 *
	 * @param RenderErrorInterface $render	Objeto usado para generar el mensaje a pantalla (opcional).
	 */
	public function __construct(?RenderErrorInterface $render = null)
	{
		// Registrar errores en un archivo
		ini_set('log_errors', '1');
		// Previene repita errores (mismo archivo, línea y mensaje de error)
		ini_set('ignore_repeated_errors', '1');
		// Asocia render si existe
		if (!empty($render)) {
			$this->setRender($render);
		}
	}

	/**
	 * Asigna o modifica objeto a usar para generar el mensaje a pantalla.
	 *
	 * @param RenderErrorInterface $render	Objeto usado para generar el mensaje a pantalla.
	 */
	public function setRender(RenderErrorInterface $render)
	{
		$this->render = $render;
	}

	/**
	 * Fija archivo de errores.
	 *
	 * El directorio que contiene el archivo debe existir y permitir la creación de
	 * nuevos archivos. El directorio y archivo deben tener permisos de escritura para PHP.
	 *
	 * Al cambiar el nombre habilita nuevamenta la rotación del log (si aplica).
	 *
	 * @param string $filename Path del archivo. Si no existe, procede a crearlo.
	 *
	 * @return bool TRUE si pudo asignar el nuevo log de errores. FALSE si no se especifica
	 * 				un archivo valido o no pudo asignar el nuevo log de errores.
	 */
	public function setErrorLog(string $filename): bool
	{
		$filename = trim($filename);
		if ($filename !== '') {
			$dirname = realpath(dirname($filename));
			if (is_dir($dirname)) {
				if (ini_set('error_log', $dirname . DIRECTORY_SEPARATOR . basename($filename)) !== false) {
					// Habilita nuevamente la rotación del log
					$this->rotatingLog = false;
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Retorna el path del log de errores en uso (si alguno).
	 *
	 * @return string Path.
	 */
	public function getErrorLog(): string
	{
		$filename = trim(ini_get('error_log'));
		if ($filename != '') {
			$filename = realpath($filename);
		}

		return $filename;
	}

	/**
	 * Inicia la vigilancia de errores y excepciones
	 *
	 * Esta función permite controlar y mostrar los errores y
	 * excepciones no atendidas, ocurridos durante la ejecución del script.
	 *
	 * @param int $error_level Nivel de errores a notificar. Por defecto
	 *                         se notifican todos los errores excepto E_NOTICE.
	 *                         Ver documentación de error_reporting() para
	 *                         más información.
	 */
	public function watch(int $error_level = E_ALL ^ E_NOTICE)
	{
		// Por defecto notifica todos los errores excepto E_NOTICE
		error_reporting($error_level);
		// Registra función a usar al terminar el script
		register_shutdown_function([$this, 'shutdown']);
		// Bloquea salidas a pantalla de mensajes de error
		ini_set("display_errors", "off");
		// Registra funciones a usar para despliegue de errores
		set_error_handler([$this, 'showError']);
		// NOTA: Si ocurre un error en el "error handler", se genera una
		// Excepcion y se pasa al "exception handler"..
		set_exception_handler([$this, 'showException']);
	}

	/**
	 * Método a ejecutar al terminar el script.
	 *
	 * Valida si hay algún error no atendido y le da el manejo personalizado.
	 */
	public function shutdown()
	{
		$this->shuttingDown = true;
		$last_error = error_get_last();
		if (!is_null($last_error)) {
			$this->showError(...$last_error);
		}
	}

	// /**
	//  * Identifica los código de error con nombres amigables.
	//  *
	//  * @param int $errno Código de error.
	//  *
	//  * @return string Título asociado al código de error.
	//  */
	// private function errorTypeName(int $errno)
	// {
	// 	// https://www.php.net/manual/en/errorfunc.constants.php#126465
	// 	$exceptions = [
	// 		E_ERROR => "Error",
	// 		E_WARNING => "Advertencia",
	// 		E_PARSE => "Error de interpretador", // Se incluye pero no puede ser capturado
	// 		E_NOTICE => "Aviso",
	// 		E_CORE_ERROR => "Error de arranque",
	// 		E_CORE_WARNING => "Advertencia de arranque",
	// 		E_COMPILE_ERROR => "Error durante compilación",
	// 		E_COMPILE_WARNING => "Advertencia durante compilación",
	// 		E_USER_ERROR => "Error generado por el Usuario",
	// 		E_USER_WARNING => "Advertencia generada por el Usuario",
	// 		E_USER_NOTICE => "Aviso generado por el Usuario",
	// 		E_STRICT => "Error de compatibilidad",
	// 		E_RECOVERABLE_ERROR => "Error recuperable",
	// 		E_DEPRECATED => "Contenido Obsoleto",
	// 		E_USER_DEPRECATED => "Contenido Obsoleto de Usuario",
	// 		E_ALL => "(Todos)"
	// 	];

	// 	$title = "Error Desconocido ($errno)";
	// 	if (isset($exceptions[$errno])) {
	// 		$title = $exceptions[$errno];
	// 	}

	// 	return $title;
	// }

	// /**
	//  * Título que identifica una excepción.
	//  *
	//  * @param mixed $code Código reportado con la excepción.
	//  *
	//  * @return string Título asociado.
	//  */
	// private function exceptionName(mixed $code)
	// {
	// 	return 'Excepción detectada' . (!empty($code) ? " (código {$code})" : '');
	// }

	/**
	 * Determina si el código de error corresponde a un PHP Fatal Error.
	 *
	 * @param int $errno Nivel de error de PHP.
	 *
	 * @return bool TRUE si el error es fatal, FALSE en otro caso.
	 */
	// private function isFatalError(int $errno): bool
	// {
	// 	return $errno & (E_USER_ERROR | E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR);
	// }

	/**
	 * Valida si el error ya fue reportado.
	 *
	 * @param DataError $error Arreglo de datos asociados al error.
	 * @return bool TRUE si el error es nuevo y no ha sido ya reportado. FALSE en otro caso.
	 */
	private function uniqueError(DataError $error): bool
	{
		// solamente incluye los valores básicos
		$key = $error->getKey();

		if (in_array($key, $this->errors)) {
			// Ya reportó este mismo error
			return false;
		}

		// Adiciona a control de repeticiones
		$this->errors[] = $key;

		return true;
	}

	/**
	 * Valida control de tamaño del log actual y renombra el log si es del caso.
	 *
	 * Valida el sufijo a usar al generar el nombre para el nuevo log de errores así:
	 *
	 * - "#": asignar un nombre consecutivo y mantiene el archivo actual.
	 * - (cadena vacia): Elimina el archivo actual y genera uno nuevo en limpio.
	 * - (cualquier otro nombre): Se adiciona antes de la extensión asociada al archivo. Si el
	 *   nuevo nombre corresponde a un archivo ya existente, lo sobreescribe.
	 *
	 * Genera un error si no le es posible renombrar el log de errores.
	 *
	 * @return bool TRUE si realizó rotación del log de errores. FALSE en otro caso.
	 */
	private function rotateLog(): bool
	{
		$filename = $this->getErrorLog();
		if (
			$filename !== '' &&
			!$this->rotatingLog &&
			$this->sizeErrorLog > 0 &&
			filesize($filename) > $this->sizeErrorLog
			) {
			$this->rotatingLog = true;
			if ($this->oldErrorLogSufix !== '') {
				$info = pathinfo($filename);
				// Renombra y mantiene solamente un histórico (por defecto)
				$new_name = $info['dirname'] . DIRECTORY_SEPARATOR . $info['filename'] . $this->oldErrorLogSufix . '.' . $info['extension'];
				if ($this->oldErrorLogSufix === '#') {
					// Genera histórico consecutivo
					$sufix = 0;
					do {
						$sufix ++;
						$new_name = $info['dirname'] . DIRECTORY_SEPARATOR . $info['filename'] . "({$sufix})." . $info['extension'];
					}
					while (is_file($new_name));
				}
				if (@rename($filename, $new_name)) {
					// Reporta rotación del archivo
					error_log("ROTATE {$new_name}");
					// Habilita uso de separador en el log
					$this->firstEntryLog = true;

					return true;
				}

				// Genera error
				trigger_error('No pudo rotar el log de errores (nombre sugerido: ' . $new_name . ')');
			}
		}

		return false;
	}

	/**
	 * Adiciona mensaje al log de errores.
	 *
	 * Valida si debe realizar rotación del log actual.
	 *
	 * @param string $message Mensaje.
	 * @param bool $raw		  TRUE para incluir el mensaje tal cual. FALSE remueve tags HTML.
	 */
	public function errorLog(string $message, bool $raw = false)
	{
		if (!$raw) {
			$message = trim(strip_tags($message));
		}
		if ($message !== '') {
			// Valida control de tamaño del log actual
			$this->rotateLog();

			if ($this->firstEntryLog) {
				$this->firstEntryLog = false;
				// Adiciona separador al log de errores
				error_log('---');
			}

			// Registra en el log de errores
			error_log($message);
		}
	}

	/**
	 * Muestra mensajes de error.
	 *
	 * Esta función procesa los datos de error proporcionados, los forma para
	 * mostrarlos, los registra en el log y, opcionalmente, termina el script en
	 * modo Desarrollador.
	 *
	 * Asegura que los errores duplicados no sean reportados más de una vez.
	 * La función también maneja la visualización del error de manera diferente
	 * según el modo actual (Desarrollador o Producción).
	 *
	 * El objeto $error contiene los siguientes elementos:
	 *
	 * - 'class'  : Nombre de la clase que generó el error.
	 * - 'type'   : Nivel de error de PHP (E_USER_ERROR, E_ERROR, ...).
	 * - 'message': Descripción del error.
	 * - 'file'   : Archivo donde se generó el error.
	 * - 'line'   : Línea del archivo donde se generó el error.
	 * - 'trace'  : Información de backtrace.
	 * - 'typeName': Nombre amigable del nivel de error.
	 *
	 * @param DataError $error Un array asociativo que contiene detalles del
	 *                          error.
	 *
	 * @return bool Retorna FALSE si el error ya ha sido reportado.
	 */
	private function viewError(DataError $error)
	{
		// Valida que este error no haya sido ya atendido
		if (!$this->uniqueError($error)) {
			return false;
		}

		// Mensaje HTML alternativo (al quitar los tags debe ser legible)
		$html = $error->htmlMessage();

		// Registra en el log de errores
		$this->errorLog($html);

		$content = false;
		if (!is_null($this->render)) {
			$content = $this->render->show($error);
		}

		if ($content === false) {
			// No pudo ejecutar la vista, muestra vista por defecto.
			// Valor de cadena vacia no debe interpretarse como FALSE pues
			// es un valor valido.
			$content = $html;
		}

		if ($content !== '') {
			// Aplica filtros programados
			$this->filterDocumentRoot($content);
			// Da salida a pantalla
			echo PHP_EOL . trim($content) . PHP_EOL;
		}

		// Aborta script
		if ($error->endScript && !$this->shuttingDown) {
			error_clear_last();
			exit;
		}

		// Si detecta error es porque ocurrió durante la ejecución de la vista
		// y solamente reporta el último que haya ocurrido.
		// (Solamente se procesan errores detectados por PHP cuando esta función no se
		// invoca a causa de otro error generado, sino cuando se invoca
		// manualmente, por ejemplo en respuesta a una Exception)

		$last_error = error_get_last();
		if (!is_null($last_error)) {
			error_clear_last();
			$this->showError(...$last_error);
		}
	}

	/**
	 * Maneja y muestra un mensaje de error.
	 *
	 * Este método procesa un error verificando si el tipo de error se encuentra
	 * incluido en el nivel de reporte de errores actual. Si se encuentra, construye
	 * un arreglo con detalles del error y lo pasa al método viewError() para manejar
	 * su visualización. Si el tipo de error no se encuentra incluido en el nivel de
	 * reporte de errores, devuelve false permitiendo que el manejo de errores de PHP
	 * siga su curso normal.
	 *
	 * @param int $type El tipo de error.
	 * @param string $message El mensaje de error.
	 * @param string $file El archivo en el que se produjo el error.
	 * @param int $line El número de línea en el que se produjo el error.
	 * @return bool|void Devuelve FALSE si el tipo de error no se encuentra incluido
	 *                   en el reporte de errores, de lo contrario, procesa el error.
	 */
	public function showError(int $type, string $message, string $file = '', int $line = 0)
	{
		$error = new DataError();
		if (!$error->newError($type, $message, $file, $line)) {
			return false;
		}

		return $this->viewError($error);
	}

	/**
	 * Muestra una excepción en la interfaz de usuario.
	 *
	 * Este método se invoca internamente (por PHP) también para manejo de errores?
	 *
	 * @param \Exception|\Error $e Objeto con los datos de la excepción o error a mostrar.
	 * @param bool 	$end_script [opcional] TRUE si se debe terminar el
	 * 							el script después de mostrar la excepción
	 * 							(valor por defecto), FALSE en otro caso.
	 */
	public function showException(\Exception|\Error $e, bool $end_script = true)
	{
		$error = new DataError();
		$error->newException($e);
		$error->endScript = $end_script;

		return $this->viewError($error);
	}

	/**
	 * Reemplaza referencias al DOCUMENT_ROOT para no revelar su ubicación
	 * en entornos no seguros.
	 *
	 * @param string $content Contenido a filtrar (valor por referencia).
	 */
	public function filterDocumentRoot(string &$content)
	{
		if (!$this->hideDocumentRoot) {
			return;
		}

		$content = str_replace(
			[$_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR, $_SERVER['DOCUMENT_ROOT']],
			['', '[..]'],
			$content
		);
	}

}
