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

    /** 10%, redondeo al centavo más cercano (mitades hacia arriba). */
    public static function propina($consumo)
    {
        return intdiv($consumo + 5, 10);
    }

    /** Reparte el redondeo sin que separar cuentas cambie la propina total. */
    public static function repartirPropina($subtotales)
    {
        $resultado = array(); $restos = array(); $suma = 0; $asignado = 0;
        foreach ($subtotales as $id => $subtotal) {
            $resultado[$id] = intdiv($subtotal, 10);
            $restos[$id] = $subtotal % 10;
            $suma += $subtotal;
            $asignado += $resultado[$id];
        }
        $ids = array_keys($subtotales);
        usort($ids, function ($a, $b) use ($restos) {
            return $restos[$b] <=> $restos[$a] ?: $a <=> $b;
        });
        $faltan = self::propina($suma) - $asignado;
        for ($i=0; $i<$faltan; $i++) { $resultado[$ids[$i]]++; }
        return $resultado;
    }

    public static function mostrar($centavos)
    {
        if (!is_int($centavos) || $centavos < 0) { throw new InvalidArgumentException('Monto inválido.'); }
        return 'Q ' . intdiv($centavos,100) . '.' . str_pad((string)($centavos % 100),2,'0',STR_PAD_LEFT);
    }
}
