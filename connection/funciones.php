<?php

/*----------  Trae Fecha de BD y formatea  ----------*/
if (!function_exists("TraeFechaExplode")) {

    function TraeFechaExplode($fechaServer)
    {

        if (empty($fechaServer)) {
            $fecha_new = '';
        } else {
            $ex = explode("-", $fechaServer);
            $fecha_new = $ex[2] . '/' . $ex[1] . '/' . $ex[0];
        }
        return $fecha_new;
    }
}

/*----------  Formatea y guarda en BD  ----------*/
/*----------  Formatea y guarda en BD  ----------*/

if (!function_exists("ExplodeFecha")) {

    function ExplodeFecha($fecha)
    {

        if (!empty($fecha)) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                return $fecha;
            }
            $ex           = explode("/", $fecha);
            $fecha_change = $ex[2] . '-' . $ex[1] . '-' . $ex[0];
        } else {
            $fecha_change = '0000-00-00';
        }

        return $fecha_change;
    }
}
