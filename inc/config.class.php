<?php



/**
 * PluginAssetlistConfig class
 */
class PluginAssetlistConfig extends CommonDBTM
{

   static protected $notable = true;

   /**
    * Devuelve el nombre de la pestaña para el item
    */
   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {
      // Si es un objeto plantilla y el tipo de item es 'Config'
      if (!$withtemplate && $item->getType() === 'Config') {
         // Devolver nombre de la pestaña
         return __('Assetlist', 'assetlist');
      }
      // Devolver string vacia en caso contrario
      return '';
   }

   /**
    * Imprime por pantalla el formulario
    */
   public function showForm($ID, array $options = []): bool
   {
      global $CFG_GLPI;

      // Si la sesión actual no tiene derecho para actualizar la configuración
      if (!Session::haveRight('config', UPDATE)) {
         // No se pudo imprimir el formulario por falta de permisos
         return false;
      }

      // Obtener configuración del objeto
      $config = self::getConfig();

      //var_dump($config);

      global $CFG_GLPI;

      // Formar array con todos los tipos almacenables por la lista
      $all_ass_types = [];
      // Empezar primero por los plugin para más robustez
      foreach ($CFG_GLPI['plugin_assetlist_assetlist_plugin_types'] as $itemtype) {$all_ass_types[$itemtype] = "(Plugin) {$itemtype::getTypeName()}";}
      foreach (json_decode($config['all_standar_assetlist_types']) as $itemtype) {$all_ass_types[$itemtype] = $itemtype::getTypeName();}

      // Obtener tipos de elementos habilitados para ser almacenados por la lista
      $selected_ass_types = json_decode($config['assetlist_types'], true);

      $all_ass_rel_types = [];
      foreach ($CFG_GLPI['plugin_assetlist_assetlist_relation_plugin_types'] as $itemtype) {$all_ass_rel_types[$itemtype] = "(Plugin) {$itemtype::getTypeName()}";}
      foreach (json_decode($config['all_standar_assetlist_relation_types']) as $itemtype) {$all_ass_rel_types[$itemtype] = $itemtype::getTypeName();}

      // Obtener tipos de relaciones habilitadas para los elementos de la lista
      $selected_ass_relation_types = json_decode($config['assetlist_relation_types'], true);

      // ---- TESTEO ----
      /*
      var_dump($all_ass_types);echo '<br>-------------------------------------------<br>';
      var_dump($all_ass_rel_types);echo '<br>-------------------------------------------<br>';
      var_dump($selected_ass_types);echo '<br>-------------------------------------------<br>';
      var_dump($selected_ass_relation_types);echo '<br>-------------------------------------------<br>';
      if ($selected_ass_types) echo "Si a los tipos<br>";
      if ($all_ass_rel_types) echo "Si a los tipos de relaciones<br>";
      */
      // ---- TESTEO ----

      // Apertura de formulario
      echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL(PluginAssetlistConfig::getType())."\" method='post'>";

      // =====================================
      // ---- Inputs ocultos y requeridos ----
      // =====================================
      // Clase de configuración
      echo "<input type='hidden' name='config_class' value='".__CLASS__."'>";
      // Contexto de configuración
      echo "<input type='hidden' name='config_context' value='plugin:Assetlist'>";

      // Apertura del cuerpo de las pestañas
      echo "<div class='center' id='tabsbody'>";

      // =============================================================================
      // ---- Primera tabla para selección de menú sobre el que mostrar el acceso ----
      // =============================================================================

      // Apertura de tabla con su encabezado
      echo "<table class='tab_cadre_fixe'><thead>";

      // Primer header y fin dell encabezado
      echo "<th colspan='4'>" . __('General Settings', 'assetlist') . '</th></thead>';

      // Primera fila -> MENU
      echo '<tr>';

      // Columna con etiqueta
      echo '<td>' . __('Plugin Menu', 'assetlist') . '</td>';

      // Columna con desplegable para selección de menu
      echo '<td colspan="3">';
      Dropdown::showFromArray('menu', [
         'assets'       => __('Assets'),
         'management'   => __('Management'),
         'plugins'      => _n('Plugin', 'Plugins', Session::getPluralNumber()),
         'tools'        => __('Tools'),
      ], ['value' => $config['menu'] ?? 'plugins']);
      echo '</td>';

      // Cierre primera file
      echo '</tr>';

      // Segunda fila -> tipos aceptados por la lista
      echo '<tr class="tab_bg_2">';
      echo '<td width="40%"><label for="$input_name">';
      echo 'Tipos de elementos admitidos en las listas';
      echo '</label></td>';

      // Columna con desplegable para selección de tipos
      echo '<td colspan="3">';
      Dropdown::showFromArray('assetlist_types',                  // Nombre del input
                              $all_ass_types, [     // Valores seleccionables
                                 // Valores preseleccionados
                                 'values' => $selected_ass_types ?? array_keys($all_ass_types),
                                 // Habilitar selección multiple 
                                 'multiple' => true]);
      echo '</td>';
      echo '</tr>';

      // Tercera fila -> tipos de relaciones aceptadas para los items de la lista
      echo '<tr class="tab_bg_2">';
      echo '<td width="40%"><label for="$input_name">';
      echo 'Tipos de relaciones admitidas para los elementos de las listas';
      echo '</label></td>';

      // Columna con desplegable para selección de tipos de relaciones
      echo '<td colspan="3">';
      Dropdown::showFromArray('assetlist_relation_types',               // Nombre del input 
                              $all_ass_rel_types, [  // Array de valores seleccionables
                                 // Valores preseleccionados
                                 'values' => $selected_ass_relation_types ?? array_keys($all_ass_rel_types),
                                 // Habilitar selección multiple 
                                 'multiple' => true]);
      echo '</td>';
      echo '</tr>';
      echo '</table>';

      // Submit button
      echo '<div style="text-align:center">';
      echo Html::submit(__('Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
      echo '</div>';

      // Cerrar el formulario
      Html::closeForm();

      // Confirmar la impresión del formulario
      return true;
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {
      if ($item->getType() === 'Config') {
         $config = new self();
         $config->showForm(-1);
      }
   }

   public static function getConfig() : array
   {
      static $config = null;
      if ($config === null) {
         $config = Config::getConfigurationValues('plugin:Assetlist');
      }

      return $config;
   }
}
