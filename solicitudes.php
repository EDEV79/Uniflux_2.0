<?php
include('connection/conexion.php');
include('connection/funciones.php');

// --------------------VARIABLES------------------
$usuario = $_SESSION['MM_NombApe'];
$fechaactual = date('Y-m-d');

//$mensaje="";
//------------------------Declaracion de Variables----------------------------------------
$nombre = "";
$tipo = "";
$tamano = "";
$nombre_tmp = "";
$numdoc = "";
$file = "";

//-------------------------------Incertar datos en la tabla-------------------------------
if (isset($_POST['agregar'])) {

    $placa = isset($_POST['placa']) ? trim($_POST['placa']) : '';
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $detalle = isset($_POST['detalle']) ? trim($_POST['detalle']) : '';
    $fecha = isset($_POST['fecha']) ? ExplodeFecha($_POST['fecha']) : null;

    $stmtInsert = mysqli_prepare(
        $conexion,
        'INSERT INTO solicitudes (placa, nombre, detalle, fecha, usuario) VALUES (?, ?, ?, ?, ?)'
    );
    mysqli_stmt_bind_param($stmtInsert, 'sssss', $placa, $nombre, $detalle, $fecha, $usuario);
    mysqli_stmt_execute($stmtInsert);
    mysqli_stmt_close($stmtInsert);

    //$mensaje= "Guardado correctamente";
    header("Location: solicitudes.php");
    exit;
}


//-----------------------ELIMINAR DOCUMENTOS------------------------------
if (isset($_GET['placa'], $_GET['id'])) {

    $placa = trim((string) $_GET['placa']);
    $id = (int) $_GET['id'];

    $stmtDelete = mysqli_prepare($conexion, 'DELETE FROM solicitudes WHERE placa = ? AND id = ?');
    mysqli_stmt_bind_param($stmtDelete, 'si', $placa, $id);
    mysqli_stmt_execute($stmtDelete);
    mysqli_stmt_close($stmtDelete);

    header("Location: solicitudes.php");
    exit;
}

//-----------------------------------------------------------------------------
?>

<?php include('include/head.php'); ?>
<?php include 'include/calendario.php'; ?>
</head>

<body>
    <?php include('include/menu.php'); ?>
    <div class="row justify-content-center mt-3">
        <div class="col-lg-10 col-md-6 text-center">
            <br><br><br>
            <h3>Recepcion de Solcitudes de Autos</h3>
            <br>
            <div style="width: 500px;margin-right: auto;margin-left: auto;">
                <form method="post" enctype="multipart/form-data">
                    <table class="table table-hover">
                        <tr><select class="form-control" name="placa" id="placa" required>
                                <option selected="selected">Seleccione la Placa del Auto</option>
                                <?php
                                $res = mysqli_query($conexion, "SELECT placa FROM inventariouber");
                                while ($f = mysqli_fetch_array($res)) {
                                    echo '<option value="' . $f['placa'] . '">' . $f['placa'] . '</option>';
                                }
                                ?>
                            </select></tr>
                        <tr><input style="height: 39px;" name="nombre" value="<?php echo $usuario ?>" placeholder="<?php echo $usuario ?>"
                                class="form-control col-sm-12"></input></tr>
                    </table>
                    <table class="table table-hover">
                        <tr><input type="text" name="fecha" placeholder="Fecha" id="datepicker1"
                                class="form-control col-sm-12"></tr>
                        <tr><textarea style="height: 100px;" name="detalle" placeholder="Detalle de la solicitud"
                                class="form-control col-sm-12"></textarea></tr>
                    </table>
                    <td align="center"><button type="submit" name="agregar" class="btn btn-success">Agregar</button>
                    </td>
                </form>
            </div>
        </div>
    </div>
</body>
<?php include('include/footer.php'); ?>

</html>