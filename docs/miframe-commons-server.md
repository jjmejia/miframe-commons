# miframe_server()

A través de la Clase `ServerData`, la función `miframe_server()` provee métodos y propiedades relacionadas con variables (`$_SERVER`), características (por Ej. la dirección IP del usuario) y acciones asociadas con la sesión Web o de Consola que se encuentre en curso.

Este helper puede usarse en una de dos formas:

````
echo miframe_server()->get('REQUEST_METHOD');
````

O en caso que se invoque multiples veces dentro del mismo bloque de código, puede asignarse a una variable local para su uso:

````
$server = miframe_server();
echo $server->get('REQUEST_METHOD');
````

## Métodos disponibles

A la fecha (agosto/2024) la clase ServerData proporciona los siguientes métodos públicos:

* `accessDir` -- Adiciona paths al listado de directorios permitidos para crear subdirectorios.
* `browser` -- Provee información relativa al navegador Web (browser) usado por el usuario.
* `checkPoint` -- Tiempo transcurrido desde la anterior invocación a este método.
* `client` -- Dirección IP del cliente remoto.
* `createTempSubdir` -- Crea el subdirectorio indicado dentro del directorio temporal.
* `documentRoot` -- Ruta física del directorio Web.
* `documentRootSpace` -- Espacio libre en el disco donde se encuentra el directorio Web.
* `executionTime` -- Tiempo transcurrido desde el inicio del script (microsegundos).
* `get` -- Valor de elemento contenido en la variable superglobal $_SERVER de PHP.
* `hasAccessTo` -- Valida que el archivo o directorio dado esté en la lista de permitidos.
* `host` -- URL asociada al servidor Web para la consulta actual.
* `ip` -- Dirección IPv4 asociada al servidor Web.
* `isLocalhost` -- Indica si la consulta se realiza directamente en el servidor Web (localhost).
* `isWeb` -- Indica si está ejecutando desde un Web Browser.
* `mkdir` -- Crea un directorio en el servidor.
* `name` -- Nombre real del servidor Web.
* `path` -- Path Web consultado por el usuario.
* `pathInfo` -- Segmento del path del URL con información útil cuando se usan URL amigables.
* `purgeFilename` -- Estandariza formato del path físico indicado.
* `purgeURLPath` -- Estandariza formato del URL path indicado.
* `rawInput` -- Información recibida en el "body" de la consulta realizada por el usuario.
* `relativePath` -- Path al directorio que contiene el script ejecutado en la consulta actual, relativo al directorio Web.
* `removeDocumentRoot` -- Remueve ruta al directorio Web.
* `script` -- Ruta física del script ejecutado.
* `scriptDirectory` -- Retorna la ruta física real o esperada para el archivo/directorio indicado.
* `self` -- Path al script ejecutado en la consulta actual, relativo al directorio Web.
* `software` -- Descripción del servidor Web.
* `startAt` -- Tiempo en que inicia la ejecución del script.
* `tempDir` -- Asigna o retorna valor del directorio temporal a usar.
* `tempDirSpace` -- Espacio libre en el disco donde se encuentra el directorio temporal.
* `useHTTPSecure` -- Indica si la consulta actual se hizo con protocolo HTTPS.