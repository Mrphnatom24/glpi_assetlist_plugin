<?php

// Clase para añadir entradas al historico de los items
use Glpi\Event;

class PluginAssetlistTools {

    /**
     * Construye un nuevo array que sirve para actualizar un objeto CommonDBTM 
     * tomando solamente las claves del array $_POST y el array $item->fields 
     * del item tratado que aparezcan en los dos arrays. Los valores que 
     * prevalecen para cada clave tomada son los del array $_POST.
     * 
     * @param array $post Array $_POST
     * @param CommonDBTM $item Item del que se quiere sacar un array que sirva para actualizarlo
     */
    public static function buildUpdateArrayFromPOSTAndItemFieldsArray(array $post, CommonDBTM $item) {
        
        // Obtener claves de $_POST
        $post_keys = array_keys($post);

        // Obtener nombres de los campos del item
        $item_fields_keys = array_keys($item->fields);

        // Obtener campos comunes de los dos arrays anteriores
        $common_keys = array_intersect($post_keys, $item_fields_keys);

        // Crear el array de actualización vacio
        $update_array = array();

        // Para cada clave encontrada en los dos arrays
        foreach ($common_keys as $key) {
            // Asignar al array final el valor de $_POST para cada clave
            if (!empty($post[$key])) {
                $update_array[$key] = $post[$key];
            }
        }

        // Asignar id del item para que el sistema sepa que elemento actualizar
        $update_array["id"] = $item->fields['id'];

        // Retornar array de actualización
        return $update_array;
    }

    /**
     * Devuelve un array simple con los nombres de las columnas de la tabla de la BBDD del tipo indicado
     * 
     * @param string $type Tipo del que se quiere sacar los nombres de sus campos
     * 
     * @return bool|string[] Array de nombres de campos o false si la clase no existe
     */
    public static function getColumnNames(string $type) {
        global $DB;
        if (class_exists($type)) {
            $col_iter = $DB->request("SHOW COLUMNS FROM `{$type::getTable()}`");
            $raw_cols = iterator_to_array($col_iter);
            $cols = array();
            foreach ($raw_cols as $raw_col) {
                $cols[] = $raw_col["Field"];
            }
            return $cols;
        }
        return false;
    }

    /**
     * Busca en las tablas de la BBDD de todos los tipos soportados 
     * por la lista de activos el elemento con el nombre indicado
     * 
     * @param string $name Nombre del item buscado
     * 
     * @return CommonDBTM|false Devuelve el item buscado o false si no se encontro
     */
    public static function getItemByName(string $name) {
        global $DB;                                 // Acceso a BBDD
        global $CFG_GLPI;                           // Acceso a variables de conf
        $config = PluginAssetlistConfig::getConfig();
        $types = json_decode($config['assetlist_types']);      // Obtener tipos aceptados por la lista
        
        // Para cada tipo
        foreach ($types as $type) {

            // Comprobación de seguridad -> tipo es una string que indica el nombre de una clase
            if (is_string($type)) {

                // Crear iterador para obtener registros cuyo nombre coincida con el buscado
                $iter = $DB->request([
                    'FROM' => $type::getTable(),
                    'WHERE' => [
                        'name' => $name
                    ]
                ]);

                // Comprobamos si existe el primer registro
                if ($iter->current()) {
                    $item = $iter->current();   // Almacenammos el registro en una variable
                    $item['itemtype'] = $type;  // Añadimos un nuevo par clave-valor
                    return $item;               // Retornamos el array
                }
            }
        }
        // No se ha encontrado el item en la BBDD
        return false;
    }
}