<?php

/**
 * Vista para demos de PDOController
 */

echo "<h3>Consulta SQL</h3>";

// Recupera registros
$Test->showNextLines(4);
$query = 'select person.id, person.name, gender.name as gender
from person
    left join gender on gender.id = person.gender_id';
$rows = $db->query($query);
echo showTable($rows);
showStats($db);

echo "<h3>Consulta SQL parcial</h3>";

// Recupera 5 registros
$Test->showNextLines(4);
$query = 'select person.id, person.name, gender.name as gender
from person
    left join gender on gender.id = person.gender_id';
$rows = $db->query($query, offset: 8, limit: 5);
echo showTable($rows);
showStats($db);

echo "<h3>Consulta SQL usando sentencias preparadas</h3>";

// Busqueda por valores
$Test->showNextLines(6);
$query = 'select person.id, person.name, gender.name as gender
from person
    left join gender on gender.id = person.gender_id
where gender.name = ?';
$values = ['Femenino'];
$rows = $db->query($query, $values);
echo showTable($rows);
showStats($db);

echo "<h3>Consulta SQL con recuperación manual de datos</h3>";

// Recupera 5 registros
$Test->showNextLines(6);
$query = 'select person.id, person.name, gender.name as gender
from person
    left join gender on gender.id = person.gender_id
where person.id > ?';
$result = $db->execute($query, [20]);
$rows = $result->fetchAll();
echo showTable($rows);
showStats($db);

echo "<h3>Consulta con errores</h3>";

// Recupera registros
$Test->showNextLines(4);
$query = 'select person.id, name, gender.name as gender
from person
    left join gender on gender.id = person.gender_id';
$rows = $db->query($query);
echo showTable($rows);
showStats($db);

if (!$db->inDebug()) {
	echo "<p class=\"test-aviso\"><b>Sugerencia:</b> Habilite el modo Debug para visualizar errores directamente en pantalla.</p>";
}
