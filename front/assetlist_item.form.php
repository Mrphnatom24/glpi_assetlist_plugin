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

$iapp = new PluginAssetlistAssetlist_Item();
$app = new PluginAssetlistAssetlist();

// Acción a realizar es una actualización
if (isset($_POST['update'])) {
    
    $iapp->check($_POST['id'], UPDATE); // Comprobar que se tiene permiso de actualización sobre el item con el id indicado
   
    //update existing relation
    if ($iapp->update($_POST)) {
        $url = $app->getFormURLWithID($_POST['assetlists_id']);
    } else {
        $url = $iapp->getFormURLWithID($_POST['id']);
    }
    Html::redirect($url);

// Accion a realizar es añadir un item
} else if (isset($_POST['add'])) {

    // Si valor del texto de QR no es vacio
    if ($_POST["plugin-assetlist-qr-text"] != "") {

        // Obtener array JSON
        $json_names = $_POST['plugin-assetlist-qr-text'];

        //echo "json_names: ";var_dump($json_names);echo '<br><br>';

        // Eliminar valores indeseados de POST
        unset($_POST["plugin-assetlist-qr-text"]);
        unset($_POST["plugin-assetlist-qr-continuous"]);

        // Limpiar la string para obtener JSON sin problemas
        $json_names = str_replace("\\", "", $json_names); // Eliminar contrabarras del JSON
        $not_found = [];

        $names = json_decode($json_names, true);
        
        //echo "names: ";var_dump($names);echo '<br><br>';

        foreach ($names as $name) {
            //echo "{$name}<br><br>";

            // Obtener item asociado al nombre
            $item = PluginAssetlistTools::getItemByName($name);

            //var_dump($item);

            // Si se pudo recuperar el item
            if (is_array($item)) {
                // Modificar valores de POST indicando los valores del item encontrado
                $_POST['itemtype'] = $item['itemtype'];
                $_POST['items_id'] = $item['id'];

                $iapp->check(-1, CREATE, $_POST);       // (id, permiso, input) -> Comprobacion del permiso de "creacion"
                $iapp->add($_POST);                     // Añadir item
            
            // Si no se pudo recuperar el item
            } else {
                // Añadir nombre del item a elementos no enecontrados
                $not_found[] = $name;
            }

            //echo '--------------------------------<br><br>';
        }

        if (count($not_found) != 0) {

            $message_not_found = '';
            $len_not_found = count($not_found);
            for ($i = 0; $i < $len_not_found; $i++) {
                $message_not_found .= "\"" . $not_found[$i] . "\"";
                if ($i < $len_not_found - 1) {
                    $message_not_found .= ", ";
                }
            }

            Session::addMessageAfterRedirect(
                sprintf(__('%1$s: %2$s'),
                        'No se encontraron los siguientes elementos',
                        $message_not_found
                    ),
                    message_type: ERROR
                );
        }

    } else {
        unset($_POST["plugin-assetlist-qr-text"]);
        unset($_POST["plugin-assetlist-qr-continuous"]);
        $iapp->check(-1, CREATE, $_POST);       // (id, permiso, input) -> Comprobacion del permiso de "creacion"
        $iapp->add($_POST);                     // Añadir item
    }
    
    Html::back();                           // Volver a la página anterior  

// Acción a realizar es purgar
} else if (isset($_POST['purge'])) {
    $iapp->check($_POST['id'], PURGE);      // Comprobar permiso para purgar
    $iapp->delete($_POST, 1);               // Borrar registro
    $url = $app->getFormURLWithID($_POST['assetlists_id']); 
    Html::redirect($url);

// La acción actual no coincide con las anteriores
} else {
    Html::displayErrorAndDie("lost");       // Mostrar error
}


