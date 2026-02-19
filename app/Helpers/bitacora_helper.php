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
