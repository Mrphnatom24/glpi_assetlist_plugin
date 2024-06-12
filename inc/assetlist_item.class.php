<?php

class PluginAssetlistAssetlist_Item extends CommonDBRelation
{
    use \Glpi\Features\Clonable;

    // ITEM 1 de la relación
    public static $itemtype_1 = PluginAssetlistAssetlist::class;        // Tipo Lista de Activos
    public static $items_id_1 = 'assetlists_id';    // Campo ID del primer item
    public static $take_entity_1 = false;

    // ITEM 2 de la relación
    public static $itemtype_2 = 'itemtype';         // Tipo
    public static $items_id_2 = 'items_id';         // Campo ID del segundo item
    public static $take_entity_2 = true;

    public static function getForeignKeyField() {
        return 'items_id';
    }

    // =============================
    // ---- Relaciones del clon ----
    // =============================
    public function getCloneRelations(): array
    {
        return [
            PluginAssetlistAssetlist_Item_Relation::class
        ];
    }

    // =========================
    // ---- Nombre del tipo ----
    // =========================
    public static function getTypeName($nb = 0) {
        return _n('Item', 'Items', $nb);
    }

    // ==============================
    // ---- Nombre de la pestaña ----
    // ==============================
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if (!PluginAssetlistAssetlist::canView()) {
            return '';
        }

        $nb = 0;
        if ($item->getType() == PluginAssetlistAssetlist::class) {
            if ($_SESSION['glpishow_count_on_tabs']) {
                if (!$item->isNewItem()) {
                    $nb = self::countForMainItem($item);
                }
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
        } else if (in_array($item->getType(), PluginAssetlistAssetlist::getTypes(true))) {
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = self::countForItem($item);
            }
            return self::createTabEntry(PluginAssetlistAssetlist::getTypeName(Session::getPluralNumber()), $nb);
        }

        return '';
    }

    // ==========================================
    // ---- Imprimir contenido de la pestaña ----
    // ==========================================
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        switch ($item->getType()) {
            case PluginAssetlistAssetlist::class:
                self::showItems($item);
                break;
            default:
                if (in_array($item->getType(), PluginAssetlistAssetlist::getTypes())) {
                    self::showForItem($item, $withtemplate);
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
        if (!$assetlist->getFromDB($ID) || !$assetlist->can($ID, READ)
        ) {
            return false;
        }

        // Obtener permiso de edicion
        $canedit = $assetlist->canEdit($ID);

        // Obtener iterador de items de la lista
        $items = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                self::$items_id_1 => $ID
            ]
        ]);

        // Rectangulo gris superior => Assetlist - Lista de activos X
        Session::initNavigateListItems(
            self::getType(),
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
            echo "<form id='plugin-assetlist-item-list' method='post' name='assetlists_form$rand'
                     id='assetlists_form$rand'
                     action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";

            // Apertura tabla
            echo "<table class='tab_cadre_fixe'>";
            
            // ======================================
            // ---- Encabezado "Añadir elemento" ----
            // ======================================
            // Apertura fila
            echo "<tr class='tab_bg_2'>";
            // Apertura columna
            echo "<th colspan='2'>" .
               __('Add an item') . "</th></tr>";    // Cierre columna y fila


            // Espacio para el checkbox que permite mantener la camara activa
            echo "<tr class=\"tab_bg_1\"><td class=\"center\">
                    <div>
                        <input style=\"margin-right:5px;\" type='checkbox' id='plugin-assetlist-qr-continuous' name='plugin-assetlist-qr-continuous' class='form-check-input'>
                        Mantener cámara activa
                    </div>
                </td></tr>";

            // Espacio para la colocación del botón que permite el escaneo de QRs
            echo "<tr class=\"tab_bg_1\">";
            echo "<td id=\"plugin-assetlist-qr-add\" class=\"d-flex justify-content-around align-items-center\">";

            echo Html::hidden("plugin-assetlist-qr-text", [
                "id" => "plugin-assetlist-qr-text",
                "style" => "display:inline;width:auto;"
            ]);
            echo "</td></tr>";

            // Apertura de fila y columna
            echo "<tr class='tab_bg_1'><td class='center'>";
            // Desplegable para seleccionar el tipo de item a mostrar
            Dropdown::showSelectItemFromItemtypes(
                ['items_id_name'   => 'items_id',
                    'itemtypes'       => PluginAssetlistAssetlist::getTypes(true),
                    'entity_restrict' => ($assetlist->fields['is_recursive']
                                      ? getSonsOf(
                                          'glpi_entities',
                                          $assetlist->fields['entities_id']
                                      )
                                       : $assetlist->fields['entities_id']),
                    'checkright'      => true,
                ]
            );
            // Cerrar columna y abrir otra nueva
            echo "</td><td class='center' class='tab_bg_1'>";
            // Input invisible con el ID de la lista de activos
            echo Html::hidden('assetlists_id', ['value' => $ID]);
            // Botón para añadir el activo seleccionado
            echo Html::submit(_x('button', 'Add'), ['name' => 'add']);
            // Cerrar columna y fila
            echo "</td></tr>";
            // Cerrar tabla
            echo "</table>";
            // Cerrar formulario
            Html::closeForm();
            // Cerrar primer bloque
            echo "</div>";
        }

        // Convertir iterador a rray
        $items = iterator_to_array($items);

        // Si no hay items asociados al listado
        if (!count($items)) {
            // Mostrar tabla vacia
            echo "<table class='tab_cadre_fixe'><tr><th>" . __('No item found') . "</th></tr>";
            echo "</table>";
        
        // Si hay items asociados al listado
        } else {
            // Si podemos editar el listado
            if ($canedit) {
                // Abrir formulario de acciones masivas
                Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
                // Crear parámetros de acciones masivas
                $massiveactionparams = [
                    'num_displayed'   => min($_SESSION['glpilist_limit'], count($items)),
                    'container'       => 'mass' . __CLASS__ . $rand
                ];
                // Mostrar acciones masivas
                Html::showMassiveActions($massiveactionparams);
            }

            // Apertura de tabla
            echo "<table class='tab_cadre_fixehov'>";
            
            // Construcción del header de la tabla
            $header = "<tr>";
            if ($canedit) {
                $header .= "<th width='10'>";
                $header .= Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
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
                    Html::showMassiveActionCheckBox(__CLASS__, $row["id"]);
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

        $itemtype = $item->getType();
        $ID       = $item->fields['id'];

        if (
            !PluginAssetlistAssetlist::canView()
            || !$item->can($ID, READ)
        ) {
            return;
        }

        $canedit = $item->can($ID, UPDATE);
        $rand = mt_rand();

        $iterator = self::getListForItem($item);
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
                action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
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
                Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
                $massiveactionparams = ['num_displayed' => min($_SESSION['glpilist_limit'], $number),
                    'container'     => 'mass' . __CLASS__ . $rand
                ];
                Html::showMassiveActions($massiveactionparams);
            }
        }
        echo "<table class='tab_cadre_fixehov'>";

        $header = "<tr>";
        if ($canedit && $number && ($withtemplate != 2)) {
            $header    .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header    .= "</th>";
        }

        $header .= "<th>" . __('Name') . "</th>";
        $header .= "<th>" . PluginAssetlistAssetlist_Item_Relation::getTypeName(Session::getPluralNumber()) . "</th>";
        $header .= "</tr>";

        if ($number > 0) {
            echo $header;
            Session::initNavigateListItems(
                __CLASS__,
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
                Session::addToNavigateListItems(__CLASS__, $cID);
                $assocID     = $data["linkid"];
                $app         = new PluginAssetlistAssetlist();
                $app->getFromResultSet($data);
                echo "<tr class='tab_bg_1" . ($app->fields["is_deleted"] ? "_2" : "") . "'>";
                if ($canedit && ($withtemplate != 2)) {
                    echo "<td width='10'>";
                    Html::showMassiveActionCheckBox(__CLASS__, $assocID);
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


    public function prepareInputForAdd($input) {
        return $this->prepareInput($input);
    }

    public function prepareInputForUpdate($input) {
        return $this->prepareInput($input);
    }

    /**
     * Prepares input (for update and add)
     *
     * @param array $input Input data
     *
     * @return false|array
     */
    private function prepareInput($input) {
        $error_detected = [];

       //check for requirements
        if (
            ($this->isNewItem() && (!isset($input['itemtype']) || empty($input['itemtype'])))
            || (isset($input['itemtype']) && empty($input['itemtype']))
        ) {
            $error_detected[] = __('An item type is required');
        }
        if (
            ($this->isNewItem() && (!isset($input['items_id']) || empty($input['items_id'])))
            || (isset($input['items_id']) && empty($input['items_id']))
        ) {
            $error_detected[] = __('An item is required');
        }
        if (
            ($this->isNewItem() && (!isset($input[self::$items_id_1]) || empty($input[self::$items_id_1])))
            || (isset($input[self::$items_id_1]) && empty($input[self::$items_id_1]))
        ) {
            $error_detected[] = __('An assetlist is required');
        }

        if (count($error_detected)) {
            foreach ($error_detected as $error) {
                Session::addMessageAfterRedirect(
                    $error,
                    true,
                    ERROR
                );
            }
            return false;
        }

        return $input;
    }

    public static function countForMainItem(CommonDBTM $item, $extra_types_where = [])
    {
        $types = PluginAssetlistAssetlist::getTypes();
        $clause = [];
        if (count($types)) {
            $clause = ['itemtype' => $types];
        } else {
            $clause = [new \QueryExpression('true = false')];
        }
        $extra_types_where = array_merge(
            $extra_types_where,
            $clause
        );
        return parent::countForMainItem($item, $extra_types_where);
    }

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        $forbidden[] = 'CommonDBConnexity:unaffect';
        $forbidden[] = 'CommonDBConnexity:affect';
        return $forbidden;
    }

    public static function getRelationMassiveActionsSpecificities()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $specificities              = parent::getRelationMassiveActionsSpecificities();
        $specificities['itemtypes'] = PluginAssetlistAssetlist::getTypes();

        return $specificities;
    }

    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                PluginAssetlistAssetlist_Item_Relation::class,
            ]
        );
    }
}
