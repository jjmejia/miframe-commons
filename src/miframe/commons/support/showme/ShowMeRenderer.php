<?php
/**
 * Clase de soporte para manejo de salidas a pantalla.
 *
 * @micode-uses miframe-helpers
 *
 * @author John Mejía
 * @since Julio 2024
 */

namespace miFrame\Commons\Support\ShowMe;

class ShowMeRenderer {

	/**
	 * @var string $css_filename	Nombre del archivo CSS principal asociado al tipo de salida
	 * 								por pantalla (para presentaciones web) o del CSS a usar para
	 * 								emular la salida por consola en un navegador Web. Puede
	 * 								indicarse una ruta URL usando el prefijo "url:".
	 */
	protected $css_filename = '';

	/**
	 * @var string $emulate_console_class	Nombre de la clase usada para emular la presentación
	 * 										de consola en un navegador Web (por defecto usa "miframe-box-console").
	 */
	protected $emulate_console_class = '';

	/**
	 * @var int $consoleWidth	Número de carácteres a mostrar por línea para salidas por consola.
	 */
	public $consoleWidth = 80;

	/**
	 * Caja de texto estandar.
	 * Puede personalizarse por completo en una clase hija o solamente personalizar las clases
	 * requeridas para su presentación.
	 *
	 * @param string $class 	Tipo de mensaje.
	 * @param string $body 		Mensaje a mostrar.
	 * @param string $title 	Título (Opcional).
	 * @param string $footnote 	Texto de menor prioridad (Opcional).
	 * @return string 			HTML para consultas web, texto regular para consola.
	 */
	public function box(string $class, string $body, string $title = '', string $footnote = '') : string {

		if ($footnote != '') {
			$footnote = "<div class=\"box-footnote box-{$class}\">" .
				$footnote .
				"</div>" . PHP_EOL;
			}

		if ($title !== '') {
			$title = "<h2 class=\"box-title\">{$title}</h2>" . PHP_EOL;
		}

		$body = trim($body);
		if ($body !== '') {
			$body = "<div class=\"box-message\">{$body}</div>" . PHP_EOL;
		}

		$text = $title . $body . $footnote;
		if ($text !== '') {
			$text = "<div class=\"miframe-box box-{$class}\">{$text}</div>" . PHP_EOL;
		}

		return $text;
	}

	/**
	 * Retorna archivo o enlace con estilos a usar (si alguno).
	 *
	 * @return string Path de archivo físico o URL de un recurso remoto (incluye el prefijo "url:").
	 */
	public function cssFilename() : string {

		if (is_file($this->css_filename)) {
			$this->css_filename = realpath($this->css_filename);
		}
		return $this->css_filename;
	}

	/**
	 * Representación string asociada a este objeto. Muestra el nombre de la clase hija.
	 *
	 * @return string Nombre dado a la clase hija.
	 */
	public function __toString() {
		return get_class($this);
	}
}