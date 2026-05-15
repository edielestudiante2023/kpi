<?php

/**
 * Helpers de permisos para el módulo CRM.
 *
 * Reglas:
 *  - "Admin CRM" = usuarios con `crm_admin = 1` en `users`, O con rol 1 (superadmin) o 2 (admin).
 *    Los admin CRM ven y editan todas las oportunidades.
 *  - "Vendedor CRM" = usuarios con `crm_habilitado = 1`. Solo ven/editan las suyas.
 *  - Cualquier usuario sin ninguna de las dos cosas → sin acceso al módulo.
 *  - El contador (rol 5) está explícitamente fuera del CRM (no le aparece en nav).
 */

if (!function_exists('crm_es_admin')) {
    /**
     * ¿El usuario actual puede ver/editar todas las oportunidades?
     */
    function crm_es_admin(): bool
    {
        $s = session();
        if (!$s) return false;
        if ((int) $s->get('crm_admin') === 1) return true;
        $rol = (int) $s->get('id_roles');
        return in_array($rol, [1, 2], true);
    }
}

if (!function_exists('crm_tiene_acceso')) {
    /**
     * ¿El usuario actual tiene acceso al módulo CRM (cualquier nivel)?
     */
    function crm_tiene_acceso(): bool
    {
        $s = session();
        if (!$s) return false;
        if ((int) $s->get('id_roles') === 5) return false; // contador externo, no
        return crm_es_admin() || (int) $s->get('crm_habilitado') === 1;
    }
}

if (!function_exists('crm_puede_ver_oportunidad')) {
    /**
     * ¿Puede el usuario actual ver esta oportunidad?
     * $oportunidad = array con al menos id_responsable.
     */
    function crm_puede_ver_oportunidad(array $oportunidad): bool
    {
        if (crm_es_admin()) return true;
        $idUsuario = (int) session()->get('id_users');
        return $idUsuario > 0 && (int) ($oportunidad['id_responsable'] ?? 0) === $idUsuario;
    }
}

if (!function_exists('crm_puede_editar_oportunidad')) {
    /**
     * Misma regla que ver en el MVP (vendedor edita las propias; admin edita todas).
     */
    function crm_puede_editar_oportunidad(array $oportunidad): bool
    {
        return crm_puede_ver_oportunidad($oportunidad);
    }
}
