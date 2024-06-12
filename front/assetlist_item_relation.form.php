<?php

include('../../../inc/includes.php');

// Crear nuevo objeto Plugin
$plugin = new Plugin();

// Si el plugin actual no está activado
if (!$plugin->isActivated('assetlist')) {
   // Mostrar error
   Html::displayNotFoundError();
}

// Comprobar si el usuario está loggeado
Session::checkLoginUser();

//Session::checkCentralAccess();

$app_item_rel = new PluginAssetlistAssetlist_Item_Relation();

if (isset($_POST['add'])) {
    $app_item_rel->check(-1, CREATE, $_POST);
    $app_item_rel->add($_POST);
    Html::back();
} else if (isset($_POST['purge'])) {
    $app_item_rel->check($_POST['id'], PURGE);
    $app_item_rel->delete($_POST, 1);
    Html::back();
}

Html::displayErrorAndDie("lost");
