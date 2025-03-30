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

use \miFrame\Commons\Patterns\Singleton;
use \miFrame\Commons\Traits\GetLocalData;

class ServerData extends Singleton
{
	/**
	 * Definiciones y métodos a usar para manejo de datos locales.
	 * Define el método "superglobal".
	 */
	use GetLocalData;

	/**
	 * @var bool $is_web 	TRUE cuando la aplicación se ejecuta por Web. FALSE se ejecuta por consola (cli).
	 */
	private bool $is_web = false;

	/**
	 * @var string $ip_client	Registra localmente la dirección IP del cliente Web.
	 * 							Para consultas de Consola registra "cli".
	 */
	private string $ip_client = '';

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
	 * @var float $start_time Hora de arranque del script (real o estimada).
	 */
	private float $start_time = 0;

	/**
	 * @var array $dir_white_list Listado de directorios permitidos para crear directorios.
	 */
	private array $dir_white_list = [];

	/**
	 * @var array $cache_path Cache de paths corregidos para agilizar procesos repetitivos.
	 */
	private array $cache_path = [];

	/**
	 * @var string $current_script Path completo del script en ejecución.
	 */
	private string $current_script = '';

	/**
	 * @var string $document_root Path completo del directorio web por defecto.
	 */
	private string $document_root = '';

	/**
	 * @var bool $forceHttpsForHost Indica si se debe forzar el uso de HTTPS al invocar el método host().
	 */
	public bool $forceHttpsForHost = false;

	/**
	 * Inicialización de la clase Singleton.
	 */
	protected function singletonStart()
	{
		// Hora de apertura inicial
		// REQUEST_TIME_FLOAT:
		// El tiempo de inicio de atención a la consulta del usuario, en microsegundos.
		$this->start_time = $this->get('REQUEST_TIME_FLOAT', microtime(true));
		$this->check_time = $this->start_time;
		// REMOTE_ADDR:
		// La dirección IP desde donde el usuario está viendo la página actual.
		// Si se consulta desde Consola, no es asignada por el servidor.
		$this->is_web = !empty($this->get('REMOTE_ADDR', false));
		// Script en ejecución
		$this->current_script = realpath($this->get('SCRIPT_FILENAME'));
		// Directorio document-root
		$this->document_root = realpath($this->get('DOCUMENT_ROOT'));
		// Autodetecta directorio temporal
		$this->autodetectTempDir();
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
	public function ipClient(): string
	{
		if ($this->ip_client === '') {
			// Recupera dirección IP
			if (!$this->isWeb()) {
				$this->ip_client = 'cli';
			} else {
				// HTTP_X_FORWARDED_FOR:
				// Usado en vez de REMOTE_ADDR cuando se consulta detrás de un proxy server.
				// Puede contener múltiples IPs de proxies por los que se ha pasado.
				// Solamente la IP del último proxy (última IP de la lista) es de fiar.
				// ( https://stackoverflow.com/questions/11452938/how-to-use-http-x-forwarded-for-properly )
				// Si no se emplean proxys para la consulta, retorna vacio.
				// REMOTE_ADDR:
				// La dirección IP desde donde el usuario está viendo la página actual.
				// HTTP_CLIENT_IP:
				// Opcional para algunos servidores Web en remplazo de REMOTE_ADDR.
				$options = ['HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR', 'HTTP_CLIENT_IP'];
				foreach ($options as $key => $name) {
					$this->ip_client = trim($this->get($name));
					// Para HTTP_X_FORWARDED_FOR puede encontrar multiples valores separados por
					// comas. Sólo el último valor es relevante.
					if (strpos($this->ip_client, ',') !== false) {
						$proxy_list = explode(",", $this->ip_client);
						$this->ip_client = trim(array_pop($proxy_list));
					}
					if ($this->ip_client !== '') {
						break;
					}
 				}
				// En caso que retorne un nombre (como "localhost") se asegura esté en
				// minusculas para facilitar comparaciones.
				$this->ip_client = strtolower($this->ip_client);
				// IPv4, IPv6, Associative name, Consola
				if (
					in_array($this->ip_client, ['127.0.0.1', '::1', 'localhost']) ||
					// Local server IP (Ej. 192.xxx)
					$this->ip_client === $this->ip()
					) {
					// Estandariza resultado
					$this->ip_client = 'localhost';
				}
			}
		}

		return $this->ip_client;
	}

	/**
	 * Indica si la consulta se realiza directamente en el servidor Web (localhost).
	 *
	 * @return bool	TRUE para consultas desde el servidor Web, FALSE en otro caso.
	 */
	public function isLocalhost(): bool
	{
		$ip_client = $this->ipClient();
		return (!$this->isWeb() ||
			in_array($ip_client, ['localhost', 'cli'])
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
	 * Nombre del servidor (host) dado al invocar esta sesión Web.
	 *
	 * @return string Nombre del servidor web.
	 */
	public function domain(): string
	{
		// SERVER_NAME:
		// Nombre del servidor host que está ejecutando este script. Si se ejecuta en
		// un host virtual, este será el valor asignado a ese host virtual.
		return $this->get('SERVER_NAME', 'unknown');
	}

	/**
	 * URL completa asociada al servidor Web para la consulta actual.
	 *
	 * Una "URL completa" se compone de los siguientes elementos (los valores entre "[]" son opcionales):
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
	 * @param string $path 		Path a completar con el esquema y dominio.
	 * @param array $args 		Variables a incluir en la URL.
	 * @return string 			URL.
	 */
	public function host(string $path = '', array $args = []): string
	{
		// Valida schema usado (http o https)
		$full_path = ($this->forceHttpsForHost || $this->useHTTPSecure()) ? 'https://' : 'http://';

		// Domain-name (nombre de dominio, ej: www.misitio.com)
		$full_path .= $this->domain();

		// SERVER_PORT:
		// Puerto en la maquina del servidor usado por el servidor Web para comunicación. Por defecto será de '80'; Para SSL (HTTPS) cambia a cualquiera sea
		// el puerto usado para dicha comunicación (por defecto 443).
		$port = 0 + $this->get('SERVER_PORT', 80);

		// Ignora puertos estándar 80 (HTTP) y 443 (HTTPS)
		if ($port > 0 && !in_array($port, [80, 443])) {
			$full_path .= ':' . $port;
		}

		// Complementa path con los parámetros adicionales recibidos (si alguno)
		return $full_path . $this->url($path, $args);
	}

	/**
	 * Retorna una URL completa, incluido el esquema y dominio.
	 *
	 * @param string $path 		Path a completar con el esquema y dominio.
	 * @param array $args 		Variables a incluir en la URL.
	 * @param bool $force_https	TRUE ignora el valor actual del scheme y retorna siempre "https" (a usar por Ej. para
	 * 							redirigir una consulta no segura con "https" a una segura que use "https").
	 * @return string 			URL.
	 */
	public function url(string $path, array $args = []): string
	{
		$params = '';
		$path = '/' . trim($path);

		// Valida si el path contiene parámetros
		$params = '';
		$pos = strpos($path, '?');
		if ($pos !== false) {
			parse_str(substr($path, $pos + 1), $args_local);
			$path = substr($path, 0, $pos);
			// Fusiona los valores en $args (da prioridad a los valores en $args)
			$args = array_merge($args_local, $args);
		}
		// (si no  hay valor alguno en $args deja $path sin modificar)
		if (count($args) > 0) {
			// Conecta
			$params = '?' . http_build_query($args);
		}
		// Retorna URL completo
		return $this->purgeURLPath($path) . $params;
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
	 * @return string				Path depurado.
	 */
	private function purgePath(string $path, string $separator = ''): string
	{
		$path = trim($path);
		// Valida si debe forzar el separador a usar
		if ($separator == '') {
			$separator = '/';
		}
		$len = strlen($separator);
		// Normaliza separadores para usar "/" durante el proceso
		// de remplazo.
		$path = str_replace(['\\', '/', $separator . $separator], $separator, $path);
		// Captura el primer elemento si y solo si es un separador.
		// Esto porque en Linux los path fisicos empiezan con "/"
		$first = '';
		if (substr($path, 0, $len) === $separator) {
			$path = substr($path, $len);
			$first = $separator;
		}
		// Elimina ultimo separador si existe (reduce valores a guardar en cache)
		while (substr($path, -$len, $len) === $separator) {
			$path = substr($path, 0, -$len);
		}
		// Si no hay segmentos a evaluar, retorna de inmediato
		if (strpos($path, $separator) === false || $path === '') {
			return $first . $path;
		}

		// Consulta cache (esto para paths que se invocan muchas veces, por ejemplo
		// al validar directorios). Usa un unico separador para asegurar
		// que funcione sea para directorios o urls si coincide el path.
		$key = md5(str_replace($separator, '/', strtolower($path)));
		if (!isset($this->cache_path[$key])) {
			$segments = explode($separator, $path);

			// Filtra y remueve espacios? No debe, pues el espacio
			// puede ser parte del nombre del archivo. Solamente aplica
			// si el elemento es vacio o "." o ".."
			array_walk($segments, function (&$value, $key) use (&$segments) {
				$cvalue = trim($value);
				if ($cvalue === '.' || $cvalue === '') {
					$value = '';
				} elseif ($cvalue === '..') {
					$value = '';
					// Retrocede hasta encontrar un no-vacio para removerlo
					do {
						$key--;
					} while ($key >= 0 && $segments[$key] === '');
					if ($key >= 0) {
						$segments[$key] = '';
					}
				}
			});

			// Elimina celdas vacias y guarda caché
			$this->cache_path[$key] = implode('/', array_filter($segments));

			// print_r($this->cache_path); echo "<hr>";
		}

		$path = str_replace('/', $separator, $this->cache_path[$key]);

		// Remueve elementos en blanco (se asegura de preservar siempre
		// el primer elemento, ya que en Linux los path fisicos empiezan con "/")
		return $first . $path;
	}

	/**
	 * Estandariza formato del URL path indicado.
	 *
	 * Remueve elementos no deseados (tales como "..", "." y segmentos vacios).
	 * De esta forma previene acceso a rutas restringidas.
	 *
	 * @param string $path			Path a depurar.
	 * @return string				Path depurado.
	 */
	public function purgeURLPath(string $path): string
	{
		return $this->purgePath($path, '/');
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
	 * @return string				Path depurado.
	 */
	public function purgeFilename(string $filename): string
	{
		return $this->purgePath($filename, DIRECTORY_SEPARATOR);
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
		return $this->current_script;
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
		return $this->connect(dirname($this->current_script), $filename, DIRECTORY_SEPARATOR);
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
		$parent = $this->purgePath($parent, $separator) . $separator;
		$son = $this->purgePath($son, $separator);
		if ($son !== '') {
			// Si ya contiene el directorio local, ignora.
			if (strtolower(substr($son, 0, strlen($parent) + 1)) === strtolower($parent)) {
				$parent = $son;
			} else {
				// Si no lo contiene, lo adiciona.
				// Purga todo por si $son comienza con $separator
				$parent = $this->purgePath($parent . $son, $separator);
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
		// NOTA: En Windows, Apache puede reportar DOCUMENT_ROOT con "/" en lugar de "\"
		return $this->connect($this->document_root, $filename, DIRECTORY_SEPARATOR);
	}

	/**
	 * Valida que el archivo/directorio indicado sea subdirectorio del directorio Web.
	 *
	 * @param string $filename	Path del archivo o directorio a evaluar.
	 * @return bool				TRUE si $path es subdirectorio de DOCUMENT_ROOT,
	 * 							FALSE en otro caso.
	 */
	public function inDocumentRoot(string $filename): bool
	{
		return $this->inDirectory($filename, $this->document_root);
	}

	/**
	 * Remueve ruta al directorio Web.
	 *
	 * @param string $filename	Path del archivo o directorio a modificar.
	 * @return string			Path corregido si es un subdirectorio del directorio Web,
	 * 							en otro caso retorna el path original.
	 */
	public function removeDocumentRoot(string $filename): string
	{
		// Debe purgar el path para asegurar que remueva correctamente si incluye ".."
		$filename = $this->purgeFilename($filename);
		if ($this->inDocumentRoot($filename)) {
			// Remueve el "/" al inicio del path residual (si aplica)
			return substr($filename, strlen($this->document_root) + 1);
		}

		return $filename;
	}

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
		$filename = $this->purgeFilename($filename) . DIRECTORY_SEPARATOR;
		$src_dir = @realpath($src_dir);
		return ($src_dir !== '' &&
			strtolower(substr($filename, 0, strlen($src_dir) + 1)) === strtolower($src_dir) . DIRECTORY_SEPARATOR);
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
			$path = realpath($path);
			// Usa llave para prevenir se duplique
			$key = md5(strtolower($path));
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
			if ($this->inDocumentRoot($filename)) {
				return true;
			}
			// Valida lista permitida (complementa directorios manuales)
			$white_list = $this->dir_white_list;
			$white_list['@temp'] = $this->tempDir();
			$white_list['@script'] = $this->scriptDirectory();
			// Valida en los directorios adicionados manualmente
			foreach ($white_list as $dir) {
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
			$path = $this->purgeFilename($pathname);
			if ($path !== '' && !is_dir($path)) {
				// Intenta crear el directorio
				// (Falla si el directorio no es hijo del actual temporal o
				// del directorio web. Para fijar un temporal en lugar diferente,
				// asegurese que el directorio indicado YA exista).
				$this->mkdir($path);
			}
			if ($path !== '' && is_dir($path)) {
				$this->temp_directory = realpath($path) . DIRECTORY_SEPARATOR;
			} else {
				// El path indicado por el usuario no es valido
				throw new \Exception('El directorio temporal indicado no es valido (' . $pathname . ').');
			}
		}

		return $this->temp_directory;
	}

	/**
	 * Autodetecta el directorio temporal asignado.
	 *
	 * Si no encuentra un directorio valido, genera una Excepción.
	 */
	private function autodetectTempDir()
	{
		// 1. Toma el directorio del sistema (o el asignado en PHP.INI)
		if ($this->temp_directory === '' || !is_dir($this->temp_directory)) {
			$this->temp_directory = realpath(sys_get_temp_dir());
		}
		// 2. Intenta acceder a un directorio "Temp" en el DOCUMENT_ROOT
		if ($this->temp_directory === '' || !is_dir($this->temp_directory)) {
			$this->temp_directory = $this->documentRoot('tmp');
		}
		// 3. Intenta acceder a un directorio "Temp" en el directorio local
		if ($this->temp_directory === '' || !is_dir($this->temp_directory)) {
			$this->temp_directory = $this->scriptDirectory('tmp');
		}
		// Valida que haya podido recuperarlo o reporta error
		if ($this->temp_directory === '' || !is_dir($this->temp_directory)) {
			throw new \Exception('No pudo recuperar un directorio temporal valido. Revise la configuración del Sistema.');
		}
	}

	/**
	 * Crea el subdirectorio indicado dentro del directorio temporal.
	 *
	 * @param string $pathname	Subdirectorio temporal a validar.
	 * @return string 			Path o FALSE si el subdirectorio deseado no existe y tampoco pudo
	 * 							ser creado.
	 */
	public function tempSubdir(string $pathname): string|false
	{
		$path = $this->connect($this->temp_directory, $pathname, DIRECTORY_SEPARATOR);
		if ($this->mkdir($path)) {
			return realpath($path) . DIRECTORY_SEPARATOR;
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
		return floatval(disk_free_space($this->document_root));
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
		$time = $this->start_time;
		if ($format !== '') {
			$time = date($format, intval($time));
		}

		return $time;
	}

	/**
	 * Tiempo transcurrido desde el inicio del script.
	 *
	 * @param int $precision (Opcional) Número de decimales a mostrar.
	 * @return float 			Tiempo transcurrido en segundos.
	 */
	public function executionTime(int $precision = 7): float
	{
		return $this->timeDiff(microtime(true), $this->start_time, $precision);
	}

	/**
	 * Tiempo transcurrido desde la anterior invocación a este método.
	 *
	 * La primera vez que se invoca, retorna el tiempo transcurrido desde el
	 * inicio del script.
	 *
	 * @param int $precision (Opcional) Número de decimales a mostrar.
	 * @return float 			Tiempo transcurrido en segundos.
	 */
	public function checkPoint(int $precision = 7): float
	{
		// Actualiza tiempos
		$previous_check = $this->check_time;
		$this->check_time = microtime(true);
		return $this->timeDiff($this->check_time, $previous_check, $precision);
	}

	/**
	 * Calcula la diferencia en segundos entre dos momentos de tiempo.
	 *
	 * Si $precision es mayor a cero, muestra el resultado con $precision decimales.
	 *
	 * @param float $now Tiempo actual.
	 * @param float $time_since Tiempo desde el cual se calcula la diferencia.
	 * @param int $precision (Opcional) Número de decimales a mostrar.
	 * @return float Diferencia en segundos.
	 */
	private function timeDiff(float $now, float $time_since, int $precision = 7): float
	{
		$time = $now - $time_since;

		// Muestra siempre $precision decimales máximo
		// (suma 1 por el punto decimal).
		if ($precision > 0) {
			$suffix = '';
			$pos = strpos($time, 'E');
			if ($pos !== false) {
				$suffix = substr($time, $pos);
			}
			$len = strlen(intval($time)) + $precision + 1;
			$time = 0 + (substr($time, 0, $len) . $suffix);
		}
		return $time;
	}
}
