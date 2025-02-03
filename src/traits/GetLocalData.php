<?php
/**
 * Clase de soporte para manejo de datos globales.
 *
 * @author John Mejía
 * @since Agosto 2024
 */

namespace miFrame\Commons\Traits;

trait GetLocalData {

	/**
	 * Retorna valor de elemento contenido en la variable superglobal indicada.
	 *
	 * Si el elemento solicitado no existe, retorna valor en $default.
	 *
	 * @param string $collector	Nombre de la variable superglonal (Ej. '_SERVER').
	 * @param string $name		Nombre del elemento a recuperar.
	 * @param string $default	(Opcional) Valor a usar si $_SERVER[$name] no existe.
	 * @return string 			Valor del elemento solicitado.
	 */
	protected function superglobal(string $collector, string $name, mixed $default = '') : string {
		if (!empty($name) && !empty($GLOBALS[$collector][$name])) {
			return $GLOBALS[$collector][$name];
		}
		return $default;
	}
}