<?php

class PluginAssetlistRecuento extends CommonDBTM
{
    public static $rightname = "recuento";

    // =========================
    // ---- Nombre del tipo ----
    // =========================
    public static function getTypeName($nb = 0) {
        return __CLASS__;
    }

    // ==============================
    // ---- Nombre de la pestaña ----
    // ==============================
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if (!PluginAssetlistAssetlist::canView()) {
            return '';
        }
        return self::createTabEntry('Recuento de activos');
    }

    // ==========================================
    // ---- Imprimir contenido de la pestaña ----
    // ==========================================
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        switch ($item->getType()) {
            case PluginAssetlistAssetlist::class:
                static::showItems($item);
                break;
            default:
                if (in_array($item->getType(), PluginAssetlistAssetlist::getTypes())) {
                    static::showForItem($item, $withtemplate);
                }
        }
        return true;
    }

    /**
     * Print enclosure items
     *
     * @param PluginAssetlistAssetlist $assetlist  Assetlist object wanted
     *
     * @return void|boolean (display) Returns false if there is a rights error.
     **/
    public static function showItems(PluginAssetlistAssetlist $assetlist) {   
        /** @var \DBmysql $DB */
        // Acceso a objeto gestor de BBDD
        global $DB;

        // Obtener ID de la lista en el sistema
        $ID = $assetlist->fields['id'];

        // Generar numero aleatorio
        $rand = mt_rand();

        // No se puede recuperar la lista de la BBDD o no se puede leer la lista
        if (!$assetlist->getFromDB($ID) || !$assetlist->can($ID, READ)) {
            return false;
        }

        // Obtener permiso de edicion
        $canedit = $assetlist->canEdit($ID);

        // Obtener iterador de items de la lista
        $items = $DB->request([
            'FROM'   => PluginAssetlistAssetlist_Item::getTable(),
            'WHERE'  => [
                PluginAssetlistAssetlist_Item::$items_id_1 => $ID
            ]
        ]);

        // Recatngulo gris superior => Assetlist - Lista de activos X
        Session::initNavigateListItems(
            PluginAssetlistAssetlist_Item::getType(),
            //TRANS : %1$s is the itemtype name,
            //        %2$s is the name of the item (used for headings of a list)
            sprintf(
                __('%1$s = %2$s'),
                $assetlist->getTypeName(1),
                $assetlist->getName()
            )
        );

        // Si se puede añadir elementos a la lista
        if ($assetlist->canAddItem('itemtype')) {
            
            /*
            =======================
            ---- Primer bloque ----
            =======================

            Encierra el primer formulario de la pestaña para la adición de nuevos elementos al listado
            */
            // Apertura primer bloque
            echo "<div class='firstbloc'>";

            // Apertura formulario
            echo "<form id='plugin-assetlist-item-count' method='post' name='assetlists_form$rand'
                     id='assetlists_form$rand'
                     action='" . Toolbox::getItemTypeFormURL(PluginAssetlistAssetlist_Item::class) . "'>";

            // Apertura tabla
            echo "<table class='tab_cadre_fixe'>";
            
            // ======================================
            // ---- Encabezado "Añadir elemento" ----
            // ======================================

            // Espacio para la colocación del botón que permite el escaneo de QRs
            echo "<tr class=\"tab_bg_1\"><td id=\"plugin-assetlist-qr-count\" class=\"d-flex justify-content-around align-items-center\">";

            echo "<div>";
            echo "<input style=\"margin-right:5px;\" type='checkbox' id='plugin-assetlist-qr-continuous' name='plugin-assetlist-qr-continuous' class='form-check-input'>";
            echo "Mantener cámara activa";
            echo "</div>";

            //echo Html::input("plugin-assetlist-qr", ["id" => "plugin-assetlist-qr"]);
            echo "</tr></td>";
            
            // Cerrar tabla
            echo "</table>";
            // Cerrar formulario
            Html::closeForm();
            // Cerrar primer bloque
            echo "</div>";
        }

        // Convertir iterador a array
        $items = iterator_to_array($items);

        // Si no hay items asociados al listado
        if (!count($items)) {
            // Mostrar tabla vacia
            echo "<table class='tab_cadre_fixe'><tr><th>" . __('No item found') . "</th></tr>";
            echo "</table>";
        
        // Si hay items asociados al listado
        } else {
            // Si podemos editar el listado
            /*
            if ($canedit) {
                // Abrir formulario de acciones masivas
                Html::openMassiveActionsForm('mass' . PluginAssetlistAssetlist_Item::class . $rand);
                // Crear parámetros de acciones masivas
                $massiveactionparams = [
                    'num_displayed'   => min($_SESSION['glpilist_limit'], count($items)),
                    'container'       => 'mass' . PluginAssetlistAssetlist_Item::class . $rand
                ];
                // Mostrar acciones masivas
                Html::showMassiveActions($massiveactionparams);
            }
            */

            // Apertura de tabla
            echo "<table id='plugin-assetlist-table-count' class='tab_cadre_fixehov'>";
            
            // Construcción del header de la tabla
            $header = "<tr>";
            if ($canedit) {
                $header .= "<th width='10'>";
                $header .= Html::getCheckAllAsCheckbox('mass' . PluginAssetlistAssetlist_Item::class . $rand);
                $header .= "</th>";
            }
            $header .= "<th>" . __('Itemtype') . "</th>";
            $header .= "<th>" . _n('Item', 'Items', 1) . "</th>";
            $header .= "<th>" . __("Serial") . "</th>";
            $header .= "<th>" . __("Inventory number") . "</th>";
            $header .= "<th>" . PluginAssetlistAssetlist_Item_Relation::getTypeName(Session::getPluralNumber()) . "</th>";
            $header .= "</tr>";
            
            // Mostrar el header al principio de la tabla
            echo $header;

            // Recorremos cada item como si fuera una columna
            foreach ($items as $row) {
                // Creamos un objeto del tipo indicado por cada array
                $item = new $row['itemtype']();
                // Cargamos en el objeto los datos de la BBDD
                $item->getFromDB($row['items_id']);

                // Abrimos fila
                echo "<tr lass='tab_bg_1'>";

                // Si podemos editar insertamos el checkbox
                if ($canedit) {
                    echo "<td>";
                    Html::showMassiveActionCheckBox(PluginAssetlistAssetlist_Item::class, $row["id"]);
                    echo "</td>";
                }
                // Mostramos el resto de la información de la fila
                echo "<td>" . $item->getTypeName(1) . "</td>";
                echo "<td>" . $item->getLink() . "</td>";
                echo "<td>" . ($item->fields['serial'] ?? "") . "</td>";
                echo "<td>" . ($item->fields['otherserial'] ?? "") . "</td>";
                echo "<td class='relations_list'>";
                echo PluginAssetlistAssetlist_Item_Relation::showListForAssetlistItem($row["id"], $canedit);
                echo "</td>";
                echo "</tr>";
            }

            // Volver a mostrar el header al final de la tabla
            echo $header;
            
            // Cerrar tabla
            echo "</table>";

            // Si puedo editar la lista y hay items
            if ($canedit && count($items)) {
                $massiveactionparams['ontop'] = false;
                Html::showMassiveActions($massiveactionparams);
            }

            // Cierro el formulario si puedo editar
            if ($canedit) {
                Html::closeForm();
            }

            // Obtener lista JavaScript para item de lista de activos
            echo PluginAssetlistAssetlist_Item_Relation::getListJSForAssetlistItem($assetlist, $canedit);
        }
    }

    /**
     * Print an HTML array of assetlists associated to an object
     *
     * @since 9.5.2
     *
     * @param CommonDBTM $item         CommonDBTM object wanted
     * @param integer    $withtemplate not used (to be deleted)
     *
     * @return void
     **/
    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        // Obtener tipo (clase) del item
        $itemtype = $item->getType();

        // Obtener ID del item
        $ID       = $item->fields['id'];

        // Si no podemos ver listas o no se puede leer el item
        if (!PluginAssetlistAssetlist::canView() || !$item->can($ID, READ)) {
            return;     // Volver
        }

        // Obtener permiso de actualización
        $canedit = $item->can($ID, UPDATE);

        // Generar numero aleatorio
        $rand = mt_rand();


        $iterator = PluginAssetlistAssetlist_Item::getListForItem($item);
        $number = count($iterator);

        $assetlists = [];
        $used      = [];
        foreach ($iterator as $data) {
            $assetlists[$data['id']] = $data;
            $used[$data['id']]      = $data['id'];
        }
        if ($canedit && ($withtemplate != 2)) {
            echo "<div class='firstbloc'>";
            echo "<form name='assetlistitem_form$rand' id='assetlistitem_form$rand' method='post'
                action='" . Toolbox::getItemTypeFormURL(PluginAssetlistAssetlist_Item::class) . "'>";
            echo "<input type='hidden' name='items_id' value='$ID'>";
            echo "<input type='hidden' name='itemtype' value='$itemtype'>";

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><th colspan='2'>" . __('Add to an assetlist') . "</th></tr>";

            echo "<tr class='tab_bg_1'><td>";
            PluginAssetlistAssetlist::dropdown([
                'entity'  => $item->getEntityID(),
                'used'    => $used
            ]);

            echo "</td><td class='center'>";
            echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }

        echo "<div class='spaced'>";
        if ($withtemplate != 2) {
            if ($canedit && $number) {
                Html::openMassiveActionsForm('mass' . PluginAssetlistAssetlist_Item::class . $rand);
                $massiveactionparams = ['num_displayed' => min($_SESSION['glpilist_limit'], $number),
                    'container'     => 'mass' . PluginAssetlistAssetlist_Item::class . $rand
                ];
                Html::showMassiveActions($massiveactionparams);
            }
        }
        echo "<table class='tab_cadre_fixehov'>";

        $header = "<tr>";
        if ($canedit && $number && ($withtemplate != 2)) {
            $header    .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . PluginAssetlistAssetlist_Item::class . $rand);
            $header    .= "</th>";
        }

        $header .= "<th>" . __('Name') . "</th>";
        $header .= "<th>" . PluginAssetlistAssetlist_Item_Relation::getTypeName(Session::getPluralNumber()) . "</th>";
        $header .= "</tr>";

        if ($number > 0) {
            echo $header;
            Session::initNavigateListItems(
                PluginAssetlistAssetlist_Item::class,
                //TRANS : %1$s is the itemtype name,
                //         %2$s is the name of the item (used for headings of a list)
                sprintf(
                    __('%1$s = %2$s'),
                    $item->getTypeName(1),
                    $item->getName()
                )
            );
            
            foreach ($assetlists as $data) {
                $cID         = $data["id"];
                Session::addToNavigateListItems(PluginAssetlistAssetlist_Item::class, $cID);
                $assocID     = $data["linkid"];
                $app         = new PluginAssetlistAssetlist();
                $app->getFromResultSet($data);
                echo "<tr class='tab_bg_1" . ($app->fields["is_deleted"] ? "_2" : "") . "'>";
                if ($canedit && ($withtemplate != 2)) {
                    echo "<td width='10'>";
                    Html::showMassiveActionCheckBox(PluginAssetlistAssetlist_Item::class, $assocID);
                    echo "</td>";
                }
                echo "<td class='b'>";
                $name = $app->fields["name"];
                if (
                    $_SESSION["glpiis_ids_visible"]
                    || empty($app->fields["name"])
                ) {
                    $name = sprintf(__('%1$s (%2$s)'), $name, $app->fields["id"]);
                }
                echo "<a href='" . PluginAssetlistAssetlist::getFormURLWithID($cID) . "'>" . $name . "</a>";
                echo "</td>";
                echo "<td class='relations_list'>";
                echo PluginAssetlistAssetlist_Item_Relation::showListForAssetlistItem($assocID, $canedit);
                echo "</td>";

                echo "</tr>";
            }
            echo $header;
            echo "</table>";
        } else {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>" . __('No item found') . "</th></tr></table>";
        }

        echo "</table>";
        if ($canedit && $number && ($withtemplate != 2)) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }
        echo "</div>";

        echo PluginAssetlistAssetlist_Item_Relation::getListJSForAssetlistItem($item, $canedit);
    }
}
