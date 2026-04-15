jQuery(document).ready(function($) {

    $("#ncliente").change(function() {
        var valor = $("#ncliente").val();
        
        // Lista de referencias y sus respectivos campos de destino
        var referencias = {
            "nombre.php": "#nombre",
            "apellido.php": "#apellido",
            "celular.php": "#celular",
            "correo.php": "#correo",
            "direccion.php": "#direccion"
        };

        // Iteramos sobre las referencias y hacemos una sola petición AJAX por cada una
        $.each(referencias, function(archivo, campo) {
            $.ajax({
                url: "referencias/" + archivo,
                type: 'GET',
                dataType: 'html',
                data: { valor: valor }, // Pasamos los datos correctamente como objeto
                success: function(data) {
                    $(campo).html(data);
                    console.log(archivo + " => " + data);
                },
                error: function(xhr, status, error) {
                    console.error("Error en " + archivo + ": " + error);
                }
            });
        });
    });

});

