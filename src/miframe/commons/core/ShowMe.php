<?php
/**
 * Clase para manejo de salidas a pantalla, sean por Web o Consola.
 *
 * En general, provee métodos para facilitar una salida por pantalla del tipo:
 *
 * +-------------------------------------------+
 * | Título                                    |
 * +-------------------------------------------+
 * |                                           |
 * | Body o cuerpo del mensaje                 |
 * |                                           |
 * +-------------------------------------------+
 * | Footnote o pie (texto de menor prioridad) |
 * +-------------------------------------------+
 *
 * @micode-uses miframe-helpers
 *
 * @author John Mejía
 * @since Julio 2024
 */

namespace miFrame\Commons\Core;

use miFrame\Commons\Patterns\Singleton;
use miFrame\Commons\Support\ShowMe\ShowMeRenderer;
use miFrame\Commons\Support\WebResponse\HTMLSupport;
// Estas clases son opcionales y pueden no incluirse como parte del proyecto
use miFrame\Commons\Support\ShowMe\ShowMeRendererWeb;
use miFrame\Commons\Support\ShowMe\ShowMeRendererCli;

class ShowMe extends Singleton {

	/**
	 * @var bool $emulateConsole	TRUE simula comportamiento de consola
	 * 								en consultas Web.
	 */
	public bool $emulateConsole = false;

	public bool $is_web_real = false;

	private array $renderers = [];

	private string $currentRender = '';

	private array $data = [];

	private array $styles = [];

	private bool $ignore_styles = false;

	private $html = null;

	private $published = false;

	/**
	 * Inicialización de la clase Singleton.
	 */
	protected function singletonStart() {

		// Preserva estado real
		$this->is_web_real = miframe_server()->isWeb();
		// Inicializa renderers
		$this->useLocalRenders();
		// Inicializa datos esperados
		$this->reset();
		// Soporte para HTML
		$this->html = new HTMLSupport();
	}

	/**
	 * Inicializa datos esperados.
	 */
	public function reset() {

		$this->data = [
			// Datos para creación del box
			'title' => '',
			'body' => '',
			'footnote' => '',
			'class' => ''
			];
	}

	/**
	 * Identifica si el script actual se ejecuta por Web o por Consola.
	 *
	 * Permite simular el comportamiento para consola desde Web fijando la
	 * propiedad $this->emulateConsole = true.
	 *
	 * @return bool TRUE para ejecuciones Web y FALSE para consola.
	 */
	public function inWeb() {

		return $this->is_web_real && !$this->emulateConsole;
	}

	/**
	 * Registra función a usar cuando se visualiza para Web.
	 *
	 * @param callable $object Objeto de la clase ShowMeRenderer.
	 */
	public function rendererWeb(ShowMeRenderer $object) {

		$this->renderers['web'] = $object;
		// Inicializa estilos
		$this->cleanStyles();
	}

	/**
	 * Registra función a usar cuando se visualiza por consola.
	 *
	 * @param callable $object Objeto de la clase ShowMeRenderer.
	 */
	public function rendererCLI(ShowMeRenderer $object) {

		$this->renderers['cli'] = $object;
		// Inicializa estilos
		$this->cleanStyles();
	}

	/**
	 * Si el proyecto no incluye las clases ShowMeRendererWeb y
	 * ShowMeRendererCli, ignora la petición de uso, no genera error.
	 */
	public function useLocalRenders() {

		if (class_exists(ShowMeRendererWeb::class)) {
			$object_web = new ShowMeRendererWeb();
			$this->rendererWeb($object_web);
		}

		if (class_exists(ShowMeRendererCli::class)) {
			$object_cli = new ShowMeRendererCli();
			$this->rendererCli($object_cli);
		}
	}

	/**
	 * Remueve objetos a usar para renderizar las salidas a pantalla, sean web o por consola.
	 */
	public function noRenderers() {

		$this->renderers = [];
	}

	/**
	 * Define título para la caja de diálogo.
	 *
	 * @param string $title Título
	 */
	public function title(string $title) : self {

		$this->data['title'] = $title;
		return $this;
	}

	/**
	 * Define cuerpo del mensaje para la caja de diálogo.
	 *
	 * @param string $body 	Mensaje a mostrar.
	 */
	public function body(string $body) : self {

		$this->data['body'] = $body;
		return $this;
	}

	/**
	 * Define texto de menor prioridad o pie de la caja de diálogo.
	 *
	 * @param string $footnote 	Texto de menor prioridad.
	 */
	public function footer(string $footnote) : self {

		$this->data['footnote'] = $footnote;
		return $this;
	}

	/**
	 * Define clase asociada a la caja de diálogo.
	 *
	 * @param string $class Nombre de la clase asociada.
	 */
	public function class(string $class) : self {

		$this->data['class'] = trim($class);
		return $this;
	}

	/**
	 * Construye salida a pantalla de la caja de diálogo, sea web o consola.
	 *
	 * @param bool $return	TRUE retorna texto, FALSE envía directo a pantalla y retorna vacio.
	 * @return string		Vacio o texto formateado.
	 */
	public function render(bool $return = false) : string {

		$text = '';
		$renderer = $this->renderer();
		$class = trim($this->data['class']);
		if ($class === '') { $class = 'regular'; }
		// echo "$class // $renderer<hr>";

		if (!method_exists($renderer, $class)) {
			$text = $renderer->box(
				$class,
				$this->data['body'],
				$this->data['title'],
				$this->data['footnote']
				);
			}
		else {
			// Intenta recuperar si es uno de los métodos estándar
			$text = $renderer->$class(
				$this->data['body'],
				$this->data['title'],
				$this->data['footnote']
				);
		}

		// Valida si hay algo por mostrat
		if ($text === '') { return $text; }

		// Recupera estilos usados (solo para Web)
		$text = $this->getStyles() .
			$text .
			PHP_EOL;

		// Inicializa datos
		$this->reset();

		// Da formato si la salida es por consola
		$text = $this->purge($text);

		if (!$return) {
			// Imprime directo a pantalla
			echo $text;
			// Libera contenido
			$text = '';
		}

		return $text;
	}

	/**
	 * Adiciona archivo con estilos.
	 *
	 * Puede usar $replace_local = true para remplazar el archivo con estilos principal y redefinir todos
	 * los estilos a usar por el objeto renderizador. Se recomienda hacer esto antes que los estilos sean
	 * publicados, usualmente con el primer uso de render(), de lo contrario se genera un mensaje de error.
	 *
	 * @param string $filename		Archivo que contiene los estilos a usar.
	 * @param bool $replace_local	TRUE para remplazar archivo con estilos principal, FALSE para
	 * 								adicionar los estilos a los existentes.
	 * @return bool					TRUE si acepta los cambios, FALSE en otro caso.
	 */
	public function css(string $filename, bool $replace_local = false) : bool {

		$filename = trim($filename);
		if ($filename === '') {
			// Nada por hacer
			return false;
		}

		if ($replace_local) {
			if (!$this->published) {
				// Remueve archivo local para usar solamente el nuevo asignado como local
				$this->styles['main'] = $filename;
			}
			else {
				// echo "ERROR<hr>";
				trigger_error('Los estilos locales ya fueron publicados', E_USER_WARNING);
			}
			return !$this->published;
		}

		// Adiciona archivo al listado
		$key = md5(strtolower($filename));
		$this->styles['files'][$key] = $filename;

		return true;
	}

	/**
	 * Adiciona estilos en línea (código a incluir luego dentro de un tag HTML "style").
	 *
	 * Tenga presente que los estilos se aplican a todos los elementos presentes en la página,
	 * independiente de en qué momento dentro del script sean adicionados.
	 *
	 * @param string $code	Estilos en línea.
	 */
	public function style(string $code) {

		$code = trim($code);
		if ($code !== '') {
			// Registra estilos a usar.
			// Genera llave para prevenir se dupliquen valores.
			$key = md5(strtolower(str_replace(["\r", "\n", "\t", ' '], '', $code)));
			$this->styles['in-line'][$key] = $code;
		}
	}

	// Puede usar cssIgnore() y luego definir todos los estilos con style().

	// Siendo que los estilos se imprimen una unica vez, tiene sentido
	// cssIgnore si se invoca despues de eso? Lo mismo cssRestore?

	/**
	 * Remueve estilos definidos previamente.
	 */
	private function cleanStyles() {

		$this->styles = [
			'main' => '',
			'files' => [],
			'in-line' => []
			];
	}

	/**
	 * Previene el uso de los estilos predefinidos con la página.
	 *
	 * Para que su uso sea efectivo, debe usarse antes del primer render(). Una vez realizada
	 * una publicación de estilos a la página estos no pueden ser retirados, simplemente se ignorarán
	 * los estilos adicionados luego de este llamado.
	 */
	public function ignoreStyles() {
		$this->ignore_styles = true;
	}

	/**
	 * Recupera la clase a usar para visualizar, ya sea para Web o consola.
	 *
	 * @return ShowMeRenderer Objeto encargado de realizar el render.
	 */
	public function renderer() : ShowMeRenderer {

		$name = 'cli';
		// Si es WEB, siempre va a usar el render WEB, aunque simule consola
		if ($this->inWeb()) {
			$name = 'web';
		}

		// print_r($this->renderers); echo "<hr>";
		if (!empty($this->renderers[$name])) {
			$this->currentRender = $name;
		}
		else {
			// Si llega a este punto, asigna un render por defecto.
			$this->currentRender = 'default-' . $name;
			// Si no ha creado la clase para manejo por defecto, lo hace una unica vez
			if (empty($this->renderers[$this->currentRender])) {
				$classname = '\miFrame\Commons\Support\ShowMe\ShowMeRenderer';
				$this->renderers[$this->currentRender] = new $classname();
			}
		}

		// Objeto actual a usar para renderizar
		$object = $this->renderers[$this->currentRender];
		// Recupera estilos usados por el renderer
		$this->styles['main'] = $object->cssFilename();

		return $object;
	}

	/**
	 * Asegura el formato de texto para su salida por consola.
	 *
	 * Remueve tags HTML existentes. Si se habilita la salida a consola en un entorno Web
	 * (emular consola) se invoca el método emulateConsole() del renderizador.
	 *
	 * @param string $text  Texto a pantalla.
	 * @return string		Texto formateado para consola.
	 */
	public function purge(string $text) : string {

		if (!$this->inWeb()) {
			// Remueve tags HTML (si alguno)
			$text = rtrim(strip_tags($text)) . PHP_EOL;
			if ($this->is_web_real) {
				// En realidad es una salida web pero DEBE emular consola
				// Definirse en el render para CLI, ya que getBox() va a invocarlo.
				$this->renderer()->emulateConsole($text);
				// Recupera estilos usados por el renderer (originalmente no se
				// publican pues no aplican para modelo CLI)
				if (!$this->ignore_styles) {
					$text = $this->getStyles(true) . $text;
				}
			}
		}

		return $text;
	}

	/**
	 * Retorna estilos requeridos para renderizar la caja de diálogo.
	 *
	 * Los estilos se incluyen en línea una única vez.
	 *
	 * @param bool $force	TRUE incluye los estilos siempre, FALSE valida si debe ignorarlos
	 * 						ya sea por tratarse de una salida por consola o porque $this->ignore_styles = true.
	 * @return string		Estilos en formato HTML.
	 */
	public function getStyles(bool $force = false) : string {

		$text = '';

		// Retorna vacio si no es web o si debe ignorar estilos
		if (!$force && (!$this->inWeb() || $this->ignore_styles)) {
			return $text;
		}

		// Archivo principal (solamente si no han sido ya publicados los estilos)
		if (!$this->published && $this->styles['main'] !== '') {
			$text .= $this->html->getStylesFrom($this->styles['main'], true);
			// Indica que ya se publicaron estilos
			$this->published = true;
		}

		// Archivos asociados
		foreach ($this->styles['files'] as $filename) {
			if ($filename !== '') {
				$text .= $this->html->getStylesFrom($filename, true);
			}
		}

		// Estilos en linea
		$styles = '';
		foreach ($this->styles['in-line'] as $code) {
			$styles .= $code . PHP_EOL;
		}

		if ($styles !== '') {
			$text .= $this->html->stylesCode($code, 'Estilos en-linea');
		}

		// Limpia pues ya envió todo
		$this->cleanStyles();

		return $text;
	}

}
