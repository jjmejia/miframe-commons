<?php

/**
 * Clase para manejo de Layout, soporte para RenderView.
 *
 * @author John Mejía
 * @since Enero 2025
 */

namespace miFrame\Commons\Support;

class RenderLayout
{
	/**
	 * @var string $viewname Nombre de la vista a usar como Layout
	 */
	private string $viewname = '';

	/**
	 * @var array $params Valores a usar en el Layout
	 */
	private array $params = [];

	/**
	 * @var string $contentViewName Nombre de la variable con el contenido de vistas previas
	 */
	private string $contentViewName = '';

	/**
	 * @var bool $alreadyUsed TRUE para indicar que el Layout fue usado
	 */
	private bool $alreadyUsed = false;

	/**
	 * Asigna vista a usar como layout.
	 *
	 * El "layout" es la vista que habrá de contener todas las vistas
	 * ejecutadas a través de $this->view().
	 *
	 * @param string $viewname Nombre/Path de la vista a usar como Layout.
	 * @param string $content_view_name Nombre de la variable que va a contener el
	 * 									texto previamente renderizado.
	 */
	public function config(string $viewname, string $content_view_name): self
	{
		$this->viewname = $viewname;
		$this->contentViewName = trim($content_view_name);
		$this->params = [];

		return $this;
	}

	/**
	 * Remueve el nombre de la vista asociado al layout.
	 */
	public function remove()
	{
		$this->viewname = '';
	}

	/**
	 * Nombre de la vista a usar como Layout.
	 *
	 * @return string Nombre de la vista
	 */
	public function viewName(): string
	{
		return $this->viewname;
	}

	/**
	 * Habilita el layout actual para su uso, incluso después de haber sido usado en la vista actual.
	 */
	public function reset()
	{
		$this->alreadyUsed = false;
	}

	/**
	 * Valida si está habilitado para renderizar el Layout.
	 *
	 * @return bool TRUE si puede renderizar el Layout, FALSE en otro caso.
	 */
	public function waitingForDeploy(): bool
	{
		return (!$this->alreadyUsed && !empty($this->viewname));
	}

	/**
	 * ASigna valor a la variable a usar como contenedor de las vistas renderizadas.
	 *
	 * @param string $content Contenido de las vistas renderizadas.
	 */
	public function setContentView(string &$content)
	{
		// Marca este Layout como "ya usado"
		$this->alreadyUsed = true;
		if ($this->contentViewName != '') {
			$this->params[$this->contentViewName] = &$content;
		}
	}

	/**
	 * Remueve contenido de las vistas renderizadas y libera memoria usada.
	 */
	public function removeContentView()
	{
		if ($this->contentViewName != '') {
			unset($this->params[$this->contentViewName]);
		}
	}

	/**
	 * Registra parámetros (valores) a usar para generar el layout.
	 *
	 * @param array $params Arreglo con valores a adicionar.
	 *
	 * @return array Arreglo con todos los valores registrados.
	 */
	public function values(array $params = []): array
	{
		// Los nuevos valores remplazan los anteriores
		if (count($params) > 0) {
			$this->params = $params + $this->params;
		}

		return $this->params;
	}

	/**
	 * Recupera valor de parámetro asignado al layout.
	 *
	 * Se provee este método para su uso en vistas. Cuando se genera el
	 * layout, igual que ocurre con las vistas, el valor de cada parámetro
	 * se exporta para su uso directo en la vista. No es necesario usar
	 * este método en el script usado para el layout.
	 *
	 * @param string $name Nombre del parámetro a recuperar.
	 * @param mixed $default Valor a retornar si el parámetro no existe.
	 *
	 * @return mixed Valor del parámetro solicitado.
	 */
	public function get(string $name, mixed $default = ''): mixed
	{
		return (
			array_key_exists($name, $this->params) ?
				$this->params[$name] :
				$default
			);
	}

}
