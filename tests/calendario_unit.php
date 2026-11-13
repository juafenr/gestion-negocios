<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
define('BASEPATH',dirname(__DIR__).'/system/');
require dirname(__DIR__).'/application/libraries/Calendario_negocio.php';
$zona_anterior=date_default_timezone_get();
try {
    $casos=array('2026-11-13T01:30:00Z'=>'2026-11-12','2026-11-13T05:59:59Z'=>'2026-11-12',
        '2026-11-13T06:00:00Z'=>'2026-11-13','2028-03-01T01:00:00Z'=>'2028-02-29','2027-01-01T02:00:00Z'=>'2026-12-31');
    foreach(array('Europe/Berlin','UTC','America/Guatemala') as $zona_php) {
        date_default_timezone_set($zona_php);
        foreach($casos as $instante=>$esperada) {
            $hoy=Calendario_negocio::hoy(new DateTimeImmutable($instante));
            if($hoy->format('Y-m-d H:i:s')!==$esperada.' 00:00:00' || date_default_timezone_get()!==$zona_php){throw new RuntimeException('Fecha incorrecta con PHP en '.$zona_php);}
            foreach(array(7,30,90) as $dias) {
                $desde=$hoy->modify('-'.($dias-1).' days');
                if((int)$desde->diff($hoy)->format('%a')!==$dias-1){throw new RuntimeException('Rango incorrecto.');}
            }
        }
    }
    echo "APROBADO: 15 cortes de calendario y 45 rangos; medianoche, año bisiesto y cambio de año.\n";
} finally {date_default_timezone_set($zona_anterior);}
