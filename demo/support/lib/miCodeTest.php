<?php
/**
 * Clase de soporte para montaje de scripts para test de módulos.
 *
 * @author John Mejia
 * @since Mayo 2022
 */

class miCodeTest {

	public function __construct(bool $relocate = true) {
		// Inicializa manejo de sesión PHP
		if (empty($_SESSION)) {
			session_start();
			// Valida si regresa al index
			if (count($_SESSION) <= 0 && $relocate) {
				// Se borraron las variables de sesión
				// Vuelve a a pagina de inicio inmediatamente anterior
				echo '<script>window.location = "' . dirname(dirname($_SERVER['SCRIPT_NAME'])) . '"</script>';
				exit;
			}
		}
	}

	/**
	 * Fija atributos de presentación
	 */
	public function config(array $data) {

		$config = array(
			// Path con el código fuente
			'src-path' => 'MICODE_DEMO_INCLUDE_PATH',
			// URL para descargar recursos web
			'url-resources' => 'MICODE_DEMO_URL_RESOURCES',
			// Registrar página de inicio
			'home' => 'MICODE_DEMO_HOME',
			// Pie de página adicional (si existe)
			'footer' => 'MICODE_DEMO_PIE_FILENAME',
			// Visitors log
			'logs-path' => 'MICODE_DEMO_LOGS',
			// Temporal
			'tmp-path' => 'MICODE_DEMO_TMP',
			// '' => '',
			// '' => '',
		);

		foreach ($data as $k => $v) {
			if (isset($config[$k])) {
				$_SESSION[$config[$k]] = $v;
			}
		}
	}

	/**
	 * Define el path a usar para buscar los scripts (directorio "src").
	 */
	public function includePath(string $path) {

		if (empty($_SESSION['MICODE_DEMO_INCLUDE_PATH'])) {
			// Asigna el path usado por el script actual
			$_SESSION['MICODE_DEMO_INCLUDE_PATH'] = __DIR__;
		}

		return $_SESSION['MICODE_DEMO_INCLUDE_PATH'] . $path;
	}

	/**
	 * Retorna directorio temporal a usar.
	 */
	public function tmpDir(string $default = '') {

		if (!empty($_SESSION['MICODE_DEMO_TMP'])) {
			return $_SESSION['MICODE_DEMO_TMP'];
		}

		return $default;
	}

	/**
	 * Define el path a usar para buscar recursos, relativo a la URL actual.
	 * Puede definirse previamente para acceder a un directorio diferente
	 * al auto-detectado.
	 */
	public function resourcesPath(string $path) {

		if (empty($_SESSION['MICODE_DEMO_URL_RESOURCES'])) {
			// Asigna el path usado por el script actual
			$_SESSION['MICODE_DEMO_URL_RESOURCES'] = dirname($_SERVER['SCRIPT_NAME']);
		}

		return $_SESSION['MICODE_DEMO_URL_RESOURCES'] . $path;
	}

	/**
	 * URL home prefijado.
	 */
	public function home(string $default_home = '') {

		if (!empty($_SESSION['MICODE_DEMO_HOME'])
			&& strtolower(basename($_SESSION['MICODE_DEMO_HOME'])) !== strtolower(basename($_SERVER['SCRIPT_NAME']))) {
			// Asigna el path usado por el script actual
			$default_home = $_SESSION['MICODE_DEMO_HOME'];
		}

		return $default_home;
	}

	/**
	 * Presenta encabezado para la salida a pantalla.
	 */
	public function start(string $title, string $description = '', string $default_home = '') {

		$estilos = $this->resourcesPath('/resources/css/tests.css');
		$title_meta = strip_tags($title);
		if ($description == '') {
			$description = 'miCode-Manager Demos -- ' . $title_meta . '.';
		}
		$description_meta = strip_tags($description);

		$domain_name = 'miCode-Manager';
		if (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] !== 'localhost') {
			$domain_name = $_SERVER['SERVER_NAME'];
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
</head>
<body>
	<h1 class="test-encab">
		<?= htmlentities($title) ?>
		<small><?= $domain_name ?></small>
	</h1>
	<?php
	$home = $this->home($default_home);
	if ($home !== '') {
		echo '<a href="' . $home . '" class="test-back-home">
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
<path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
</svg> Regresar</a>';
	}

?>
		<div class="test-content">
		<p class="test-description"><?= $description ?></p>
	<?php

	}

	public function htmlPre(string $text) {

		echo PHP_EOL . '<pre class="code">' . trim($text) . '</pre>' . PHP_EOL;
	}

	private function footer() {

		$contents = '';
		if (!empty($_SESSION['MICODE_DEMO_PIE_FILENAME'])
			&& file_exists($_SESSION['MICODE_DEMO_PIE_FILENAME'])
			) {
			$contents = trim(file_get_contents($_SESSION['MICODE_DEMO_PIE_FILENAME']));
		}

		return $contents;
	}

	/**
	 * Da cierre a la página demo.
	 */
	public function end() {

		// Adiciona pie de página
		$adicional = $this->footer();

		echo '<div class="foot"><b>miCode-Manager</b> &copy; ' . date('Y') . '. ' . $adicional . '</div>';
		echo "</div></body></html>";
	}

	/*
	public function link(string $name, array $data) {

		// $enlace_base = basename(miframe_server_get('SCRIPT_FILENAME'));
		$enlace_base = '';
		if (count($data) > 0) {
			$enlace_base .= '?' . http_build_query($data);
		}
		$enlace_base = '<a href="' . $enlace_base . '">' . $name . '</a>';

		return $enlace_base;
	}

	public function option(string $option, string $text_ok, string $text_nok, string &$link) {

		$retornar = false;

		$data = $_REQUEST;
		$info = $text_ok;
		if (array_key_exists($option, $_REQUEST)) {
			$retornar = true;
			unset($data[$option]);
			$info = $text_nok;
		}
		else {
			$data[$option] = '';
		}

		if ($link != '') { $link .= ' | '; }
		$link .= miframe_test_link($info, $data);

		return $retornar;
	}
	*/

	/**
	 * Despliega contenido de variable.
	 */
	public function dump(mixed $data) {

		$info = '';
		if (is_array($data)) {
			$info .= '<table cellspacing="0">';
			foreach ($data as $k => $v) {
				if (is_bool($v)) {
					$v = ($v ? 'true' : 'false');
				}
				elseif (!is_numeric($v) && !is_float($v)) {
					// NOTA: var_export() sobre float adiciona muchos decimales (Linux).
					$v = var_export($v, true);
				}
				$info .= '<tr>' .
					'<td><b>' . $k . '</b></td>' .
					'<td class="dump-connect">=></td>' .
					'<td>' . htmlentities($v) . '</td>' .
					'</tr>';
			}
			$info .= '</table>';
		}
		else {
			$info = htmlentities(var_export($data, true));
		}

		return $this->htmlPre($info);
	}

	/**
	 * Registra visitas.
	 */
	public function visitorLog(string $src) {

		$date = date('Ymd');
		$src = trim(strtolower($src));

		// Valida si existe directorio asociado
		if ($src == '' || empty($_SESSION['MICODE_DEMO_LOGS'])) { return; }

		// Valida si ya registró esta visita hoy
		if (isset($_SESSION['MICODE_DEMO_VISITS'][$src])
			&& $_SESSION['MICODE_DEMO_VISITS'][$src] == $date
			) {
			return;
		}

		$http_referer = '';
		if (!empty($_SERVER['HTTP_REFERER'])) {
			$http_referer = trim($_SERVER['HTTP_REFERER']);
		}

		$path = $_SESSION['MICODE_DEMO_LOGS'];
		// Remueve ultimo caracter "/"
		if (substr($path, -1, 1) == '/') {
			$path = substr($path, 0, -1);
		}
		if (!is_dir($path)) {
			throw new \Exception('Directorio para logs no existe (' . $path . ')');
		}

		$filename = $path . '/visitas-' . $src . '.csv';
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
		$_SESSION['MICODE_DEMO_VISITS'][$src] = $date;
	}
}