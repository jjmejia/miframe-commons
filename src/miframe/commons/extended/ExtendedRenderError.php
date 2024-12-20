<?php

/**
 * Maneja errores PHP.
 *
 * Requiere miframe_render() y miframe_server() para la vista usada.
 *
 * @author John Mejía
 * @since Diciembre 2024
 */

namespace miFrame\Commons\Extended;

use miFrame\Commons\Interfaces\RenderErrorInterface;
use miFrame\Commons\Support\DataError;

class ExtendedRenderError implements RenderErrorInterface
{
	/**
	 * @var bool $inDeveloperModeEndScript 	Habilita el terminar el script cuando se produce
	 * 										un error en modo Desarrollo.
	 */
	public bool $inDeveloperModeEndScript = true;

	/**
	 * @var string $errorMessage Mensaje de error a mostrar en caso de error fatal,
	 * 								  para visualizaciones en producción.
	 */
	public string $errorMessage = 'Ha ocurrido un error irrecuperable en el Sistema, favor revisar el log de errores.';

	/**
	 * @var string $warningMessage Mensaje de error a mostrar en caso de errores tipo
	 * 							   warning, para visualizaciones en producción.
	 */
	public string $warningMessage = 'Ha ocurrido una incidencia, favor revisar el log de errores.';

	private array $previous = [];

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
	 * El arreglo $data_error contiene al menos los siguientes elementos:
	 *
	 * - 'class'  : Nombre de la clase que generó el error.
	 * - 'type'   : Nivel de error de PHP (E_USER_ERROR, E_ERROR, ...).
	 * - 'message': Descripción del error.
	 * - 'file'   : Archivo donde se generó el error.
	 * - 'line'   : Línea del archivo donde se generó el error.
	 * - 'trace'  : Información de backtrace.
	 * - 'type_name': Nombre amigable del nivel de error.
	 *
	 * @param DataError $error Un array asociativo que contiene detalles del
	 *                          error.
	 *
	 * @return bool Retorna FALSE si el error ya ha sido reportado.
	 */
	public function show(DataError $error): string|false
	{
		$content = '';

		// Recupera objeto renderizador
		$render = miframe_render();

		if ($render->inDeveloperMode() && $this->inDeveloperModeEndScript) {
			// Termina el script en modo Desarrollo
			$error->endScript = true;
		}

		$data_error = $error->export();

		// Texto no enviado a pantalla (si alguno)
		$data_error['buffer'] = '';

		if (!$render->inDeveloperMode()) {
			// Oculta información a pantalla en producción.
			// A partir de este punto, puede retornar cadena vacia
			// (false se interpreta como un error, por eso lo remplaza)

			if ($data_error['end_script']) {
				// Error que se muestra en pantalla y termina ejecución
				$content = trim($this->errorMessage);
			} elseif (
				$data_error['class'] !== 'Error' || // Es una excepción
				// $data_error['type'] === E_USER_WARNING || $data_error['type'] == E_WARNING
				$data_error['type'] & (E_USER_WARNING | E_WARNING)
			) {
				// Error que se muestra en pantalla sin terminar la ejecución
				// (solamente muestra uno aunque hayan varios errores en la página)
				// Para errores tipo NOTICE u otros, no genera salida a pantalla alguna.
				$content = trim($this->warningMessage);
			}

			// Previene mostrar path de archivos en pantalla
			$data_error['file'] = '';
			$data_error['line'] = 0;

			// Valida si el mensaje ya fue publicado localmente
			if ($content !== '') {
				$key = md5($data_error['type']) . '/' . md5($content);
				if (!$this->uniqueReport($key)) {
					$content = '';
				}
			}

			// Actualiza mensaje previo a su salida a pantalla
			$data_error['message'] = $content;
		}

		// Muestra en pantalla (si hay algún mensaje reportado)
		if ($data_error['message'] !== '') {
			// Actualiza mensaje previo a su salida a pantalla
			// $data_error['message'] = $content;
			// Captura textos no envíados a pantalla
			if ($data_error['end_script']) {
				while (ob_get_level()) {
					$data_error['buffer'] .= ob_get_clean();
				}
			}

			// Ejecuta vista solo si no está en el bloque de cierre(?)
			// Retorna FALSE si ocurre algún error.
			$content = $render->view('show-error', $data_error);
		}

		return $content;
	}

	private function uniqueReport(string $key): bool
	{
		if (in_array($key, $this->previous)) {
			return false; // Ignora este mensaje a pantalla
		}

		$this->previous[] = $key;
		return true;
	}
}
