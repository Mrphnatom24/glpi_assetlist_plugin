<?php

/**
 * Esta clase gestiona la pestaña "Actualización masiva". Esta pestaña se compone de un formulario 
 * con gran variedad de campos. Una vez se guarde el formulario, el valor de cada campo se aplica 
 * a los elementos de la lista actual. Si el campo no existe no se aplica la actualización de ese campo.
 */

/**
 * Notas para el programador
 * - Investigar como usar TemplateRenderer para cargar un archivo twig desde un plugin
 * - En caso contrario diseñar el formulario a base de "echo"
 */

 use \Glpi\Application\View\TemplateRenderer;
 use \Glpi\Features\AssetImage;

class PluginAssetlistMassive_Update extends CommonDBTM
{
    use AssetImage;

    public static $rightname = "plugin-assetlist-massive_update";

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
        return self::createTabEntry('Actualización masiva');
    }

    // ==========================================
    // ---- Imprimir contenido de la pestaña ----
    // ==========================================
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

        // Si estamos tratando una Assetlist
        if ($item instanceof PluginAssetlistAssetlist) {
            
            // Obtener ID de la lista en el sistema
            $ID = $item->fields['id'];

            // No se puede recuperar la lista de la BBDD o no se puede leer la lista
            if (!$item->getFromDB($ID) || !$item->can($ID, READ)) {
                return false;
            }

            // Elemento padre
            //<div class="tab-content p-2 flex-grow-1 card border-start-0" style="min-height: 150px">

            static::printFormWithTemplateRenderer($item);
        }
        
        return true;
    }

    public static function printFormWithTemplateRenderer(CommonDBTM $item) {
        // Crear una deep copy del objeto pasado
        $nitem = unserialize(serialize($item));

        // Imprimir por pantalla todos los campos y sus valores del objeto actual
        //var_dump($nitem->fields);
        //echo '<br><br>';

        // Obtener los valores predominantes de los items de la lista
        $nitem->fields = PluginAssetlistAssetlist::getPredominantValuesAmongListItems($nitem->fields['id']);
        $nitem->fields['id'] = $item->fields['id'];

        //var_dump($nitem->fields);
        //foreach ($nitem->fields as $k => $v) {if (empty($v)) {$nitem->fields[$k] = null;}}

        //$item->initForm($item->fields['id'], []);
        TemplateRenderer::getInstance()->display('@assetlist/pages/assetlist.html.twig', [
            'item'   => $nitem,
            'params' => ["target" => PluginAssetlistMassive_Update::getFormURL(true)],
        ]);
        return true;
    }
}
