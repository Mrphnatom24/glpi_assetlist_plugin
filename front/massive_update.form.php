<?php

// Uso de clase externa
use Glpi\Event;

// Incluir archivo de inicialización de variables
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

// Comprobar permiso para leer listas
Session::checkRight('assetlist', READ);

//var_dump($_POST);echo '<br><br>';
//Html::displayErrorAndDie("");

unset($_POST['entities_id']);

// Definir id vacio si no está definido
if (empty($_GET["id"])) {
    $_GET["id"] = "";
    //Html::displayErrorAndDie("No se puede realizar una actualización masiva sin una lista ya creada");
}

// Definir valor plantilla si no está definido
if (!isset($_GET["withtemplate"])) {
    $_GET["withtemplate"] = "";
}

// Crear nuevo objeto lista
$app = new PluginAssetlistAssetlist();

/*
¿Cuales son las acciones de $_POST con las que trabaja este archvivo?
- delete    -> se muestra el botón solo si no hay items en la papelera, la acción se produce sobre todos los items
- restore   -> solo se muestra el boton si hay algunos items en la papelera, la acción se produce sobre estos
- purge     -> solo se muestra el boton si hay algunos items en la papelera, la acción se produce sobre estos
- update    -> se actualizan todos los items de la lista estén o no en la papelera
*/

// ================
// ---- BORRAR ----
// ================
if (isset($_POST["delete"])) {

    // Comprobar permiso de borrado de listas
    $app->check($_POST["id"], DELETE);

    // Mandar a borrar todos los items de la lista
    $app::deleteAssetlistItems($_POST['id']);

    //Html::displayErrorAndDie('Fin delete');

    // Registrar borrado en el sistema
    Event::log(
        $_POST["id"],
        "assetlist",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s massively deletes the list items'), $_SESSION["glpiname"])
    );

    // Redirigir a la página de listados
    Html::back();;

// ===================
// ---- RESTAURAR ----
// ===================
} else if (isset($_POST["restore"])) {
    $app->check($_POST["id"], DELETE);

    // Mandar a borrar todos los items de la lista
    $app::restoreAssetlistItems($_POST['id']);
        
    //Html::displayErrorAndDie("Fin restore");
    
    Event::log(
        $_POST["id"],
        "assetlist",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s massively restores an item'), $_SESSION["glpiname"])
    );
    Html::back();

// ================
// ---- PURGAR ----
// ================
} else if (isset($_POST["purge"])) {

    // Comprobar permiso de purga
    $app->check($_POST["id"], PURGE);

    // Mandar a purgar todos los items de la lista
    $app::purgeAssetlistItems($_POST['id']);

    // Registrar purga en el sistema
    Event::log(
        $_POST["id"],
        "assetlist",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s massively purges the list items'), $_SESSION["glpiname"])
    );
    Html::back();

// ====================
// ---- ACTUALIZAR ----
// ====================
} else if (isset($_POST["update"])) {

    // Comprobar permiso de actualización
    $app->check($_POST["id"], UPDATE);

    // Actualizar los items de la lista
    $app::updateAssetlistItems($_POST);

    // Registrar actualización en el sistema
    Event::log(
        $_POST["id"],
        "assetlist",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s massively updates the list items'), $_SESSION["glpiname"])
    );

    Html::back();
} else {
    $menus = ["assets", "assetlist"];
    PluginAssetlistAssetlist::displayFullPageForItem($_GET['id'], $menus, [
        'withtemplate' => $_GET['withtemplate']
    ]);
}

