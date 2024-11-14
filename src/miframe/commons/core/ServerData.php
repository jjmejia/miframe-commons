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

namespace miFrame\Commons\Core;

// use Error;
use \miFrame\Commons\Patterns\Singleton;
use \miFrame\Commons\Traits\GetLocalData;
// use \miFrame\Commons\Core\GetLocalData;

class ServerData extends Singleton
{
	// class ServerData extends GetLocalData {

	/**
	 * Definiciones y métodos a usar para manejo de datos locales.
	 * Define el método "superglobal".
	 */
	use GetLocalData;

	private $is_web = false;

	/**
	 * @var string $client_ip	Registra localmente la dirección IP del cliente Web.
	 * 							Para consultas de Consola registra "cli".
	 */
	private string $client_ip = '';

	/**
	 * @var string $path_info	Almacena segmento del path tomado de la URL relativo al
	 * 							directorio que contiene el script invocado (cuando se usan
	 * 							direcciones URL amigables o "pretty").
	 */
	private string $path_info = '';

	/**
	 * @var string $temp_directory	Directorio usado para registro de archivos temporales.
	 * 								Debe tener permisos de escritura/lectura para el usuario
	 * 								asociado al WebServer en el Sistema Operativo.
	 */
	private string $temp_directory = '';

	/**
	 * @var float $check_time Tiempo entre intervalos de chequeo.
	 */
	private float $check_time = 0;

	/**
	 * @var array $dir_white_list Listado de directorios permitidos para crear directorios.
	 */
	private array $dir_white_list = [];

	/**
	 * @var array $cache_path Cache de paths corregidos para agilizar procesos repetitivos.
	 */
	private array $cache_path = [];

	/**
	 * Inicialización de la clase Singleton.
	 */
	protected function singletonStart()
	{

		// REMOTE_ADDR:
		// La dirección IP desde donde el usuario está viendo la página actual.
		// Si se consulta desde Consola, no es asignada por el servidor.
		$this->is_web = !empty($this->get('REMOTE_ADDR', false));
	}

	/**
	 * Valor de elemento contenido en la variable superglobal $_SERVER de PHP.
	 *
	 * Si el elemento solicitado no existe, retorna valor en $default.
	 *
	 * @param string $name	  Nombre del elemento a recuperar.
	 * @param string $default (Opcional) Valor a usar si $_SERVER[$name] no existe.
	 * @return string 		  Valor del elemento solicitado.
	 */
	public function get(string $name, string $default = ''): string
	{
		$server_param = strtoupper(trim($name));
		return $this->superglobal('_SERVER', $server_param, $default);
	}

	/**
	 * Indica si está ejecutando desde un Web Browser.
	 *
	 * @return bool TRUE si está consultando por WEB, FALSE si es por consola (cli).
	 */
	public function isWeb(): bool
	{
		return $this->is_web;
	}

	/**
	 * Dirección IP del cliente remoto.
	 *
	 * Para consultas de consola retorna "cli".
	 *
	 * @return string Dirección IP del cliente remoto.
	 */
	public function client(): string
	{

		if ($this->client_ip === '') {
			// Recupera dirección IP
			if (!$this->isWeb()) {
				$this->client_ip = 'cli';
			} else {
				// HTTP_X_FORWARDED_FOR:
				// Usado en vez de REMOTE_ADDR cuando se consulta detrás de un proxy server.
				// Puede contener múltiples IPs de proxies por los que se ha pasado.
				// Solamente la IP del último proxy (última IP de la lista) es de fiar.
				// ( https://stackoverflow.com/questions/11452938/how-to-use-http-x-forwarded-for-properly )
				// Si no se emplean proxys para la consulta, retorna vacio.
				$proxys = $this->get('HTTP_X_FORWARDED_FOR');
				if (!empty($proxys)) {
					$proxy_list = explode(",", $proxys);
					$this->client_ip = trim(end($proxy_list));
				} else {
					// REMOTE_ADDR:
					// La dirección IP desde donde el usuario está viendo la página actual.
					$this->client_ip = $this->get('REMOTE_ADDR');
					if (empty($this->client_ip)) {
						// HTTP_CLIENT_IP:
						// Opcional para algunos servidores Web en remplazo de REMOTE_ADDR.
						$this->client_ip = $this->get('HTTP_CLIENT_IP');
					}
				}

				// En caso que retorne un nombre (como "localhost") se asegura esté en
				// minusculas para facilitar comparaciones.
				$this->client_ip = strtolower($this->client_ip);
			}
		}

		return $this->client_ip;
	}

	/**
	 * Indica si la consulta se realiza directamente en el servidor Web (localhost).
	 *
	 * @return bool	TRUE para consultas desde el servidor Web, FALSE en otro caso.
	 */
	public function isLocalhost(): bool
	{

		$client_ip = $this->client();
		return (!$this->isWeb() ||
			// IPv4, IPv6, Associative name, Consola
			in_array($client_ip, ['127.0.0.1', '::1', 'localhost', 'cli']) ||
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
	public function useHTTPSecure(): bool
	{

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
	public function host(bool $force_https = false): string
	{

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
		if (in_array($port, [':80', ':443'])) {
			$port = '';
		}

		return $scheme . $domain_name . $port . '/';
	}

	/**
	 * Path al script ejecutado en la consulta actual, relativo al directorio Web.
	 *
	 * El valor del path es tomado de $_SERVER['SCRIPT_NAME'].
	 *
	 * @return string Path.
	 */
	public function self(): string
	{

		// SCRIPT_NAME:
		// Contiene la ruta al script actual, vista desde el servidor Web.
		// Puede diferir de la ingresada por el usuario cuando se usan "URL amigables".
		return $this->get('SCRIPT_NAME');
	}

	/**
	 * Path al directorio que contiene el script ejecutado en la consulta actual, relativo al directorio Web.
	 *
	 * El valor del path local es tomado de $_SERVER['SCRIPT_NAME'].
	 * Si se recibe el argumento $path, lo complementa adicionando el
	 * subdirectorio del script actual. Es decir, retorna:
	 *
	 *     (dirname(SCRIPT_NAME))/($path).
	 *
	 * @param string $path	(Opcional) Path a complementar.
	 * @return string 		Path.
	 */
	public function relativePath(string $path = ''): string
	{

		return $this->connect(dirname($this->self()), $path, '/');
	}

	/**
	 * Nombre real del servidor Web.
	 *
	 * @return string Nombre del servidor o FALSE si no está disponible.
	 */
	public function name(): string|false
	{
		return gethostname();
	}

	/**
	 * Dirección IPv4 asociada al servidor Web.
	 *
	 * @return string Retorna la dirección IP del servidor o el nombre del servidor
	 * 				  en caso de ocurrir algún error.
	 */
	public function ip(): string
	{
		return gethostbyname($this->name());
	}

	/**
	 * Path Web consultado por el usuario.
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
	public function path(): string
	{

		// REQUEST_URI:
		// El URI dado por el usuario para acceder a esta página.
		// Puede contener valores GET (queryes) separados por "?".
		// Ejemplo: var1=xxx&var2=zzz...
		// Se usa parse_url() para garantizar que recupere solamente el path.
		return parse_url($this->get('REQUEST_URI'), PHP_URL_PATH);
	}

	/**
	 * Segmento del path del URL con información útil cuando se usan URL amigables.
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
	public function pathInfo(): string
	{

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
	 * @param string $path			Path a depurar.
	 * @param string $separator		Separador a usar. SI no se define este valor,
	 * 								por defecto se usara "/" (separador URL).
	 * 								Si encuentra algún separador "\" en $path,
	 * 								usa este para construir el path depurado.
	 * @param bool $remove_first	Remueve separador si es el primer elemento del path.
	 * @return string				Path depurado.
	 */
	private function purgePath(string $path, string $separator = '', bool $remove_first = false): string
	{

		$path = trim($path);

		// Valida que haya algo que realizar
		if ($path === '') {
			return $path;
		}

		// Valida si debe forzar el separador a usar
		if ($separator == '') {
			$separator = '/';
		}

		// Normaliza separadores para usar "/" durante el proceso
		// de remplazo.
		$path = str_replace('\\', '/', $path);

		// Consulta cache (esto para paths que se invocan muchas veces, por ejemplo
		// al validar directorios).
		$key = strtolower($path);
		if (!isset($this->cache_path[$key])) {
			// Segmentos ya depurados

			// NOTA: No usa siempre realpath() porque puede reportar erroneamente
			// un path con una ruta en disco físico para un path de una URL
			// (indicada por $path).

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
						while ($i > 0 && $segments[$i - 1] == '') {
							$i--;
						}
						// Retrocede un segmento
						if ($i > 0) {
							$segments[$i - 1] = '';
						}
					} elseif ($segments[$i] == '.') {
						$segments[$i] = '';
					}
				}
			}

			// Remueve elementos en blanco (se asegura de preservar siempre
			// el primer elemento, ya que en Linux los path fisicos empiezan con "/")
			$first = array_shift($segments);
			if (count($segments) > 0) {
				$segments = array_filter($segments);
			}

			// Registra cache
			$this->cache_path[$key] = [$first, ...$segments];
		}

		$segments = $this->cache_path[$key];

		// Remueve elementos en blanco (se asegura de preservar siempre
		// el primer elemento, ya que en Linux los path fisicos empiezan con "/")
		$path = implode($separator, $segments);

		$len = strlen($separator);
		// Valida si conscientemente solicita remover el primer separador
		if ($remove_first && substr($path, 0, $len) === $separator) {
			$path = substr($path, $len);
		}

		return $path;
	}

	/**
	 * Estandariza formato del URL path indicado.
	 *
	 * Remueve elementos no deseados (tales como "..", "." y segmentos vacios).
	 * De esta forma previene acceso a rutas restringidas.
	 *
	 * @param string $path			Path a depurar.
	 * @param bool $remove_first	Remueve separador si es el primer elemento del path.
	 * @return string				Path depurado.
	 */
	public function purgeURLPath(string $path, bool $remove_first = false): string
	{

		return $this->purgePath($path, '/', $remove_first);
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
	 * Nota: En Linux se tiene el mismo resultado que purgeURLPath() ya que el
	 * separador de directorios usados es el mismo ("/").
	 *
	 * @param string $filename		Path del archivo o directorio a depurar.
	 * @param bool $remove_first	Remueve separador si es el primer elemento del path.
	 * @return string				Path depurado.
	 */
	public function purgeFilename(string $filename, bool $remove_first = false): string
	{

		return $this->purgePath($filename, DIRECTORY_SEPARATOR, $remove_first);
	}

	/**
	 * Ruta física del script ejecutado.
	 *
	 * @return string 		Path.
	 */
	public function script(): string
	{

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
	 * @param string $filename	(Opcional) Path del archivo o directorio a complementar.
	 * @return string 			Path.
	 */
	public function scriptDirectory(string $filename = ''): string
	{

		return $this->connect(dirname($this->script()), $filename, DIRECTORY_SEPARATOR);
	}

	/**
	 * Complementa paths usando el separador indicado.
	 *
	 * Adiciona al path hijo ($son) el directorio al que pertenece el padre ($parent).
	 * Si el hijo ya contiene el valor de padre, no hace cambios.
	 *
	 * @param string $parent	Path padre.
	 * @param string $son		Path hijo.
	 * @param string $separator	Separador de segmentos del path ("\" o "/").
	 * @return string			Path hijo completo.
	 */
	private function connect(string $parent, string $son, string $separator): string
	{

		$parent .= $separator;
		// Da formato de path para archivo fisico, siempre (no lo termina en "/"
		// porque puede ser un nombre de archivo)
		$son = $this->purgePath($son, $separator);
		if ($son !== '') {
			// Si ya contiene el directorio local, ignora.
			// Para la validación si adiciona el separador "/" a $son, para garantizar
			// hallazgo del directorio padre.
			if ($this->inDirectory($son . $separator, $parent)) {
				$parent .= substr($son, strlen($parent));
			}
			// Si no lo contiene, lo adiciona.
			else {
				// Valida si debe remover separador al inicio de $son
				$len = strlen($separator);
				if (substr($son, 0, $len) === $separator) {
					$son = substr($son, $len);
				}
				$parent .= $son;
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
	 * @param string $filename	(Opcional) Path de archivo o directorio a incluir.
	 * @return string 			Path.
	 */
	public function documentRoot(string $filename = '')
	{

		// DOCUMENT_ROOT:
		// El directorio raíz bajo el que se ejecuta este script, tal como fuera
		// definido en la configuración del servidor Web.
		return $this->connect(realpath($this->get('DOCUMENT_ROOT')), $filename, DIRECTORY_SEPARATOR);
	}

	/**
	 * Valida que el archivo/directorio indicado sea subdirectorio del directorio Web.
	 *
	 * @param string $filename	Path del archivo o directorio a evaluar.
	 * @return bool				TRUE si $path es subdirectorio de DOCUMENT_ROOT,
	 * 							FALSE en otro caso.
	 */
	private function inDocumentRoot(string $filename): bool
	{

		return $this->inDirectory($filename, $this->documentRoot());
	}

	/**
	 * Remueve ruta al directorio Web.
	 *
	 * @param string $filename	Path del archivo o directorio a modificar.
	 * @return string			Path corregido si es un subdirectorio del directorio Web,
	 * 							FALSE en otro caso.
	 */
	public function removeDocumentRoot(string $filename): string|false
	{

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
	 * @param string $filename	Path del archivo o directorio a evaluar.
	 * @return string			TRUE si es archivo o subdirectorio del directorio temporal,
	 * 							FALSE en otro caso.
	 */
	/*private function inTempDir(string $filename) : bool {

		return $this->inDirectory($filename, $this->tempDir());
	}*/

	/**
	 * Valida que el archivo/directorio indicado sea subdirectorio del directorio actual.
	 *
	 * Util para validar cuando se ejecuta por consola desde un directorio diferente al Web.
	 *
	 * @param string $filename	Path del archivo o directorio a evaluar.
	 * @return string			TRUE si es archivo o subdirectorio del directorio actual,
	 * 							FALSE en otro caso.
	 */
	/*private function inScriptDirectory(string $filename) : bool {

		return $this->inDirectory($filename, $this->scriptDirectory());
	}*/

	/**
	 * Auxiliar para detectar un directorio o archivo dentro de otro.
	 *
	 * @param string $filename	Path del archivo o directorio a evaluar.
	 * @param string $src_dir	Path del directorio que puede o no contener a $filename.
	 * @return string			TRUE si es archivo o subdirectorio del directorio actual,
	 * 							FALSE en otro caso.
	 */
	private function inDirectory(string $filename, string $src_dir): bool
	{

		$filename = $this->purgeFilename($filename);

		return ($src_dir !== '' &&
			strtolower(substr($filename, 0, strlen($src_dir))) === strtolower($src_dir));
	}

	/**
	 * Adiciona paths al listado de directorios permitidos para crear subdirectorios.
	 *
	 * Usado para casos que requiera acceso a directorios diferentes al Web y
	 * al temporal, por ejemplo, al ejecutar desde consola.
	 *
	 * @param string $path Directorio existente.
	 */
	public function addAccessDir(string $path)
	{

		if (is_dir($path)) {
			$path = realpath($path) . DIRECTORY_SEPARATOR;
			$key = strtolower($path);
			$this->dir_white_list[$key] = $path;
		}
	}

	/**
	 * Valida que el archivo o directorio dado esté en la lista de permitidos.
	 *
	 * Valida por defecto el directorio Web y el directorio temporal antes de
	 * contrastar contra el listado de directorios manualmente adicionados.
	 *
	 * @param string $filename 	Path del archivo o directorio a evaluar.
	 * @return bool				TRUE si el archivo o directorio pertenece a un directorio
	 * 							registrado como valido.
	 */
	public function hasAccessTo(string $filename): bool
	{

		$filename = $this->purgeFilename($filename);
		if ($filename !== '') {
			// Valida directorios básicos
			if ($this->inDocumentRoot($filename)
				// || $this->inTempDir($filename)
				// Tradicionalmente scriptDirectory() está en el root,
				// a menos que sea ejecutado por consola.
				// || $this->inScriptDirectory($filename)
			) {
				return true;
			}
			// Valida lista permitida
			// $this->dir_white_list['@root'] = $this->documentRoot();
			$this->dir_white_list['@temp'] = $this->tempDir();
			$this->dir_white_list['@script'] = $this->scriptDirectory();
			// Valida en los directorios adicionados manualmente
			foreach ($this->dir_white_list as $dir) {
				if ($this->inDirectory($filename, $dir)) {
					return true;
				}
			}
		}

		return false;
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
	public function mkdir(string $pathname, bool $recursive = true): bool
	{

		$result = false;
		$pathname = $this->purgeFilename($pathname);
		if ($pathname !== '') {
			// TRUE si el directorio ya existe
			$result = is_dir($pathname);
			// El directorio a crear debe estar contenido en $_SERVER['DOCUMENT_ROOT']
			// o en el directorio de temporales.
			if (
				!$result &&
				$this->hasAccessTo($pathname)
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
	 * - Valor indicado por el usuario (intenta crearlo si no existe).
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
	public function tempDir(string $pathname = ''): string
	{

		// 1. Asigna path dado por el usuario
		if ($pathname !== '') {
			$pathname = $this->purgeFilename($pathname);
			if ($pathname !== '' && !is_dir($pathname)) {
				// Intenta crear el directorio
				// (Falla si el directorio no es hijo del actual temporal o
				// del directorio web. Para fijar un temporal en lugar diferente,
				// asegurese que el directorio indicado YA exista).
				$this->mkdir($pathname);
			}
			$path = realpath($pathname);
			if ($path != '' && is_dir($path)) {
				$this->temp_directory = $path . DIRECTORY_SEPARATOR;
			} else {
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
			$path = $this->documentRoot('tmp');
			if ($this->mkdir($path)) {
				$this->temp_directory = $path;
			}
		}
		// 5. Intenta crear/acceder a un directorio "Temp" en el directorio local
		if ($this->temp_directory === '' || !is_dir($this->temp_directory)) {
			$path = $this->scriptDirectory('tmp');
			if ($this->mkdir($path)) {
				$this->temp_directory = $path;
			}
		}
		// Valida que haya podido recuperarlo o reporta error
		if ($this->temp_directory === '' || !is_dir($this->temp_directory)) {
			throw new \Exception('No pudo recuperar un directorio temporal valido. Revise la configuración del Sistema.');
		}

		// Adiciona separador siempre para las opciones 3, 4 y 5
		// $this->temp_directory .= DIRECTORY_SEPARATOR;

		return $this->temp_directory;
	}

	/**
	 * Crea el subdirectorio indicado dentro del directorio temporal.
	 *
	 * @param string $pathname	Subdirectorio temporal a validar.
	 * @return string 			Path o FALSE si el subdirectorio deseado no existe y tampoco pudo
	 * 							ser creado.
	 */
	public function createTempSubdir(string $pathname): string|false
	{

		$temp = $this->tempDir();
		$path = $this->connect($temp, $pathname, DIRECTORY_SEPARATOR);
		if ($temp !== '' && $this->mkdir($path)) {
			return realpath($path);
		}

		return false;
	}

	/**
	 * Información recibida en el "body" de la consulta realizada por el usuario.
	 *
	 * Del manual:
	 * https://www.php.net/manual/en/wrappers.php.php
	 * > php://input is a read-only stream that allows you to read raw data from
	 * > the request body. php://input is not available in POST requests with
	 * > enctype="multipart/form-data" if enable_post_data_reading option is enabled.
	 *
	 * @return string Datos recibidos (si alguno) o FALSE si ocurre algún error.
	 */
	public function rawInput(): string|false
	{
		return @file_get_contents("php://input");
	}

	/**
	 * Descripción del servidor Web.
	 *
	 * Ej. "Apache/2.4.48 (Win64) OpenSSL/1.1.1k PHP/8.3.6".
	 *
	 * @return string Información del servidor Web.
	 */
	public function software()
	{

		// SERVER_SOFTWARE:
		// Cadena de identificación del servidor Web. tal como se envía en los
		// headers HTTP de respuesta.
		return $this->get('SERVER_SOFTWARE');
	}

	/**
	 * Provee información relativa al navegador Web (browser) usado por el usuario.
	 *
	 * Ej. "Mozilla/5.0 (Windows NT 10.0; Win64; x64)..."
	 *
	 * @return string Información del browser.
	 */
	public function browser()
	{

		// PENDIENTE:Ampliar funcionalidad usando get_browser().
		return $this->get('HTTP_USER_AGENT');
	}

	/**
	 * Espacio libre en el disco donde se encuentra el directorio Web.
	 *
	 * @return float Devuelve el número de bytes disponibles como un float.
	 */
	public function documentRootSpace(): float
	{

		// disk_free_space() puede retornar false, por lo que se convierte a
		// float para prevenir conflictos de type.
		return floatval(disk_free_space($this->documentRoot()));
	}

	/**
	 * Espacio libre en el disco donde se encuentra el directorio temporal.
	 *
	 * @return float Devuelve el número de bytes disponibles como un float.
	 */
	public function tempDirSpace(): float
	{

		// disk_free_space() puede retornar false, por lo que se convierte a
		// float para prevenir conflictos de type.
		return floatval(disk_free_space($this->tempDir()));
	}

	/**
	 * Tiempo en que inicia la ejecución del script.
	 *
	 * Puede retornarse como texto en un formato de fecha definido por el usuario,
	 * entre los valores establecidos para el manejo de la función PHP date()
	 * (consultar https://www.php.net/manual/es/function.date.php ).
	 *
	 * @param string $format (Opcional) Formato en que se retorna la fecha de inicio.
	 * @return float Tiempo de inicio en microsegundos.
	 */
	public function startAt(string $format = ''): float|string
	{

		// REQUEST_TIME_FLOAT:
		// El tiempo de inicio de atención a la consulta del usuario, en microsegundos.
		$time = $this->get('REQUEST_TIME_FLOAT', 0);
		if ($format !== '') {
			$time = date($format, intval($time));
		}

		return $time;
	}

	/**
	 * Tiempo transcurrido desde el inicio del script (microsegundos).
	 *
	 * @return float Tiempo de ejecución en microsegundos.
	 */
	public function executionTime(): float
	{

		return microtime(true) - $this->startAt();
	}

	/**
	 * Tiempo transcurrido desde la anterior invocación a este método.
	 *
	 * La primera vez que se invoca, retorna el tiempo transcurrido desde el
	 * inicio del script.
	 *
	 * @param int $precision	(Opcional) Indica cuantos decimales mostrar.
	 * @return float 			Tiempo transcurrido en microsegundos.
	 */
	public function checkPoint(int $precision = 7): float
	{

		if ($this->check_time <= 0) {
			$this->check_time = $this->startAt();
		}
		// Actualiza tiempos
		$previous_check = $this->check_time;
		$this->check_time = microtime(true);
		$time = $this->check_time - $previous_check;

		// Registra también en el log de eventos
		// Muestra siempre $precision decimales máximo
		// (suma 1 por el punto decimal).
		if ($precision > 0) {
			$len = strlen(intval($time)) + $precision + 1;
			$time = substr($time, 0, $len);
		}

		return $time;
	}
}
