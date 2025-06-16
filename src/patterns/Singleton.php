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
    private static $instances = array();

    /**
     * Cada clase a usar este Singleton debe definir su método de
     * inicialización alterno al __construct() para prevenir que esas
     * clases sean instanciadas usando "new".
     */
    abstract protected function singletonStart();

    /**
     * Valida si la instancia o clase asociada a este Singleton ya fue creada.
     *
     * @return bool TRUE si ya existe una instancia, FALSE en otro caso.
     */
    final public static function alreadyCreated(): bool
    {
        return isset(self::$instances[static::class]);
    }

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
        $cls = static::class; // Nombre de la clase hija
        if (!self::alreadyCreated()) {
            self::$instances[$cls] = new static;
            // Ejecuta inicialización de la instancia
            self::$instances[$cls]->singletonStart();
        }

        /*
        Una pequeña aclaración sobre el uso de "static" en este contexto de clases:
        https://www.php.net/manual/en/language.oop5.late-static-bindings.php

        class A {
            public static function get_self() {
                return new self();
            }

            public static function get_static() {
                return new static();
            }
        }

        class B extends A {}

        echo get_class(B::get_self());   // A
        echo get_class(B::get_static()); // B
        echo get_class(A::get_self());   // A
        echo get_class(A::get_static()); // A
        */

        return self::$instances[$cls];
    }

    /**
     * No se permite la creación manual de la Clase usando "new".
     * Tampoco de ninguna clase que extienda este patrón, esto para
     * prevenir la existencia de múltiples versiones del mismo objeto.
     */
    private function __construct() {}

    /**
     * Previene la clonación de esta instancia, no de sus hijas.
     */
    private function __clone() {}

    /**
     * Previene que esta instancia sea serializada.
     */
    public function __wakeup()
    {
        throw new \Exception("No se permite serialize()/unserialize() esta Clase.");
    }
}
