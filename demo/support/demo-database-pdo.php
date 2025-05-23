<?php

/**
 * Demo para pruebas de la clase PDOController.
 *
 * @author John Mejía
 * @since Diciembre 2024
 */

// Configuración de demo, crea objeto $Test
include_once __DIR__ . '/../demo-config.php';

// Apertura de la página demo
$Test->title = 'Conexión y consultas a bases de datos';
$Test->description = 'Esta demo ilustra el uso de la clase <code>PDOController</code> usada para consultas a bases de datos en PHP.';
$Test->start();

// Opciones adicionales (por defecto incluye "Habilitar modo Debug")
// if ($Test->choice(...)) { ... }

// Inicializa conexión según elección del usuario.
// Crea variable $db para manejo de la base de datos.
include_once __DIR__ . '/demo-database-files/main-functions.php';

// Cierre de la página
$Test->end();
