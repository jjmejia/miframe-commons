<?php

/**
 * Contruye queries SQL.
 */

namespace miFrame\Commons\Support;
use miFrame\Commons\Interfaces\SQLConsultorInterface;

class SQLQueryBuilder {

	private ?DBMotorData $db = null;
	private ?SQLConsultorInterface $motor = null;

	private int $offset = 0;
	private int $limit = 0;
	// private string $pkey = '';
	private array $dataQuery = [];
	// private array $where = [];
	// private array $filters = [];
	// private array $valuesQuery = [];
	// private string $lastQuery = '';
	private bool $usingJoin = false;
	private array $valuesBind = [];
	private string $lastBind = '';

	public function __construct(DBMotorData $db, SQLConsultorInterface $motor)
	{
		$this->db = $db;
		$this->motor = $motor;
		// Secuencia esperada de valores
		$this->valuesBind = [
			'select' => [],
			// 'from' => [],
			'on' => [],
			'where' => [],
			// 'group by' => [],
			// 'order by' => [],
			// 'having' => []
			];
	}

	/**
	 * $columns es un arreglo con los nombres de las columnas.
	 * Las llaves no numericas corresponden al nombre a usar
	 * en remplazo del elemento dado, por ej: 'total' => 'count(id)'
	 * Ejemplos validos:
	 * [ 'column1', 'column2 as alias1', 'alias2' => 'column3' ]
	 * [ 'column1, column2 as alias1, column3 as alias2' ]
	 * (string) 'column1, column2 as alias1, column3 as alias2'
	 */
	public function select(array|string $columns): self
	{
		$select = '';
		// Registra elemento
		if (!is_array($columns)) {
			$select = trim($columns);
		}
		else {
			$select = $this->columns($columns);
		}
		if ($select !== '') {
			$this->dataQuery['select'] = $select;
		}

		return $this;
	}

	/**
	 * Ejemplos validos:
	 * [ 'column1', 'column2 as alias1', 'alias2' => 'column3' ]
	 * [ 'column1, column2 as alias1, column3 as alias2' ]
	 */
	private function columns(array $columns): string
	{
		$select = '';
		foreach ($columns as $key => $name) {
			// Si algun elemento es una arreglo, lo desglosa
			if (is_array($name)) {
				$name = $this->columns($name);
			}
			// Adiciona elemento simple
			$name = trim($name);
			if ($name !== '') {
				if ($select != '') {
					$select .= ', ';
				}
				$select .= $name;
				if (!is_numeric($key)) {
					$select .= ' as ' . $key;
				}
			}
		}

		return $select;
	}

	private function tableName(string $table, string $alias = ''): string
	{
		$table = trim($table);
		if ($table !== '') {
			$alias = trim($alias);
			if ($alias !== '') {
				$table .= ' as ' . $alias;
			}
		}

		return $table;
	}

	public function from(string $table, string $alias = ''): self
	{
		$table = $this->tableName($table, $alias);
		if ($table !== '') {
			$this->dataQuery['from'][] = $table;
		}

		return $this;
	}

	/**
	 * https://www.geeksforgeeks.org/sql-join-set-1-inner-left-right-and-full-joins/
	 */
	public function join(string $type, string $table, string $alias = ''): self
	{
		$table = $this->tableName($table, $alias);
		if ($table !== '') {
			$this->dataQuery['from'][] = [$type . ' join' => $table, 'on' => ''];
			$this->usingJoin = true;
		}

		return $this;
	}

	public function leftJoin(string $table, string $alias = ''): self
	{
		return $this->join('left', $table, $alias);
	}

	public function rightJoin(string $table, string $alias = ''): self
	{
		return $this->join('right', $table, $alias);
	}

	public function innerJoin(string $table, string $alias = ''): self
	{
		return $this->join('inner', $table, $alias);
	}

	public function fullJoin(string $table, string $alias = ''): self
	{
		return $this->join('full', $table, $alias);
	}

	public function on(array|string ...$elements): self
	{
		return $this->updateOn($elements);
	}

	private function updateOn(array $elements, string $conector = '')
	{
		if (!$this->usingJoin) {
			// No hay una referencia join previa
			trigger_error('No se ha especificado ningún JOIN antes de invocar el método on()', E_USER_WARNING);
		}
		else {
			// Para algunos casos, como los inner, valida "on"
			$from = $this->evalConditional($elements, 'and');
			// $condition = trim($condition);
			if ($from['sql'] !== '') {
				$key = array_key_last($this->dataQuery['from']);
				if (is_array($this->dataQuery['from'][$key])) {
					// El 4to elemento es la condicion
					// Complementa con el conector
					$this->connectSQL($this->dataQuery['from'][$key], 'on', $conector, $from['sql']);
				}
			}
			$this->setValuesBind('on', $from['values']);
		}

		return $this;
	}

	private function setValuesBind(string $type, array &$values, bool $replace = true)
	{
		if ($replace || empty($this->valuesBind[$type])) {
			$this->valuesBind[$type] = $values;
		} elseif (count($values) > 0) {
			$this->valuesBind[$type] = array_merge($this->valuesBind[$type], $values);
		}
		// Registra esta secuencia para validaciones siguientes
		$this->lastBind = $type;
	}

	/**
	 * Supported operators are: '===', '!==', '!=', '==', '=', '<>', '>', '<', '>=', and '<=':
	 * Ejemplos:
	 * - where('id', 10) --> id = 10
	 * - where('deleted_at', '!=', null) --> deleted_at is NOT null
	 * - where('number', '>', 10) --> number > 10
	 *
	 * whereIn, whereNotIn, whereBetween, whereNotBetween, whereNotNull, whereNull,
	 */
	public function where(array|string ...$elements): self
	{
		// validar si remplaza el valor del where o adiciona (conector = and/or)
		// (previo) and (nuevo)
		// buscar alternativa: whereAnd(...) whereOr(...)
		// sqlwhere['sql'][] = '(...)'
		// sqlwhere['sql'][] = 'and'
		// sqlwhere['sql'][] = '(...)'
		// Cómo lo hace Eloquent?

		// where(a, >, b)->orWhere(a, <, c)...
		// creo que mi metodo es mas efectivo, en parte|
		// pero adicionar llaves lo complica cuando la misma llave
		// se repite. Pude entonces rehacerse sin llaves para simplificar?

		// o mantenerlo como está ahora?

		return $this->updateWhere($elements);
	}

	private function updateWhere(array $elements, string $conector = ''): self
	{
		// Captura nuevo query y valores
		$new = $this->evalConditional($elements, 'and');
		// $new = $this->where;
		// Combina los dos valores
		// $this->where['sql'] = '';
		$this->connectSQL($this->dataQuery, 'where', $conector, $new['sql']);
		$this->setValuesBind('where', $new['values'], ($conector == ''));

		return $this;
	}

	private function connectSQL(array &$data, string $index, string $conector, string $new)
	{
		$replace = ($conector == '' || empty($data[$index]));
		if ($replace) {
			$data[$index] = $new;
		}
		elseif ($new !== '') {
			if (substr($data[$index], 0, 1) !== '(') {
				// Encapsula primer condicion (solamente la primer vez)
				$data[$index] = "({$data[$index]})";
			}
			$data[$index] .= " {$conector} ({$new})"; // Encapsula
		}
	}

	public function or(array|string ...$elements): self
	{
		return $this->updateAndOr($elements, 'or');
	}

	public function and(array|string ...$elements): self
	{
		return $this->updateAndOr($elements, 'and');
	}

	private function updateAndOr(array $elements, string $conector): self
	{
		$method = 'update' . ucfirst($this->lastBind);
		if ($this->lastBind !== '' && method_exists($this, $method)) {
			return $this->$method($elements, $conector);
		}

		// Si llega a este punto es porque no ha encontrado un predecesor
		// valido (on, where, etc.)
		trigger_error("No se ha especificado un predecesor valido para el uso del método {$conector}()", E_USER_WARNING);

		return $this;
	}

	private function orderByRaw(string $type, array $columns)
	{
		$order = '';
		foreach ($columns as $name) {
			$type_temp = $type;
			if (is_array($name)) {
				if (!isset($name[0])) {
					// Ignora?
					trigger_error('El ordenamiento SQL requiere como mínimo el nombre de columna', E_USER_WARNING);
					continue;
				}
				if (isset($name[1])) {
					// Tipo de orden: asc/desc
					$name[1] = strtolower(trim($name[1]));
					if ($name[1] !== '' && $name[1] !== 'asc' && $name[1] !== 'desc') {
						// Ignora
						trigger_error("El tipo de ordenamiento SQL debe ser \"asc\" o \"desc\", valor \"{$name[1]}\" encontrado", E_USER_WARNING);
						continue;
					}
				}
				// Recibe [campo, asc/desc]
				$type_temp = $name[1];
				$name = $name[0];
			}
			$name = trim(str_replace("\t", ' ', $name));
			if ($name !== '') {
				if ($order !== '') {
					$order .= ', ';
				}
				$order .= $name;
				// Si manualmente ha indicado "asc" o "desc", ignora
				if (strpos($name, ' ') === false && $type_temp !== '') {
				 	$order .= ' ' . $type_temp;
				}
			}
		}

		return $order;
	}

	/**
	 * Ejemplos:
	 * - orderBy('column_1', 'column_2');
	 * - orderBy('column_1 desc', 'column_2 asc');
	 * - orderBy(['column_1', 'desc'], ['column_2', 'asc']);
	 * - orderBy(['column_1', 'desc'], 'column_2');
	 */
	public function orderBy(array|string ...$columns): self
	{
		$order = $this->orderByRaw('asc', $columns);
		if ($order !== '') {
			$this->dataQuery['order by'] = $order;
		}

		return $this;
	}

	/**
	 * Similar a orderBy() pero adiciona por defecto el "DESC" para cada caso.
	 */
	public function orderByDesc(array|string ...$columns): self
	{
		$order = $this->orderByRaw('desc', $columns);
		if ($order !== '') {
			$this->dataQuery['order by'] = $order;
		}

		return $this;
	}

	/**
	 * Registro de inicio
	 */
	public function offset(int $start): self
	{
		if ($start >= 0) {
			$this->offset = $start;
		}
		return $this;
	}

	/**
	 * Cantidad de registros
	 */
	public function limit(int $limit): self
	{
		if ($limit >= 0) {
			$this->limit = $limit;
		}
		return $this;
	}

	public function having(string ...$elements): self
	{
		return $this->updateHaving($elements);
	}

	/**
	 * https://www.geeksforgeeks.org/sql-having-clause-with-examples/
	 */
	private function updateHaving(array $elements, string $conector = ''): self
	{
		// Captura nuevo query y valores
		$new = $this->evalConditional($elements, 'and');
		// $new = $this->where;
		// Combina los dos valores
		// $this->where['sql'] = '';
		$this->connectSQL($this->dataQuery, 'having', $conector, $new['sql']);
		$this->setValuesBind('having', $new['values'], ($conector == ''));

		return $this;
	}

	public function groupBy(string ...$columns): self
	{
		return $this;
	}

	private function buildAndRun(int $offset = 0, int $limit = 0, string $query = ''):array|false
	{
		// Si no especifica query, lo reconstruye
		if ($query == '') {
			$query = $this->build();
		}

		if ($offset < 0) { $offset = 0; }
		if ($limit < 0) { $limit = 0; }
		if ($offset > 0 || $limit > 0) {
			if (!$this->motor->splice($query, $offset, $limit)) {
				// Realiza consulta manual.
				// Dado que debe recuperar TODOS los registros, no es
				// el método más idóneo de obtener estos datos, especialmente
				// en tablas grandes.
				$result = $this->queryWithValues($query);
				if (is_array($result)) {
					$result = array_splice($result, $offset, $limit);
				}
				return $result;
			}
		}

		return $this->queryWithValues($query);
	}

	/**
	 * Retorna registros encontrados limitados por offset() y limit().
	 * Si no ha declarado offset() y limit(), retorna todos los registros
	 * (similar al método all()).
	 * Cada invocacion al get() reconstruye el query en caso
	 * que modifique alguno de sus elementos.
	 */
	public function get():array|false
	{
		return $this->buildAndRun($this->offset, $this->limit);
	}

	/**
	 * Retorna todos los registros encontrados.
	 */
	public function all():array|false
	{
		return $this->buildAndRun();
	}

	public function build(): string
	{
		// Establece orden en que arma los queries
		$sequence = ['select', 'from', 'where', 'group by', 'order by', 'having'];

		if (empty($this->dataQuery['select'])) {
			// No ha declarado la sentencia de arranque
			return false;
		}

		// echo "<pre>"; print_r($this->dataQuery); echo "</pre><hr>";
		// echo "<pre>"; print_r($this->valuesBind); echo "</pre><hr>";

		$query = '';
		foreach ($sequence as $sentence) {
			if (!empty($this->dataQuery[$sentence])) {
				$data = $this->dataQuery[$sentence];
				if (is_array($data)) {
					// Si algún elemento de $data es un arreglo, lo reduce
					$data = $this->array2string($data);
				} else {
					$data = trim($data);
				}
				if ($data !== '') {
					$query .= $sentence . ' ' . $data . PHP_EOL;
				}
			}
		}

		// echo "$query<hr>";

		return $query;
	}

	private function array2string(array $data): string
	{
		$content = '';
		foreach ($data as $k => $v) {
			$k = trim($k);
			if (!is_numeric($k) && $k !== '') {
				$content .= ' ' . $k;
			}
			if (!is_array($v)) {
				$content .= ' ' . trim($v);
			}
			else {
				$content .= PHP_EOL . ' ' . $this->array2string($v);
			}
		}

		return trim($content);
	}

	/**
	 * Si el limite es 1, desglosa el registro encontrado.
	 */
	private function getAllOrOne(int $offset, int $limit): array|false
	{
		$result = $this->buildAndRun($offset, $limit);
		if (is_array($result) && $limit == 1 && !empty($result[0])) {
			// Cuando solicita un solo registro, facilita su lectura
			$result = $result[0];
		}

		return $result;
	}

	/**
	 * Como get() pero solamente retorna el primer elemento.
	 * PosgreSQL y MSSQL tiene una sentencia SQL para esto!
	 * https://www.geeksforgeeks.org/sql-top-limit-fetch-first-clause/
	 * pero no hay mucha diferencia respecto al metodo aqui usado.
	 */
	public function first(int $count = 0): array|false
	{
		// Valida cantidades a recuperar
		if ($count <= 0) {
			$count = 1;
		}

		// $result = false;
		// $query = $this->build();
		// if ($this->motor->first($query, $count)) {
		// 	// Modificó el query para recuperar el primer elemento
		// 	$result = $this->query($query);
		// }
		// else {
		// 	// Consulta manual usando limit/offset
		// 	$result = $this->buildAndRun(0, $count, $query);
		// }

		return $this->getAllOrOne(0, $count);
	}

	public function last(int $count = 0): array|false
	{
		$result = false;

		// Valida cantidades a recuperar
		if ($count <= 0) {
			$count = 1;
		}

		$total = $this->count();
		if ($total > 0) {
			$result = $this->getAllOrOne($total - $count, $count);
		}
		elseif ($total == 0) {
			// El query no es fallido, solo que no hay elementos
			$result = [];
		}

		return $result;
	}

	/**
	 * Retorna elemento al azar. Por diversión...
	 */
	public function rand(): array|false
	{
		$result = false;

		$total = $this->count();
		if ($total > 0) {
			// El indice es base 0, lo que significa que va de 0 a $total - 1
			$index = rand(0, $total - 1);
			$result = $this->fetch($index);
			if (is_array($result)) {
				// print_r($result);
				$result['__index_random'] = $index;
			}
		}
		elseif ($total == 0) {
			$result = []; // El query no es fallido, solo no hay elementos
		}

		return $result;
	}

	/**
	 * Recupera el registro en la posición indicada (si existe)
	 */
	public function fetch(int $offset)
	{
		return $this->getAllOrOne($offset, 1);
	}

	private function evalConditional(array $elements, string $conector): array
	{
		// $data = false;

		$conditional = '';
		$values = [];
		$use_conector = true;

		foreach ($elements as $item) {
			$column = '';
			// echo "{$column} "; print_r($item); echo " >> $conditional<hr>";
			if (!is_array($item)) {
				// No asociado a una columna o valor
				$conditional .= " {$item} ";
				$use_conector = false;
				continue;
			}
			// $value es un arreglo. Si define llave para el primer
			// elemento, la toma como nombre de la columna
			$column = array_key_first($item);
			$partial = '';
			if (is_array($item) && count($item) == 1) {
				// Recupera el valor asignado y lo maneja como simple
				$item = array_shift($item);
			}
			if (!is_numeric($column)) {
				// Adiciona nombre de columna por defecto
				$partial .= "{$column}";
				if (is_array($item)) {
					$partial .= ' in '; // Va a recibir múltiples valores
				}
				elseif (is_null($item)) {
					// Caso especial
					$partial .= ' is null';
				}
				elseif (strpos($item, '%') !== false || strpos($item, '_') !== false) {
					// En este caso usa "like" para validar la respuesta
					// (podría existir un falso positivo en ocasiones, en esos
					// casos evitar usar arreglos con llaves relacionales)
					$partial .= ' like ';
				}
				else {
					$partial .= ' = ';
				}
			}
			// Adiciona marcadores
			if (is_array($item)) {
				$markers = '?' . str_repeat(',?', count($item) - 1);
				$partial .= "({$markers})";
				$values = array_merge($values, array_values($item));
				// echo "COLUMN $column: "; print_r($value); print_r($values_new); echo "<hr>";
			}
			elseif (!is_null($item)) {
				$partial .= "?";
				$values[] = $item;
			}
			// Pendiente, el conector iria antes, no despues y
			// depende de si hay o no un valor manual de conexion asignado.
			if ($partial !== '') {
				if ($conditional !== '' && $use_conector) {
					$conditional .= " {$conector} ";
				}
				$conditional .= $partial;
			}
			// Habilita conector en el siguiente ciclo
			$use_conector = true;
		}

		// echo "FILTERFIND: $conditional<hr>"; print_r($values_new); echo "<hr>";

		return ['sql' => trim($conditional), 'values' => $values];
	}

	public function search(): self
	{
		return $this;
	}

	public function count(): int
	{
		$total = -1;
		$query = $this->build();
		if ($query !== '') {
			$query = "SELECT count(*) as total FROM ($query) as tcontada";
			$result = $this->queryWithValues($query);
			// print_r($result); echo "<hr>";
			if (is_array($result) && isset($result[0]['total'])) {
				$total = $result[0]['total'];
			}
		}

		return $total;
	}

	private function queryWithValues(string $query): array|false
	{
		// Los valores estan ordenados desde el inicio
		$values = array_values($this->valuesBind);
		$values = array_merge(...$values);

		return $this->db->query($query, $values);
	}

	public function set(array $values): self
	{
		$this->valuesBind['where'] = $values;
		return $this;
	}

	public function lastQuery()
	{
		return $this->db->lastQuery();
	}
}