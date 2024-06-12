# GLPi Assetlist Plugin

## Introducción

Este plugin implementa listas de activos mediante las cuales
se pueden realizar actualizaciones masivas de los elementos
que las componen. También se puede hacer comprobaciones de los
elementos que las componen mediante el escaneo del código QR asociado
a cada activo.

## Documentación

### Actualizaciones conjuntas a los elementos de la lista

#### Campos que no actualizan

Los campos que se descartan del input usado para 
actualizar todos los elementos de las listas son los siguinetes:
- Nombre del elemento. Son considerados un campo identificativo esencial ya que de no ser asi si realizaramos una actualización de por ejemplo la lista llamada "LST-001" todos los lementos que la componen también tendrían renombrados su campo de nombre.

#### Mediante página del formulario

El formulario de la lista de activos puede resultar ser lo más poderoso para realizar actualizaciones sobre varios campos porque toda actualización realizada a la lista se refleja también sobre los campos de sus elementos. Esta actualización se lleva a cabo antes de actualizar la propia lista debido a que no existe ningún gancho POST_UPDATE.

#### Mediante acciones masivas

Mediante el botón "Acciones" de la página de listados se puede indicar un campo para actualizar. No es tan potente como el formulario.

### Añadir un activo mediante QR

En la pestaña "Elementos" encontrará un campo de texto con un botón que pone "Escanear QR" al lado.
Una vez lo pulsé el texto del QR se insertará en un campo de texto escondido. Luego solo tendrá que pulsar el botón "Añadir" 
para que se añada automaticamente el item.

### Recuento de activos de una lista

En el formulario de la lista de activos encontrará, al igual que en el apartado anterior, otro botón 
con el texto "Escanear QR" pero con una funcionalidad diferente. Después de que escanee el QR, eliminará de
la tabla html la fila del elemento que se corresponda con el QR escaneado.

## Instalación

```sh
cd /my/glpi/deployment/main/directory/plugins
git clone https://github.com/Mrphnatom24/glpi_assetlist_plugin.git
```

Después de realizar la instalación principal sería necesario añadir el tipo "Assetlist" a los tipos de análisis de impacto en "Configuración > General > Análisis de impacto". De esta manera habilitamos la pestaña "Análisis de impacto" para nuestro objeto "Assetlist".
