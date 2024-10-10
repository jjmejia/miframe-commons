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

class ShowMeRendererCli extends ShowMeRenderer {

	/**
	 * @var array $replace_tags Listado de tags a remplazar por una marca característica
	 * 							Por ejemplo, para enmarcar texto que usa negrita con "*"
	 * 							de forma que <b>texto</b> se visualice como *texto* al
	 * 							renderizar).
	 */
	private $replace_tags = [];

	/**
	 * Constructor del objeto.
	 */
	public function __construct() {

		// Estilos a usar para esta caja
		$this->css_filename = __DIR__ . '/../../resources/emulate.css';

		// Clase a aplicar al simular salida por consola en el navegador web.
		// $this->emulate_console_class = 'miframe-box-console';

		// Número de carácteres a mostrar por línea.
		$this->consoleWidth = 120;

		// Remplazos por defecto
		$this->replace('a', 	['[', ']']);
		$this->replace('b', 	'*');
		$this->replace('i', 	'_');
		$this->replace('code', 	'`');
		$this->replace('br', 	PHP_EOL);
		$this->replace('p', 	PHP_EOL);
	}

	/**
	 * Salida a pantalla para mensajes informativos.
	 *
	 * @param string $body	 	Mensaje a mostrar.
	 * @param string $title 	Título (Opcional).
	 * @param string $footnote 	Texto de menor prioridad (Opcional).
	 * @return string 			Texto a pantalla.
	 */
	public function info(string $body, string $title = '', string $footnote = '') : string {

		return $this->localBox('(I)', '-', $body, $title, $footnote);
	}

	/**
	 * Salida a pantalla para mensajes de advertencia.
	 *
	 * @param string $body	 	Mensaje a mostrar.
	 * @param string $title 	Título (Opcional).
	 * @param string $footnote 	Texto de menor prioridad (Opcional).
	 * @return string 			Texto a pantalla.
	 */
	public function warning(string $body, string $title = '', string $footnote = '') : string {

		return $this->localBox('(!)', '-', $body, $title, $footnote);
	}

	/**
	 * Salida a pantalla para mensajes de error no críticos.
	 *
	 * @param string $body	 	Mensaje a mostrar.
	 * @param string $title 	Título (Opcional).
	 * @param string $footnote 	Texto de menor prioridad (Opcional).
	 * @return string 			Texto a pantalla.
	 */
	public function alert(string $body, string $title = '', string $footnote = '') : string {

		return $this->localBox('(E)', '-', $body, $title, $footnote);
	}

	/**
	 * Salida a pantalla para mensajes de error críticos.
	 *
	 * @param string $body	 	Mensaje a mostrar.
	 * @param string $title 	Título (Opcional).
	 * @param string $footnote 	Texto de menor prioridad (Opcional).
	 * @return string 			Texto a pantalla.
	 */
	public function critical(string $body, string $title = '', string $footnote = '') : string {

		if ($title == '') {
			$title = 'Error Fatal';
		}

		return $this->localBox('#', '#', $body, $title, $footnote);
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

		if ($class != '') {
			$class = strtoupper(trim($class)) . '>';
		}

		return $this->localBox($class, '-', $body, $title, $footnote);
	}

	/**
	 * Constructor de salida a pantalla.
	 *
	 * @param string $mark 		Marca que identifica el mensaje.
	 * @param string $separator Carácter usado para separadores del título.
	 * @param string $body	 	Mensaje a mostrar.
	 * @param string $title 	Título (Opcional).
	 * @param string $footnote 	Texto de menor prioridad (Opcional).
	 * @return string 			Texto a pantalla.
	 */
	private function localBox(string $mark, string $separator, string $body, string $title = '', string $footnote = '') : string {

		if ($footnote !== '') {
			$body .=  PHP_EOL .
			'...' . PHP_EOL .
			$footnote;
		}
		if ($title !== '') {
			// 'console' => '>',
			// 'debug' => 'DEBUG>',
			// 'box-code' => '{.}'
			$title = trim($mark . ' ' . $title);
		}

		$text = PHP_EOL .
			$this->consoleTitle($title, $separator) .
			$this->consoleBody($body);

		return $text;
	}

	/**
	 * Rompe texto en líneas con una longitud de $this->consoleWidth carácteres.
	 *
	 * Se usa este método porque la función PHP wordwrap() no discrimina los caracteres de fin de línea "\n".
	 * Remueve cualquier tag HTML incluido en el texto.
	 *
	 * @param string $text	Texto a formatear.
	 * @param string $break	Cadena a usar como separador de fin de línea.
	 * @return string 		Texto formateado.
	 */
	private function wordwrap(string $text, string $break) {

		$text = strip_tags(str_ireplace(
					array_keys($this->replace_tags),
					$this->replace_tags,
					$text));

		$blanks = 0;
		if ($text !== '' &&
			$this->consoleWidth > 0 &&
			strlen($text) > $this->consoleWidth
			) {
			$lines = explode("\n", $text);
			$text = '';
			// Solo permite una linea en blanco consecutiva
			foreach ($lines as $line) {
				if (trim($line) == '') {
					if ($blanks < 1) {
						$blanks ++;
						$text .= PHP_EOL;
					}
					continue;
				}

				$blanks = 0;
				$text .= wordwrap(rtrim($line), $this->consoleWidth, $break, true) . PHP_EOL;
			}
		}

		$text = rtrim($text) . PHP_EOL;

		return $text;
	}

	/**
	 * Da formto al texto de titulo.
	 *
	 * Adiciona una línea de separación arriba y abajo del título, usando el patrón establecido en
	 * $separator y con la longitud establecida por $this->consoleWidth.
	 *
	 * @param string $title		Título.
	 * @param string $separator	Cadena a usar como patrón para
	 * @return string 			Texto formateado.
	 */
	private function consoleTitle(string $title, string $separator = '-') {

		$title = trim($title);
		if ($title !== '') {
			$separator_text = $separator;
			if ($this->consoleWidth > 0) {
				$separator_text = trim(str_repeat($separator, $this->consoleWidth)) . PHP_EOL;
			}
			$title = $separator_text .
				$this->wordwrap(strtoupper($title), PHP_EOL) .
				$separator_text;
		}

		return $title;
	}

	/**
	 * Adiciona tags HTML al listado de remplazos, para darle formato al mostrar en pantalla.
	 *
	 * Por ejemplo, puede enmarcar texto que usa negrita con "*", de forma que <b>texto</b> o
	 * <b class="xxx">texto</b> se visualice como *texto* al renderizar.
	 *
	 * Puede indicar un patrón diferente para la apertura y cierre definiendo $replace como un
	 * arreglo de dos valores. El primero corresponde a la apertura y el segundo al cierre. Por
	 * ejemplo, puede enmarcar un enlace de forma que <a htref="xxx">Enlace</b> se
	 * visualice como [Enlace] usando el arreglo [ '[', ']' ] como valor para $replace.
	 *
	 * Se evaluan tags sin cierre explícito, como es el caso de "<br>" o "<br />".
	 *
	 * @param string $tag			Tag HTML asociado.
	 * @param string|array $replace	Patrón de remplazo.
	 */
	private function replace(string $tag, string|array $replace) {

		$tag = strtolower(trim($tag));
		$open_replace = $replace;
		$close_replace = $replace;
		// Si $replace es un arreglo, indica apertura y cierre y debe
		// contener dos elementos.
		if (is_array($replace)) {
			$replace = array_values($replace);
			if (count($replace) >= 2) {
				$open_replace = $replace[0];
				$close_replace = $replace[1];
			}
			else {
				// Nada qué hacer
				return;
			}
		}

		// Completo
		$this->replace_tags["<{$tag}>"] = $open_replace . "<{$tag}>";
		// Con elementos incluidos
		$this->replace_tags["<{$tag} "] = $open_replace . "<{$tag} ";
		// Auto-cierre
		$this->replace_tags["<{$tag}/>"] = $open_replace . "<{$tag}/>";
		// Cierre
		$this->replace_tags["</{$tag}>"] = $close_replace . "</{$tag}>";
	}

	/**
	 * Da formto al texto de titulo.
	 *
	 * @param string $text		Texto a formatear.
	 * @return string 			Texto formateado.
	 */
	private function consoleBody(string $text = '') {

		// Las consolas ya no están limitadas por el ancho, pueden modificarse (al menos en Windows)
		return $this->wordwrap(
					$text,
					PHP_EOL
					);
	}

}