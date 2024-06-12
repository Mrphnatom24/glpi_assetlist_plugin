// El codigo se queda atascado en un bucle de redirecciones infinitas hacia la misma página
// =================
// ---- NO USAR ----
// =================

/*
// Esperar a que el documento termine de cargarse
$(document).on('ready', () => {
    // Si nos encontramos en la página de formulario de configuracion de assetlist
    if (window.location.href.indexOf('/plugins/assetlist/front/config.form') > -1) {
        let submit = $('button[type="submit"]').first();
        // Pulsamos el botón para realizar el envio
        submit.click();
    }
});
*/