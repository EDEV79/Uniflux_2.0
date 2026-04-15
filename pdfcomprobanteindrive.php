<?php
require('fpdf/fpdf.php');
include('connection/conexion.php');
include('connection/funciones.php');

// Verificar si se reciben los parámetros id y celular
if (isset($_GET['id']) && isset($_GET['celular'])) {
    $id = $_GET['id'];
    $celular = $_GET['celular'];

    // Preparar consulta SQL parametrizada para evitar SQL Injection
    $query = "SELECT id, socio_app, celular, placa, platdigital, fecha_inicial, fecha_final, tarifa_neta, cuota_semanal, adelantos_extras, abonos, total_patrono FROM comprobanteuber WHERE id = ? AND celular = ?";
    $stmt = mysqli_prepare($conexion, $query);

    // Asignar valores a los parámetros y ejecutar la consulta
    mysqli_stmt_bind_param($stmt, "is", $id, $celular);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Crear el objeto PDF
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetMargins(10, 10, 10);
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 14);


    $pdf->Image('img/Edmicars_logo1.png', 10, 0, 40); // Ajusta la posición y tamaño del logo según sea necesario


    // Detalles del comprobante obtenidos de la base de datos
    while ($row = mysqli_fetch_assoc($result)) {

        // Cabecera del PDF
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'Comprobante de Pago No.' . $row['id'], 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell(0, 10, 'Servicios Edmicar', 0, 1, 'C');
        $pdf->Ln(5);

        // Detalles encabezado
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(45, 8, 'Usuario:', 1, 0, 'L');
        $pdf->Cell(45, 8, $row['socio_app'], 1, 0, 'C');
        $pdf->Cell(45, 8, 'Fecha de Inicio:', 1, 0, 'L');
        $pdf->Cell(45, 8, TraeFechaExplode($row['fecha_inicial']), 1, 1, 'C');
        $pdf->Cell(45, 8, 'Celular:', 1, 0, 'L');
        $pdf->Cell(45, 8, $row['celular'], 1, 0, 'C');
        $pdf->Cell(45, 8, 'Fecha Final:', 1, 0, 'L');
        $pdf->Cell(45, 8, TraeFechaExplode($row['fecha_final']), 1, 1, 'C');
        $pdf->Cell(45, 8, 'Placa:', 1, 0, 'L');
        $pdf->Cell(45, 8, $row['placa'], 1, 0, 'C');
        $pdf->Cell(45, 8, 'Plataforma:', 1, 0, 'L');
        $pdf->Cell(45, 8, $row['platdigital'], 1, 1, 'C');
        $pdf->Ln(10);

        // Detalles financieros
        $pdf->SetFont('Arial', 'B', 10,);
        $pdf->Cell(180, 8, 'Detalles', 1, 0, 'C');
        $pdf->Cell(90, 8, '', '', 1, 'C');
        $pdf->SetFont('Arial', '', 10);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(90, 8, 'Concepto', 1, 0, 'C');
        $pdf->Cell(90, 8, 'Cantidad', 1, 0, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(90, 8, '', 0, 0, 'L');
        $pdf->Cell(90, 8, '', 0, 1, 'C');

        $pdf->Cell(90, 8, 'Deposito Usuario', 1, 0, 'L');
        $pdf->Cell(90, 8, '$ ' . $row['tarifa_neta'], 1, 1, 'C');
        $pdf->Cell(90, 8, 'Cuota Semanal', 1, 0, 'L');
        $pdf->Cell(90, 8, '$ ' . $row['cuota_semanal'], 1, 1, 'C');

        $pdf->Cell(90, 8, 'Adelantos', 1, 0, 'L');
        $pdf->Cell(90, 8, '$ ' . $row['adelantos_extras'], 1, 1, 'C');
        $pdf->Cell(90, 8, 'Abonos', 1, 0, 'L');
        $pdf->Cell(90, 8, '$ ' . $row['abonos'], 1, 1, 'C');

        $pdf->Cell(90, 8, '', 1, 0, 'L');
        $pdf->Cell(90, 8, '', 1, 1, 'C');
        $pdf->Cell(90, 8, 'Total', 1, 0, 'L');
        $pdf->Cell(90, 8, '$ ' . $row['total_patrono'], 1, 1, 'C');

        $pdf->Ln(14);
    }

    // Firma de recibido
    $pdf->Cell(0, 5, '_____________________________________', 0, 1, 'C');
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 5, 'Firma de Recibido', 0, 1, 'C');

    // Salida del PDF
    $pdf->Output();

    // Cerrar la consulta y la conexión a la base de datos
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
} else {
    // Manejar caso donde no se reciben los parámetros esperados
    echo "Error: Se esperaban los parámetros 'id' y 'celular'.";
}
