<?php

/**
 * Proporciona ayuda para remover el path del "Document Root"
 * en los textos de salida a pantalla.
 */

namespace miFrame\Commons\Traits;

trait SanitizeRenderContent {

	/**
	 * @var bool $hideDocumentRoot Indica si se oculta el DOCUMENT_ROOT en los mensajes de error.
	 */
	public bool $hideDocumentRoot = true;

	/**
	 * Reemplaza referencias al DOCUMENT_ROOT para no revelar su ubicación
	 * en entornos no seguros.
	 *
	 * @param string $content Contenido a filtrar (valor por referencia).
	 */
	public function sanitizeDocumentRoot(string &$content)
	{
		// Apache no usa "\" cuando opera en Windows, sino "/" en algunas variables globales
		// (por ej. $_SERVER['DOCUMENT_ROOT']) por lo que pueden diferir en sintaxis.
		// Por eso no usa directamente $_SERVER['DOCUMENT_ROOT'] sino el valor arrojado por
		// miframe_server() que lo estandariza al separador correcto ("\" para Windows).
		$document_root = miframe_server()->documentRoot();
		if (
			$this->hideDocumentRoot &&
			$content !== ''
			) {
			$content = str_replace(
				// Busca document Root con separador y sin separador (por precaución)
				[$document_root, substr($document_root, 0, -1)],
				['', '[..]'],
				$content
			);
		}
	}

}