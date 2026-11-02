<?php
/** Pruebas de centavos y distribución de propinas. No modifica ninguna base. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('BASEPATH',__DIR__.'/../system/');
require __DIR__.'/../application/libraries/Dinero.php';
function verificar($ok,$mensaje) {
    if (!$ok) { throw new RuntimeException($mensaje); }
    echo 'OK: '.$mensaje."\n";
}
try {
    verificar(Dinero::centavos('100.00')===10000,'Conversión exacta a centavos');
    verificar(Dinero::centavos('0.01')===1,'Un centavo no se pierde');
    verificar(Dinero::centavos('12.5')===1250,'Un decimal se interpreta correctamente');
    verificar(Dinero::decimal(12345)==='123.45','Formato sin coma flotante');
    verificar(Dinero::propina(10000)===1000,'10% de Q 100 es Q 10');
    verificar(Dinero::propina(5)===1,'Medio centavo se redondea hacia arriba');
    verificar(Dinero::propina(4)===0,'Menos de medio centavo se redondea hacia abajo');
    verificar(Dinero::repartirPropina(array(1=>6000,2=>4000))===array(1=>600,2=>400),'Propina de Q 10 repartida en Q 6 y Q 4');
    $reparto=Dinero::repartirPropina(array(1=>5,2=>5,3=>5));
    verificar(array_sum($reparto)===2 && $reparto[1]===1 && $reparto[2]===1 && $reparto[3]===0,'Repartir no agrega centavos al total');
    foreach (array('-1','1.001','1e2',' 1','1,50','NaN','10000000000.00','',NULL,array('1')) as $malo) {
        $rechazado=FALSE;
        try { Dinero::centavos($malo); } catch (InvalidArgumentException $e) { $rechazado=TRUE; }
        verificar($rechazado,'Monto inválido rechazado: '.json_encode($malo));
    }
    mt_srand(2026);
    for ($caso=0;$caso<1000;$caso++) {
        $consumos=array();
        for ($i=1;$i<=mt_rand(2,20);$i++) { $consumos[$i]=mt_rand(0,1000000); }
        $propinas=Dinero::repartirPropina($consumos);
        if (array_sum($propinas)!==Dinero::propina(array_sum($consumos))) { throw new RuntimeException('La propina cambió al separar cuentas.'); }
        foreach ($consumos as $id=>$subtotal) {
            if ($propinas[$id]<intdiv($subtotal,10) || $propinas[$id]>intdiv($subtotal,10)+1) { throw new RuntimeException('Distribución desproporcionada.'); }
        }
    }
    echo "OK: 1000 distribuciones conservan el total y ajustan como máximo un centavo por cuenta.\n";
} catch (Throwable $e) { fwrite(STDERR,'FALLO: '.$e->getMessage()."\n");exit(1); }
