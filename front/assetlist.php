<?php

// Incluir archivo de inicialización de variables
include ('../../../inc/includes.php');

// Crear nuevo objeto Plugin
$plugin = new Plugin();

// Si el plugin actual no está activado
if (!$plugin->isActivated('assetlist')) {
   // Mostrar error
   Html::displayNotFoundError();
}

// Comprobar si el usuario está loggeado
Session::checkLoginUser();

$config = PluginAssetlistConfig::getConfig();

// Imprimir encabezado de página
Html::header(PluginAssetlistAssetlist::getTypeName(Session::getPluralNumber()), '', $config['menu'], PluginAssetlistAssetlist::class);

// Mostrar todos los objetos Assetlist
Search::show(PluginAssetlistAssetlist::class);

// Imprimir pie de página
Html::footer();
