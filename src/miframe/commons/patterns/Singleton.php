<?php
/**
 * Clase usada para definir patrones Singleton.
 *
 * Permite crear una clase como una extensión de este patrón (con lo cual
 * la clase creada automáticamente pasa a ser unSingleton) o asociando un
 * objeto al modelo para que se comporte como unSingleton.
 *
 * Referencias:
 * - https://github.com/lordvadercito/DesignPatternsPHP/blob/master/Singleton/Singleton.php
 * - https://refactoring.guru/es/design-patterns/singleton/php/example
 *
 * @author John Mejía
 * @since Julio 2024
 */

namespace miFrame\Commons\Patterns;

abstract class Singleton
{
    /**
     * @var array $instances Referencias a las instancias singleton.
     */
    protected static $instances = array();

    /**
     * Cada clase a usar este Singleton debe definir su método de
     * inicialización alterno al __construct() para prevenir que esas
     * clases sean instanciadas usando "new".
     */
    abstract protected function singletonStart();

    /**
     * Retorna la instancia actual, creada solamente una vez por tipo de Clase hija.
     *
     * Para manejo de diferentes tipos de Clases hijas y que cada una tenga su "Singleton",
     * requiere manejo de multiples instancias, de lo contrario todas las hijas apuntarían al
     * mismo elemento.
     *
     * @return self
     */
    final public static function getInstance()
    {
        $cls = static::class;
        if (!isset(self::$instances[$cls])) {
            static::$instances[$cls] = new static;
            // Ejecuta inicialización de la instancia
            static::$instances[$cls]->singletonStart();
        }

        return static::$instances[$cls];
    }

    /**
     * No se permite la creación manual de la Clase usando "new".
     * Tampoco de ninguna clase que extienda este patrón, esto para
     * prevenir la existencia de múltiples versiones del mismo objeto.
     */
    private function __construct()
    {
    }

    /**
     * Previene la clonación de esta instancia, no de sus hijas.
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