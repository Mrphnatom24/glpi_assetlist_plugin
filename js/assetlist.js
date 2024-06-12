/* global CFG_GLPI */
/* global GLPI_PLUGINS_PATH */

window.GlpiPluginAssetlistCameraInput = null;

class AssetlistCameraInput {

   constructor() {

      // Lista de posibles funciones a llamar
      this.possibleHooks = [
         this.hookAssetlistItemsList,
         this.hookAssetlistItemsCount
      ];

      // Inicializar este objeto
      this.init();
   }

   /**
    * Comprueba el soporte
    * @returns 
    */
   checkSupport() {
      return (typeof navigator.mediaDevices !== 'undefined' && typeof navigator.mediaDevices.getUserMedia !== 'undefined');
   }

   /**
    * Inicializa el viewport. El viewport se refiere al dialogo modal en el que se muestra la webcam
    */
   initViewport() {
      // Inserción de código en etiqueta main
      $(`<div id="plugin-assetlist-camera-input-viewport" class="modal" role="dialog">
         <div class="modal-dialog" role="dialog">
            <div class="modal-content">
               <div id="plugin-assetlist-qr-notification" class="modal-header center"></div>

               <div id="plugin-assetlist-modal-body" class="modal-body">
                  <video id="plugin-assetlist-video" autoplay muted preload="auto"></video>
               </div>
                  
               <div id="plugin-assetlist-qr-buttons" class="modal-footer">
                  <button type="button" id="plugin-assetlist-qr-buttons-cancel" class="btn btn-secondary">Cancelar</button>
                  <button type="button" id="plugin-assetlist-qr-buttons-confirm" class="btn btn-primary" title="Camera search">Confirmar</button>
               </div>
            </div>
         </div>
      </div>`).appendTo('main');

      // Inicialmente no visible
      $('#plugin-assetlist-camera-input-viewport').modal({
         show: false
      });

      // Asignar función a ejecutar cuando se esconda el dialogo
      $('#plugin-assetlist-camera-input-viewport').on('hide.bs.modal', () => {
         // stop the video stream
         const video = $('#plugin-assetlist-camera-input-viewport video').get(0);
         if (video.srcObject) {
            video.srcObject.getTracks().forEach(track => track.stop());
         }
      });
   }

   /**
    * 
    * @returns Codigo html del boton
    */
   getCameraInputButton() {
      return `<button type="button" class="plugin-assetlist-camera-input btn btn-outline-secondary" title="Camera search">
             <i class="fas fa-camera fa-lg"></i>
             Escanear QR
         </button>`;
   }

   removeCorrespondingRow(item_name) {
      const filas = document.querySelectorAll('table#plugin-assetlist-table-count tbody tr');
      let result = false;
      // Recorre cada fila
      filas.forEach(fila => {
         // Obtiene el valor de la segunda columna (nombre)
         const nombre = fila.cells[2].textContent.trim();

         // Compara el valor de la columna con el nombre buscado
         if (nombre === item_name) {
            ////console.log(nombre);
            // Quitar fila del DOM
            fila.remove();
            // Se eliminó la fila buscada
            result = true;
            return;
         }
      });
      // No se encontró ninguna fila con el nombre buscado
      return result;
   }

   /**
    * Esconde el diálogo para el escaneo de QRs 
    */
   hideDialog() {
      $('#plugin-assetlist-camera-input-viewport').modal('hide');
   }

   /**
    * Muestra el diálogo para el escaneo de QRs
    */
   showDialog() {
      $('#plugin-assetlist-camera-input-viewport').modal('show');
   }

   /**
    * Actualiza la información de la notificación del diálogo
    * @param {*} class_obj 
    */
   updateNotification(text) {
      // Identificamos el elemento de notificación
      let notification = $('div#plugin-assetlist-qr-notification').first();
      
      // Avisamos del escaneo del item
      notification.css("display", "block");
      notification.text(text);
   }

   /**
    * Hace sonar un pitido que indica una acción correcta
    */
   playCorrect() {
      let sound = new Audio('../audio/correct-beep-short.mp3');
      sound.play();
   }

   /**
    * Hace sonar un pitido que indica una acción incorrecta
    */
   playIncorrect() {
      let sound = new Audio('../audio/incorrect-beep.mp3');
      sound.play();
   }

   /**
    * Función custom para insertar el botón dentro de la página de formulario de assetlist en la sección de items
    * @param {*} class_obj 
    */
   hookAssetlistItemsList(class_obj) {
      // Esperar a que el documento termine de cargarse
      $(document).ajaxComplete((event, xhr, settings) => {

         // Si nos encontramos en la página de formulario de assetlist
         if (window.location.href.indexOf('/plugins/assetlist/front/assetlist.form') > -1) {

            // Si no hay un botón en la sección de elementos
            if (!$('form[id=plugin-assetlist-item-list] td[id="plugin-assetlist-qr-add"] .plugin-assetlist-camera-input').length) {

               // Obtener elemento contenedor
               const container = $('form[id=plugin-assetlist-item-list] td[id="plugin-assetlist-qr-add"]').first();

               // ====================================================================
               // ---- Proceso de inyeccion del codigo del boton en el contenedor ----
               // ====================================================================
               
               // Si el contenedor no es indefinido
               if (container !== undefined) {
                  // Añadir el codigo del boton al final del contenedor
                  container.append(class_obj.getCameraInputButton());

               // Si el contenedor es indefinido
               } else {
                  // No hacemos nada
                  return;
               }

               // Obtenemos el boton y le asigamos una funcion que actue cuando sea pulsado
               container.find('.plugin-assetlist-camera-input').on('click', () => {

                  console.log("list");

                  // Input oculto que almacena los textos de los QR escaneados en formato JSON
                  let input = $('form[id="plugin-assetlist-item-list"] input[id="plugin-assetlist-qr-text"]').first();

                  // Botón CANCELAR del diálogo
                  let cancel = $('button[id="plugin-assetlist-qr-buttons-cancel"]').first();
                  cancel.on('click', function() {
                     input.val("");                // Se marca el valor del input como vacio
                     class_obj.hideDialog();       // Esconder diálogo
                  });

                  // Botón CONFIRMAR del diálogo
                  let confirm = $('button[id="plugin-assetlist-qr-buttons-confirm"]').first();
                  confirm.unbind('click');
                  // Asignar listener para click
                  confirm.on('click', function() {
                     // Identificamos el botón de envio del formulario
                     let submit = $('form[id="plugin-assetlist-item-list"] button[type="submit"]').first();
                     // Pulsamos el botón para realizar el envio
                     submit.click();
                  });

                  // Checkbox que indica si mantenemos la cámara abierta para más escaneos
                  let continuous = $('form[id="plugin-assetlist-item-list"] input[id="plugin-assetlist-qr-continuous"]').first();

                  let can_detect = true;

                  // Mostrar dialogo
                  class_obj.showDialog();

                  // Obtener dispositivo de video del usuario
                  navigator.mediaDevices.getUserMedia({
                     audio: false,
                     video: {
                        facingMode: "environment",          // Cambiar a frontal
                        frameRate: { ideal: 10, max: 15 },  // Cambiar para que detecte cada 1,5 segundos
                        focusMode: ['continuous', 'auto']
                     }
                     // Después 
                     }).then((stream) => {
                        // Obtener la primera etiqueta <video< dentro del elemento con id #plugin-assetlist-camera-input-viewport
                        const video = $('#plugin-assetlist-camera-input-viewport video').get(0);
                        // Asignar el stream como fuente de video
                        video.srcObject = stream;

                        // for each frame (or at least an intermittent check), try to detect a barcode with this.detector
                        // Para cada frame intenta detectar un codigo de barras con this.detector

                        // Añadir un listener de evento
                        video.addEventListener('timeupdate', () => {

                           if (can_detect) {
                              // Detectar listado de codigos de barras
                              class_obj.detector.detect(video).then((barcodes) => {
                                 // Si hay codigos de barras
                                 if (barcodes.length > 0) {
                                 
                                    // Obtener nuevo valor de qr
                                    let newqr = barcodes[0].rawValue;
                                 
                                    // Definir variable de array
                                    let qrs;

                                    // Si el input no está vacio
                                    if (input.val() != "") {
                                       // Obtener contenido json parseado como array de QRS
                                       qrs = JSON.parse(input.val())
                                    } else {
                                       // Declaramos el array vacio
                                       qrs = [];
                                    }

                                    // Si el QR escaneado no está incluido
                                    if (!qrs.includes(newqr)) {

                                       // Sonar pitido de nuevo escaneo
                                       class_obj.playCorrect();

                                       // Insertamos el nuevo texto de QR dentro de nuestro array
                                       qrs.push(newqr);

                                       // Convertimos a JSON el listado de textos de QRs y lo insertamos dentro del input
                                       let jqrs = JSON.stringify(qrs);
                                       input.val(jqrs);

                                       // Avisamos del escaneo del item
                                       class_obj.updateNotification("Escaneado: \"" + newqr + "\"");

                                       ////console.log(jqrs);

                                    } else {
                                       // Sonar pitido de QR ya escaneado
                                       class_obj.playIncorrect();

                                       // Avisamos que el item ya fue escaneado
                                       class_obj.updateNotification("\"" + newqr + "\" ya fue escaneado");
                                    }

                                    if (!continuous.is(':checked')) {
                                       // Esconder dialogo
                                       class_obj.hideDialog();
                                       // Confirmar datos
                                       confirm.click();
                                    }

                                    // Desactivar el escaneo de QR por un segundo
                                    //video.srcObject = null;
                                    //video.pause();
                                    can_detect = false;
                                    setTimeout(() => {
                                       //video.play();
                                       //video.srcObject = stream;
                                       can_detect = true;
                                    }, 1200);
                                 }
                              });
                           }
                           
                        });
                     });
                  });   
               }
            }
         });
   }

   /**
    * Función que inyecta un botón en el formulario de assetlist si no hay ninguno
    * @param {} class_obj 
    */
   hookAssetlistItemsCount(class_obj) {
      // Esperar a que el documento termine de cargarse
      $(document).ajaxComplete((event, xhr, settings) => {

         // Si nos encontramos en la página de formulario de assetlist
         if (window.location.href.indexOf('/plugins/assetlist/front/assetlist.form') > -1) {

            // Si no hay un botón en la sección de elementos
            if (!$('form[id=plugin-assetlist-item-count] td[id="plugin-assetlist-qr-count"] .plugin-assetlist-camera-input').length) {

               // Obtener elemento contenedor
               const container = $('form[id=plugin-assetlist-item-count] td[id="plugin-assetlist-qr-count"]').first();

               // ====================================================================
               // ---- Proceso de inyeccion del codigo del boton en el contenedor ----
               // ====================================================================
               
               // Si el contenedor no es indefinido
               if (container !== undefined) {
                  // Añadir el codigo del boton al final del contenedor
                  //$('td#plugin-assetlist-qr').append('<p>Hello</p>')
                  container.append(class_obj.getCameraInputButton());

               // Si el contenedor es indefinido
               } else {
                  // No hacemos nada
                  return;
               }

               // Obtenemos el boton y le asigamos una funcion que actue cuando sea pulsado
               container.find('.plugin-assetlist-camera-input').on('click', () => {

                  console.log("count");

                  // Array de textos de QR
                  let qrs = [];

                  // Botón CANCELAR del diálogo
                  let cancel = $('button[id="plugin-assetlist-qr-buttons-cancel"]').first();
                  cancel.on('click', function() {
                     qrs = [];                     // Vaciar array
                     class_obj.hideDialog();       // Esconder diálogo
                  });

                  // Botón CONFIRMAR del diálogo
                  let confirm = $('button[id="plugin-assetlist-qr-buttons-confirm"]').first();
                  confirm.unbind('click');
                  // Asignar listener para click
                  confirm.on('click', function() {
                     class_obj.hideDialog();
                  });

                  // Checkbox que indica si mantenemos la cámara abierta para más escaneos
                  let continuous = $('form[id="plugin-assetlist-item-count"] input[id="plugin-assetlist-qr-continuous"]').first();

                  // Input oculto que almacena los textos de los QR escaneados en formato JSON
                  let input = $('form[id="plugin-assetlist-item-count"] input[id="plugin-assetlist-qr-text"]').first();

                  let can_detect = true;

                  // Mostrar dialogo
                  $('#plugin-assetlist-camera-input-viewport').modal('show');

                  // Obtener dispositivo de video del usuario
                  navigator.mediaDevices.getUserMedia({
                     audio: false,
                     video: {
                        facingMode: "environment",
                        frameRate: { ideal: 10, max: 15 },
                        focusMode: ['continuous', 'auto']
                     }
                     // Después 
                     }).then((stream) => {
                        // Obtener la primera etiqueta <video< dentro del elemento con id #plugin-assetlist-camera-input-viewport
                        const video = $('#plugin-assetlist-camera-input-viewport video').get(0);
                        // Asignar el stream como fuente de video
                        video.srcObject = stream;

                        // for each frame (or at least an intermittent check), try to detect a barcode with this.detector
                        // Para cada frame intenta detectar un codigo de barras con this.detector

                        // Añadir un listener de evento
                        video.addEventListener('timeupdate', () => {
                           // Detectar listado de codigos de barras
                           class_obj.detector.detect(video).then((barcodes) => {
                           
                              if (can_detect) {
                                    // Si hay codigos de barras
                                 if (barcodes.length > 0) {


                                    // Obtener nuevo valor de qr
                                    let newqr = barcodes[0].rawValue;

                                    // Si el QR escaneado está incluido
                                    if (qrs.includes(newqr)) {
                                       class_obj.playIncorrect();
                                       //console.log("Contenido en la lista: " + newqr);
                                       // Avisamos del escaneo del item
                                       class_obj.updateNotification("Elemento \"" + newqr + "\" ya escaneado anteriormente");
                                       
                                    } else {
                                       // Quitar la fila cuyo campo nombre coincida con el buscado
                                       let done = class_obj.removeCorrespondingRow(newqr);
                                       //console.log("Done: " + done);

                                       // Si se quitó la fila
                                       if (done) {
                                          class_obj.playCorrect();
                                          //console.log("Eliminado: " + newqr);
                                          // Añadir QR al listado
                                          qrs.push(newqr);
                                          // Avisar de que se encontro el elemento
                                          class_obj.updateNotification("El elemento \"" + newqr + "\" fue encontrado en la lista");
                                    
                                       // Si no se encontró ninguna fila
                                       } else {
                                          class_obj.playIncorrect();
                                          //console.log("No encontrado: " + newqr)
                                          // Avisar que el elemento no existe en la lista
                                          class_obj.updateNotification("No se encontro el elemento \"" + newqr + "\"");
                                       }
                                    }

                                    

                                    // Si no tenemos que mantener el diálogo
                                    if (!continuous.is(':checked')) {
                                       // Esconder dialogo
                                       class_obj.hideDialog();
                                    }

                                    // Desactivar el escaneo de QR por un segundo
                                    //video.srcObject = null;
                                    //video.pause();
                                    can_detect = false;
                                    setTimeout(() => {
                                       //video.play();
                                       //video.srcObject = stream;
                                       can_detect = true;
                                    }, 1200);
                                 }
                              }
                           });
                        });
                     });
               });   
            }
         }
      });
   }

   init() {
      // Comprueba si el plugin es soportado
      if (!this.checkSupport()) {
         return;
      }

      // Intenta obtener los formatos soportados por el detector de barras
      try {
         window['BarcodeDetector'].getSupportedFormats();
      } catch {
         window['BarcodeDetector'] = barcodeDetectorPolyfill.BarcodeDetectorPolyfill;
      }

      // Crea un nuevo detector de barras
      this.detector = new BarcodeDetector();

      // Inicializa el dialogo para visualizar la camara
      this.initViewport();

      // Ejecuta cada función gancho definida
      $.each(this.possibleHooks, (i, func) => {
         func(this);
      });
   }
}

$(document).on('ready', () => {
   window.GlpiPluginAssetlistCameraInput = new AssetlistCameraInput();
});
