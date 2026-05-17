<?php

/**
 * Helpers de permisos para el módulo Marketing.
 *
 * Reglas (espejo del CRM):
 *  - Acceso al módulo: cualquier usuario logueado excepto contador (rol 5).
 *  - Admin de marketing: mismo flag `crm_admin` (no se duplica). Quien admina
 *    el CRM también admina el catálogo de tipos de acción de marketing.
 */

if (!function_exists('marketing_es_admin')) {
    function marketing_es_admin(): bool
    {
        $s = session();
        if (!$s) return false;
        if ((int) $s->get('crm_admin') === 1) return true;
        $rol = (int) $s->get('id_roles');
        return in_array($rol, [1, 2], true);
    }
}

if (!function_exists('marketing_tiene_acceso')) {
    function marketing_tiene_acceso(): bool
    {
        $s = session();
        if (!$s || !$s->get('id_users')) return false;
        if ((int) $s->get('id_roles') === 5) return false;
        return true;
    }
}
