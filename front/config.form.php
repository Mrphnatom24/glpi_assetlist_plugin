<?php

/*
Este es un archivo que permite la actualización de las variables de configuración del plugin "assetlist". 
Su uso se debe a que GLPI no ofrece mecanismos de almacenamiento de arrays como variables de configuración.
Para conseguir el almacenamiento del array se realiza su transformación a JSON. Se está intentando buscar una 
solución que consista en redireccionar a la pagina de configuración normal con todos los datos transformados 
en un formato legible para el sistema.
*/

// Not sure if necessary to include
include ('../../../inc/includes.php');

// Check if plugin is enabled
$plugin = new Plugin();
if (!$plugin->isActivated('assetlist')) {
   Html::displayNotFoundError();
}

// Check if logged ing
Session::checkLoginUser();

// Check if I can update conf
Session::checkRight("config", UPDATE);

// Empty associative array -> used for updating configuration
$updateConf = [];

//Html::header("TITLE", PluginAssetlistConfig::getFormURL());

// Open form
//echo '<form action="' . Config::getFormURL() . '" method="post">';

// Menu where access shall be displayed
if (!empty($_POST["menu"])) {
    $updateConf["menu"] = $_POST["menu"];
    //echo Html::hidden('menu', ['value' => $_POST["menu"]]);
}

// allowed itemtypes for assetlists
if (!empty($_POST['assetlist_types'])) {
    $v = json_encode($_POST['assetlist_types']);
    //$_POST['assetlist_types'] = $v;
    $updateConf['assetlist_types'] = $v;
    //echo Html::hidden('assetlist_types', ['value' => $v]);
}

// allowed relation types for assetlist items
if (!empty($_POST['assetlist_relation_types'])) {
    $v = json_encode($_POST['assetlist_relation_types']);
    //$_POST['assetlist_relation_types'] = $v;
    $updateConf['assetlist_relation_types'] = $v;
    //echo Html::hidden('assetlist_relation_types', ['value' => $v]);
}

//echo Html::submit(__('Save'), ['name' => 'update', 'class' => 'btn btn-primary', 'id' => 'submit']);
//Html::redirect(Config::getFormURL(true));     // Redirect does not pass $_POST to next page

//Html::closeForm();

//Html::footer();

// Update plugin conf through array
Config::setConfigurationValues("plugin:Assetlist", $updateConf);

// Return to previous page
Html::back();