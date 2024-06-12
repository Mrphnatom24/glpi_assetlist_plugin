# GLPI Assetlist Plugin

## Introducción

Este complemento implementa listas de activos que se pueden utilizar para realizar actualizaciones masivas de los elementos que las componen. También pueden añadirse nuevos elementos a la lista mediante su código QR o se puede hacer un recuento de los mismos activos que componen la lista mediante el código anteriormente mencionado.

This plugin implements asset lists that can be used to perform bulk updates to the elements that comprise them. New items can also be added to the list using its QR code or a count of the same assets that make up the list can be made using the aforementioned code.

## Documentación

### Actualizaciones conjuntas a los elementos de la lista

#### Campos que no actualizan

Los campos que se descartan del input usado para actualizar todos los elementos de las listas son los siguientes:
- Campo "name". Es considerado un campo identificativo esencial
  ya que de no ser asi si realizaramos una actualización por ejemplo de
  la lista llamada "LST-001" todos los elementos que la componen también
  tendrían actualizados el valor de su campo "name" a "LST-001".

#### Mediante pestaña de actualización masiva

La pestaña de actualización masiva de una lista permite ejecutar modificaciones sobre todos los elementos que componen la lista, especificamente a los campos que tienen todos ellos en común, exceptuando los anteriormente mencionados.

### Añadir un activo mediante QR

En la pestaña "Elementos" encontrará un campo de texto con un botón que pone "Escanear QR" al lado. Se abrirá un diálogo que mostrará la cámara de su dispositivo. 

### Recuento de activos de una lista

En el formulario de la lista de activos encontrará, al igual que en el apartado anterior, otro botón 
con el texto "Escanear QR" pero con una funcionalidad diferente. Después de que escanee el QR, eliminará del listado la fila del elemento que se corresponda con el QR escaneado.

## Instalación

```sh
cd /my/glpi/deployment/main/directory/plugins
git clone https://github.com/Mrphnatom24/glpi_assetlist_plugin.git
```

Después de realizar la instalación principal sería necesario añadir el tipo "Assetlist" a los tipos de análisis de impacto en "Configuración > General > Análisis de impacto". De esta manera habilitamos la pestaña "Análisis de impacto" para nuestro objeto "Assetlist".
