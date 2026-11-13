<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Quetzales exactos: la lógica opera con centavos enteros, nunca con float. */
class Dinero
{
    public static function centavos($valor)
    {
        if (PHP_INT_SIZE < 8) {
            throw new RuntimeException('Se necesita PHP de 64 bits.');
        }
        if (!is_string($valor) || !preg_match('/^(0|[1-9][0-9]{0,9})(?:\.([0-9]{1,2}))?$/D', $valor, $m)) {
            throw new InvalidArgumentException('Usa montos positivos o cero, con punto y hasta dos decimales (ejemplo: 25.50).');
        }
        return ((int) $m[1]) * 100 + (int) str_pad($m[2] ?? '', 2, '0');
    }

    public static function decimal($centavos)
    {
        if (!is_int($centavos) || $centavos < 0 || $centavos > 999999999999) {
            throw new InvalidArgumentException('El monto supera el límite permitido.');
        }
        return intdiv($centavos, 100) . '.' . str_pad((string) ($centavos % 100), 2, '0', STR_PAD_LEFT);
    }

    /** Redondeo al centavo más cercano (mitades hacia arriba). */
    public static function propina($consumo, $porcentaje = 10)
    {
        if (!in_array($porcentaje, array(0, 10), TRUE)) { throw new InvalidArgumentException('Porcentaje de propina inválido.'); }
        return intdiv($consumo * $porcentaje + 50, 100);
    }

    /** Reparte el redondeo sin que separar cuentas cambie la propina total. */
    public static function repartirPropina($subtotales, $porcentaje = 10)
    {
        if (!in_array($porcentaje, array(0, 10), TRUE)) { throw new InvalidArgumentException('Porcentaje de propina inválido.'); }
        $resultado = array(); $restos = array(); $suma = 0; $asignado = 0;
        foreach ($subtotales as $id => $subtotal) {
            $resultado[$id] = intdiv($subtotal * $porcentaje, 100);
            $restos[$id] = ($subtotal * $porcentaje) % 100;
            $suma += $subtotal;
            $asignado += $resultado[$id];
        }
        $ids = array_keys($subtotales);
        usort($ids, function ($a, $b) use ($restos) {
            return $restos[$b] <=> $restos[$a] ?: $a <=> $b;
        });
        $faltan = self::propina($suma, $porcentaje) - $asignado;
        for ($i=0; $i<$faltan; $i++) { $resultado[$ids[$i]]++; }
        return $resultado;
    }

    /** Reparte un monto exacto por consumo y asigna los centavos restantes de forma estable. */
    public static function repartirMonto($subtotales, $monto)
    {
        if (!is_int($monto) || $monto < 0) { throw new InvalidArgumentException('Monto de propina inválido.'); }
        $suma = array_sum($subtotales);
        if ($suma === 0) {
            if ($monto !== 0) { throw new InvalidArgumentException('No puede repartirse propina sin consumo.'); }
            return array_fill_keys(array_keys($subtotales), 0);
        }
        $resultado = array(); $restos = array(); $asignado = 0;
        foreach ($subtotales as $id=>$subtotal) {
            if ($monto===0 || $subtotal <= intdiv(PHP_INT_MAX, $monto)) {
                $producto = $subtotal * $monto;
                $resultado[$id] = intdiv($producto, $suma);
                $restos[$id] = $producto % $suma;
            } else {
                if (!function_exists('bcmul')) { throw new RuntimeException('El reparto requiere BCMath para este importe.'); }
                $producto = bcmul((string)$subtotal,(string)$monto,0);
                $resultado[$id] = (int)bcdiv($producto,(string)$suma,0);
                $restos[$id] = (int)bcmod($producto,(string)$suma);
            }
            $asignado += $resultado[$id];
        }
        $ids = array_keys($subtotales);
        usort($ids, function ($a,$b) use ($restos) { return $restos[$b] <=> $restos[$a] ?: $a <=> $b; });
        for ($i=0;$i<$monto-$asignado;$i++) { $resultado[$ids[$i]]++; }
        return $resultado;
    }

    public static function mostrar($centavos)
    {
        if (!is_int($centavos) || $centavos < 0) { throw new InvalidArgumentException('Monto inválido.'); }
        return 'Q ' . intdiv($centavos,100) . '.' . str_pad((string)($centavos % 100),2,'0',STR_PAD_LEFT);
    }
}
