<?php
require('fpdf/fpdf.php');
include('connection/conexion.php');
include('connection/funciones.php');

define('LOGO_PATH', __DIR__ . '/assets/logo.png');
define('LOGO_FALLBACK_PATH', __DIR__ . '/img/transnegralogonew.png');
define('QR_TEMP_DIR', __DIR__ . '/tmp/qr');
define('VALIDATION_SALT', 'AHE_VALIDATION_2026');

if (!function_exists('pdf_text')) {
    function pdf_text($value)
    {
        if ($value === null) {
            return '';
        }

        return utf8_decode((string) $value);
    }
}

if (!function_exists('format_time_12h')) {
    function format_time_12h($time)
    {
        if (empty($time) || strpos($time, ':') === false) {
            return '';
        }

        list($h, $m) = explode(':', $time);
        $h = (int) $h;
        $ampm = $h >= 12 ? 'PM' : 'AM';
        $h12 = $h % 12;
        if ($h12 === 0) {
            $h12 = 12;
        }

        return sprintf('%02d:%02d %s', $h12, (int) $m, $ampm);
    }
}

if (!function_exists('build_validation_token')) {
    function build_validation_token(array $row)
    {
        $payload = implode('|', array(
            isset($row['id']) ? $row['id'] : '',
            isset($row['cedula']) ? $row['cedula'] : '',
            isset($row['fecha']) ? $row['fecha'] : '',
            isset($row['precio_show']) ? $row['precio_show'] : '',
            isset($row['creado_en']) ? $row['creado_en'] : '',
            VALIDATION_SALT,
        ));

        return strtoupper(substr(hash('sha256', $payload), 0, 24));
    }
}

if (!function_exists('format_currency')) {
    function format_currency($amount)
    {
        return '$ ' . number_format((float) $amount, 2, '.', ',');
    }
}

if (!function_exists('current_base_url')) {
    function current_base_url()
    {
        $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $https ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

        return $scheme . '://' . $host;
    }
}

if (!function_exists('build_validation_url')) {
    function build_validation_url($id, $token)
    {
        $path = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '/comprobanteinvestpdf.php';
        return current_base_url() . $path . '?validate=1&id=' . urlencode((string) $id) . '&token=' . urlencode($token);
    }
}

if (!function_exists('fetch_remote_content')) {
    function fetch_remote_content($url)
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 6);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $content = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($content !== false && $status >= 200 && $status < 300) {
                return $content;
            }
        }

        $context = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'timeout' => 6,
            ),
        ));

        $content = @file_get_contents($url, false, $context);
        if ($content !== false) {
            return $content;
        }

        return null;
    }
}

if (!function_exists('create_qr_image')) {
    function create_qr_image($payload, $cacheKey)
    {
        if (!is_dir(QR_TEMP_DIR)) {
            @mkdir(QR_TEMP_DIR, 0775, true);
        }

        if (!is_dir(QR_TEMP_DIR) || !is_writable(QR_TEMP_DIR)) {
            return null;
        }

        $targetPath = QR_TEMP_DIR . '/qr_' . $cacheKey . '.png';
        $serviceUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&margin=0&data=' . rawurlencode($payload);
        $pngData = fetch_remote_content($serviceUrl);

        if ($pngData === null || strlen($pngData) < 16) {
            return null;
        }

        // PNG signature check.
        if (substr($pngData, 0, 8) !== "\x89PNG\x0D\x0A\x1A\x0A") {
            return null;
        }

        $written = @file_put_contents($targetPath, $pngData);
        if ($written === false) {
            return null;
        }

        return $targetPath;
    }
}

if (!class_exists('ComprobantePDF')) {
    class ComprobantePDF extends FPDF
    {
        public $generatedAt = '';
        public $validationCode = '';
        public $qrImagePath = null;

        public function Footer()
        {
            drawFooter($this, $this->generatedAt, $this->validationCode, $this->qrImagePath);
        }
    }
}

if (!function_exists('drawHeader')) {
    function drawHeader($pdf, array $data)
    {
        $leftX = 10;
        $rightX = 118;

        if (file_exists(LOGO_PATH)) {
            $pdf->Image(LOGO_PATH, $leftX, 8, 24);
        } elseif (file_exists(LOGO_FALLBACK_PATH)) {
            $pdf->Image(LOGO_FALLBACK_PATH, $leftX, 8, 24);
        }

        $pdf->SetXY($rightX, 10);
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->SetTextColor(23, 37, 84);
        $pdf->Cell(82, 6, 'H ENTERTAINMENT', 0, 1, 'R');

        $pdf->SetX($rightX);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(17, 24, 39);
        $pdf->Cell(82, 6, pdf_text('RECIBO DE PAGO'), 0, 1, 'R');

        $pdf->SetX($rightX);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(107, 114, 128);
        $pdf->Cell(82, 5, pdf_text('Recibo #') . $data['id'], 0, 1, 'R');

        $pdf->SetX($rightX);
        $pdf->Cell(82, 5, 'Fecha: ' . $data['fecha_doc'], 0, 1, 'R');

        $pdf->SetDrawColor(209, 213, 219);
        $pdf->Line(10, 34, 200, 34);
        $pdf->Ln(18);
    }
}

if (!function_exists('drawClientInfo')) {
    function drawClientInfo($pdf, array $data)
    {
        $startX = 10;
        $startY = $pdf->GetY();
        $blockW = 190;
        $blockH = 54;

        $pdf->SetFillColor(248, 250, 252);
        $pdf->Rect($startX, $startY, $blockW, $blockH, 'F');

        $pdf->SetDrawColor(226, 232, 240);
        $pdf->Rect($startX, $startY, $blockW, $blockH);

        $pdf->SetXY($startX + 5, $startY + 5);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(17, 24, 39);
        $pdf->Cell(0, 5, pdf_text('Datos del Cliente'), 0, 1, 'L');

        $left = array(
            array('Nombre', $data['nombre']),
            array(pdf_text('Cedula'), $data['cedula']),
            array(pdf_text('Telefono'), $data['celular']),
            array('Correo', $data['email']),
        );

        $right = array(
            array('Lugar', $data['lugar']),
            array('Fecha', $data['fecha']),
            array('Hora', $data['hora']),
        );

        $lineY = $startY + 13;
        foreach ($left as $row) {
            $pdf->SetXY($startX + 6, $lineY);
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(120, 120, 120);
            $pdf->Cell(26, 5, pdf_text($row[0]) . ':', 0, 0, 'L');

            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor(17, 24, 39);
            $pdf->Cell(58, 5, pdf_text($row[1]), 0, 1, 'L');

            $lineY += 9;
        }

        $lineY = $startY + 13;
        foreach ($right as $row) {
            $pdf->SetXY($startX + 104, $lineY);
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(120, 120, 120);
            $pdf->Cell(20, 5, pdf_text($row[0]) . ':', 0, 0, 'L');

            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor(17, 24, 39);
            $pdf->Cell(66, 5, pdf_text($row[1]), 0, 1, 'L');

            $lineY += 9;
        }

        $pdf->SetY($startY + $blockH + 8);
    }
}

if (!function_exists('drawSummary')) {
    function drawSummary($pdf, $total)
    {
        $x = 10;
        $y = $pdf->GetY();
        $w = 190;
        $h = 22;

        $pdf->SetFillColor(219, 234, 254);
        $pdf->Rect($x, $y, $w, $h, 'F');

        $pdf->SetDrawColor(147, 197, 253);
        $pdf->Rect($x, $y, $w, $h);

        $pdf->SetXY($x + 6, $y + 4);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(30, 64, 175);
        $pdf->Cell(90, 6, 'TOTAL PAGADO', 0, 0, 'L');

        $pdf->SetFont('Arial', 'B', 17);
        $pdf->SetTextColor(17, 24, 39);
        $pdf->Cell(88, 10, format_currency($total), 0, 1, 'R');

        $pdf->SetY($y + $h + 8);
    }
}

if (!function_exists('drawDescription')) {
    function drawDescription($pdf, $text)
    {
        $safeText = trim((string) $text) === '' ? 'Sin comentarios adicionales.' : $text;

        $x = 10;
        $y = $pdf->GetY();
        $w = 190;

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(17, 24, 39);
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, 6, pdf_text('Descripcion del Servicio'), 0, 1, 'L');

        $bodyY = $pdf->GetY() + 1;
        $bodyH = 26;

        $pdf->SetFillColor(249, 250, 251);
        $pdf->Rect($x, $bodyY, $w, $bodyH, 'F');

        $pdf->SetDrawColor(229, 231, 235);
        $pdf->Rect($x, $bodyY, $w, $bodyH);

        $pdf->SetXY($x + 4, $bodyY + 4);
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->SetTextColor(55, 65, 81);
        $pdf->MultiCell($w - 8, 5, pdf_text($safeText), 0, 'L');

        $pdf->SetY($bodyY + $bodyH + 8);
    }
}

if (!function_exists('drawSignature')) {
    function drawSignature($pdf)
    {
        $pdf->Ln(8);
        $y = $pdf->GetY();
        $pdf->SetDrawColor(156, 163, 175);
        $pdf->Line(70, $y + 10, 140, $y + 10);

        $pdf->SetY($y + 12);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->Cell(0, 6, pdf_text('Firma de Recepcion'), 0, 1, 'C');
    }
}

if (!function_exists('drawFooter')) {
    function drawFooter($pdf, $generatedAt, $validationCode, $qrImagePath)
    {
        if (!empty($qrImagePath) && file_exists($qrImagePath)) {
            $pdf->SetFont('Arial', '', 7);
            $pdf->SetTextColor(120, 120, 120);
            $pdf->SetXY(144, 248);
            $pdf->Cell(54, 4, 'Validacion: ' . $validationCode, 0, 1, 'R');
            $pdf->Image($qrImagePath, 172, 252, 26, 26);
        }

        $pdf->SetY(-16);
        $pdf->SetDrawColor(229, 231, 235);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());

        $pdf->SetY(-13);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->Cell(90, 5, 'EMC INVEST / FACTANSYS', 0, 0, 'L');
        $pdf->Cell(60, 5, 'Generado: ' . $generatedAt, 0, 0, 'C');
        $pdf->Cell(40, 5, 'Pagina ' . $pdf->PageNo() . '/{nb}', 0, 0, 'R');
    }
}

if (isset($_GET['validate']) && (string) $_GET['validate'] === '1' && isset($_GET['id']) && isset($_GET['token'])) {
    $id = (int) $_GET['id'];
    $token = strtoupper(trim((string) $_GET['token']));

    $query = 'SELECT id, cedula, fecha, precio_show, creado_en, nombre FROM eventos WHERE id = ? LIMIT 1';
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    header('Content-Type: text/html; charset=UTF-8');

    if ($row = mysqli_fetch_assoc($result)) {
        $expected = build_validation_token($row);
        $valid = hash_equals($expected, $token);

        echo '<!doctype html><html><head><meta charset="utf-8"><title>Validacion de Comprobante</title></head><body style="font-family:Arial,sans-serif;background:#f8fafc;color:#111827;padding:24px;">';
        if ($valid) {
            echo '<h2 style="color:#166534;margin:0 0 8px;">Comprobante valido</h2>';
            echo '<p style="margin:4px 0;">ID: <strong>' . (int) $row['id'] . '</strong></p>';
            echo '<p style="margin:4px 0;">Cliente: <strong>' . htmlspecialchars((string) $row['nombre'], ENT_QUOTES, 'UTF-8') . '</strong></p>';
            echo '<p style="margin:4px 0;">Token: <strong>' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '</strong></p>';
        } else {
            echo '<h2 style="color:#b91c1c;margin:0 0 8px;">Token invalido</h2>';
            echo '<p style="margin:4px 0;">No se pudo validar este comprobante.</p>';
        }
        echo '</body></html>';
    } else {
        echo '<!doctype html><html><head><meta charset="utf-8"><title>Validacion de Comprobante</title></head><body style="font-family:Arial,sans-serif;background:#f8fafc;color:#111827;padding:24px;"><h2 style="color:#b91c1c;margin:0 0 8px;">Registro no encontrado</h2></body></html>';
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    exit;
}

// Verificar si se reciben los parametros para generar el PDF.
if (isset($_GET['id']) && isset($_GET['cedula'])) {
    $id = (int) $_GET['id'];
    $cedula = (string) $_GET['cedula'];

    $query = 'SELECT id, nombre, cedula, celular, email, lugar, fecha, hora, comentarios, precio_show FROM eventos WHERE id = ? AND cedula = ?';
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'is', $id, $cedula);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $validationToken = build_validation_token($row);
        $validationUrl = build_validation_url($row['id'], $validationToken);
        $qrPath = create_qr_image($validationUrl, md5($validationUrl));

        $data = array(
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'cedula' => $row['cedula'],
            'celular' => $row['celular'],
            'email' => $row['email'],
            'lugar' => $row['lugar'],
            'fecha' => !empty($row['fecha']) ? date('d/m/Y', strtotime($row['fecha'])) : '',
            'hora' => format_time_12h($row['hora']),
            'comentarios' => $row['comentarios'],
            'precio_show' => $row['precio_show'],
            'fecha_doc' => date('d/m/Y'),
        );

        $pdf = new ComprobantePDF('P', 'mm', 'A4');
        $pdf->AliasNbPages();
        $pdf->generatedAt = date('d/m/Y H:i');
        $pdf->validationCode = $validationToken;
        $pdf->qrImagePath = $qrPath;
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 22);
        $pdf->AddPage();

        drawHeader($pdf, $data);
        drawClientInfo($pdf, $data);
        drawSummary($pdf, $data['precio_show']);
        drawDescription($pdf, $data['comentarios']);
        drawSignature($pdf);

        $pdf->Output('I', 'comprobante_evento.pdf');

        if (!empty($qrPath) && file_exists($qrPath)) {
            @unlink($qrPath);
        }
    } else {
        echo 'No se encontro el registro.';
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
} else {
    echo 'Parametros no validos.';
}
