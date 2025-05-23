# miframe-commons

Este repositorio contiene una colección de librerías de uso común para proyectos de PHP.

El directorio `demos` contiene ejemplos de uso y el directorio `docs` contiene más información sobre estas librerías.


## miframe_autoload()

Facilita la definición de enrutamientos para las clases requeridas.

Ejemplo de uso:
````
require_once 'miframe/commons/autoload.php';
// Enruta todas las clases que comiencen con "miFrame\Commons\Core\" al directorio indicado.
miframe_autoload()->register('miFrame\Commons\Core\*', __DIR__ . '/classes/*.php');
````
Más información sobre este helper está disponible en [docs/miframe-commons-autoload.md](https://github.com/jjmejia/miframe-commons/blob/main/docs/miframe-commons-autoload.md).


## miframe_server()

Provee métodos y propiedades relacionadas con variables (`$_SERVER`), características (por Ej. la dirección IP del usuario) y acciones asociadas con la sesión Web o de Consola que se encuentre en curso.

Ejemplo de uso:
````
require_once 'miframe/commons/helpers.php';
// Imprime contenido de $_SERVER['REQUEST_METHOD']
echo miframe_server()->get('REQUEST_METHOD');
````

Más información sobre este helper está disponible en [docs/miframe-commons-server.md](https://github.com/jjmejia/miframe-commons/blob/main/docs/miframe-commons-server.md).


## miframe_html()

Facilita el manejo de recursos CSS y similares, evitando enviar al navegador información duplicada.

Ejemplo de uso:
````
require_once 'miframe/commons/helpers.php';
// Adiciona un archivo CSS existente en disco.
// Aunque invoque este mismo archivo varias veces, lo incluye solamente una vez.
miframe_html()->cssLocal($filename_css);
// Envía estilos al navegador
echo miframe_html()->cssExport();
````

## miframe_render() y miframe_view()

Provee métodos para el uso organizado de vistas en aplicaciones PHP.

Entiéndase como "vista" el script usado como frontend o interfaz gráfica de usuario (GUI) para una aplicación o página Web. Este es uno de los pilares de los modelos MVC (Modelo-Vista-Controlador).

Ejemplo de uso:
````
require_once 'miframe/commons/helpers.php';
// Muestra vista $viewname
echo miframe_render()->view($viewname, $params);
// Forma alternativa de visualizar la misma vista
echo miframe_view($viewname, $params);
// Muestra en pantalla contenido de la variable $_REQUEST
miframe_dump($_REQUEST);
````


**_Importante:_**
_Hasta nuevo aviso, esta colección de librerías se encuentra en continuo proceso de Desarrollo._