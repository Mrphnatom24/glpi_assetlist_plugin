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

// Definir id vacio si no está definido
if (empty($_GET["id"])) {
    $_GET["id"] = "";
}

// Definir valor plantilla si no está definido
if (!isset($_GET["withtemplate"])) {
    $_GET["withtemplate"] = "";
}

// Crear nuevo objeto lista
$app = new PluginAssetlistAssetlist();

// La acción es añadir
if (isset($_POST["add"])) {

    // Comprobar permiso para añadir
    $app->check(-1, CREATE, $_POST);

    // Si se consigue añadir la nueva lista
    if ($newID = $app->add($_POST)) {

        // Registrar evento de creación de la lista en el historico del objeto
        Event::log(
            $newID,
            "assetlist",
            4,
            "inventory",
            sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"])
        );

        // Si el objeto se creo de nuevo
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($app->getLinkURL());     // Redirigir a la URL de la clase de la lista
        }
    }
    // Volver a la página anterior
    Html::back();
    
} else if (isset($_POST["delete"])) {
    $app->check($_POST["id"], DELETE);
    $app->delete($_POST);

    Event::log(
        $_POST["id"],
        "assetlist",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s deletes an item'), $_SESSION["glpiname"])
    );
    $app->redirectToList();
} else if (isset($_POST["restore"])) {
    $app->check($_POST["id"], DELETE);

    $app->restore($_POST);
    Event::log(
        $_POST["id"],
        "assetlist",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s restores an item'), $_SESSION["glpiname"])
    );
    $app->redirectToList();
} else if (isset($_POST["purge"])) {
    $app->check($_POST["id"], PURGE);

    $app->delete($_POST, 1);
    Event::log(
        $_POST["id"],
        "assetlist",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s purges an item'), $_SESSION["glpiname"])
    );
    $app->redirectToList();
} else if (isset($_POST["update"])) {
    $app->check($_POST["id"], UPDATE);

    $app->update($_POST);
    Event::log(
        $_POST["id"],
        "assetlist",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );
    Html::back();
} else {
    $menus = ["assets", "assetlist"];
    PluginAssetlistAssetlist::displayFullPageForItem($_GET['id'], $menus, [
        'withtemplate' => $_GET['withtemplate']
    ]);
}

