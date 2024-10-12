<?php
/**
 * Clase de soporte para manejo de salidas a pantalla.
 *
 * @author John Mejía
 * @since Julio 2024
 */

namespace miFrame\Commons\Support\ShowMe;

class ShowMeRendererWeb extends ShowMeRenderer {

	public function __construct() {

		// Estilos a usar para esta caja
		$this->css_filename = __DIR__ . '/../../resources/framebox.css';
	}

	/**
	 * Caja de texto para mensajes de error críticos.
	 *
	 * @param string $message 	Mensaje a mostrar.
	 * @param string $title 	Título (Opcional).
	 * @param string $footnote 	Texto de menor prioridad (Opcional).
	 * @return string 			HTML.
	 */
	public function critical(string $message, string $title = '', string $footnote = '') : string {

		if ($title == '') {
			$title = 'Error Fatal';
		}

		return $this->box('critical', $message, $title, $footnote);
	}

	/**
	 * Caja de texto estandar.
	 *
	 * @param string $class 	Tipo de mensaje.
	 * @param string $body 		Mensaje a mostrar.
	 * @param string $title 	Título (Opcional).
	 * @param string $footnote 	Texto de menor prioridad (Opcional).
	 * @return string 			HTML para consultas web, texto regular para consola.
	 */
	public function box(string $class, string $body, string $title = '', string $footnote = '') : string {

		if ($class == '') {
			$class = 'mute';
		}

		return parent::box($class, $body, $title, $footnote);
	}
}