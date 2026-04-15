jQuery(document).ready(function($) {

    $("#nempleado").change(function() {
        var valor = $("#nempleado").val();
        var link = "referencias/sociocelular.php";
        $.ajax({
            url: link,
            type: 'GET',
            dataType: 'html',
            data: "valor=" + valor,
            success: function(data) {
                $("#celular").html(data);
                console.log(data);
            }
        });
    });
        $("#nempleado").change(function() {
        var valor = $("#nempleado").val();
        var link = "referencias/sociodireccion.php";
        $.ajax({
            url: link,
            type: 'GET',
            dataType: 'html',
            data: "valor=" + valor,
            success: function(data) {
                $("#direccion").html(data);
                console.log(data);
            }
        });
    });


});