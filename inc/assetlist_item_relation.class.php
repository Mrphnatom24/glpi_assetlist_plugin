<?php

class PluginAssetlistAssetlist_Item_Relation extends CommonDBRelation
{
    //public static $rightname = 'assetlist_item_relation';     // Nombre para los permisos

    public static $itemtype_1 = PluginAssetlistAssetlist_Item::class;
    public static $items_id_1 = 'assetlists_items_id';
   //static public $take_entity_1 = false;

    public static $itemtype_2 = 'itemtype';
    public static $items_id_2 = 'items_id';
   //static public $take_entity_2 = true;

    public static function getTypeName($nb = 0) {
        return _nx('assetlist', 'Relation', 'Relations', $nb);
    }

    /**
     * Get item types that can be linked to an assetlist item
     *
     * @param boolean $all Get all possible types or only allowed ones
     *
     * @return array
     */
    public static function getTypes($all = false): array {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $config = PluginAssetlistConfig::getConfig();
        //$types = $CFG_GLPI['assetlist_relation_types'];
        $types = json_decode($config['assetlist_relation_types']);

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

    public static function canCreate() {
        return PluginAssetlistAssetlist_Item::canUpdate();
    }


    public function canCreateItem() {
        $app_item = new PluginAssetlistAssetlist_Item();
        $app_item->getFromDB($this->fields['assetlists_items_id']);
        return $app_item->canUpdateItem();
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
            $error_detected[] = __('An assetlist item is required');
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

    /**
     * count number of assetlist's items relations for a give item
     *
     * @param CommonDBTM $item the give item
     * @param array $extra_types_where additional criteria to pass to the count function
     *
     * @return int number of relations
     */
    public static function countForMainItem(CommonDBTM $item, $extra_types_where = [])
    {
        $types = self::getTypes();
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


    /**
     * return an array of relations for a given Assetlist_Item's id
     *
     * @param int $assetlists_items_id
     *
     * @return array array of string with icons and names
     */
    public static function getForAssetlistItem(int $assetlists_items_id = 0)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'assetlists_items_id' => $assetlists_items_id
            ]
        ]);

        $relations = [];
        foreach ($iterator as $row) {
            $itemtype = $row['itemtype'];
            $item = new $itemtype();
            $item->getFromDB($row['items_id']);
            $relations[$row['id']] = "<i class='" . $item->getIcon() . "' title='" . $item::getTypeName(1) . "'></i>" .
                        "&nbsp;" . $item::getTypeName(1) .
                        "&nbsp;-&nbsp;" . $item->getLink();
        }

        return $relations;
    }


    /**
     * return a mini list of relation for a given Assetlist_Item's id
     * It's need the javascript return by self::getListJSForAssetlistItem
     * we separate in two function because the list is usually displayed in a form tag
     * and we need to display an additionnal form.
     *
     * @param int $assetlists_items_id the id of Assetlist_Item
     * @param bool $canedit do we have the right to edit
     *
     * @return string the html for the list
     */
    public static function showListForAssetlistItem(int $assetlists_items_id = 0, bool $canedit = true)
    {
        $relations_str = "";
        foreach (PluginAssetlistAssetlist_Item_Relation::getForAssetlistItem($assetlists_items_id) as $rel_id => $link) {
            $del = "";
            if ($canedit) {
                $del = "<i class='delete_relation pointer fas fa-times'
                       data-relations-id='$rel_id'></i>";
            }
            $relations_str .= "<li>$link $del</li>";
        }

        return "<ul>$relations_str</ul>
         <span class='pointer add_relation' data-assetlists-items-id='{$assetlists_items_id}'>
            <i class='fa fa-plus' title='" . __('New relation') . "'></i>
            <span class='sr-only'>" . __('New relation') . "</span>
         </span>
      </td>";
    }


    /**
     * Return the corresponding javascript to an mini html list of relation
     * see self::showListForAssetlistItem docblock
     *
     * @param CommonDBTM $item the item where the mini list will be displayed,
     *                         we use this to check entities/is_recursive attributes
     * @param bool $canedit do we have the right to edit
     *
     * @return string the javascript
     */
    public static function getListJSForAssetlistItem(
        CommonDBTM $item = null,
        bool $canedit = true
    ) {
        if ($canedit) {
            $form_url  = PluginAssetlistAssetlist_Item_Relation::getFormURL();
            $modal_html = json_encode("
                <form action='{$form_url}' method='POST'>
                <p>"
                . Dropdown::showSelectItemFromItemtypes([
                    'items_id_name'   => 'items_id',
                    'itemtypes'       => PluginAssetlistAssetlist_Item_Relation::getTypes(true),
                    'entity_restrict' => ($item->fields['is_recursive'] ?? false)
                                       ? getSonsOf('glpi_entities', $item->fields['entities_id'])
                                       : $item->fields['entities_id'],
                    'checkright'     => true,
                    'display'        => false,
                ])
                . "</p>
                <input type='hidden' name='assetlists_items_id'>
                " . Html::submit(_x('button', "Add"), ['name' => 'add']) . "
            " . Html::closeForm(false));

            $crsf_token = Session::getNewCSRFToken();

            $js = <<<JAVASCRIPT
         $(function() {
            $(document).on('click', '.add_relation', function() {
               var assetlists_items_id = $(this).data('assetlists-items-id');

               glpi_html_dialog({
                  title: _x('button', "Add an item"),
                  body: {$modal_html},
                  id: 'add_relation_dialog',
                  show: function() {
                     $('#add_relation_dialog input[name=assetlists_items_id]').val(assetlists_items_id);
                  },
               })
            });

            $(document).on('click', '.delete_relation', function() {
               var relations_id = $(this).data('relations-id');

               $.post('{$form_url}', {
                  'id': relations_id,
                  '_glpi_csrf_token': '$crsf_token',
                  'purge': 1,
               }, function() {
                  location.reload();
               })
            });
         });
JAVASCRIPT;
            return Html::scriptBlock($js);
        }

        return "";
    }
}
