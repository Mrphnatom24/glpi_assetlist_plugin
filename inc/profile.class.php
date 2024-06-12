<?php

class PluginAssetlistProfile extends Profile
{

   // Nombre de permiso asociado a la clase
   public static $rightname = "config";

   /**
    * Función que devuelve el nombre de la pestaña para la configuración del perfil
    */
   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      // Devuelve el nombre de la pestaña creada
      return self::createTabEntry(__('Assetlist', 'assetlist'));
   }

   /**
    * Muestra el contenido de la pestaña para la configuración de permisos
    */
   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {
      $profile = new self();                 // Crear nuevo objeto de la clase actual
      $profile->showForm($item->getID());    // Llamar a metodo encargado de mostrar el formulario del objeto
      return true;                           // Indica que se pudo imprimir el contenido de la pestaña
   }

   /**
    * Función encargada de mostrar el formulario del objeto actual
    */
   public function showForm($profiles_id = 0, $openform = true, $closeform = true)
   {
      // Si no podemos visualizar el objeto actual
      if (!self::canView()) {
         // No se pudo imprimir el formulario por falta de permisos
         return false;
      }

      // Apertura de bloque
      echo "<div class='spaced'>";

      // Creación de nuevo objeto Profile
      $profile = new Profile();

      // Carga en el objeto actual los datos del perfil almacenados en la BBDD
      $profile->getFromDB($profiles_id);

      // Obtener derecho a actualizar el objeto actual
      $can_edit = Session::haveRight(self::$rightname, UPDATE);

      // Si debo abrir el formulario y puedo editarlo
      if ($openform && $can_edit) {
         
         // Apertura del formulario
         echo "<form method='post' action='" . $profile::getFormURL() . "'>";
      }

      // Crear array asociativo de opciones
      $matrix_options = ['canedit' => $can_edit,
         'default_class' => 'tab_bg_2'];
      
      // Crear array de permisos
      $rights = [
         [  // Un array por cada clase administrada
            'itemtype' => PluginAssetlistAssetlist::class,                                 // Tipo del item
            'label' => PluginAssetlistAssetlist::getTypeName(Session::getPluralNumber()),  // Etiqueta
            'field' => PluginAssetlistAssetlist::$rightname                                // Nombre de permiso de la clase
         ]
      ];

      // Establecer la variable title en la matriz de opciones
      $matrix_options['title'] = __('Assetlist', 'assetlist');

      // Imprimir por pantalla la matriz de opciones (matriz de checkbox)
      $profile->displayRightsChoiceMatrix($rights, $matrix_options);

      // Si puedo editar y debo cerrar el formulario
      if ($can_edit && $closeform) {
         // Abrir bloque centrado
         echo "<div class='center'>";
         // Imprimir input escondido de ID
         echo Html::hidden('id', ['value' => $profiles_id]);
         // Imprimir boton de guardado de las opciones seleccionadas
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
         
         // Cerrar bloque centrado
         echo "</div>\n";

         // Cerrar formulario
         Html::closeForm();
      }
      // Cerrar bloque espaciado
      echo '</div>';

      // Indicar que se pudo imprimir el formulario en la página
      return true;
   }
}
