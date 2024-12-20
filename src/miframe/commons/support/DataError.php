<?php

/**
 * Objeto de soporte para manejo de errores.
 *
 * @author John Mejía
 * @since Diciembre 2024
 */

namespace miFrame\Commons\Support;

class DataError {

	/**
	 * - 'class'  : Nombre de la clase que generó el error.
	 * - 'type'   : Nivel de error de PHP (E_USER_ERROR, E_ERROR, ...).
	 * - 'message': Descripción del error.
	 * - 'file'   : Archivo donde se generó el error.
	 * - 'line'   : Línea del archivo donde se generó el error.
	 * - 'trace'  : Información de backtrace.
	 * - 'type_name': Nombre amigable del nivel de error.
	 */

	private string $class = '';
	private mixed $type = 0;
	private string $message = '';
	private string $file = '';
	private int $line = 0;
	private array $trace = [];
	private string $typeName = '';

	public bool $endScript = false;

	/**
	 * Registra mensaje de error.
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
	public function newError(int $type, string $message, string $file = '', int $line = 0)
	{
		if (!error_reporting() & $type) {
			// Este código de error no está incluido en error_reporting, así que
			// se retorna para que sea manejado con el proceso estandar de PHP
			return false;
		}

		$this->class = 'Error';
		$this->type = $type;
		$this->message = $message;
		$this->file = $file;
		$this->line = $line;
		// Traza actual
		$this->trace = $this->getTrace(__FUNCTION__);
		// Terminar ejecución
		$this->endScript = $this->isFatalError();
		// Nombre legible asociado al tipo de error
		$this->typeName = $this->errorTypeName();

		// Si no indica origen pero existe traza, toma la última reportada?
		// No necesariamente, porque puede ocurrir con errores generados en
		// closures o uso de eval().
		if (
			$this->file === '' &&
			$this->line == 0 &&
			!empty($this->trace) &&
			isset($this->trace[0])
			) {
			$this->file = $this->trace[0]['file'];
			$this->line = $this->trace[0]['line'];
		}

		return true;
	}

	/**
	 * Muestra una excepción en la interfaz de usuario.
	 *
	 * Este método se invoca internamente (por PHP) también para manejo de errores?
	 *
	 * @param \Exception|\Error $e Objeto con los datos de la excepción o error a mostrar.
	 */
	public function newException(\Exception|\Error $e)
	{
		$type = $e->getCode();

		$this->class = get_class($e);
		$this->type = $type;
		$this->message = $e->getMessage();
		$this->file = $e->getFile();
		$this->line = $e->getLine();
		// Traza actual
		// Incluye la traza ya que las excepciones pueden provenir de cualquier punto
		// y el backtrace() no necesariamente va a coincidir con el origen del evento.
		$this->trace = $e->getTrace();
		// Terminar ejecución
		$this->endScript = true;
		// Nombre legible asociado al tipo de error
		$this->typeName = $this->exceptionName();

		// Si no recupera la traza, intenta manualmente
		// ('file' y 'line' reportados pueden no aparecer en este caso
		// ya que el trace reportará posiblemente la ubicación del catch() usado,
		// si es que existe).
		if (empty($this->trace)) {
			$this->trace = $this->getTrace(__FUNCTION__);
		}
	}

	/**
	 * Recupera la traza actual.
	 *
	 * Remueve las primeras entradas si están relacionadas con esta clase,
	 * de esta forma incluye solamente la traza relevante para el error ocurrido.
	 *
	 * @param string $function Nombre de la función que invoca este método.
	 *
	 * @return array Traza para depuración.
	 */
	private function getTrace(string $function): array
	{
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		if (!is_array($trace)) {
			$trace = [];
		}
		// Remueve elemento "0" que corresponde a este método
		$i = 0;
		while (
			isset($trace[$i]) &&
			$trace[$i]['class'] === __CLASS__ &&
			($trace[$i]['function'] === __FUNCTION__ ||
			$trace[$i]['function'] === $function)
			) {
			// Remueve primer elemento y renumera indices numericos
			array_shift($trace);
		}

		return $trace;
	}

	/**
	 * Determina si el código de error corresponde a un PHP Fatal Error.
	 *
	 * @param int $errno Nivel de error de PHP.
	 *
	 * @return bool TRUE si el error es fatal, FALSE en otro caso.
	 */
	private function isFatalError(): bool
	{
		return $this->type & (E_USER_ERROR | E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR);
	}

	public function getKey()
	{
		return sha1(serialize([
			$this->class,
			$this->type,
			$this->message,
			$this->file,
			$this->line
		]));
	}

	public function htmlMessage(): string
	{
		$message = trim($this->message);
		// Si el mensaje termina en punto (.) lo remueve
		if (substr($message, -1, 1) === '.') {
			$message = substr($message, 0, -1);
		}

		// Mensaje HTML alternativo (al quitar los tags debe ser legible)
		return "<div style=\"background: #fadbd8; padding: 15px; margin: 5px 0\">" .
			"<h3>{$this->typeName}:</h3> <p>" .
			nl2br($message) .
			" en \"{$this->file}\" línea {$this->line}</p>" .
			"</div>";

	}

	/**
	 * Identifica los código de error con nombres amigables.
	 *
	 * @param int $errno Código de error.
	 *
	 * @return string Título asociado al código de error.
	 */
	private function errorTypeName()
	{
		// https://www.php.net/manual/en/errorfunc.constants.php#126465
		$exceptions = [
			E_ERROR => "Error",
			E_WARNING => "Advertencia",
			E_PARSE => "Error de interpretador", // Se incluye pero no puede ser capturado
			E_NOTICE => "Aviso",
			E_CORE_ERROR => "Error de arranque",
			E_CORE_WARNING => "Advertencia de arranque",
			E_COMPILE_ERROR => "Error durante compilación",
			E_COMPILE_WARNING => "Advertencia durante compilación",
			E_USER_ERROR => "Error generado por el Usuario",
			E_USER_WARNING => "Advertencia generada por el Usuario",
			E_USER_NOTICE => "Aviso generado por el Usuario",
			E_STRICT => "Error de compatibilidad",
			E_RECOVERABLE_ERROR => "Error recuperable",
			E_DEPRECATED => "Contenido Obsoleto",
			E_USER_DEPRECATED => "Contenido Obsoleto de Usuario",
			E_ALL => "(Todos)"
		];

		$title = "Error Desconocido ($this->type)";
		if (isset($exceptions[$this->type])) {
			$title = $exceptions[$this->type];
		}

		return $title;
	}

	/**
	 * Título que identifica una excepción.
	 *
	 * @param mixed $code Código reportado con la excepción.
	 *
	 * @return string Título asociado.
	 */
	private function exceptionName()
	{
		return 'Excepción detectada' . (!empty($this->type) ? " ({$this->type})" : '');
	}

	public function export()
	{
		return [
			'class' => $this->class,
			'type' => $this->type,
			'type_name' => $this->typeName,
			'message' => $this->message,
			'file' => $this->file,
			'line' => $this->line,
			'end_script' => $this->endScript,
			'trace' => $this->trace
		];
	}
}