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
use miFrame\Commons\Components\ErrorData;

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

	/**
	 * @var array $previous Registra mensajes de error ya reportados en producción.
	 */
	private array $previous = [];

	/**
	 * @var array $cacheContent Almacena errores renderizados para forzar su visualización en caso que otro error termine el script.
	 */
	private array $cacheContent = [];

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
	 * @param ErrorData $error Obketo que contiene detalles del error.
	 *
	 * @return bool Retorna FALSE si el error ya ha sido reportado.
	 */
	public function show(ErrorData $error): string
	{
		$content = '';

		// Recupera objeto renderizador
		/**
		 * @var ExtendedRenderView $render
		 */
		$render = miframe_render();

		if ($render->inDeveloperMode() && $this->inDeveloperModeEndScript) {
			// Termina el script en modo Desarrollo
			$error->endScript = true;
		}

		// Obtiene datos del error
		$data_error = $error->export();

		// Texto no enviado a pantalla (si alguno)
		$data_error['buffer'] = '';

		if (!$render->inDeveloperMode()) {
			// Oculta información a pantalla en producción.
			// A partir de este punto, puede retornar cadena vacia
			// (false se interpreta como un error, por eso lo remplaza)

			if ($error->endScript) {
				// Error que se muestra en pantalla y termina ejecución
				$content = trim($this->errorMessage);
			} elseif (
				$data_error['class'] !== 'Error' || // Es una excepción
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
				if (!$this->uniqueKey($key) && !$error->endScript) {
					// Ya registrado, remueve contenido
					// (y por extensión no lo muestra en pantalla),
					// a menos que esta versión de por terminado el script.
					$content = '';
				}
			}

			// Actualiza mensaje previo a su salida a pantalla
			$data_error['message'] = $content;
		}

		// Muestra en pantalla (si hay algún mensaje reportado)
		if ($data_error['message'] !== '') {
			// Valida acciones en caso que el script termine
			if ($error->endScript) {
				// Captura textos no envíados a pantalla
				while (ob_get_level()) {
					$data_error['buffer'] .= ob_get_contents();
					ob_end_clean();
				}
				// Busca errores previos y los remueve del buffer
				foreach ($this->cacheContent as $previousError) {
					$data_error['buffer'] = str_replace($previousError, '', $data_error['buffer']);
				}
			}

			// Ejecuta vista solo si no está en el bloque de cierre(?)
			// Retorna FALSE si ocurre algún error.
			$content = $render->capture('show-error', $data_error, $error->htmlMessage());
			// Valida errores previamente renderizados
			$this->evalCachedErrors($content, $error->endScript);
			// Adiciona estilos previamente guardados
			$render->exportStyles($content);
		}

		return $content;
	}

	/**
	 * Valida si una clave dada ya ha sido registrada.
	 *
	 * @param string $key La clave a validar.
	 * @return bool Devuelve TRUE si la clave no ha sido previamente registrada, FALSE en caso contrario.
	 */
	private function uniqueKey(string $key): bool
	{
		if (!in_array($key, $this->previous)) {
			$this->previous[] = $key;
			return true;
		}

		return false;
	}

	/**
	 * En caso que el error termine el script, adiciona errores previamente renderizados.
	 *
	 * De otra forma, estos errores y el css previamente renderizado quedaría en el
	 * buffer no publicado y no se visualizarían correctamente.
	 *
	 * @param string $content Contenido renderizado a modificar.
	 * @param bool $end_script TRUE solamente si el error termina el script.
	 */
	private function evalCachedErrors(string &$content, bool $end_script)
	{
		if (!$end_script) {
			// Guarda mensaje de error para revisar en caso que
			// se presente posteriormente un error fatal
			if (trim($content) !== '') {
				$this->cacheContent[] = $content;
			}
		} else {
			// Adiciona errores previos reportados
			$content = implode(PHP_EOL, $this->cacheContent) .
				PHP_EOL .
				$content;
			// Libera memoria
			$this->cacheContent = [];
		}
	}
}
