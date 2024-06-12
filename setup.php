<?php

// Version actual del plugin
define('PLUGIN_ASSETLIST_VERSION', '0.9.1');

// Versiones aceptadas de GLPI
define('PLUGIN_ASSETLIST_MIN_GLPI', '10.0.0');
define('PLUGIN_ASSETLIST_MAX_GLPI', '10.1.0');

use Glpi\Plugin\Hooks;

// ===================================
// ---- Función de inicialización ----
// ===================================
function plugin_init_assetlist()
{
   // Obtener acceso a los hooks
	global $PLUGIN_HOOKS;
   // Obtener acceso a variables de configuracion
   global $CFG_GLPI;

   // Todas las acciones se realizan via POST y se cierran las páginas con Html::footer()
	$PLUGIN_HOOKS['csrf_compliant']['assetlist'] = true;

   // Crear nuevo objeto plugin
   $plugin = new Plugin();

   // Comprobar si el plugin actual está instalado y activo
   if ($plugin->isInstalled('assetlist') && $plugin->isActivated('assetlist')) {

      // Array de tipos de plugin para ser almacenados en assetlist
      if (!array_key_exists('plugin_assetlist_assetlist_plugin_types', $CFG_GLPI)) {
         $CFG_GLPI['plugin_assetlist_assetlist_plugin_types'] = [];
      }

      // Array de tipos de relaciones de plugin para los items de assetlist
      if (!array_key_exists('plugin_assetlist_assetlist_relation_plugin_types', $CFG_GLPI)) {
         $CFG_GLPI['plugin_assetlist_assetlist_relation_plugin_types'] = [];
      }

      // Permitir la asignación a tickets para los objetos del plugin actual -> se llamara a "plugin_assetlist_AssignToTicket()"
      $PLUGIN_HOOKS['assign_to_ticket']['assetlist'] = "";

      // Añadir archivos javascript para detección de códigos QR
      $PLUGIN_HOOKS['add_javascript']['assetlist'][] = 'public/lib/zbar-wasm/index.js';
      $PLUGIN_HOOKS['add_javascript']['assetlist'][] = 'public/lib/barcode-detector-polyfill/index.js';

      // Archivo js que maneja la interfaz para el escaneo
      $PLUGIN_HOOKS['add_javascript']['assetlist'][] = 'js/assetlist.js';

      // Archivo js para autoenvio de formulario 
      $PLUGIN_HOOKS['add_javascript']['assetlist'][] = 'js/assetlist_autoclicker.js';

      // Archivo de estilos
      $PLUGIN_HOOKS['add_css']['assetlist'][] = 'css/assetlist.css';

      // ======================================
      // ---- Habilitar pestaña de impacto ----
      // ======================================
      // Añadir clase a tipos de impacto, también se debe añadir en la configuración general para que funcione
      $CFG_GLPI['impact_asset_types'][PluginAssetlistAssetlist::getType()] = "plugins/assetlist/pics/impact/assetlist.png";

      // =======================================
      // ---- Habilitar pestaña de contrato ----
      // =======================================
      // No se puede seleccionar el contrato a asociar pero desde el formulario del contrato puede asociarse el objeto
      $CFG_GLPI['contract_types'][] = PluginAssetlistAssetlist::class;

      // Añadir nombre de permiso del recurso a los derechos del helpdesk
      Profile::$helpdesk_rights[] = PluginAssetlistAssetlist::$rightname;

      // Obtener los valores de configuración para el plugin actual en cuanto al menu
      $config = Config::getConfigurationValues('plugin:Assetlist', ['menu']);

      // Si la sesión actual tiene derecho a leer las listas de activos
      if (Session::haveRight(PluginAssetlistAssetlist::$rightname, READ)) {

         // Añadir menu en activos cuya clase controladora sea PluginAssetlistAssetlist
         $PLUGIN_HOOKS['menu_toadd']['assetlist'] = [$config['menu'] ?? 'plugins' => PluginAssetlistAssetlist::class];
      }

      // Añadir clase Profile para administrar permisos sobre las listas, accesible mediante una pestaña en la clase Profile
      Plugin::registerClass(PluginAssetlistProfile::class, ['addtabon' => ['Profile']]);

      // Añadir clase Config, accesible mediante una pestaña en la clase Config
      Plugin::registerClass(PluginAssetlistConfig::class, ['addtabon' => 'Config']);

      // Registrar clases que permiten la administración de las listas de activos
      Plugin::registerClass(PluginAssetlistAssetlist::class);                 // Lista
      Plugin::registerClass(PluginAssetlistAssetlist_Item::class);            // Elemento de la lista
      Plugin::registerClass(PluginAssetlistAssetlist_Item_Relation::class);   // Relacion del elemento de la lista
      Plugin::registerClass(PluginAssetlistRecuento::class);                  // Clase controladora para pestaña de recuento de activos
      Plugin::registerClass(PluginAssetlistMassive_Update::class);            // Clase controladora del formulario de actualización masiva
   }
   return true;
}

function plugin_version_assetlist()
{
	return [
	      'name'         => __('Assetlist', 'assetlist'),
	      'version'      => PLUGIN_ASSETLIST_VERSION,
	      'author'       => 'Ioan Ciceu',
	      'license'      => 'GPLv3',
	      'homepage'     =>'https://google.com',
	      'requirements' => [
	         'glpi'   => [
	                     'min' => PLUGIN_ASSETLIST_MIN_GLPI,
	                     'max' => PLUGIN_ASSETLIST_MAX_GLPI
	         ]
	      ]
	];
}