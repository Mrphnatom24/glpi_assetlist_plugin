<?php

use \Glpi\Application\View\TemplateRenderer;
use Glpi\Event;
use \Glpi\Features\AssetImage;

/**
 * Assetlists Class
 **/
class PluginAssetlistAssetlist extends CommonDBTM {

    use \Glpi\Features\Clonable;
    use AssetImage;
    use Glpi\Features\DCBreadcrumb;

   // From CommonDBTM
    public $dohistory = true;                   // El objeto tendrá historico
    public static $rightname = 'assetlist';     // Nombre para los permisos
    protected $usenotepad = true;               // El objeto tendrá pestaña para notas

    // ==========================================
    // ---- Funciones modificaciones masivas ----
    // ==========================================

    /**
     * Devuelve un array asociativo para usarse como array de relleno del formulario usando la 
     * clase TemplateRenderer. Se trata de un array asociativo donde el valor de cada clave representa 
     * el valor global de los items de la lista para esa clave. Si los valores de un mismo campo difieren
     * entre al menos dos elementos de la lista, entonces se asignara un valor vacio a dicho campo.
     * 
     * @param integer $ID ID de la lista de la que se quieren sacar los valores predominantes de los items
     * 
     * @return array
     */
    public static function getPredominantValuesAmongListItems(int $ID) {
        $forbidden_keys = ["name", "date_creation", "id", /*"entities_id",*/ "date_mod", "pictures"];

        // Array de claves de la tabla de la lista
        $assetlist_keys = PluginAssetlistTools::getColumnNames(static::class);
        
        // Array de relleno de valores de la lista
        $form_fill_array = array();

        // Obtener iterador de elementos de la lista
        $iter = static::getItemsIterator($ID);

        // Para cada relacion de la lista
        foreach ($iter as $relation) {

            // Crear objeto vacio y rellenarlo mediante la BBDD
            $item = new $relation['itemtype']();
            $item->getFromDB($relation['items_id']);

            // Recorremos las claves de la assetlist
            foreach ($assetlist_keys as $key) {
                // Si la clave no existe en el objeto
                if (!array_key_exists($key, $item->fields)) {
                    // Continuamos a la siguiente iteración
                    continue;
                }

                // Si la clave no existe en nuestro array de relleno
                if (!array_key_exists($key, $form_fill_array)) {

                    // Si la clave no está prohibida
                    if (array_search($key, $forbidden_keys) == "") {

                        //echo "Result(" . $key . "): " . array_search($key, $forbidden_keys) . "<br><br>";

                        // Tomamos el valor de la clave del objeto para nuestro array
                        $form_fill_array[$key] = $item->fields[$key];
                    }
                
                // Si la clave si existe en nuestro array de relleno
                } else {

                    // Si los valores no coinciden
                    if ($form_fill_array[$key] != $item->fields[$key]) {

                        // Actuar según el campo
                        switch ($key) {
                            // Campos numericos
                            case 'is_recursive':
                            case 'locations_id':
                            case 'users_id':
                            case 'users_id_tech':
                            case 'groups_id':
                            case 'groups_id_tech':
                            case 'states_id':
                            case 'is_helpdesk_visible':
                                $form_fill_array[$key] = 0;
                                break;
                            
                            // Campos textuales
                            case 'comment':
                            case 'serial':
                            case 'externalidentifier':
                            case 'otherserial':
                            case 'contact':
                            case 'contact_num':
                                $form_fill_array[$key] = "";
                                break;
                            
                            // Campo de imagenes -> tratamiento especial                            
                            case 'pictures':
                                $form_fill_array[$key] = "[]";
                                break;
                            
                            // Campos eliminados de la lista
                            case "name":
                            case "date_creation":
                            // Campos de la lista no modificados
                            case "id":              // Se debe reasignar el id de la lista tratada al salir de
                            case "entities_id":
                            case "date_mod":
                                unset($form_fill_array[$key]);
                                break;

                            case "is_deleted":
                                $form_fill_array[$key] = true;
                                break;

                            
                            default:
                        }
                    }
                }

            }
        }

        // Reasignar ID de la lista en caso de alteraciones inesperadas
        $form_fill_array['id'] = $ID;
        //var_dump($form_fill_array);
        return $form_fill_array;
    }

    /**
     * Función que devuelve un iterador sobre los items (relaciones) 
     * que componen la lista indicada por el ID
     * 
     * @param int $ID ID de la lista buscada en la BBDD
     * 
     * @return iterator
     */
    public static function getItemsIterator($ID) {
        global $DB;
        // Crear iterador para obtener items de la lista con id = $ID
        return $DB->request([
            'FROM' => PluginAssetlistAssetlist_Item::getTable(),
            'WHERE' => [
                'assetlists_id' => $ID
            ]
        ]);
    }

    /**
     * Actualiza todos los elementos de una lista de activos dado su ID
     * 
     * @param int   $ID     Identificador de la lista de activos
     * @param array $fs     Array $_POST 
     * 
     * @return void
     */
    public static function updateAssetlistItems(array $post) {
        
        // Clase de GLPI para hacer consultas a la BBDD
        global $DB;

        // Obtener ID del listado
        $ID = $post['id'];

        // Obtener copia del array de POST
        $cpost = $post;

        $iter = static::getItemsIterator($ID);

        // Para cada relación/Item
        foreach ($iter as $relation) {

            // Crear objeto vacio y rellenarlo mediante la BBDD
            $item = new $relation['itemtype']();
            $item->getFromDB($relation['items_id']);

            // Crear array de actualización tomando en cuenta los campos comunes
            $update_array = PluginAssetlistTools::buildUpdateArrayFromPOSTAndItemFieldsArray($cpost, $item);

            // Actualizar el item
            $item->update($update_array);
        }
    }

    /**
     * Elimina todos los elementos de una lista de activos dado su ID
     */
    public static function deleteAssetlistItems($ID) {
        // Clase de GLPI para hacer consultas a la BBDD
        global $DB;

        // Crear iterador para obtener items de la lista con id = $ID
        $iter = static::getItemsIterator($ID);

        // Para cada relación/Item
        foreach ($iter as $relation) {

            // Crear objeto vacio y rellenarlo mediante la BBDD
            $item = new $relation['itemtype']();
            $item->getFromDB($relation['items_id']);

            // Crear array de actualización tomando en cuenta los campos comunes
            $update_array = PluginAssetlistTools::buildUpdateArrayFromPOSTAndItemFieldsArray(['is_deleted' => true], $item);

            // Actualizar el item
            $item->delete($update_array);
        }
    }

    /**
     * Purga todos los elementos de una lista de activos dado su ID
     */
    public static function purgeAssetlistItems($ID) {
        // Clase de GLPI para hacer consultas a la BBDD
        global $DB;

        // Crear iterador para obtener items de la lista con id = $ID
        $iter = static::getItemsIterator($ID);

        // Para cada relación/Item
        foreach ($iter as $relation) {

            // Crear objeto vacio y recuperarlo de la BBDD
            $item = new $relation['itemtype']();
            $item->getFromDB($relation['items_id']);

            // Crear array de actualización tomando en cuenta los campos comunes
            $update_array = PluginAssetlistTools::buildUpdateArrayFromPOSTAndItemFieldsArray([], $item);

            // Si el item ya está en la papelera
            if ($item->fields['is_deleted']) {
                // Borrar forzosamente el item de su tabla correspondiente
                $item->delete($update_array, 1);
                // Borrar las relaciones asociadas al item
                $DB->delete(PluginAssetlistAssetlist_Item_Relation::getTable(), 
                    ['assetlists_items_id'=> $relation['items_id']]);
                // Borrar item de la tabla de items
                $DB->delete(PluginAssetlistAssetlist_Item::getTable(),
                    ['items_id' => $relation['items_id']]);
            }
            // Los items que no estén en la papelera los deja tranquilos
        }
    }

    /**
     * Restaura todos los elementos de una lista dado su ID
     */
    public static function restoreAssetlistItems($ID) {
        // Clase de GLPI para hacer consultas a la BBDD
        global $DB;

        // Crear iterador para obtener items de la lista con id = $ID
        $iter = static::getItemsIterator($ID);

        // Para cada relación/Item
        foreach ($iter as $relation) {

            // Crear objeto vacio y rellenarlo mediante la BBDD
            $item = new $relation['itemtype']();
            $item->getFromDB($relation['items_id']);

            // Crear array de actualización tomando en cuenta los campos comunes
            $update_array = PluginAssetlistTools::buildUpdateArrayFromPOSTAndItemFieldsArray(['is_deleted' => false], $item);

            // Actualizar el item
            $item->restore($update_array);
        }
    }

    // =========================
    // ---- Nombre del tipo ----
    // =========================
    static function getTypeName($nb = 0) {
        //return 'Assetlist Type';
        return _n('Assetlist', 'Assetlists', $nb);
    }

    // =========================
    // ---- Nombre del menú ----
    // =========================
    static function getMenuName() {
        return 'Lista de activos';
    }

    // =============================
    // ---- Relaciones del clon ----
    // =============================
    public function getCloneRelations(): array
    {
        return [
            PluginAssetlistAssetlist_Item::class,
            Contract_Item::class,
            Document_Item::class,
            Infocom::class,
            Notepad::class,
            KnowbaseItem_Item::class
        ];
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong)
         ->addImpactTab($ong, $options)
         ->addStandardTab(PluginAssetlistAssetlist_Item::class, $ong, $options)
         ->addStandardTab(PluginAssetlistRecuento::class, $ong, $options)
         ->addStandardTab(PluginAssetlistMassive_Update::class, $ong, $options)
         ->addStandardTab('Contract_Item', $ong, $options)
         ->addStandardTab('Document_Item', $ong, $options)
         ->addStandardTab('Infocom', $ong, $options)
         ->addStandardTab('Certificate_Item', $ong, $options)
         ->addStandardTab('Domain_Item', $ong, $options)
         ->addStandardTab('KnowbaseItem_Item', $ong, $options)
         ->addStandardTab('Ticket', $ong, $options)
         ->addStandardTab('Item_Problem', $ong, $options)
         ->addStandardTab('Change_Item', $ong, $options)
         ->addStandardTab('ManualLink', $ong, $options)
         ->addStandardTab('DatabaseInstance', $ong, $options)
         ->addStandardTab('Notepad', $ong, $options)
         ->addStandardTab('Log', $ong, $options);

        return $ong;
    }


    // Prepara input para cuando se va a añadir un objeto
    public function prepareInputForAdd($input) {
        $input = parent::prepareInputForAdd($input);
        return $this->managePictures($input);
    }

    // Prepara input para cuando se a actualizar un objeto
    public function prepareInputForUpdate($input) {
        $input = parent::prepareInputForUpdate($input);
        return $this->managePictures($input);
    }

    /**
     * Print the assetlist form
     *
     * @param $ID        integer ID of the item
     * @param $options   array
     *     - target filename : where to go when done.
     *     - withtemplate boolean : template or basic item
     *
     * @return boolean item found
     */
    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display('@assetlist/pages/assetlist.html.twig', [
            'item'   => $this,
            'params' => $options,
        ]);
        return true;
    }

    public function rawSearchOptions()
    {
        // Opciones de busqueda de la clase padre
        $tab = parent::rawSearchOptions();

        // Campo comentario
        $tab[] = [
            'id'            => '4',
            'table'         => self::getTable(),
            'field'         =>  'comment',
            'name'          =>  __('Comments'),
            'datatype'      =>  'text'
        ];

        // Campo localización
        $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

        // Campo Item asociado a la lista
        $tab[] = [
            'id'            => '5',
            'table'         =>  PluginAssetlistAssetlist_Item::getTable(),
            'field'         => 'items_id',
            'name'               => _n('Associated item', 'Associated items', 2),
            'nosearch'           => true,
            'massiveaction' => false,
            'forcegroupby'  =>  true,
            'additionalfields'   => ['itemtype'],
            'joinparams'    => ['jointype' => 'child']
        ];

        // Campo nombre del usuario
        $tab[] = [
            'id'            => '6',
            'table'         => User::getTable(),
            'field'         => 'name',
            'name'          => User::getTypeName(1),
            'datatype'      => 'dropdown'
        ];

        $tab[] = [
            'id'            => '8',
            'table'         => Group::getTable(),
            'field'         => 'completename',
            'name'          => Group::getTypeName(1),
            'condition'     => ['is_itemgroup' => 1],
            'datatype'      => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '14',
            'table'              => $this->getTable(),
            'field'              => 'contact',
            'name'               => __('Alternate username'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '15',
            'table'              => $this->getTable(),
            'field'              => 'contact_num',
            'name'               => __('Alternate username number'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'            => '24',
            'table'         => User::getTable(),
            'field'         => 'name',
            'linkfield'     => 'users_id_tech',
            'name'          => __('Technician in charge'),
            'datatype'      => 'dropdown',
            'right'         => 'own_ticket'
        ];

        $tab[] = [
            'id'            => '49',
            'table'         => Group::getTable(),
            'field'         => 'completename',
            'linkfield'     => 'groups_id_tech',
            'name'          => __('Group in charge'),
            'condition'     => ['is_assign' => 1],
            'datatype'      => 'dropdown'
        ];

        $tab[] = [
            'id'            => '9',
            'table'         => self::getTable(),
            'field'         => 'date_mod',
            'name'          => __('Last update'),
            'massiveaction' => false,
            'datatype'      => 'datetime'
        ];

        $tab[] = [
            'id'            => '12',
            'table'         => self::getTable(),
            'field'         => 'serial',
            'name'          => __('Serial number'),
        ];

        $tab[] = [
            'id'            => '13',
            'table'         => self::getTable(),
            'field'         => 'otherserial',
            'name'          => __('Inventory number'),
        ];

        $tab[] = [
            'id'            => '31',
            'table'         => self::getTable(),
            'field'         => 'id',
            'name'          => __('ID'),
            'datatype'      => 'number',
            'massiveaction' => false
        ];

        $tab[] = [
            'id'            => '80',
            'table'         => 'glpi_entities',
            'field'         => 'completename',
            'name'          => Entity::getTypeName(1),
            'datatype'      => 'dropdown'
        ];

        $tab[] = [
            'id'            => '7',
            'table'         => self::getTable(),
            'field'         => 'is_recursive',
            'name'          => __('Child entities'),
            'massiveaction' => false,
            'datatype'      => 'bool'
        ];

        $tab[] = [
            'id'            => '81',
            'table'         => Entity::getTable(),
            'field'         => 'entities_id',
            'name'          => sprintf('%s-%s', Entity::getTypeName(1), __('ID'))
        ];

        $tab[] = [
            'id'                 => '61',
            'table'              => $this->getTable(),
            'field'              => 'is_helpdesk_visible',
            'name'               => __('Associable to a ticket'),
            'datatype'           => 'bool'
        ];

        $tab[] = [
            'id'                 => '32',
            'table'              => 'glpi_states',
            'field'              => 'completename',
            'name'               => __('Status'),
            'datatype'           => 'dropdown',
            //'condition'          => ['is_visible_assetlist' => 1]
        ];

        $tab = array_merge($tab, Certificate::rawSearchOptionsToAdd());

        return $tab;
    }


    public static function rawSearchOptionsToAdd(string $itemtype)
    {
        $tab = [];

        $tab[] = [
            'id' => 'assetlist',
            'name' => self::getTypeName(Session::getPluralNumber())
        ];

        $tab[] = [
            'id'                 => '1210',
            'table'              => self::getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'forcegroupby'       => true,
            'datatype'           => 'itemlink',
            'itemlink_type'      => 'Assetlist',
            'massiveaction'      => false,
            'joinparams'         => [
                'condition'  => ['NEWTABLE.is_deleted' => 0],
                'beforejoin' => [
                    'table'      => PluginAssetlistAssetlist_Item::getTable(),
                    'joinparams' => ['jointype' => 'itemtype_item']
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '1212',
            'table'              => User::getTable(),
            'field'              => 'name',
            'name'               => User::getTypeName(1),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => self::getTable(),
                    'joinparams'         => [
                        'beforejoin' => [
                            'table'      => PluginAssetlistAssetlist_Item::getTable(),
                            'joinparams' => ['jointype' => 'itemtype_item']
                        ]
                    ]
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '1213',
            'table'              => Group::getTable(),
            'field'              => 'name',
            'name'               => Group::getTypeName(1),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => self::getTable(),
                    'joinparams'         => [
                        'beforejoin' => [
                            'table'      => PluginAssetlistAssetlist_Item::getTable(),
                            'joinparams' => ['jointype' => 'itemtype_item']
                        ]
                    ]
                ]
            ]
        ];

        return $tab;
    }


    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                PluginAssetlistAssetlist_Item::class,
            ]
        );
    }


    public static function getIcon()
    {
        return "ti ti-versions";
    }

    /**
     * Get item types that can be linked to an assetlist
     *
     * @param boolean $all Get all possible types or only allowed ones
     *
     * @return array
     */
    public static function getTypes($all = false): array
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $config = PluginAssetlistConfig::getConfig();
        //$types = $CFG_GLPI['assetlist_types'];
        $types = json_decode($config['assetlist_types']);

        foreach ($types as $key => $type) {
            if (!class_exists($type)) {
                continue;
            }

            if ($all === false && !$type::canView()) {
                unset($types[$key]);
            }
        }
        return $types;
    }

    public function getSpecificMassiveActions($checkitem = null)
    {

        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);

        if ($isadmin) {
            $prefix                    = 'PluginAssetlistAssetlist_Item' . MassiveAction::CLASS_ACTION_SEPARATOR;
            $actions[$prefix . 'add']    = _x('button', 'Add an item');
            $actions[$prefix . 'remove'] = _x('button', 'Remove an item');
        }

        KnowbaseItem_Item::getMassiveActionsForItemtype($actions, __CLASS__, 0, $checkitem);

        return $actions;
    }

    public static function getMassiveActionsForItemtype(
        array &$actions,
        $itemtype,
        $is_deleted = false,
        CommonDBTM $checkitem = null
    ) {
        if (in_array($itemtype, self::getTypes())) {
            if (self::canUpdate()) {
                $action_prefix                    = 'PluginAssetlistAssetlist_Item' . MassiveAction::CLASS_ACTION_SEPARATOR;
                $actions[$action_prefix . 'add']    = "<i class='fa-fw fas fa-file-contract'></i>" .
                                                _x('button', 'Add to an assetlist');
                $actions[$action_prefix . 'remove'] = _x('button', 'Remove from an assetlist');
            }
        }
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        //echo "{$ma->getAction()}<br><br>";
        switch ($ma->getAction()) {
            case 'add_item':
                PluginAssetlistAssetlist::dropdown();
                echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;
                break;
        }
        return parent::showMassiveActionsSubForm($ma);
    }

    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        $appli_item = new PluginAssetlistAssetlist_Item();

        /*
        echo "{$ma->getAction()}<br><br>";
        Html::displayErrorAndDie();
        */
        switch ($ma->getAction()) {
            case 'add_item':
                $input = $ma->getInput();
                foreach ($ids as $id) {
                    $input = [
                        'assetlists_id'   => $input['assetlists_id'],
                        'items_id'        => $id,
                        'itemtype'        => $item->getType()
                    ];
                    if ($appli_item->can(-1, UPDATE, $input)) {
                        if ($appli_item->add($input)) {
                             $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                        } else {
                             $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        }
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                    }
                }

                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }
}
