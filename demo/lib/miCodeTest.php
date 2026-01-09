<?php

/**
 * Clase de soporte para montaje de scripts para test de módulos.
 *
 * @author John Mejia
 * @since Mayo 2022
 */

class miCodeTest
{
	private array $choices = [];
	private string $codePre = '';
	private array $content_script = [];

	// Para visualización de líneas de código
	private string $filename = '';

	// Dominio web
	private string $domainName = '';

	/**
	 * @var string $title Título de la página.
	 */
	public string $title = '';

	/**
	 * @var string $description Descripción de la página.
	 */
	public string $description = '';

	/**
	 * @var string $styles Estilos adicionales en línea.
	 */
	public string $styles = '';

	/**
	 * @var bool $useMiFrameErrorHandler TRUE hace uso de miframe_errors() para gestión de errores.
	 */
	public bool $useMiFrameErrorHandler = true;

	/**
	 * Constructor de la clase.
	 * Inicializa la sesión PHP y la configuración.
	 */
	public function __construct()
	{
		// Inicia sesión si no ha sido iniciada previamente
		session()->name('miframe-commons-demo')->start();
	}

	private function uri(): string
	{
		$path = dirname($_SERVER['SCRIPT_NAME']);
		// Cuando se ejecuta directo y no a través del router, el REQUEST_URI incluye el
		// directorio "demo"
		$pos = strpos($path, '/support');
		if ($pos !== false) {
			$path = substr($path, 0, $pos);
		}

		return $path;
	}

	private function urlDemo(): string
	{
		return config('apps_miframe_commons_url', $this->uri()) . '/';
	}

	public function home(bool $force = false): string
	{
		$url = '';
		$path_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		if ($force || strpos($path_uri, '/demo-') !== false) {
			// No es el index de las demos
			$url = config('apps_miframe_commons_home', $this->uri());
		}
		return $url;
	}

	/**
	 * Define el path a usar para buscar recursos, relativo a la URL actual.
	 *
	 * Puede definirse previamente para acceder a un directorio diferente
	 * al auto-detectado.
	 *
	 * @param string $path Ruta relativa del recurso.
	 * @return string Ruta completa del recurso.
	 */
	private function resourcesPath(string $path): string
	{
		if ($path !== '' && $path[0] !== '/') {
			$path = '/' . $path;
		}
		return $this->urlDemo() . 'resources' . $path;
	}

	/**
	 * Presenta encabezado para la salida a pantalla.
	 */
	public function start()
	{
		$estilos = $this->resourcesPath('/css/tests.css');
		$favicon = $this->resourcesPath('/img/favicon.png');

		$title_meta = strip_tags($this->title);
		$description = trim($this->description);
		if ($description == '') {
			$description = 'miCode-Manager Demos -- ' . $title_meta . '.';
		}
		$description_meta = strip_tags($description);

		// Designa dominio por defecto si no ha definido alguno
		$this->domainName = server()->domain();
		if (empty($this->domainName) || $this->domainName == 'localhost') {
			// Nombre especial para localhost
			$this->domainName = 'localhost: ' . substr(dirname($this->urlDemo()), 1);
		}

		if (!server()->isWeb()) {
			// Salida por consola
			echo strip_tags($this->title . PHP_EOL . $this->description) . PHP_EOL . PHP_EOL;
			return;
		}

		// Estilos adicionales en linea
		$estilos_add = miframe_html()->cssExport();

?>
		<!DOCTYPE html>
		<html>

		<head>
			<meta charset="utf-8" />
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<meta name="viewport" content="width=device-width, initial-scale=1" />
			<meta name="robots" content="nofollow" />
			<meta name="description" content="<?= $description_meta ?>" />
			<meta property="og:title" content="<?= $title_meta ?>" />
			<meta property="og:type" content="website" />
			<meta property="og:description" content="<?= $description_meta ?>" />
			<title><?= htmlentities($this->title) ?></title>
			<link rel="icon" type="image/png" href="<?= $favicon ?>" />
			<link rel="stylesheet" href="<?= $estilos ?>">
			<?= $estilos_add ?>
		</head>

		<body>
			<h1 class="test-encab">
				<?= htmlentities($this->title) ?>
				<small><?= $this->domainName ?></small>
			</h1>
			<?php

			$home = $this->home();
			// Valida si definió enlace a "home"
			if (!empty($home)) {
				echo '<a href="' . $home . '" class="test-back-home">
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
<path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
</svg> Regresar</a>';
			}
			// Apertura del contenedor de la página demo a mostrar

			?>
			<div class="test-description"><?= $description ?></div>
			<div class="test-content">
		<?php

		// Manejo personalizado de errores
		if ($this->useMiFrameErrorHandler) {
			miframe_errors();
		}
	}

	/**
	 * Muestra texto formateado en HTML.
	 *
	 * @param string $text Texto a mostrar.
	 */
	public function htmlCode(string $text)
	{
		if (trim($text) == '') {
			return;
		}

		// Colores a usar con highlight_string()
		// https://www.php.net/manual/es/misc.configuration.php#ini.syntax-highlighting
		// ini_set('highlight.bg', '');
		ini_set('highlight.comment', '#007400');
		ini_set('highlight.default', '#242424');
		// ini_set('highlight.html', '');
		ini_set('highlight.keyword', '#aa0d91');
		ini_set('highlight.string', '#c41a16');

		// Estandariza codigo
		$lines = explode("\n", $text);
		// Remueve cualqiuer primer linea en blanco
		while (trim($lines[0]) === '') {
			array_shift($lines);
		}
		// Valida si quedó algo qué validar
		if (!is_array($lines) || count($lines) <= 0) {
			return;
		}
		// Si la primer linea contiene espacios, los remueve de las siguientes (si aplica)
		$count_remove = 0;
		while (trim(substr($lines[0], $count_remove, 1)) === '') {
			$count_remove++;
		}
		// Limpia cada línea
		foreach ($lines as &$eachline) {
			$eachline = rtrim($eachline);
			if ($count_remove > 0) {
				if (trim(substr($eachline, 0, $count_remove)) === '') {
					$eachline = substr($eachline, $count_remove);
				}
			}
		}
		// Elimina lineas en blanco
		$lines = array_filter($lines);
		// Reconstruye y colorea (highlight_string() requiere el tah de inicio PHP)
		$text = highlight_string('<?php ' . implode(PHP_EOL, $lines), true);

		echo PHP_EOL .
			str_replace(['&lt;?php ', '<pre>'], ['', '<pre class="code">'], $text) .
			PHP_EOL;
	}

	public function htmlPre(string $text)
	{
		echo PHP_EOL . '<pre class="code console">' . trim($text) . '</pre>' . PHP_EOL;
	}

	/**
	 * Da cierre a la página demo.
	 *
	 * @param bool $show_repo Indica si se muestra el enlace al repositorio.
	 */
	public function end(bool $show_repo = true)
	{
		if ($show_repo && server()->isLocalhost()) {
			echo "<h2>Localhost / Clases usadas</h2>";
			$this->dump(miframe_autoload()->matches());
		}

		$github_repo = config('github_repo', '');
		if ($show_repo && !empty($github_repo)) {
			echo '<div class="test-repo">';
			echo '<h2>¿Tienes curiosidad por el código fuente?</h2>';
			echo '<p><a href="' . $github_repo . '" target="_blank">';
			// https://www.svgrepo.com/svg/475654/github-color
			echo '<svg width="24px" height="24px" viewBox="0 -0.5 48 48" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g id="Icons" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"> <g id="Color-" transform="translate(-700.000000, -560.000000)" fill="#3E75C3"> <path d="M723.9985,560 C710.746,560 700,570.787092 700,584.096644 C700,594.740671 706.876,603.77183 716.4145,606.958412 C717.6145,607.179786 718.0525,606.435849 718.0525,605.797328 C718.0525,605.225068 718.0315,603.710086 718.0195,601.699648 C711.343,603.155898 709.9345,598.469394 709.9345,598.469394 C708.844,595.686405 707.2705,594.94548 707.2705,594.94548 C705.091,593.450075 707.4355,593.480194 707.4355,593.480194 C709.843,593.650366 711.1105,595.963499 711.1105,595.963499 C713.2525,599.645538 716.728,598.58234 718.096,597.964902 C718.3135,596.407754 718.9345,595.346062 719.62,594.743683 C714.2905,594.135281 708.688,592.069123 708.688,582.836167 C708.688,580.205279 709.6225,578.054788 711.1585,576.369634 C710.911,575.759726 710.0875,573.311058 711.3925,569.993458 C711.3925,569.993458 713.4085,569.345902 717.9925,572.46321 C719.908,571.928599 721.96,571.662047 724.0015,571.651505 C726.04,571.662047 728.0935,571.928599 730.0105,572.46321 C734.5915,569.345902 736.603,569.993458 736.603,569.993458 C737.9125,573.311058 737.089,575.759726 736.8415,576.369634 C738.3805,578.054788 739.309,580.205279 739.309,582.836167 C739.309,592.091712 733.6975,594.129257 728.3515,594.725612 C729.2125,595.469549 729.9805,596.939353 729.9805,599.18773 C729.9805,602.408949 729.9505,605.006706 729.9505,605.797328 C729.9505,606.441873 730.3825,607.191834 731.6005,606.9554 C741.13,603.762794 748,594.737659 748,584.096644 C748,570.787092 737.254,560 723.9985,560" id="Github"> </path> </g> </g> </g></svg> ';
			echo 'Repositorio disponible en <b>github.com</b></a></p>';
			echo '</div>';
		}

		// 	Cierra contenedor "test-content" abierto en $this->start()
		echo '</div>';

		// Registra visita
		$this->updateVisitorLog();

		echo '</body></html>';
	}

	/**
	 * Genera un enlace HTML.
	 *
	 * @param string $name Nombre del enlace.
	 * @param array $data Datos adicionales para el enlace.
	 * @param bool $raw Indica si se retorna el enlace en formato raw.
	 * @return string Enlace HTML.
	 */
	public function link(string $name, array $data = [], $raw = false): string
	{
		$enlace_base = (!empty($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '');
		if (count($data) > 0) {
			$enlace_base .= '?' . http_build_query($data);
		}
		if (!$raw) {
			$enlace_base = '<a href="' . $enlace_base . '">' . $name . '</a>';
		}

		return $enlace_base;
	}

	/**
	 * Obtiene el parámetro de la URL.
	 *
	 * @param string $param_name Nombre del parámetro.
	 * @param array $links Enlaces disponibles.
	 * @return string Valor del parámetro.
	 */
	public function getParam(string $param_name, array $links): string
	{
		// Captura vista elegida por el usuario
		$post_view = '';
		if (isset($_GET[$param_name])) {
			$post_view = strtolower(trim($_GET[$param_name]));
		}
		// Valida que exista
		if (!isset($links[$post_view])) {
			$post_view = array_key_first($links);
		}

		return $post_view;
	}

	/**
	 * Genera múltiples enlaces HTML.
	 *
	 * @param string $param_name Nombre del parámetro.
	 * @param array $links Enlaces disponibles.
	 * @return string Enlaces HTML formateados.
	 */
	public function multipleLinks(string $param_name, array $links): string
	{
		$post_view = $this->getParam($param_name, $links);
		// Crea enlaces para selección de las vistas
		$views_links = '';
		foreach ($links as $k => $view_title) {
			if ($views_links != '') {
				$views_links .= ' | ';
			}
			if ($post_view == $k) {
				$views_links .= $view_title;
			} else {
				$data =  [$param_name => $k] + $_GET;
				$views_links .= $this->link($view_title, $data);
			}
		}

		return $views_links;
	}

	/**
	 * Define una opción de elección por parte del usuario.
	 *
	 * @param string $option Nombre de la opción.
	 * @param string $text_nok Texto cuando no está seleccionada.
	 * @param string $text_ok Texto cuando está seleccionada.
	 * @return bool TRUE si la opción está seleccionada.
	 */
	public function choice(string $option, string $text_nok, string $text_ok): bool
	{
		$retornar = false;

		$data = $_GET;
		$info = $text_nok;
		if (array_key_exists($option, $data)) {
			$retornar = true;
			unset($data[$option]);
			$info = $text_ok;
		} else {
			$data[$option] = true;
		}

		$this->choices[$option] = ['title' => $info, 'data' => $data, 'def' => $text_nok];

		return $retornar;
	}

	/**
	 * Renderiza las opciones de elección.
	 *
	 * @param string $separator Separador entre opciones.
	 * @param bool $use_checkboxes Indica si se usan checkboxes.
	 * @return string Opciones renderizadas.
	 */
	public function renderChoices($separator = ' | ', $use_checkboxes = false): string
	{
		$text = '';
		foreach ($this->choices as $option => $info) {
			if ($text !== '') {
				$text .= $separator;
			}
			if ($use_checkboxes) {
				// print_r($info); echo " -- $option<hr>";
				$checked = '';
				if (empty($info['data'][$option])) {
					$checked = ' checked';
				}
				$enlace = $this->link($info['title'], $info['data'], true);
				$text .= "<label><input type=\"checkbox\"{$checked} onclick=\"window.location='{$enlace}'\">{$info['def']}</label>";
			} else {
				$text .= $this->link($info['title'], $info['data']);
			}
			// Remueve opción ya recuperada
			unset($this->choices[$option]);
		}

		return $text;
	}

	private function varDump(mixed $v): string
	{
		if (is_bool($v)) {
			$v = ($v ? 'true' : 'false');
		} elseif (!is_numeric($v) && !is_float($v)) {
			// NOTA: var_export() sobre float adiciona muchos decimales (Linux).
			$v = var_export($v, true);
		}
		return str_replace('\\\\', '\\', htmlentities($v));
	}

	/**
	 * Despliega el contenido de una variable.
	 *
	 * @param mixed $data Datos a mostrar.
	 * @param bool $one_line TRUE remueve saltos de línea en el contenido visible de la variable.
	 */
	public function dump(mixed $data, bool $one_line = false)
	{
		$info = '';
		if (is_array($data)) {
			$info .= '<table cellspacing="0">';
			foreach ($data as $k => $v) {
				$data_visible = '';
				if ($one_line && is_array($v)) {
					// Imprime contenido del arreglo en un solo valor
					$v = "[" .
						array_reduce(array_keys($v), function ($acum, $key) use ($v) {
							if ($acum !== '') {
								$acum .= ', ';
							}
							return $acum . $this->varDump($key) . " => " . $this->varDump($v[$key]);
						}, '') .
						']';
				} else {
					$v = $this->varDump($v);
				}

				$info .= '<tr>' .
					'<td valign="top"><b>' . $k . '</b></td>' .
					'<td valign="top" class="dump-connect">=></td>' .
					'<td>' . $v . '</td>' .
					'</tr>';
			}
			$info .= '</table>';
		} else {
			$info = htmlentities(var_export($data, true));
		}

		$this->htmlPre($info);
	}

	/**
	 * Registra visitas en archivos log.
	 */
	private function updateVisitorLog()
	{
		if (function_exists('registerVisitor')) {
			// Registra visitas
			registerVisitor();
			// Muestra pie de página
			echo lekosdev_footer();
		}
	}

	/**
	 * Copia las siguientes líneas de código para mostrarlas en pantalla.
	 *
	 * @param int $lines Número de líneas a copiar.
	 */
	public function copyNextLines(int $lines = 1)
	{
		// Muestra las lineas siguientes a la posición actual
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$content = '';
		$index = 0;
		// Para el caso de showNextLines(), el backtrace "0" no apunta
		// al archivo que invoca la petición sino a este archivo.
		while (!empty($trace[$index]) && $trace[$index]['file'] == __FILE__) {
			$index++;
		}
		if (!empty($trace[$index])) {
			if ($this->filename !== $trace[$index]['file']) {
				$this->filename = $trace[$index]['file'];
				$this->content_script = file($trace[$index]['file']);
			}
			for ($i = $trace[$index]['line']; $i < $trace[$index]['line'] + $lines; $i++) {
				if (array_key_exists($i, $this->content_script)) {
					$content .= rtrim(str_replace("\t", '    ', $this->content_script[$i])) . PHP_EOL;
				}
			}
		}

		// Si solamente retorna una linea, elimina espacios al inicio
		if ($lines == 1) {
			$content = ltrim($content);
		}

		$this->codePre .= $content;
	}

	/**
	 * Muestra en pantalla las siguientes líneas de código.
	 *
	 * @param int $lines Número de líneas a mostrar.
	 */
	public function showNextLines(int $lines = 1, array $replace = [])
	{
		$this->codePre = '';
		$this->copyNextLines($lines);
		$content = $this->pasteLines($replace);
		if ($content != '') {
			// Salida a pantalla
			$this->htmlCode($content);
		}
	}

	/**
	 * Pega las líneas copiadas.
	 *
	 * @return string Líneas copiadas.
	 */
	public function pasteLines(array $replace = []): string
	{
		$text = $this->codePre;
		$this->codePre = '';
		// Remplaza texto para dar legibilidad
		if (count($replace) > 0) {
			return str_replace(array_keys($replace), $replace, $text);
		}
		return $text;
	}

	public function htmlPasteLines(array $replace = [])
	{
		$this->htmlCode($this->pasteLines($replace));
	}

	/**
	 * Aborta la ejecución del script con un mensaje de error.
	 *
	 * @param string $message Mensaje de error.
	 */
	public function abort(string $message)
	{
		echo "<p class=\"test-aviso\"><b>Error:</b> {$message}</p>";
		$this->end(false);
		exit();
	}
}
