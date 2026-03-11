<?php

if (!function_exists('formatMinutosHoras')) {
    function formatMinutosHoras(float $min): string
    {
        $h = floor($min / 60);
        $m = round($min - ($h * 60));
        if ($h > 0) return $h . 'h ' . $m . 'min';
        return $m . ' min';
    }
}

if (!function_exists('calcularMetaHoras')) {
    /**
     * Calcula meta de horas descontando novedades de tiempo.
     *
     * @param int    $diasHabiles        Días hábiles del periodo
     * @param string $jornada            'completa' o 'media'
     * @param float  $horasColectivas    Horas colectivas (base 8h jornada completa)
     * @param float  $horasIndividuales  Horas individuales de este usuario
     */
    function calcularMetaHoras(int $diasHabiles, string $jornada, float $horasColectivas = 0, float $horasIndividuales = 0): float
    {
        $horasDia   = $jornada === 'media' ? 4 : 8;
        $eficiencia = $jornada === 'media' ? 0.90 : 0.80;
        $metaBase   = $diasHabiles * $horasDia * $eficiencia;

        // Escalar colectivas para media jornada (4h off full-time → 2h off half-time)
        $colectivasAjustadas = $horasColectivas * ($horasDia / 8);
        $descuento = ($colectivasAjustadas + $horasIndividuales) * $eficiencia;

        return round(max($metaBase - $descuento, 0), 2);
    }
}
