<?php
/**
 * Clase para manejo de valores registrados en $_SERVER y  funcionalidades asociadas.
 * relacionadas con la información propia del servidor Web, como path a los directorios
 * de trabajo (Ej. DOCUMENT_ROOT), consulta y creación de URLs referidas al servidor,
 * etc.
 *
 * Documentación respecto a la variable superglobal $_SERVER disponible en:
 * https://www.php.net/manual/en/reserved.variables.server.php
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
	 * Define el método "superglobal".
	 */
	use GetLocalData;

	/**
	 * @var string $client_ip	Registra localmente la dirección IP del cliente Web.
	 * 							Para consultas de Consola registra "cli".
	 */
	private $client_ip = '';

	/**
	 * @var string $path_info	Almacena segmento del path tomado de la URL relativo al
	 * 							directorio que contiene el script invocado (cuando se usan
	 * 							direcciones URL amigables o "pretty").
	 */
	private $path_info = '';

	/**
	 * @var string $temp_directory	Directorio usado para registro de archivos temporales.
	 * 								Debe tener permisos de escritura/lectura para el usuario
	 * 								asociado al WebServer en el Sistema Operativo.
	 */
	private $temp_directory = '';

	/**
	 * Valor de elemento contenido en la variable superglobal $_SERVER de PHP.
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
	 * Indica si está ejecutando desde un Web Browser.
	 *
	 * @return bool TRUE si está consultando por WEB, FALSE si es por consola (cli).
	 */
	public function isWeb() : bool {

		// REMOTE_ADDR:
		// La dirección IP desde donde el usuario está viendo la página actual.
		// Si se consulta desde Consola, no es asignada por el servidor.
		return empty($this->get('REMOTE_ADDR', false));
	}

	/**
	 * Dirección IP del cliente remoto.
	 *
	 * Para consultas de consola retorna "cli".
	 *
	 * @return string Dirección IP del cliente remoto.
	 */
	public function client() {

		if (!$this->isWeb()) {
			$this->client_ip = 'cli';
		}
		elseif ($this->client_ip === '') {
			// Recupera dirección IP.

			// HTTP_X_FORWARDED_FOR:
			// Usado en vez de REMOTE_ADDR cuando se consulta detrás de un proxy server.
			// Puede contener múltiples IPs de proxies por los que se ha pasado.
			// Solamente la IP del último proxy (última IP de la lista) es de fiar.
			// ( https://stackoverflow.com/questions/11452938/how-to-use-http-x-forwarded-for-properly )
			// Si no se emplean proxys para la consulta, retorna vacio.
			$proxys = $this->get('HTTP_X_FORWARDED_FOR');
			if (!empty($proxys)){
				$proxy_list = explode (",", $proxys);
				$this->client_ip = trim(end($proxy_list));
			}
			else {
				// REMOTE_ADDR:
				// La dirección IP desde donde el usuario está viendo la página actual.
				$this->client_ip = $this->get('REMOTE_ADDR');
				if (empty($client_ip)) {
					// HTTP_CLIENT_IP:
					// Opcional para algunos servidores Web en remplazo de REMOTE_ADDR.
					$client_ip = $this->get('HTTP_CLIENT_IP');
				}
			}
			// En caso que retorne un nombre (como "localhost") se asegura esté en
			// minusculas para facilitar comparaciones.
			$this->client_ip = strtolower($this->client_ip);
		}

		return $this->client_ip;
	}

	/**
	 * Indica si la consulta se realiza directamente en el servidor Web (localhost).
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
	 * Indica si la consulta actual se hizo con protocolo HTTPS.
	 *
	 * Por definición:
	 *
	 * > HTTPS es el protocolo seguro de HTTP, que usa encriptación para proteger
	 * > las comunicaciones entre un navegador y un sitio web.
	 * > (https://www.cloudflare.com/learning/ssl/what-is-https/)
	 *
	 * @return bool TRUE si la consulta fue hecha usando HTTPS.
	 */
	public function useHTTPSecure() : bool {

		// HTTPS:
		// Asignado a un valor no vacio si el script fue consultado usando protocolo HTTPS.
		return !empty($this->get('HTTPS'));
	}

	/**
	 * URL asociada al servidor Web para la consulta actual.
	 *
	 * Una URL se compone de los siguientes elementos (los valores entre "[]" son opcionales):
	 *
	 * (Scheme)://(domain name)[:puerto]/(path)[?(queries)]
	 *
	 * Se retorna el URL con los valores asignados para:
	 *
	 * - Scheme: Tradicionalmente es alguno entre "http" o "https".
	 * - Domain name: Nombre usado para consultar el servidor Web (Ej. "localhost").
	 * - Puerto: (Opcional) Solamente se indica cuando usa un valor diferente a los
	 *   puertos estándar 80 o 443 (para "http" y "https" respectivamente)).
	 *
	 * Referencias:
	 * - https://developer.mozilla.org/es/docs/Learn/Common_questions/Web_mechanics/What_is_a_URL
	 * - https://stackoverflow.com/questions/7431313/php-getting-full-server-name-including-port-number-and-protocol
	 * - https://stackoverflow.com/questions/5800927/how-to-identify-server-ip-address-in-php
	 *
	 * @param bool $force_https	TRUE ignora el valor actual del scheme y retorna siempre "https" (a usar por Ej. para
	 * 							redirigir una consulta no segura con "https" a una segura que use "https").
	 * @return string 			URL.
	 */
	public function host(bool $force_https = false) : string	{

		$scheme = 'http://';
		if ($force_https || $this->useHTTPSecure()) {
			$scheme = 'https://';
		}

		// SERVER_NAME:
		// Nombre del servidor host que está ejecutando este script. Si se ejecuta en un host virtual, este será el valor asignado a ese host virtual.
		$domain_name = $this->get('SERVER_NAME', 'nn');

		// SERVER_PORT:
		// Puerto en la maquina del servidor usado por el servidor Web para comunicación. Por defecto será de '80'; Para SSL (HTTPS) cambia a cualquiera sea
		// el puerto usado para dicha comunicación (por defecto 443).
		$port = ':' . $this->get('SERVER_PORT', 80);

		// Ignora puertos estándar 80 (HTTP) y 443 (HTTPS)
		if (in_array($port, [ ':80', ':443' ])) {
			$port = '';
		}

		return $scheme . $domain_name . $port . '/';
	}

	/**
	 * Retorna el path Web al script ejecutado en la consulta actual.
	 *
	 * El valor del path es tomado de $_SERVER['SCRIPT_NAME'].
	 *
	 * @return string URI.
	 */
	public function self() : string {

		// SCRIPT_NAME:
		// Contiene la ruta al script actual, vista desde el servidor Web.
		// Puede diferir de la ingresada por el usuario cuando se usan "URL amigables".
		return $this->get('SCRIPT_NAME');
	}

	/**
	 * Complementa el path indicado adicionando el subdirectorio del script actual.
	 *
	 * El valor del path local es tomado de $_SERVER['SCRIPT_NAME'].
	 * Es decir, se complementa así:
	 *
	 *     (dirname(SCRIPT_NAME))/($script_path).
	 *
	 * @param string $path	(Opcional) Path a complementar.
	 * @return string 		URI.
	 */
	public function local(string $path = '') : string {

		return $this->dirname($this->self(), $path, '/');
	}

	/**
	 * Retorna el nombre real del servidor Web.
	 *
	 * @return string Nombre del servidor o FALSE si no está disponible.
	 */
	public function name() : string|false {
		return gethostname();
	}

	/**
	 * Dirección IPv4 asociada al servidor Web.
	 *
	 * @return string Retorna la dirección IP del servidor o el nombre del servidor
	 * 				  en caso de ocurrir algún error.
	 */
	public function ip() : string {
		return gethostbyname($this->name());
	}

	/**
	 * Retorna el path Web consultado por el usuario.
	 *
	 * Una URL se compone de los siguientes elementos (los valores entre "[]" son opcionales):
	 *
	 * (Scheme)://(domain name)[:puerto]/(path)[?(queries)]
	 *
	 * Este método retorna el valor del path al recurso solicitado por la consulta Web.
	 *
	 * Este valor puede o no coincidir con el valor en $this->self().
	 * $this->self() retorna el path real del script ejecutado, en tanto que
	 * este método retorna el componente URI tal como fue consultado por el usuario.
	 * Son diferentes cuando se emplean "URLs amigables" para acceder a los
	 * servicios Web disponibles.
	 *
	 * @return string Path.
	 */
	public function path() : string {

		// REQUEST_URI:
		// El URI dado por el usuario para acceder a esta página.
		// Puede contener valores GET (queryes) separados por "?".
		// Ejemplo: var1=xxx&var2=zzz...
		// Se usa parse_url() para garantizar que recupere solamente el path.
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

		$script_name = $this->self();
		$request_uri = $this->path();

		// Escenarios:
		// 1. Ya fue asignada o no existe porque $script_name == $request_uri
		if ($this->path_info === '' && $script_name !== $request_uri) {
			// 2. Existe valor en $_SERVER

			// PATH_INFO:
			// Contiene cualquier información (pathname) diferente a la del script actual,
			// que precede a la información de queryes.
			// Nota: No todos los servidores Web reportan este valor.
			$this->path_info = $this->get('PATH_INFO', false);

			if ($this->path_info === false) {
				// 3. No existe y debe recuperarlo manualmente.
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
	 * Estandariza path indicado y remueve elementos no deseados.
	 *
	 * Remueve ".." y ".", asi previene acceso a rutas restringidas.
	 * Aplica tanto para separadores de directorio Windows ("\") como Linux/Unix o
	 * de rutas web ("/"), por lo que puede usarse para limpiar tanto paths de
	 * archivos/directorios en disco del servidor, como a URLs de consulta.
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
	public function purgePath(string $path, string $force_separator = '') : string {

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
	 * Estandariza formato del path físico indicado.
	 *
	 * Remueve elementos no deseados (tales como "..", "." y segmentos vacios),
	 * sean de un archivo o un directorio. De esta forma previene acceso a rutas
	 * restringidas.
	 *
	 * Aplica tanto para separadores de directorio Windows ("\") como
	 * Linux/Unix ("/").
	 *
	 * @param string $filename	Path a depurar, sea de archivo o directorio.
	 * @return string			Path depurado.
	 */
	public function purgeFilename(string $filename) : string {

		// Realiza limpieza manual
		return $this->purgePath($filename, DIRECTORY_SEPARATOR);
	}

	/**
	 * Retorna la ruta física del script ejecutado.
	 *
	 * @return string 		Path.
	 */
	public function script() : string {

		// SCRIPT_FILENAME:
		// Ruta absoluta en disco al script en ejecución.
		// Nota: Si un script es ejecutado por Consola se retorna la ruta indicada por
		// el usuario. Esto es, si indica solo el nombre (file.php) o un enrutamiento
		// relativo (../file.php), ese mismo valor será retornado.
		// La función realpath() garantiza que se retorne la ruta completa
		// (para Consolas, puede verse afectado si modifica el directorio por defecto
		// usando la función chdir()).
		return realpath($this->get('SCRIPT_FILENAME'));
	}

	/**
	 * Retorna la ruta física real o esperada para el archivo/directorio indicado.
	 *
	 * Complementa el path recibido con el directorio que contiene al script actual,
	 * es decir:
	 *
	 *     (dirname(SCRIPT_FILENAME))/($path_to_scriptdir).
	 *
	 * @param string $filename	(Opcional) Archivo/directorio a complementar.
	 * @return string 			Path.
	 */
	public function localScript(string $filename = '') : string {

		return $this->dirname($this->script(), $filename, DIRECTORY_SEPARATOR);
	}

	/**
	 * Complementa paths usando el separador indicado.
	 *
	 * Adiciona al path hijo ($son) el directorio al que pertenece el padre ($parent).
	 * Si el hijo ya contiene el valor de padre, no hace cambios.
	 *
	 * @param string $parent	Path padre.
	 * @param string $son		Path hijo.
	 * @param string $separator	Separador de segmentos del path.
	 * @return string			Path hijo completo.
	 */
	private function dirname(string $parent, string $son, string $separator) : string {

		$parent = dirname($parent) . $separator;
		// Da formato de path para archivo fisico, siempre
		$son = $this->purgePath($son, $separator);
		if ($son !== '') {
			// Si ya contiene el directorio local, ignora.
			if (strtolower(substr($son, 0, strlen($parent))) === strtolower($parent)) {
				return $son;
			}
			// Si no lo contiene, lo adiciona.
			else {
				return $parent . $son;
			}
		}

		return $parent;
	}


	/**
	 * Ruta física del directorio Web.
	 *
	 * Si indica un valor de directorio o archivo lo complementa con el path
	 * al directorio web (DOCUMENT_ROOT). Es decir:
	 *
	 *     (DOCUMENT_ROOT)/($path_to_root).
	 *
	 * @param string $filename	(Opcional) Directorio/nombre de archivo asociado.
	 * @return string 			Path.
	 */
	public function documentRoot(string $filename = '') {

		// DOCUMENT_ROOT:
		// El directorio raíz bajo el que se ejecuta este script, tal como fuera
		// definido en la configuración del servidor Web.
		return realpath($this->get('DOCUMENT_ROOT')) . DIRECTORY_SEPARATOR . $this->purgeFilename($filename);
	}

	/**
	 * Valida que el archivo/directorio indicado sea subdirectorio del directorio Web.
	 *
	 * @param string $filename	Path de archivo o directorio a evaluar.
	 * @return bool				TRUE si $path es subdirectorio de DOCUMENT_ROOT,
	 * 							FALSE en otro caso.
	 */
	public function inDocumentRoot(string $filename) : bool {

		$filename = $this->purgeFilename($filename);
		$document_root = $this->documentRoot();

		return ($filename !== '' &&
			strtolower(substr($filename, 0, strlen($document_root))) === strtolower($document_root));
	}

	/**
	 * Remueve ruta al directorio Web.
	 *
	 * @param string $filename	Path de archivo o directorio a evaluar.
	 * @return string			Path corregido si es un subdirectorio del directorio Web,
	 * 							FALSE en otro caso.
	 */
	public function removeDocumentRoot(string $filename) : string|false {

		if ($this->inDocumentRoot($filename)) {
			// Debe purgar el path para asegurar que remueva correctamente si incluye ".."
			$filename = $this->purgeFilename($filename);
			$document_root = $this->documentRoot();
			return substr($filename, strlen($document_root));
		}

		return false;
	}

	/**
	 * Valida que el archivo/directorio indicado sea subdirectorio del directorio temporal.
	 *
	 * @param string $path	Path a evaluar.
	 * @return string		TRUE si $path es subdirectorio del directorio temporal,
	 * 						FALSE en otro caso.
	 */
	public function inTempDir(string $path) : bool {

		$tempdir = $this->tempDir();
		$path = $this->purgeFilename($path);

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
		$pathname = $this->purgeFilename($pathname);
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
	 * Asigna o retorna valor del directorio temporal a usar.
	 *
	 * El directorio temporal se recupera (en su orden) de:
	 *
	 * - Valor indicado por el usuario.
	 * - Valor registrado previamente.
	 * - Directorio temporal del sistema.
	 * - Directorio "Temp" a crear en el directorio Web.
	 * - Directorio "Temp" a crear en el directorio actual.
	 *
	 * Si no puede encontrar un directorio temporal valido, genera una "PHP Exception".
	 *
	 * @param string $pathname	(Opcional) Directorio temporal a usar.
	 * @return string 			Path.
	 */
	public function tempDir(string $pathname = '') : string {

		// 1. Asigna path dado por el usuario
		if ($pathname !== '') {
			$path = realpath($this->purgeFilename($pathname));
			if ($path != '' && is_dir($path)) {
				$this->temp_directory = $path;
			}
			else {
				// El path indicado por el usuario no es valido
				throw new \Exception('El directorio temporal indicado no es valido (' . $pathname . ').');
			}
		}
		// 2. Si ya existe, lo retorna
		if ($this->temp_directory !== '' && is_dir($this->temp_directory)) {
			return $this->temp_directory;
		}
		// 3. Toma el directorio del sistema (o el asignado en PHP.INI)
		if ($this->temp_directory === '') {
			$this->temp_directory = realpath(sys_get_temp_dir());
		}
		// 4. Intenta crear/acceder a un directorio "Temp" a crear en el DOCUMENT_ROOT
		if ($this->temp_directory === '' || !is_dir($this->temp_directory)) {
			$path = $this->documentRoot('Temp');
			if ($this->mkdir($path)) {
				$this->temp_directory = $path;
			}
		}
		// 5. Intenta crear/acceder a un directorio "Temp" en el directorio local
		if ($this->temp_directory === '' || !is_dir($this->temp_directory)) {
			$path = $this->local('Temp');
			if ($this->mkdir($path)) {
				$this->temp_directory = $path;
			}
		}
		// Valida que haya podido recuperarlo o reporta error
		if ($this->temp_directory === '' || !is_dir($this->temp_directory)) {
			throw new \Exception('No pudo recuperar un directorio temporal valido. Revise la configuración del Sistema.');
		}

		// Adiciona separador siempre
		$this->temp_directory .= DIRECTORY_SEPARATOR;

		return $this->temp_directory;
	}

	/**
	 * Crea el subdirectorio indicado dentro del directorio temporal.
	 *
	 * @param string $pathname	Subdirectorio temporal a validar.
	 * @return string 			Path o FALSE si el subdirectorio deseado no existe y tampoco pudo
	 * 							ser creado.
	 */
	public function createTempSubdir(string $pathname) : string|false {

		$temp = $this->tempDir();
		$path = $temp . DIRECTORY_SEPARATOR . $this->purgeFilename($pathname);
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
	 * Descripción del servidor Web.
	 *
	 * Por Ej. "Apache/2.4.48 (Win64) OpenSSL/1.1.1k PHP/8.3.6".
	 *
	 * @return string Información del servidor Web.
	 */
	public function software() {

		// SERVER_SOFTWARE:
		// Cadena de identificación del servidor Web. tal como se envía en los
		// headers HTTP de respuesta.
		return $this->get('SERVER_SOFTWARE');
	}

	/**
	 * Provee información relativa al navegador Web (browser) usado por el usuario.
	 *
	 * Por Ej. "Mozilla/5.0 (Windows NT 10.0; Win64; x64)..."
	 *
	 * @return string Información del browser.
	 */
	public function browser() {

		// PENDIENTE:Ampliar funcionalidad usando get_browser().
		return $this->get('HTTP_USER_AGENT');
	}

	/**
	 * Espacio libre en el disco donde se encuentra el directorio Web.
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

	/**
	 * Tiempo transcurrido desde el inicio del script (microsegundos).
	 *
	 * @return float Tiempo de ejecución en microsegundos.
	 */
	public function executionTime() {

		// REQUEST_TIME_FLOAT:
		// El tiempo de inicio de atención a la consulta del usuario, en microsegundos.
		return microtime(true) - $this->get('REQUEST_TIME_FLOAT', 0);
	}
}
