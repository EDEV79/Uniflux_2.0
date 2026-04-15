jQuery(document).ready(function ($) {

    $("#placa").change(function () {
        var valor = $("#placa").val();
        var link = "referencias/marca.php";
        $.ajax({
            url: link,
            type: 'GET',
            dataType: 'html',
            data: "valor=" + valor,
            success: function (data) {
                $("#marca").html(data);
                console.log(data);
            }
        });
    });
    $("#placa").change(function () {
        var valor = $("#placa").val();
        var link = "referencias/modelo.php";
        $.ajax({
            url: link,
            type: 'GET',
            dataType: 'html',
            data: "valor=" + valor,
            success: function (data) {
                $("#modelo").html(data);
                console.log(data);
            }
        });
    });
    $("#placa").change(function () {
        var valor = $("#placa").val();
        var link = "referencias/ano.php";
        $.ajax({
            url: link,
            type: 'GET',
            dataType: 'html',
            data: "valor=" + valor,
            success: function (data) {
                $("#ano").html(data);
                console.log(data);
            }
        });
    });
    $("#placa").change(function () {
        var valor = $("#placa").val();
        var link = "referencias/admincosto.php";
        $.ajax({
            url: link,
            type: 'GET',
            dataType: 'html',
            data: "valor=" + valor,
            success: function (data) {
                $("#admincosto").html(data);
                console.log(data);
            }
        });
    });


});