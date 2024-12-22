<?php

/**
 * Proporciona ayuda para remover el path del "Document Root"
 * en los textos de salida a pantalla.
 */

namespace miFrame\Commons\Traits;

trait removeDocumentRootContent {

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
	public function removeDocumentRoot(string &$content)
	{
		if ($this->hideDocumentRoot && $content !== '') {
			$content = str_replace(
				[$_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR, $_SERVER['DOCUMENT_ROOT']],
				['', '[..]'],
				$content
			);
		}
	}

}