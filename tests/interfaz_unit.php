<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('BASEPATH',dirname(__DIR__).'/system/');
function html_escape($valor) { return htmlspecialchars($valor,ENT_QUOTES,'UTF-8'); }
require dirname(__DIR__).'/application/helpers/interfaz_helper.php';
$casos=array(array(NULL,'Sin vencimiento'),array('2028-02-28','Vencido'),array('2028-02-29','Vence hoy'),
    array('2028-03-30','Vence pronto'),array('2028-03-31','Vigente'));
foreach($casos as $caso) {
    if(strpos(ui_vencimiento($caso[0],'2028-02-29'),'>'.$caso[1].'<')===FALSE) {
        fwrite(STDERR,'FALLO: etiqueta de vencimiento '.$caso[1].PHP_EOL);exit(1);
    }
}
echo "APROBADO: cinco límites de vencimiento, incluido año bisiesto y límite de 30 días.\n";
