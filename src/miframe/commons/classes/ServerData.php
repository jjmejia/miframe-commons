<?php
/**
 * Clase para manejo de valores registrados en $_SERVER y  funcionalidades asociadas.
 * relacionadas con la información propia del servidor Web, como path a los directorios
 * de trabajo (Ej. DOCUMENT_ROOT), consulta y creación de URLs referidas al servidor,
 * etc.
 *
 * @author John Mejía
 * @since Julio 2024
 */

namespace miFrame\Commons\Classes;

use \miFrame\Commons\Patterns\Singleton;
use \miFrame\Commons\Traits\GetLocalData;

class ServerData extends Singleton {

	/**
	 * Definiciones y métodos a usar para manejo de datos locales.
	 */
	use GetLocalData;

	/**
	 * Registra localmente la dirección IP del cliente Web.
	 * Para consultas de Consola registra "cli"
	 */
	private $client_ip = '';

	/**
	 * Almacena segmento del path tomado de la URL relativo al
	 * directorio que contiene el script invocado (cuando se usan
	 * direcciones URL amigables o "pretty").
	 */
	private $path_info = '';

	/**
	 * Directorio usado para registro de archivos temporales.
	 * Debe tener permisos de escritura/lectura para el usuario asociado
	 * al WebServer en el Sistema Operativo.
	 */
	private $temp_directory = '';

	/**
	 * En TRUE simula comportamiento de consola en consultas Web.
	 */
	public $emulateConsole = false;

	/**
	 * Retorna valor de elemento contenido en la variable superglobal $_SERVER.
	 *
	 * Si el elemento solicitado no existe, retorna valor en $default.
	 *
	 * @param string $name	  Nombre del elemento a recuperar.
	 * @param string $default (Opcional) Valor a usar si $_SERVER[$name] no existe.
	 * @return string 		  Valor del elemento solicitado.
	 */
	public function get(string $name, string $default = '') : string {
		$server_param = strtoupper(trim($name));
		return $this->superglobal('_SERVER', $server_param, $default);
	}

	/**
	 * Evalua si está ejecutando desde un Web Browser.
	 *
	 * @return bool TRUE si está consultando por WEB, FALSE si es por consola (cli).
	 */
	public function isWeb() : bool {

		return ($this->get('REMOTE_ADDR') !== '' && !$this->emulateConsole);
	}

	/**
	 * Retorna la dirección IP del cliente remoto.
	 *
	 * Para consultas de consola retorna "cli".
	 *
	 * Referencia: https://stackoverflow.com/questions/11452938/how-to-use-http-x-forwarded-for-properly
	 *
	 * @return string Dirección IP del cliente remoto.
	 */
	public function client() {

		if (!$this->isWeb()) {
			$this->client_ip = 'cli';
		}
		elseif ($this->client_ip === '') {
			// Recupera dirección IP
			$proxys = $this->get('HTTP_X_FORWARDED_FOR');
			if (!empty($proxys)){
				// Header can contain multiple IP-s of proxies that are passed through.
				// Only the IP added by the last proxy (last IP in the list) can be trusted.
				$proxy_list = explode (",", $proxys);
				$this->client_ip = trim(end($proxy_list));
			}
			else {
				$this->client_ip = $this->get('REMOTE_ADDR');
				if (empty($client_ip)) {
					$client_ip = $this->get('HTTP_CLIENT_IP');
				}
			}
			// En caso de usar nombres asociativos (como "localhost") se asegura esté en
			// minusculas para facilitar comparaciones.
			$this->client_ip = strtolower($this->client_ip);
		}

		return $this->client_ip;
	}

	/**
	 * Valida si la consulta se realiza directamente en el servidor Web (localhost).
	 *
	 * @return bool	TRUE para consultas desde el servidor Web, FALSE en otro caso.
	 */
	public function isLocalhost() : bool {

		$client_ip = $this->client();
		return (!$this->isWeb() ||
			// IPv4, IPv6, Associative name, Consola
			in_array($client_ip, [ '127.0.0.1', '::1', 'localhost', 'cli' ]) ||
			// Local server IP (Ej. 192.xxx)
			$client_ip === $this->ip()
			);
	}

	/**
	 * Valida si la consulta actual se hizo con protocolo "https".
	 *
	 * Por definición:
	 *
	 * > HTTPS es el protocolo seguro de HTTP, que usa encriptación para proteger
	 * > las comunicaciones entre un navegador y un sitio web.
	 * > (https://www.cloudflare.com/learning/ssl/what-is-https/)
	 *
	 * @return bool TRUE si la consulta fue hecha con "https".
	 */
	public function useHTTPSecure() : bool {
		$https = strtolower($this->get('HTTPS', ''));
		return ($https === 'on' || intval($https) > 0);
	}

	/**
	 * Retorna una URL completa asociada al servidor Web.
	 *
	 * Una URL se compone de los siguientes elementos (los valores entre "[]" son opcionales):
	 *
	 * (Scheme)://(domain name)[:puerto]/(path)[?(queries)]
	 *
	 * - Scheme: Tradicionalmente es alguno entre "http" o "https".
	 * - Domain name: Nombre usado para consultar el servidor Web (Ej. "localhost").
	 * - Puerto: (Opcional) Solamente se indica cuando usa un valor diferente a los
	 *   puertos estándar 80 o 443 (para "http" y "https" respectivamente)).
	 * - Path: Path al recurso solicitado por la consulta Web.
	 * - Queries: Parámetros de consulta (Ej: var1=xxx&var2=xxx).
	 *
	 * Los valores a retornar para Scheme y Domain name son los usados por el usuario
	 * remoto en su consulta al servidor actual.
	 *
	 * Referencias:
	 * - https://developer.mozilla.org/es/docs/Learn/Common_questions/Web_mechanics/What_is_a_URL
	 * - https://stackoverflow.com/questions/7431313/php-getting-full-server-name-including-port-number-and-protocol
	 * - https://stackoverflow.com/questions/5800927/how-to-identify-server-ip-address-in-php
	 *
	 * @param string $path 		(Opcional) Valor de path a usar. SI no se indica,
	 * 							retorna solamente el schema, domain name y puerto.
	 * @param bool $force_https	TRUE forza el valor del scheme a "https".
	 * @return string 			URL.
	 */
	public function host(string $path = '', bool $force_https = false) : string	{

		$scheme = 'http://';
		if ($force_https || $this->useHTTPSecure()) {
			$scheme = 'https://';
		}

		$domain_name = $this->get('SERVER_NAME', 'nn');
		$port = ':' . $this->get('SERVER_PORT', 80);
		// Adiciona puerto si no es 80 (http) ni 443 (https)
		if (in_array($port, [ ':80', ':443' ])) {
			$port = '';
		}

		$path = trim($path);
		if ($path != '' && substr($path, 0, 1) !== '/') {
			// Adiciona conector
			$path = '/' . $path;
		}

		return $scheme . $domain_name . $port . $path;
	}

	/**
	 * Retorna URL path (URI) al script ejecutado en la consulta actual.
	 *
	 * El valor del path es tomado de $_SERVER['SCRIPT_NAME'].
	 * Si indica un valor de path ($script_path) lo complementa con el path
	 * al script actual (no incluye el nombre del script). Es decir:
	 *
	 *     (dirname(SCRIPT_NAME))/($script_path).
	 *
	 * @param string $script_path	(Opcional) Path asociado.
	 * @return string 				URI.
	 */
	public function self(string $script_path = '') : string {

		$path = trim($this->get('SCRIPT_NAME'));
		$script_path = $this->cleanPath($script_path, '/');
		if ($script_path !== '') {
			// Adiciona este path al directorio definido por SCRIPT_NAME
			$path = dirname($path) . '/' . $script_path;
		}

		return $path;
	}

	/**
	 * Retorna el nombre del servidor Web.
	 *
	 * @return string Nombre del servidor o FALSE si no está disponible.
	 */
	public function name() : string|false {
		return gethostname();
	}

	/**
	 * Retorna la dirección IPv4 asociada al servidor Web.
	 *
	 * @return string Retorna la dirección IP del servidor o el nombre del servidor
	 * 				  en caso de ocurrir algún error.
	 */
	public function ip() : string {
		return gethostbyname($this->name());
	}

	/**
	 * Retorna el path consultado por el usuario (referido como URI).
	 *
	 * @return string Path.
	 */
	public function path() : string {
		// REQUEST_URI puede contener valores GET (Ej. path?var1=xxx).
		// Usa parse_url() para garantizar recupere solamente el path.
		return parse_url($this->get('REQUEST_URI'), PHP_URL_PATH);
	}

	/**
	 * Retorna el segmento del path con información útil cuando se usan URL amigables.
	 *
	 * Las "URL amigables" son URLs diseñadas para proveer en si mismas información
	 * respecto a lo que hacen. Por ejemplo, si se tiene un script para editar usuarios,
	 * podría consultarse directamente con una URL apuntando al script, que podría
	 * estar en algo como:
	 *
	 *     /public/control/admin/userEdit.php?userid=xx
	 *
	 * Esa no es una URL muy "amigable". La alternativa sería una URL del tipo:
	 *
	 *     /public/admin/users/edit/xx
	 *
	 * En este caso, asumiendo que se tiene un archivo en "/public/index.php" que
	 * centraliza las peticiones, tendremos que el PATH_INFo sería entonces:
	 *
	 *     /admin/users/edit/xx
	 *
	 * Mismo que será evaluado por el script "index.php" para identificar y ejecutar
	 * el respectivo script de edición en "/public/control/admin/userEdit.php".
	 * Algunos servidores Web fijan este valor en el elemento $_SERVER['PATH_INFO']
	 * pero no es un valor estándar para todos los servidores.
	 *
	 * Referencias:
	 * - https://stackoverflow.com/questions/9879225/serverpath-info-on-localhost
	 * - https://www.php.net/manual/en/reserved.variables.server.php
	 *
	 * @return string Segmento del path o texto vacio si no encuentra alguna.
	 */
	public function pathInfo() : string {

		$script_name = $this->get('SCRIPT_NAME');
		$request_uri = $this->path();

		// Escenarios:
		// 1. Ya fue asignada o no existe porque $script_name == $request_uri
		if ($this->path_info === '' && $script_name !== $request_uri) {
			// 2. Existe valor en $_SERVER
			$this->path_info = $this->get('PATH_INFO');
			if ($this->path_info === '') {
				// 3. Debe recuperarlo manualmente.
				// Puede venir en el URI:
				// $request_uri = $script_name . $path_info, o
				// $request_uri = dirname($script_name) . $path_info
				$this->path_info = strstr($request_uri, $script_name, true);
				if ($this->path_info == '') {
					$this->path_info = strstr($request_uri, dirname($script_name), true);
				}
			}
		}

		return $this->path_info;
	}

	/**
	 * Estandariza path, sea de un archivo o un URL.
	 *
	 * Remueve ".." y ".", asi previene acceso a rutas restringidas.
	 * Aplica tanto para separadores de directorio Windows ("\") como
	 * Linux/Unix ("/"), por lo que puede usarse para limpiar tanto paths
	 * de archivos/directorios en disco del servidor, como a URLs de consulta.
	 *
	 * Referencia:
	 * - https://www.php.net/manual/en/dir.constants.php#114579
	 *   (Builds a file path with the appropriate directory separator).
	 *
	 * @param string $path				Path a depurar.
	 * @param string $force_separator	Separador a usar. SI no se define este valor,
	 * 									por defecto se usara "/" (separador URL).
	 * 									Si encuentra algún separador "\" en $path,
	 * 									usa este para construir el path depurado.
	 * @return string					Path depurado.
	 */
	public function cleanPath(string $path, string $force_separator = '') : string {

		$path = trim($path);

		// Valida que haya algo que realizar
		if ($path === '') {
			return $path;
		}

		// NOTA: No usa siempre realpath() porque puede reportar erroneamente
		// un path con una ruta en disco físico para un path de una URL
		// (indicada por $path).

		// Determina path a usar al final
		$separator = DIRECTORY_SEPARATOR;
		// Hay al menos un separador de directorio
		// (En Windows son diferentes, en Linux son el mismo)
		$exists_separator = (strpos($path, $separator));
		$path = str_replace($separator, '/', $path);
		if (!$exists_separator) {
			// Estandariza al modelo Linux (URL)
			$separator = '/';
		}
		// Valida si debe forzar el separador a usar
		if ($force_separator !== '') {
			$separator = $force_separator;
		}

		// Remueve ".." y "." sobre paths de archivos que no existen
		$segments = explode('/', $path);

		// Procesa arreglo si encuentra un caracter ".", aunque sea un separador
		// de extensión, por si ha definido ".." o "." en alguno de los segmentos.
		if (strpos($path, '.') !== false) {
			$total_segments = count($segments);
			for ($i = 0; $i < $total_segments; $i++) {
				$segments[$i] = trim($segments[$i]);
				if ($segments[$i] == '..') {
					// Limpia segmentdo
					$segments[$i] = '';
					// Retrocede. Ignora segmentos vacios, del tipo: "xxx//xxx"
					while ($i > 0 && $segments[$i - 1] == '') { $i--; }
					// Retrocede un segmento
					if ($i > 0) {
						$segments[$i - 1] = '';
					}
				}
				elseif ($segments[$i] == '.') {
					$segments[$i] = '';
				}
			}
		}

		// Remueve elementos en blanco
		$path = implode($separator, array_filter($segments));

		return $path;
	}

	/**
	 * Estandariza path físico, sea de un archivo o un directorio.
	 *
	 * Remueve ".." y ".", asi previene acceso a rutas restringidas.
	 * Aplica tanto para separadores de directorio Windows ("\") como
	 * Linux/Unix ("/").
	 *
	 * @param string $path				Path a depurar.
	 * @return string					Path depurado.
	 */
	public function cleanFilePath(string $path) : string {
		if (file_exists($path)) {
			return realpath($path);
		}
		// Realiza limpieza manual
		return $this->cleanPath($path, DIRECTORY_SEPARATOR);
	}

	/**
	 * Retorna la ruta física del script usado.
	 *
	 * Si indica un valor de directorio o archivo lo complementa con el
	 * path al script actual (no incluye el nombre del script ejecutado). Es decir:
	 *
	 *     (dirname(SCRIPT_FILENAME))/($path_to_scriptdir).
	 *
	 * @param string $path_to_scriptdir	(Opcional) Directorio/nombre de archivo asociado.
	 * @return string 					Path.
	 */
	public function script(string $path_to_scriptdir = '') : string {

		$path = realpath($this->get('SCRIPT_FILENAME'));
		$path_to_scriptdir = $this->cleanFilePath($path_to_scriptdir);
		if ($path_to_scriptdir !== '') {
			$path = dirname($path) . DIRECTORY_SEPARATOR . $path_to_scriptdir;
		}

		return $path;
	}

	/**
	 * Retorna la ruta física del directorio Web asignado al Servidor (DOCUMENT_ROOT).
	 *
	 * Si indica un valor de directorio o archivo lo complementa con el path
	 * al script actual (no incluye el nombre del script ejecutado). Es decir:
	 *
	 *     (DOCUMENT_ROOT)/($path_to_root).
	 *
	 * @param string $path_to_root	(Opcional) Directorio/nombre de archivo asociado.
	 * @return string 				Path.
	 */
	public function documentRoot(string $path_to_root = '') {
		return realpath($this->get('DOCUMENT_ROOT')) . DIRECTORY_SEPARATOR . $this->cleanFilePath($path_to_root);
	}

	/**
	 * Valida que el path indicado esté contenido en DOCUMENT_ROOT.
	 *
	 * @param string $path	Path a evaluar.
	 * @return string		TRUE si $path es subdirectorio de DOCUMENT_ROOT,
	 * 						FALSE en otro caso.
	 */
	public function inDocumentRoot(string $path) : bool {

		$path = $this->cleanFilePath($path);
		$document_root = $this->documentRoot();

		return ($path !== '' &&
			strtolower(substr($path, 0, strlen($document_root))) === strtolower($document_root));
	}

	/**
	 * Valida que el path indicado esté contenido en el directorio temporal.
	 *
	 * @param string $path	Path a evaluar.
	 * @return string		TRUE si $path es subdirectorio del directorio temporal,
	 * 						FALSE en otro caso.
	 */
	public function inTempDir(string $path) : bool {

		$tempdir = $this->tempDir();
		$path = $this->cleanFilePath($path);

		return ($tempdir !== '' &&
			strtolower(substr($path, 0, strlen($tempdir))) === strtolower($tempdir));
	}

	/**
	 * Crea un directorio en el servidor.
	 * Requiere se inqique el path completo y que esté contenido ya sea dentro
	 * de DOCUMENT_ROOT o del directorio temporal.
	 * El modo predeterminado para creación del directorio es 0777, lo que significa
	 * el acceso más amplio posible.
	 *
	 * Nota: el modo es ignorado en Windows.
	 *
	 * @param string $pathname	La ruta del directorio.
	 * @param bool $recursive	TRUE Permite la creación de directorios anidados
	 * 							especificado en $pathname.
	 */
	public function mkdir(string $pathname, bool $recursive = true) : bool {

		$result = false;
		$pathname = $this->cleanFilePath($pathname);
		if ($pathname !== '') {
			// TRUE si el directorio ya existe
			$result = is_dir($pathname);
			// El directorio a crear debe estar contenido en $_SERVER['DOCUMENT_ROOT']
			// o en el directorio de temporales.
			if (!$result &&
				($this->inDocumentRoot($pathname) || $this->inTempDir($pathname))
				) {
				$result = @mkdir($pathname, 0777, $recursive);
			}
		}

		return $result;
	}

	/**
	 * Retorna directorio temporal a usar o asigna valor a ser usado.
	 *
	 * El directorio temporal se recupera de:
	 * - Valor registrado previamente, o
	 * - Directorio temporal del sistema.
	 *
	 * Si no puede encontrar un directorio temporal valido, retorna vacio.
	 *
	 * @param string $pathname	Opcional. Directorio temporal a usar.
	 * @return string 			Path.
	 */
	public function tempDir(string $pathname = '') : string {

		if ($pathname !== '') {
			$pathname = realpath($this->cleanFilePath($pathname));
			if ($pathname != '') {
				$this->temp_directory = $pathname;
			}
		}
		if ($this->temp_directory === '') {
			$this->temp_directory = realpath(sys_get_temp_dir());
		}

		return $this->temp_directory;
	}

	/**
	 * Valida el subdirectorio indicado en el directorio temporal y retorna el path completo.
	 *
	 * @param string $pathname	Subdirectorio temporal a validar.
	 * @return string 			Path o FALSE si el subdirectorio deseado no existe y no pudo
	 * 							ser debidamente creado.
	 */
	public function createTempSubdir(string $pathname) : string|false {

		$temp = $this->tempDir();
		$path = $temp . DIRECTORY_SEPARATOR . $this->cleanFilePath($pathname);
		if ($temp !== '' && !$this->mkdir($path)) {
			return false;
		}

		return realpath($path);
	}

	/**
	 * Retorna información recibida en el "body" de la consulta realizada por el usuario.
	 *
	 * Del manual:
	 * https://www.php.net/manual/en/wrappers.php.php
	 * > php://input is a read-only stream that allows you to read raw data from
	 * > the request body. php://input is not available in POST requests with
	 * > enctype="multipart/form-data" if enable_post_data_reading option is enabled.
	 *
	 * @return string Datos recibidos (si alguno) o FALSE si ocurre algún error.
	 */
	public function rawInput() : string|false {
		return @file_get_contents("php://input");
	}

	/**
	 * Información relativa al servidor Web.
	 * Por Ej. "Apache/2.4.48 (Win64) OpenSSL/1.1.1k PHP/8.3.6".
	 *
	 * @return string Información del servidor Web.
	 */
	public function software() {
		return $this->get('SERVER_SOFTWARE');
	}

	/**
	 * Información relativa al navegador Web (browser) usado por el usuario.
	 * Por Ej. "Mozilla/5.0 (Windows NT 10.0; Win64; x64)..."
	 *
	 * @return string Información del browser.
	 */
	public function browser() {
		// PENDIENTE:Ampliar funcionalidad usando get_browser().
		return $this->get('HTTP_USER_AGENT');
	}

	/**
	 * Retorna el espacio libre en el disco donde se encuentra el DOCUMENT_ROOT.
	 *
	 * @return float Devuelve el número de bytes disponibles como un float.
	 */
	public function documentRootSpace() : float {
		// disk_free_space() puede retornar false, por lo que se convierte a
		// float para prevenir conflictos de type.
		return floatval(disk_free_space($this->documentRoot()));
	}

	/**
	 * Retorna el espacio libre en el disco donde se encuentra el directorio temporal.
	 *
	 * @return float Devuelve el número de bytes disponibles como un float.
	 */
	public function tempDirSpace() : float {
		// disk_free_space() puede retornar false, por lo que se convierte a
		// float para prevenir conflictos de type.
		return floatval(disk_free_space($this->tempDir()));
	}
}
