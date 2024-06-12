<?php

use GlpiPlugin\Assetlist\Assetlist;

/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_assetlist_install() {

   global $DB;

   // Obtener información sobre el formato de los datos en la BBDD
   $default_charset = DBConnection::getDefaultCharset();
   $default_collation = DBConnection::getDefaultCollation();
   $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

   // Obtener nombres de las tablas de todas las clases
   $ass_table = PluginAssetlistAssetlist::getTable();
   $ass_items_table = PluginAssetlistAssetlist_Item::getTable();
   $ass_items_rel_table = PluginAssetlistAssetlist_Item_Relation::getTable();

   // ¿Limpiar instalación? -> No
   $clean_install = false;

   // ===========================
   // ---- Tabla de listados ----
   // ===========================
   if (!$DB->tableExists($ass_table)) {
      $query = "CREATE TABLE `{$ass_table}` (
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `appliances_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `is_recursive` tinyint NOT NULL DEFAULT '0',
         `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
         `is_deleted` tinyint NOT NULL DEFAULT '0',
         `comment` text COLLATE utf8mb4_unicode_ci,
         `locations_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `users_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `users_id_tech` int {$default_key_sign} NOT NULL DEFAULT '0',
         `groups_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `groups_id_tech` int {$default_key_sign} NOT NULL DEFAULT '0',
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         `states_id` int unsigned NOT NULL DEFAULT '0',
         `externalidentifier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
         `serial` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
         `otherserial` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
         `contact` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
         `contact_num` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
         `is_helpdesk_visible` tinyint NOT NULL DEFAULT '1',
         `pictures` text COLLATE utf8mb4_unicode_ci,
         PRIMARY KEY (`id`),
         UNIQUE KEY `unicity` (`externalidentifier`),
         KEY `entities_id` (`entities_id`),
         KEY `is_recursive` (`is_recursive`),
         KEY `name` (`name`),
         KEY `is_deleted` (`is_deleted`),
         KEY `locations_id` (`locations_id`),
         KEY `users_id` (`users_id`),
         KEY `users_id_tech` (`users_id_tech`),
         KEY `groups_id` (`groups_id`),
         KEY `groups_id_tech` (`groups_id_tech`),
         KEY `states_id` (`states_id`),
         KEY `serial` (`serial`),
         KEY `otherserial` (`otherserial`),
         KEY `is_helpdesk_visible` (`is_helpdesk_visible`),
         KEY `date_mod` (`date_mod`),
         KEY `date_creation` (`date_creation`)
       ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

      $DB->query($query) or die("error creating glpi_plugin_assetlist_assetlists ". $DB->error());

      // Limpiar instalación -> SI
      $clean_install = true;
   }

   // ========================
   // ---- Tabla de items ----
   // ========================
   if (!$DB->tableExists($ass_items_table)) {
      $query = "CREATE TABLE `{$ass_items_table}` (
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,								-- ID de tabla
         `assetlists_id` int {$default_key_sign} NOT NULL DEFAULT '0',						-- ID del assetlist
         `items_id` int {$default_key_sign} NOT NULL DEFAULT '0',								-- ID del item
         `itemtype` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',	-- Tipo del item
         PRIMARY KEY (`id`),
         UNIQUE KEY `unicity` (`assetlists_id`,`items_id`,`itemtype`),
         KEY `item` (`itemtype`,`items_id`)
       ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

      $DB->query($query) or die("error creating glpi_plugin_assetlist_assetlists_items". $DB->error());
   }

   // ============================================
   // ---- Tabla de relaciones listados-items ----
   // ============================================
   if (!$DB->tableExists($ass_items_rel_table)) {
      $query = "CREATE TABLE `{$ass_items_rel_table}` (
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,					-- ID de la relación assetlist-Item
         `assetlists_items_id` int {$default_key_sign} NOT NULL DEFAULT '0',		-- ID del assetlist
         `itemtype` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,	-- Tipo del item asociado
         `items_id` int {$default_key_sign} NOT NULL DEFAULT '0',					-- ID del item
         PRIMARY KEY (`id`),
         KEY `assetlists_items_id` (`assetlists_items_id`),
         KEY `item` (`itemtype`,`items_id`)
       ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

      $DB->query($query) or die("error creating glpi_plugin_assetlist_assetlists_items_relations ". $DB->error());
   }

   // Acceso a variables de tipos
   global $CFG_GLPI;

   // Formar array de tipos por defecto de Assetlist
   $ass_types = [
      // Tipos incluidos ya anteriormente
      Computer::class,        Monitor::class,   NetworkEquipment::class,
      Peripheral::class,      Phone::class,     Printer::class,
      Software::class,        Appliance::class, Cluster::class,
      DatabaseInstance::class,Database::class,

      // Nuevos tipos incluidos
      CartridgeItem::class,
      ConsumableItem::class,
      Rack::class,
      Enclosure::class,
      PDU::class,
      PassiveDCEquipment::class,
      Cable::class
   ];

   // Formar array por defecto de relaciones para items de assetlist
   $ass_relation_types = [Location::class, Network::class, Domain::class, Appliance::class];

   // Si no hay valores de configuración para el plugin
   if (!count(Config::getConfigurationValues('plugin:Assetlist'))) {
      // Definir valores de configuración por defecto
      Config::setConfigurationValues('plugin:Assetlist', [
         'config_class'                         => PluginAssetlistConfig::class,       // Clase encargada de la configuración
         'menu'                                 => 'plugins',                          // Menu de acceso para la gestión de listados
         'assetlist_types'                      => json_encode($ass_types),            // Tipos de elementos habilitados            
         'all_standar_assetlist_types'          => json_encode($ass_types),            // Todos los tipos estandar seleccionables
         'assetlist_relation_types'             => json_encode($ass_relation_types),   // Tipos de relaciones habilitadas
         'all_standar_assetlist_relation_types' => json_encode($ass_relation_types),   // Todos los tipos de relaciones estandar seleccionables
      ]);
   }

   $migration = new Migration(PLUGIN_ASSETLIST_VERSION);
   if ($clean_install) {
      $migration->addRight(PluginAssetlistAssetlist::$rightname);
   }
   $migration->executeMigration();
	return true;
}


/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_assetlist_uninstall() {
   // Base de datos global 
   global $DB;

   // ==========================
   // ---- Codigo plantilla ----
   // ==========================

   // Listado de tablas a eliminar
   $tables = [
      PluginAssetlistAssetlist::getTable(),
      PluginAssetlistAssetlist_Item::getTable(),
      PluginAssetlistAssetlist_Item_Relation::getTable()
   ];

   // Eliminar cada tabla del listado si existe
   foreach ($tables as $table) {
      if ($DB->tableExists($table)) {
         if ($DB->tableExists($table)) {
            $DB->queryOrDie('DROP TABLE' . $DB::quoteName($table));
         }
      }
   }

   // Eliminar valores de configuración del plugin actual
   Config::deleteConfigurationValues('plugin:Assetlist', [
      'config_class', 
      'menu', 
      'assetlist_types',
      'all_standar_assetlist_types',
      'assetlist_relation_types',
      'all_standar_assetlist_relation_types'
   ]);

	return true;
}

/**
 * Función que devuelve un listado (clase => representación) 
 * para añadir nuevos items a la asignación de tickets
 */
function plugin_assetlist_AssignToTicket() {
   return [
      PluginAssetlistAssetlist::class => PluginAssetlistAssetlist::getTypeName()
   ];
}