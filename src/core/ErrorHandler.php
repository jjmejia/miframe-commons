<?php

/**
 * Manejador de errores PHP y de usuario.
 *
 * Nota: Cuando los errores ocurren dentro de un tag script, pueden no
 * visualizarse. Por ello se recomienda en desarrollo habilitar la opción
 * de terminar script al encontrar error.
 *
 * @author John Mejía
 * @since Diciembre 2024
 */

namespace miFrame\Commons\Core;

use Exception;
use miFrame\Commons\Interfaces\RenderErrorInterface;
use miFrame\Commons\Components\ErrorData;
use miFrame\Commons\Traits\SanitizeRenderContent;

class ErrorHandler
{
	use SanitizeRenderContent;

	/**
	 * @var RenderError $render Objeto usado para generar el mensaje a pantalla.
	 */
	private ?RenderErrorInterface $render = null;

	/**
	 * @var array $errors Lista de errores ya reportados.
	 */
	private array $errors = [];

	/**
	 * @var string $firstEntryLog TRUE cuando no ha realizado entrada alguna al log de errores.
	 */
	private bool $firstEntryLog = true;

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
	 * @var bool $checkLogSize TRUE para validar el log de errores (previene ciclos)
	 */
	private bool $checkLogSize = true;

	/**
	 * @var int $maxErrors Limite de errores a manejar por ciclo.
	 */
	public int $maxErrors = 1000;

	/**
	 * @var int $countErrors Control de errores ocurridos
	 */
	private int $countErrors = 0;

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
			$this->setRenderer($render);
		}
	}

	/**
	 * Asigna o modifica objeto a usar para generar el mensaje a pantalla.
	 *
	 * @param RenderErrorInterface $render	Objeto usado para generar el mensaje a pantalla.
	 */
	public function setRenderer(RenderErrorInterface $render)
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
		$filename = $this->getRealErrorLogPath($filename);
		if ($filename !== '') {
			if (ini_set('error_log', $filename) !== false) {
				// Habilita nuevamente la rotación del log
				$this->checkLogSize = true;
				return true;
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
		return $this->getRealErrorLogPath(ini_get('error_log'));
	}

	/**
	 * Retorna siempre el path completo del archivo log.
	 *
	 * @param string $filename Path a evaluar
	 * @return string Path validado, cadena vacia si no existe el directorio asociado.
	 */
	private function getRealErrorLogPath(string $filename): string
	{
		$path = '';
		$filename = trim($filename);
		if ($filename !== '') {
			$dirname = @realpath(dirname($filename));
			$basename = trim(basename($filename));
			// $dirbase y $basename retornan cadena vacia si no se puede obtener valor
			if ($dirname && $basename && is_dir($dirname)) {
				$path = $dirname . DIRECTORY_SEPARATOR . $basename;
			}
		}
		return $path;
	}

	/**
	 * Inicia la vigilancia de errores y excepciones
	 *
	 * Esta función permite controlar y mostrar los errores y
	 * excepciones no atendidas, ocurridos durante la ejecución del script.
	 *
	 * Para monitorear todos los errores excepto E_NOTICE, se puede invocar
	 * este método con argumento E_ALL ^ E_NOTICE.
	 *
	 * @param int $error_level Nivel de errores a notificar. Por defecto
	 *                         se notifican todos los errores. Veáse la
	 * 						   documentación de error_reporting() para
	 *                         más información.
	 */
	public function watch(int $error_level = E_ALL)
	{
		error_reporting($error_level);
		// Bloquea salidas a pantalla de mensajes de error
		ini_set("display_errors", "off");
		// Registra funciones a usar para despliegue de errores
		set_error_handler([$this, 'showError']);
		// NOTA: Si ocurre un error en el "error handler", se genera una
		// Excepcion y se pasa al "exception handler"..
		set_exception_handler([$this, 'showException']);
	}

	/**
	 * Valida si el error ya fue reportado.
	 *
	 * @param ErrorData $error Objeto de datos asociados al error.
	 * @return bool TRUE si el error es nuevo y no ha sido ya reportado. FALSE en otro caso.
	 */
	private function uniqueError(ErrorData $error): bool
	{
		// solamente incluye los valores básicos
		$key = $error->getKey();

		if (!in_array($key, $this->errors)) {
			// No ha reportado este error
			// Adiciona a control de repeticiones
			$this->errors[] = $key;
			return true;
		}

		return false;
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
			$this->checkLogSize &&
			$this->sizeErrorLog > 0 &&
			filesize($filename) > $this->sizeErrorLog
			) {

			// A tener en cuenta (Copilot):
			// The rotateLog method uses filesize which can be slow for large files.
			// Consider optimizing this check if performance becomes an issue.
			// @stat($filename)['size'] > $this->sizeErrorLog

			// Actualiza para no checar nuevamente este mismo archivo en este ciclo
			$this->checkLogSize = false;
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

				// Genera error enmascarable (E_USER_NOTICE)
				trigger_error(
					"Rotación fallida del log de errores: el archivo \"{$filename}\" no pudo ser renombrado a \"{$new_name}\"",
					E_USER_NOTICE
				);
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
	 * @param ErrorData $error Objeto que contiene detalles del error.
	 *
	 * @return bool Retorna FALSE si el error ya ha sido reportado.
	 */
	private function viewError(ErrorData $error)
	{
		// Valida que este error no haya sido ya atendido
		if (!$this->uniqueError($error)) {
			return false;
		}

		// Mensaje HTML alternativo (al quitar los tags debe ser legible)
		$html = $error->htmlMessage();

		// Registra en el log de errores
		$this->errorLog($html);

		$content = $html;
		// Ejecuta render registrado (si alguno)
		if (!empty($this->render)) {
			$content = $this->render->show($error, $html);
		}

		// Remueve Document Root de la salida a pantalla (por precaución)
		$this->sanitizeDocumentRoot($content);

		// Da salida a pantalla
		echo PHP_EOL . $content . PHP_EOL;

		// Valida cantidad de errores procesados
		$this->checkCountErrors();

		// Valida si aborta script
		if ($error->endScript) {
			$this->abort();
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
	 * Realiza control de la cantidad de errores procesados.
	 */
	private function checkCountErrors()
	{
		// Decrementa conteo de errores procesados
		if ($this->maxErrors > 0) {
			$this->countErrors ++;
			if ($this->countErrors >= $this->maxErrors) {
				// Aborta script (ayuda a prevenir ciclos infinitos por errores)
				$this->abort("Se alcanzó el límite de errores a procesar ({$this->maxErrors})");
			}
		}
	}

	/**
	 * Aborta ejecución de inmediato.
	 *
	 * @param string $message Mensaje a mostrar antes de terminar la ejecución del script.
	 */
	public function abort(string $message = '')
	{
		if ($message !== '') {
			$message = "<div style=\"background: #fadbd8; padding: 30px; margin: 5px 0\"><b>Script Interrumpido:</b> {$message}</div>";
			error_log(strip_tags($message));
			echo $message;
		}
		// Limpia errores pendientes
		error_clear_last();
		exit;
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
		if (!(error_reporting() & $type)) {
			// Este código de error no está incluido en error_reporting, así que
			// se ignora su presentación a pantalla.
			return;
		}

		$error = new ErrorData();
		$error->newError($type, $message, $file, $line);
		$this->viewError($error);
	}

	/**
	 * Muestra una excepción en la interfaz de usuario.
	 *
	 * Este método se invoca internamente (por PHP) cuando ocurre un error mientras
	 * atiende una excepción vía try/catch.
	 *
	 * @param \Exception|\Error $e Objeto con los datos de la excepción o error a mostrar.
	 * @param bool 	$end_script [opcional] TRUE si se debe terminar el
	 * 							el script después de mostrar la excepción
	 * 							(valor por defecto), FALSE en otro caso.
	 */
	public function showException(\Exception|\Error $e, bool $end_script = true)
	{
		$error = new ErrorData();
		$error->newException($e);
		$error->endScript = $end_script;
		$this->viewError($error);
	}
}
