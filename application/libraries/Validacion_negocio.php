<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Reglas compartidas de servidor (RF-61 y RF-62). No convierte entradas inválidas a cero. */
class Validacion_negocio
{
    public static function texto($valor, $campo, $maximo, $obligatorio = TRUE)
    {
        if (!is_string($valor)) { throw new InvalidArgumentException($campo.': debe ser texto.'); }
        $valor=trim($valor);
        if (($obligatorio && $valor==='') || mb_strlen($valor,'UTF-8')>$maximo || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',$valor)) {
            throw new InvalidArgumentException($campo.': revisa el contenido (máximo '.$maximo.' caracteres).');
        }
        return $valor;
    }
    public static function id($valor, $campo = 'Identificador')
    {
        if ((!is_string($valor) && !is_int($valor)) || !preg_match('/^[0-9]{1,10}$/D',(string)$valor) || (int)$valor<1 || (int)$valor>4294967295) {
            throw new InvalidArgumentException($campo.': selecciona un valor válido.');
        }
        return (int)$valor;
    }
    public static function decimal($valor, $campo, $permite_cero = TRUE, $digitos = 8)
    {
        if (!is_string($valor) || !preg_match('/^[0-9]{1,'.$digitos.'}(?:\.[0-9]{1,2})?$/D',$valor)) {
            throw new InvalidArgumentException($campo.': usa un número sin negativos, con punto y hasta dos decimales.');
        }
        $partes=explode('.',$valor);
        $centavos=((int)$partes[0])*100+(int)str_pad($partes[1] ?? '',2,'0');
        if (!$permite_cero && $centavos===0) { throw new InvalidArgumentException($campo.': debe ser mayor que cero.'); }
        return intdiv($centavos,100).'.'.str_pad((string)($centavos%100),2,'0',STR_PAD_LEFT);
    }
    public static function fecha($valor, $campo = 'Fecha', $opcional = FALSE)
    {
        if ($opcional && ($valor===NULL || $valor==='')) { return NULL; }
        if (!is_string($valor) || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D',$valor)) { throw new InvalidArgumentException($campo.': usa una fecha válida.'); }
        $d=DateTime::createFromFormat('!Y-m-d',$valor);
        if (!$d || $d->format('Y-m-d')!==$valor || $valor<'1000-01-01' || $valor>'9998-12-31') { throw new InvalidArgumentException($campo.': no es válida.'); }
        return $valor;
    }
    public static function insumo(array $datos, $nuevo)
    {
        $datos['nombre']=self::texto($datos['nombre'] ?? NULL,'Nombre',150);
        $datos['stock_minimo']=self::decimal($datos['stock_minimo'] ?? NULL,'Stock mínimo');
        if ($nuevo) { $datos['cantidad_actual']=self::decimal($datos['cantidad_actual'] ?? NULL,'Cantidad actual'); }
        if (!in_array($datos['unidad_medida'] ?? NULL,array('kg','g','mg','l','ml','unidad'),TRUE)) { throw new InvalidArgumentException('Unidad de medida no válida.'); }
        $datos['fecha_vencimiento']=self::fecha($datos['fecha_vencimiento'] ?? NULL,'Vencimiento',TRUE);
        return $datos;
    }
    public static function clave($valor)
    {
        if (!is_string($valor) || !preg_match('/^[a-f0-9]{64}$/D',$valor)) { throw new InvalidArgumentException('Recarga el formulario antes de guardar.'); }
        return $valor;
    }
}
