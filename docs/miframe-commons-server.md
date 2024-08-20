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

* `browser` -- Provee información relativa al navegador Web (browser) usado por el usuario.
* `checkPoint` -- Tiempo transcurrido desde la anterior invocación a este método.***
* `client` -- Dirección IP del cliente remoto.
* `createTempSubdir` -- Crea el subdirectorio indicado dentro del directorio temporal.
* `documentRoot` -- Ruta física del directorio Web.
* `documentRootSpace` -- Espacio libre en el disco donde se encuentra el directorio Web.
* `executionTime` -- Tiempo transcurrido desde el inicio del script (microsegundos).
* `from` -- Trace con el nombre de script y línea desde donde se invoca un evento.
* `get` -- Valor de elemento contenido en la variable superglobal $_SERVER de PHP.
* `host` -- URL asociada al servidor Web para la consulta actual.
* `inDocumentRoot` -- Valida que el archivo/directorio indicado sea subdirectorio del directorio Web.
* `inTempDir` -- Valida que el archivo/directorio indicado sea subdirectorio del directorio temporal.
* `ip` -- Dirección IPv4 asociada al servidor Web.
* `isLocalhost` -- Indica si la consulta se realiza directamente en el servidor Web (localhost).
* `isWeb` -- Indica si está ejecutando desde un Web Browser.
* `log` -- Registra mensaje en el log de errores de PHP y en pantalla (opcional).
* `mkdir` -- Crea un directorio en el servidor.
* `name` -- Nombre real del servidor Web.
* `path` -- Path Web consultado por el usuario.
* `pathInfo` -- Segmento del path del URL con información útil cuando se usan URL amigables.
* `purgeFilename` -- Estandariza formato del path físico indicado.
* `purgePath` -- Estandariza path indicado y remueve elementos no deseados.
* `rawInput` -- Información recibida en el "body" de la consulta realizada por el usuario.
* `relativePath` -- Path al directorio que contiene el script ejecutado en la consulta actual, relativo al directorio Web.
* `removeDocumentRoot` -- Remueve ruta al directorio Web.
* `script` -- Retorna la ruta física del script ejecutado.
* `scriptDirectory` -- Ruta física real o esperada para el archivo/directorio indicado.
* `self` -- Path al script ejecutado en la consulta actual, relativo al directorio Web.
* `software` -- Descripción del servidor Web.
* `startAt` -- Tiempo en que inicia la ejecución del script.
* `tempDir` -- Asigna o retorna valor del directorio temporal a usar.
* `tempDirSpace` -- Espacio libre en el disco donde se encuentra el directorio temporal.
* `useHTTPSecure` -- Indica si la consulta actual se hizo con protocolo HTTPS.
