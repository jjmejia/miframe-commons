<?php
/**
 * Clase usada para definir patrones Singleton.
 *
 * Referencias:
 * - https://github.com/lordvadercito/DesignPatternsPHP/blob/master/Singleton/Singleton.php
 * - https://refactoring.guru/es/design-patterns/singleton/php/example
 *
 * @author John Mejía
 * @since Julio 2024
 */

namespace miFrame\Commons\Patterns;

class Singleton
{
    /**
     * @var array $instances Referencias a las instancias singleton.
     */
    protected static $instances = array();

    /**
     * Retorna la instancia actual, creada solamante una vez por tipo de Clase hija.
     *
     * Para manejo de diferentes tipos de Clases hijas y que cada una tenga su "Singleton",
     * requiere manejo de multiples instancias, de lo contrario todas las hijas apuntarían al
     * mismo elemento.
     *
     * @return self
     */
    public static function getInstance()
    {
        $cls = static::class;
        if (!isset(self::$instances[$cls])) {
            static::$instances[$cls] = new static;
        }

        return static::$instances[$cls];
    }

    /**
     * No se permite la creación manual de la Clase usando "new".
     * Se define el método __construct() como privado para realizar este bloqueo.
     */
    private function __construct()
    {
    }

    /**
     * Previene la clonación de esta instancia.
     */
    private function __clone()
    {
    }

    /**
     * Previene que esta instancia sea serializada.
     */
    public function __wakeup()
    {
		throw new \Exception("No se permite serialize()/unserialize() esta Clase.");
    }
}