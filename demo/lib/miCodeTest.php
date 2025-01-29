<?php

/**
 * Clase de soporte para montaje de scripts para test de módulos.
 *
 * @author John Mejia
 * @since Mayo 2022
 */

class miCodeTest
{
	private $config = [];
	private $choices = [];
	private $codePre = '';
	private $filename = '';
	private $content_script = [];

	/**
	 * Constructor de la clase.
	 * Inicializa la sesión PHP y la configuración.
	 */
	public function __construct()
	{
		// Inicializa manejo de sesión PHP
		if (empty($_SESSION)) {
			session_start();
		}

		// Inicializa config
		$this->initConfig();
	}

	/**
	 * Inicializa datos de configuración.
	 */
	private function initConfig()
	{
		$this->config = array(
			// Identificador del Dominio principal
			'domain-name' => '',
			// Path con el código fuente
			'src-path' => '', 		// 'MICODE_DEMO_INCLUDE_PATH',
			// URL para descargar recursos web
			'url-resources' => '', 	// 'MICODE_DEMO_URL_RESOURCES',
			// Registrar página de inicio
			'home' => '', 			// 'MICODE_DEMO_HOME',
			// Pie de página adicional (si existe)
			'footer-path' => '', 	// 'MICODE_DEMO_PIE_FILENAME',
			// Path para log de visitas
			'logs-path' => '', 		// 'MICODE_DEMO_LOGS',
			// Nombre del log de visitas
			'visitor-log' => '',
			// Temporal
			'tmp-path' => '', 		// 'MICODE_DEMO_TMP',
			// Repositorio Github
			'github-repo' => ''
		);
	}

	/**
	 * Configura los atributos de presentación.
	 *
	 * @param array $data Datos de configuración.
	 */
	public function config(array $data)
	{
		foreach ($data as $k => $v) {
			if (array_key_exists($k, $this->config)) {
				$this->config[$k] = $v;
				if (strpos($k, '-path') !== false) {
					$this->config[$k] = @realpath($v);
				}
			}
		}
	}

	/**
	 * Define el path a usar para buscar los scripts (directorio "src" de miframe-commons).
	 *
	 * @param string $path Ruta relativa del directorio de scripts.
	 * @return string Ruta completa del directorio de scripts.
	 */
	public function includePath(string $path): string
	{
		if (empty($this->config['src-path'])) {
			// Asigna el path usado por el script actual
			$this->config['src-path'] = __DIR__ . DIRECTORY_SEPARATOR;
		}

		return $this->config['src-path'] . $path;
	}

	/**
	 * Retorna el directorio temporal a usar.
	 *
	 * @param string $default Ruta por defecto si no se ha configurado.
	 * @return string Ruta del directorio temporal.
	 */
	public function tmpDir(string $default = ''): string
	{
		if (!empty($this->config['tmp-path'])) {
			return $this->config['tmp-path'];
		}

		return $default;
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
	public function resourcesPath(string $path): string
	{
		if (empty($this->config['url-resources'])) {
			// Asigna el path usado por el script actual
			$this->config['url-resources'] = dirname($_SERVER['SCRIPT_NAME']) . '/';
		}

		return $this->config['url-resources'] . $path;
	}

	/**
	 * Presenta encabezado para la salida a pantalla.
	 *
	 * @param string $title Título de la página.
	 * @param string $description Descripción de la página.
	 * @param string $styles Estilos adicionales en línea.
	 */
	public function start(string $title, string $description = '', string $styles = '')
	{
		$estilos = $this->resourcesPath('/css/tests.css');
		$title_meta = strip_tags($title);
		if ($description == '') {
			$description = 'miCode-Manager Demos -- ' . $title_meta . '.';
		}
		$description_meta = strip_tags($description);

		// Designa dominio por defecto si no ha definido alguno
		if ($this->config['domain-name'] == '' && !empty($_SERVER['SERVER_NAME'])) {
			$this->config['domain-name'] = $_SERVER['SERVER_NAME'];
		}

		if (empty($_SERVER['REMOTE_ADDR'])) {
			// Salida por consola
			echo strip_tags($title . PHP_EOL . $description) . PHP_EOL . PHP_EOL;
			return;
		}

		// Estilos adicionales en linea
		$estilos_add = '';
		if ($styles !== '') {
			$estilos_add = '<style>' . $styles . '</style>';
		}

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
	<title><?= htmlentities($title) ?></title>
	<link rel="stylesheet" href="<?= $estilos ?>">
	<?= $estilos_add ?>
</head>

<body>
	<h1 class="test-encab">
		<?= htmlentities($title) ?>
		<small><?= $this->config['domain-name'] ?></small>
	</h1>
	<?php

	// Valida si definió enlace a "home"
	if (!empty($this->config['home'])) {
		echo '<a href="' . $this->config['home'] . '" class="test-back-home">
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
<path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
</svg> Regresar</a>';
	}
	// Apertura del contenedor de la página demo a mostrar

	?>
	<div class="test-content">
		<p class="test-description"><?= $description ?></p>
		<?php

	}

	/**
	 * Muestra texto formateado en HTML.
	 *
	 * @param string $text Texto a mostrar.
	 */
	public function htmlPre(string $text)
	{
		echo PHP_EOL . '<pre class="code">' . trim($text) . '</pre>' . PHP_EOL;
	}

	/**
	 * Genera el pie de página.
	 *
	 * @return string Contenido del pie de página.
	 */
	private function footer(): string
	{
		$contents = '';
		if (
			!empty($this->config['footer-path'])
			&& file_exists($this->config['footer-path'])
		) {
			$contents = trim(file_get_contents($this->config['footer-path']));
		}

		if ($contents != '') {
			$contents = PHP_EOL .
				'<!-- Footer -->' . PHP_EOL .
				$contents . PHP_EOL .
				'<!-- Footer ends -->' . PHP_EOL;
		}

		return $contents;
	}

	/**
	 * Da cierre a la página demo.
	 *
	 * @param bool $show_repo Indica si se muestra el enlace al repositorio.
	 */
	public function end(bool $show_repo = true)
	{
		// Repositorio en Github
		if ($show_repo && !empty($this->config['github-repo'])) {
			echo '<div class="test-repo">';
			echo '<h2>¿Tienes curiosidad por el código fuente?</h2>';
			echo '<p><a href="' . $this->config['github-repo'] . '" target="_blank">';
			// https://www.svgrepo.com/svg/475654/github-color
			echo '<svg width="24px" height="24px" viewBox="0 -0.5 48 48" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g id="Icons" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"> <g id="Color-" transform="translate(-700.000000, -560.000000)" fill="#3E75C3"> <path d="M723.9985,560 C710.746,560 700,570.787092 700,584.096644 C700,594.740671 706.876,603.77183 716.4145,606.958412 C717.6145,607.179786 718.0525,606.435849 718.0525,605.797328 C718.0525,605.225068 718.0315,603.710086 718.0195,601.699648 C711.343,603.155898 709.9345,598.469394 709.9345,598.469394 C708.844,595.686405 707.2705,594.94548 707.2705,594.94548 C705.091,593.450075 707.4355,593.480194 707.4355,593.480194 C709.843,593.650366 711.1105,595.963499 711.1105,595.963499 C713.2525,599.645538 716.728,598.58234 718.096,597.964902 C718.3135,596.407754 718.9345,595.346062 719.62,594.743683 C714.2905,594.135281 708.688,592.069123 708.688,582.836167 C708.688,580.205279 709.6225,578.054788 711.1585,576.369634 C710.911,575.759726 710.0875,573.311058 711.3925,569.993458 C711.3925,569.993458 713.4085,569.345902 717.9925,572.46321 C719.908,571.928599 721.96,571.662047 724.0015,571.651505 C726.04,571.662047 728.0935,571.928599 730.0105,572.46321 C734.5915,569.345902 736.603,569.993458 736.603,569.993458 C737.9125,573.311058 737.089,575.759726 736.8415,576.369634 C738.3805,578.054788 739.309,580.205279 739.309,582.836167 C739.309,592.091712 733.6975,594.129257 728.3515,594.725612 C729.2125,595.469549 729.9805,596.939353 729.9805,599.18773 C729.9805,602.408949 729.9505,605.006706 729.9505,605.797328 C729.9505,606.441873 730.3825,607.191834 731.6005,606.9554 C741.13,603.762794 748,594.737659 748,584.096644 C748,570.787092 737.254,560 723.9985,560" id="Github"> </path> </g> </g> </g></svg> ';
			echo 'Repositorio disponible en <b>github.com</b></a></p>';
			echo '</div>';
		}

		// Registra visita
		$this->updateVisitorLog();

		echo '<div class="foot">' .
			'<b>' . $this->config['domain-name'] . '</b> &copy; ' . date('Y') . '.' .
			$this->footer() .
			'</div>' . PHP_EOL .
			'</div>' . // Contenedor "test-content" abierto en $this->start()
			'</body></html>';
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
		$enlace_base = (!empty($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '');
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

	/**
	 * Despliega el contenido de una variable.
	 *
	 * @param mixed $data Datos a mostrar.
	 */
	public function dump(mixed $data)
	{
		$info = '';
		if (is_array($data)) {
			$info .= '<table cellspacing="0">';
			foreach ($data as $k => $v) {
				if (is_bool($v)) {
					$v = ($v ? 'true' : 'false');
				} elseif (!is_numeric($v) && !is_float($v)) {
					// NOTA: var_export() sobre float adiciona muchos decimales (Linux).
					$v = var_export($v, true);
				}
				$info .= '<tr>' .
					'<td valign="top"><b>' . $k . '</b></td>' .
					'<td valign="top" class="dump-connect">=></td>' .
					'<td>' . htmlentities($v) . '</td>' .
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
		if (empty($_SERVER['REMOTE_ADDR'])) {
			// No está ejecutando por web
			return;
		}

		$date = date('Ymd');
		$src = trim(strtolower($this->config['visitor-log']));

		// Valida si existe directorio asociado
		if ($src == '' || empty($this->config['logs-path'])) {
			return;
		}

		// Valida si ya registró esta visita hoy (requiere sesion activa)
		// Si ya fue registrada, termina.
		if (
			isset($_SESSION) &&
			!empty($_SESSION['MICODE_DEMO_VISITS'][$src]) &&
			$_SESSION['MICODE_DEMO_VISITS'][$src] == $date
		) {
			return;
		}

		// Valida directorio destino.
		$path = $this->config['logs-path'];
		// Si se indica directorio pero no existe, reporta error.
		if (!is_dir($path)) {
			throw new \Exception('Directorio para logs no existe (' . $path . ')');
		}

		// Recupara referencia
		$http_referer = '';
		if (!empty($_SERVER['HTTP_REFERER'])) {
			$http_referer = trim($_SERVER['HTTP_REFERER']);
		}

		$filename = realpath($path) . DIRECTORY_SEPARATOR . 'visitas-' . $src . '.csv';
		$client_ip = '?';
		if (!empty($_SERVER['REMOTE_ADDR'])) {
			// REMOTE_ADDR:
			// La dirección IP desde donde el usuario está viendo la página actual.
			$client_ip = $_SERVER['REMOTE_ADDR'];
		}

		// Complementa mensaje.
		// Ej.
		// [2024-Aug-21 14:43:27] ***190.27.101.6
		$message = '"' .
			implode('";"', [
				date('Y-m-d H:i:s'),
				$client_ip,
				str_replace('"', '""', $_SERVER['HTTP_USER_AGENT']),
				str_replace('"', '""', $http_referer)
			]) .
			'"' . PHP_EOL;

		if (!file_exists($filename)) {
			// Adiciona encabezado
			$encab = implode(';', ['Fecha', 'UserIP', 'Browser', 'Referer']) . PHP_EOL;
			error_log($encab, 3, $filename);
		}

		// error_log($message, $message_type = 0, $destination = ?,$extra_headers = ?)
		// $message_type = 3:
		// message es añadido al final del fichero destination.
		// No se añade automáticamente una nueva línea al final del string message.
		// (https://www.php.net/manual/es/function.error-log.php)
		error_log($message, 3, $filename);

		// Marca como ya visitada
		if (isset($_SESSION)) {
			$_SESSION['MICODE_DEMO_VISITS'][$src] = $date;
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
	public function showNextLines(int $lines = 1)
	{
		$this->codePre = '';
		$this->copyNextLines($lines);
		$content = $this->pasteLines();
		if ($content != '') {
			$this->htmlPre($content);
		}
	}

	/**
	 * Pega las líneas copiadas.
	 *
	 * @return string Líneas copiadas.
	 */
	public function pasteLines(): string
	{
		$text = $this->codePre;
		$this->codePre = '';
		return $text;
	}

	/**
	 * Genera un token de seguridad.
	 *
	 * El token es almacenado en la sesión.
	 *
	 * @param string $name Nombre del token.
	 * @param string $key Complemento del token.
	 * @return string Token generado.
	 */
	public function tokenizer(string $name, string $key): string
	{
		$token =  bin2hex(random_bytes(32));
		$_SESSION[$name] = $key . $token;
		return $token;
	}

	/**
	 * Evalúa un token de seguridad.
	 *
	 * Si la validación es fallida, aborta la ejecución del script.
	 *
	 * @param string $param Nombre del parámetro en $_GET a evaluar.
	 * @param string $name Nombre del token.
	 * @param string $key Complemento del token.
	 */
	public function evalToken(string $param, string $name, string $key)
	{
		if (
			empty($_SESSION[$name]) ||
			empty($_GET[$param]) ||
			!hash_equals($_SESSION[$name], $key . $_GET[$param])
		) {
			$this->abort('Acceso no autorizado');
		}
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
