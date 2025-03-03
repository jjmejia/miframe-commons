<?php

/**
 * Clase para manejo de datos capturados de archivos .env
 */

namespace miFrame\Commons\Core;

use miFrame\Commons\Patterns\Singleton;

class EnvData extends Singleton {

	private array $data = [];
	private string $filename = '';
	private string $prefix = '';

	protected function singletonStart()
	{
		$this->load();
	}

	/**
	 * $name es "case insensitive".
	 */
	public function get(string $name, mixed $default = ''): mixed
	{
		$name = strtolower(trim($name));
		if ($name !== '' && array_key_exists($name, $this->data)) {
			return $this->data[$name];
		}
		return $default;
	}

	public function getNumber(string $name, int|float $default = 0): int|float
	{
		return $this->get($name, $default) + 0;
	}

	public function getBoolean(string $name, bool $default = false): bool
	{
		return boolval($this->get($name, $default));
	}

	public function url(string $name, string $default = ''): string
	{
		return miframe_server()->host($this->get($name, $default));
	}

	public function documentRoot(string $name, string $default = ''): string
	{
		return miframe_server()->documentRoot($this->get($name, $default));
	}

	public function load(string $prefix = ''): bool
	{
		$result = false;
		$basename = trim($prefix) . '.env';

		if ($this->filename === '' || $this->prefix !== $prefix)
		{
			// Limpia datos previos (si alguno)
			$this->prefix = $prefix;
			$this->data = [];
			$server = miframe_server();
			// Busca archivo
			$path = $server->documentRoot($basename);
			$result = file_exists($path);
			if (!$result) {
				// Busca el archivo hacia atrás en tanto no alcance el root
				$elements = explode(DIRECTORY_SEPARATOR, $server->removeDocumentRoot($server->scriptDirectory()));
				// print_r($elements); echo "<hr>"; $this->server->scriptDirectory(); echo "\n";
				do {
					$path = $server->documentRoot(array_shift($elements) . DIRECTORY_SEPARATOR . $basename);
					$result = file_exists($path);
				}
				while (count($elements) > 0 && !$result);
			}
			if ($result) {
				// Encontró el archivo
				// Registra en variables globales el contenido del .env
				$env_data = parse_ini_file($path);
				// Convierte todas las llaves a minusculas
				if (is_array($env_data) && count($env_data) > 0) {
					$this->data = array_change_key_case($env_data, CASE_LOWER);
				}
				$this->filename = $path;
			}
		}

		return $result;
	}
}