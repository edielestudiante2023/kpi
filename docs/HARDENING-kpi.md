# HARDENING DE REPOSITORIO — kpi

**Fecha:** 2026-04-05
**Aplicativo:** kpi — Sistema de Gestion de Indicadores de Desempeno
**Empresa:** Cycloid Talent
**Preparado para:** Edwin Lopez (consultor de infraestructura)

---

## TABLA DE CONTENIDO

1. Descripcion del aplicativo
2. Mapa de base de datos
3. Inventario de API Keys y servicios externos
4. Documentacion del proyecto (README, CONTRIBUTING, .env.example)
5. Ramas de trabajo
6. Pipelines CI/CD (Gitea)
7. Organizacion del repositorio
8. Hallazgos criticos y acciones pendientes

---

## 1. DESCRIPCION DEL APLICATIVO

### Stack tecnologico

| Componente | Tecnologia |
|------------|-----------|
| Backend | PHP 8.4 + CodeIgniter 4.7.2 |
| Base de datos | MySQL 8.0.45 (DigitalOcean Managed, SSL required) |
| Servidor web | Nginx (Ubuntu 24.04, Hetzner LXC) |
| Email | SendGrid API v3 |
| IA | OpenAI GPT-3.5-turbo (generacion de indicadores y actividades) |
| PWA | Bitacora de tiempo (manifest + service worker + push notifications) |
| Push | Web Push API (minishlink/web-push ^10.0) |
| Excel | PhpSpreadsheet (importacion CSV, exportaciones) |

### Modulos principales (10)

| Modulo | Descripcion |
|--------|-------------|
| Indicadores KPI | Definir, asignar, diligenciar y evaluar indicadores por perfil de cargo con formulas personalizadas |
| Actividades/Tickets | Tablero Kanban por estado y responsable, comentarios, archivos adjuntos, dashboard |
| Bitacora (PWA) | Registro de tiempo por actividad, analisis diario/semanal/mensual, centros de costo, liquidacion quincenal |
| Conciliaciones | Portafolios, facturacion, cuentas bancarias, conciliacion bancaria, carga masiva CSV |
| Usuarios y Roles | CRUD completo con perfiles de cargo, equipos, areas, jerarquias jefe-subordinado |
| Dashboards | Vistas diferenciadas por rol: superadmin, admin, jefatura, trabajador |
| Auditoria | Historial de cambios en indicadores, ediciones, trazabilidad completa |
| Sesiones | Monitoreo de tiempo de uso por usuario, sesiones activas, exportacion |
| Notificaciones | Email (SendGrid) + Push (Web Push API) + preferencias por usuario |
| IA (OpenAI) | Generacion automatica de indicadores y actividades con GPT-3.5-turbo |

### Roles de usuario

| Rol | Acceso |
|-----|--------|
| superadmin | Todo el sistema + gestion de usuarios + configuracion global |
| admin | Dashboard administrativo + gestion de equipos y areas |
| jefatura | Indicadores de equipo + aprobacion de formulas + historial jerarquico |
| trabajador | Mis indicadores + diligenciar formulas + bitacora de tiempo |

### Estructura del proyecto

```
kpi/
├── app/
│   ├── Commands/          # 5 comandos spark (cron jobs)
│   ├── Config/            # Routes.php, Database.php, OpenAI.php, Filters.php (42 archivos)
│   ├── Controllers/       # 35 controladores
│   ├── Filters/           # Auth, SesionTrackingFilter
│   ├── Helpers/           # bitacora_helper
│   ├── Libraries/         # 5 librerias (Evaluador, Notificadores, OpenAI, Push)
│   ├── Models/            # 37 modelos
│   └── Views/             # 124 vistas (17 subdirectorios por modulo)
├── docs/                  # Documentacion tecnica
├── migrations/            # 23 migraciones SQL
├── public/                # Punto de entrada web + PWA assets
├── tests/                 # Tests PHPUnit
├── writable/              # Logs, cache, sesiones, uploads
├── .env                   # Variables de entorno (NO commitear)
├── .env.example           # Template de variables
├── CONTRIBUTING.md        # Guia de contribucion
├── composer.json          # Dependencias PHP
└── spark                  # CLI de CodeIgniter
```

### Cron jobs (4 tareas programadas)

| Comando | Frecuencia | Descripcion |
|---------|-----------|-------------|
| `php spark bitacora:resumen-diario` | Diario | Envia reporte diario de actividades de bitacora |
| `php spark bitacora:notificar-activas` | Cada 30 min | Notifica actividades activas o vencidas |
| `php spark actividades:recordatorio-revision` | Diario | Recuerda items en revision a creadores (max 2/dia) |
| `php spark resumen-diario` | Diario | Resumen diario general |

---

## 2. MAPA DE BASE DE DATOS

**Motor:** MySQL 8.0.45 (DigitalOcean Managed)
**Base de datos:** kpicycloid
**Tamano total:** 5.03 MB
**SSL:** Required

### Usuarios de base de datos

| Usuario | Permisos | Uso |
|---------|----------|-----|
| cycloid_userdb | Full access (SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, INDEX, etc.) | Aplicacion principal (CRUD) + migraciones |

**Nota:** No existe un usuario readonly. Se recomienda crear uno para consultas de dashboards/reportes.

### Resumen

- **39 tablas** (BASE TABLE)
- **5 vistas** (VIEW)
- **38 foreign keys** definidas
- **8 tablas vacias** (21%) — modulos pendientes o nuevos

### Tablas por modulo funcional

**Nucleo — Usuarios y roles (6 tablas):**

| Tabla | Registros | Tamano |
|-------|-----------|--------|
| users | 8 | 80 KB |
| roles | 4 | 32 KB |
| accesos_rol | 18 | 32 KB |
| perfiles_cargo | 6 | 16 KB |
| equipos | 4 | 48 KB |
| areas | 6 | 32 KB |

**Indicadores KPI (5 tablas):**

| Tabla | Registros | Tamano |
|-------|-----------|--------|
| indicadores | 12 | 32 KB |
| indicadores_perfil | 5 | 48 KB |
| indicadores_area | 0 | 48 KB |
| partes_formula_indicador | 78 | 32 KB |
| historial_indicadores | 18 | 48 KB |

**Actividades/Tickets (6 tablas):**

| Tabla | Registros | Tamano |
|-------|-----------|--------|
| actividades | 83 | 192 KB |
| actividad_comentarios | 26 | 48 KB |
| actividad_historial | 137 | 48 KB |
| actividad_archivos | 6 | 48 KB |
| actividad_paginas | 0 | 64 KB |
| categorias_actividad | 6 | 16 KB |

**Bitacora de tiempo (8 tablas):**

| Tabla | Registros | Tamano |
|-------|-----------|--------|
| bitacora_actividades | 680 | 144 KB |
| centros_costo | 15 | 48 KB |
| liquidaciones_bitacora | 0 | 32 KB |
| detalle_liquidacion | 16 | 48 KB |
| bitacora_correcciones | 4 | 64 KB |
| dias_festivos | 18 | 48 KB |
| dias_habiles_config | 178 | 48 KB |
| novedades_individuales | 2 | 32 KB |

**Conciliaciones bancarias (7 tablas):**

| Tabla | Registros | Tamano |
|-------|-----------|--------|
| tbl_conciliacion_bancaria | 3,103 | 2,624 KB |
| tbl_facturacion | 1,245 | 672 KB |
| tbl_portafolios | 2 | 32 KB |
| tbl_cuentas_banco | 2 | 32 KB |
| tbl_centros_costo | 13 | 32 KB |
| tbl_facturacion_cruda | 0 | 64 KB |
| tbl_movimiento_bancario_crudo | 0 | 64 KB |

**Otros (7 tablas):**

| Tabla | Registros | Tamano |
|-------|-----------|--------|
| sesiones_usuario | 41 | 80 KB |
| push_subscriptions | 10 | 32 KB |
| indicador_auditoria | 0 | 48 KB |
| novedades_colectivas | 0 | 32 KB |
| preferencias_notificacion | 0 | 32 KB |
| resumen_uso_diario | 0 | 48 KB |
| actividades_historial | 0 | 32 KB |

### Vistas (5)

| Vista | Proposito |
|-------|-----------|
| vw_auditoria_indicadores | Historial de cambios en indicadores |
| vw_estadisticas_actividades | Estadisticas agregadas de actividades |
| vw_resumen_uso_usuario | Resumen de tiempo de uso por usuario |
| vw_sesiones_usuario | Detalle de sesiones de usuario |
| vw_tablero_actividades | Datos para tablero Kanban de actividades |

### Foreign Keys (38 relaciones)

**Tabla central: `users`** — 14 tablas dependen de `users.id_users` via FK:
- actividad_archivos, actividad_comentarios, actividad_historial, actividades (creador + asignado), bitacora_actividades, centros_costo, detalle_liquidacion, indicador_auditoria, liquidaciones_bitacora, preferencias_notificacion, push_subscriptions, resumen_uso_diario, sesiones_usuario, users (autorreferencia: id_jefe)

**Otras dependencias importantes:**
- `actividades` → areas, categorias_actividad, users (creador y asignado)
- `bitacora_actividades` → users, centros_costo
- `indicadores_perfil` → indicadores, perfiles_cargo
- `partes_formula_indicador` → indicadores
- `tbl_conciliacion_bancaria` → tbl_centros_costo, tbl_cuentas_banco
- `tbl_facturacion` → tbl_portafolios

### Tablas mas grandes por peso

| Tabla | Registros | Tamano |
|-------|-----------|--------|
| tbl_conciliacion_bancaria | 3,103 | 2,624 KB |
| tbl_facturacion | 1,245 | 672 KB |
| actividades | 83 | 192 KB |
| bitacora_actividades | 680 | 144 KB |
| sesiones_usuario | 41 | 80 KB |

### Tablas vacias (8)

actividad_paginas, actividades_historial, indicador_auditoria, indicadores_area, liquidaciones_bitacora, novedades_colectivas, preferencias_notificacion, resumen_uso_diario, tbl_facturacion_cruda, tbl_movimiento_bancario_crudo

**Observacion:** Algunas son modulos nuevos (conciliacion cruda), otras posiblemente por implementar (resumen_uso_diario, indicadores_area).

---

## 3. INVENTARIO DE API KEYS Y SERVICIOS EXTERNOS

### Resumen

| Servicio | Variable | Archivos | Estado |
|----------|----------|----------|--------|
| SendGrid | `SENDGRID_API_KEY` | 3 | Activa |
| OpenAI | `OPENAI_API_KEY` | 3 | Activa |
| Web Push | VAPID keys locales | 1 | Activa (sin API key externa) |

### SendGrid

Usado en 3 archivos para email transaccional: recuperacion de password, notificaciones de actividades, reportes diarios de bitacora.

**Patron:** `$apiKey = env('SENDGRID_API_KEY');` → `new \SendGrid($apiKey)`

**Archivos:**
- `app/Controllers/AuthController.php:222` — Recuperacion de password
- `app/Libraries/NotificadorBitacora.php:500` — Reporte diario de bitacora
- `app/Libraries/NotificadorActividades.php:623` — Notificaciones de actividades

**From hardcodeado:** `notificacion.cycloidtalent@cycloidtalent.com` en los 3 archivos.

### OpenAI

Usado en 3 archivos para IA generativa: generacion de indicadores KPI y generacion de actividades.

**Patron:** cURL directo a `https://api.openai.com/v1/chat/completions`
**Modelo:** gpt-3.5-turbo (configurable via `OPENAI_MODEL`)

**Archivos:**
- `app/Config/OpenAI.php:45` — Configuracion centralizada
- `app/Libraries/OpenAIService.php:24` — Servicio de IA
- `app/Controllers/OpenAIController.php` — Endpoints `/ia/generar-indicador` y `/ia/generar-actividad`

### Web Push (minishlink/web-push)

- `app/Libraries/PushNotifier.php` — Notificaciones push para bitacora
- Usa VAPID keys (public/private) desde `.env`
- No requiere API key externa

### HALLAZGOS CRITICOS DE SEGURIDAD

**CRITICO — `.env.production` en el repositorio:**

El archivo `.env.production` esta trackeado en git y contiene credenciales de produccion:
- Host de BD DigitalOcean
- Usuario y password de BD
- Todas las API keys

**CRITICO — Credenciales hardcodeadas en migraciones:**

| Archivo | Problema |
|---------|----------|
| `migrations/2026_03_17_truncate_sesiones.php:12-15` | Host + usuario de BD de produccion hardcodeado |
| `migrations/2026_03_17_fix_sesiones_fecha_fin.php:24-27` | Host + usuario de BD de produccion hardcodeado |
| `migrations/2026_03_17_diagnostico_sesiones.php:14-17` | Host + usuario de BD de produccion hardcodeado |
| `migrations/2026_03_11_novedades_tiempo_pro.php:9-12` | Host + usuario de BD de produccion hardcodeado |
| `migrations/2026_03_11_fix_duplicados_checklist.php:9-12` | Host + usuario de BD de produccion hardcodeado |
| `migrations/2026_03_10_bitacora_correcciones_pro.php:9-12` | Host + usuario de BD de produccion hardcodeado |
| `migrations/2026_02_19_habilitar_usuarios_bitacora.php:14-18` | Host + usuario de BD de produccion hardcodeado |
| `migrations/2026_02_19_bitacora_tables.php:17-21` | Host + usuario de BD de produccion hardcodeado |
| + 5 migraciones adicionales con host hardcodeado | Usan env() pero con host visible |

**CRITICO — Credenciales en documentacion:**

| Archivo | Problema |
|---------|----------|
| `docs/liquidacion-quincenal.md:283-286` | Credenciales de produccion documentadas |

**ALTO — `public/backupdb.sql` trackeado:**

Dump de base de datos con estructura y datos trackeado en git dentro del directorio publico.

**ALTO — Email from hardcodeado:**

`notificacion.cycloidtalent@cycloidtalent.com` hardcodeado en 3 archivos en vez de usar variable de entorno.

**MEDIO — `app/Controllers/TestDB.php` en produccion:**

Controlador de prueba de conexion a BD que no deberia estar en produccion.

### Recomendaciones

1. **INMEDIATO:** Rotar TODAS las API Keys y passwords de BD (pueden estar en historial de git)
2. **INMEDIATO:** Remover `.env.production` del tracking de git
3. **URGENTE:** Extraer host de BD de migraciones a variables de entorno
4. **URGENTE:** Remover credenciales de `docs/liquidacion-quincenal.md`
5. **ALTO:** Mover email from a variable de entorno
6. **ALTO:** Eliminar `TestDB.php` y `public/backupdb.sql`

---

## 4. DOCUMENTACION DEL PROYECTO

### Archivos creados en el repositorio

| Archivo | Descripcion |
|---------|-------------|
| `README.md` | Documentacion principal: stack, modulos, roles, estructura, instalacion, cron jobs, deploy |
| `CONTRIBUTING.md` | Guia de contribucion: flujo de ramas, convencion de commits, reglas, proceso de revision |
| `.env.example` | Template con todas las variables de entorno necesarias (sin valores reales) |

### README.md incluye

- Stack tecnologico completo (PHP 8.4, CI4 4.7.2, MySQL 8, PWA)
- 10 modulos con descripcion
- 4 roles de usuario con accesos
- Estructura de carpetas
- Requisitos previos e instrucciones de instalacion
- 6 variables de entorno documentadas
- 4 cron jobs con frecuencia y descripcion
- Instrucciones de deploy
- Links a documentacion adicional

### CONTRIBUTING.md incluye

- Flujo de ramas (main → develop → feature/ → hotfix/)
- Convencion de commits (feat:, fix:, docs:, refactor:, chore:)
- Convencion de nombres de ramas
- 5 reglas (no push directo, no credenciales, no temporales, no destructivos, no force push)
- Proceso de revision con pipeline CI/CD

### .env.example incluye

- Variables de entorno para BD principal
- API Keys de email (SendGrid) y OpenAI
- Configuracion de bitacora (emails, quincena, correcciones)
- VAPID keys para push notifications
- Configuracion de sesiones

---

## 5. RAMAS DE TRABAJO

### Estructura creada

```
main          ← Produccion. Solo codigo validado y estable.
develop       ← Integracion. Aqui se unen los cambios antes de ir a main.
feature/xxx   ← Nuevas funcionalidades. Se crean desde develop.
hotfix/xxx    ← Correcciones urgentes. Se crean desde main.
```

### Estado actual

| Rama | Estado | Commit actual |
|------|--------|---------------|
| main | Existente, en remoto | 52ec650 (upgrade CI4 v4.7.2) |
| cycloid | Existente, en remoto — sincronizada con main | 52ec650 |
| develop | **Creada** durante hardening, pendiente push a remoto | Mismo commit que main |
| backup-antes-de-e73 | Legacy — solo local | Backup antiguo |
| backup-antes-de-volver-a-ayer | Legacy — solo local | Backup antiguo |

### Ramas a limpiar

| Rama | Accion | Razon |
|------|--------|-------|
| cycloid | Mantener temporalmente | Rama de trabajo actual del usuario |
| backup-antes-de-e73 | Eliminar | Backup local obsoleto |
| backup-antes-de-volver-a-ayer | Eliminar | Backup local obsoleto |

### Proteccion de ramas (pendiente en Gitea)

- **main:** protegida, requiere PR, no push directo
- **develop:** protegida, requiere PR desde feature/

### Flujo de trabajo

- Nueva funcionalidad: `develop` → `feature/nombre` → PR a `develop` → PR a `main`
- Hotfix urgente: `main` → `hotfix/nombre` → PR a `main` + PR a `develop`

---

## 6. PIPELINES CI/CD

### Plataforma: Gitea con Gitea Runner (act_runner)

### Pipeline 1: Validar y Deploy a Dev/QA

**Archivo:** `.gitea/workflows/validate-and-deploy-qa.yml`
**Trigger:** Push/PR a develop o feature/*

```
git push → Gitea → Runner → Tests + Trivy + Semgrep → Deploy SSH → LXC (Dev/QA)
```

| Job | Que hace | Bloquea si falla |
|-----|----------|------------------|
| test | `php -l` en todos los .php de app/ | Si |
| trivy | Escaneo de vulnerabilidades en dependencias (HIGH/CRITICAL) | Si |
| semgrep | Analisis estatico de seguridad (reglas PHP + secrets + security-audit) | Si |
| secrets-scan | Busca API keys hardcodeadas (SendGrid, OpenAI, BD) | Si |
| deploy-qa | SSH al LXC Dev/QA y ejecuta deploy | Solo en push a develop |

### Pipeline 2: Cutover a Produccion

**Archivo:** `.gitea/workflows/cutover-production.yml`
**Trigger:** Push a main (despues de merge de PR desde develop)

```
PR develop → main → Validacion → Trivy → Semgrep → Deploy SSH → LXC Produccion (Hetzner)
                                                                → Verificacion post-deploy
```

| Job | Que hace |
|-----|----------|
| validate | Sintaxis PHP + busqueda de credenciales |
| trivy | Escaneo vulnerabilidades (paralelo con semgrep) |
| semgrep | Analisis estatico seguridad (paralelo con trivy) |
| deploy-production | SSH al Hetzner + deploy + verificacion HTTP post-deploy |

**Todo por pipeline, nada manual.**

### Secrets necesarios en Gitea

**Para Dev/QA:** QA_HOST, QA_USER, QA_SSH_KEY, QA_PATH
**Para Produccion:** PROD_HOST, PROD_USER, PROD_SSH_KEY, PROD_PATH

### Flujo completo

```
feature/xxx → push → Validacion → PR a develop → Validacion → merge
                                                                 ↓
                                          Deploy automatico a LXC Dev/QA
                                                                 ↓
                                              Pruebas en QA (manuales o auto)
                                                                 ↓
                                          PR develop → main → Validacion → merge
                                                                             ↓
                                                     Cutover automatico a Hetzner LXC
                                                                             ↓
                                                          Verificacion post-deploy
                                                                             ↓
                                                              EN PRODUCCION
```

---

## 7. ORGANIZACION DEL REPOSITORIO

### Estado del repositorio

| Aspecto | Estado actual | Accion |
|---------|---------------|--------|
| Visibilidad | Publico en GitHub | Migrar a Gitea privado |
| .gitignore | Actualizado (excluye env, vendor, cache, IDE, stackdump, backupdb) | OK |
| .env.example | Creado con todas las variables | OK |
| .env.production | Trackeado en git con credenciales | Remover del tracking |
| Archivos basura | Varios identificados | Pendiente limpieza |

### Archivos problematicos trackeados en git

**Archivos con datos sensibles:**

| Archivo | Problema | Accion |
|---------|----------|--------|
| `.env.production` | Credenciales de produccion | Remover de git, agregar a .gitignore |
| `public/backupdb.sql` | Dump de BD en directorio publico | Remover de git |
| `docs/liquidacion-quincenal.md` | Credenciales hardcodeadas en lineas 283-286 | Editar y remover credenciales |

**Archivos de desarrollo/notas:**

| Archivo | Problema | Accion |
|---------|----------|--------|
| `info.txt` | Notas de desarrollo/conversaciones ChatGPT | Remover o mover a docs/ |
| `app/Controllers/TestDB.php` | Controlador de prueba, no debe estar en produccion | Remover |

**Plantillas CSV en public/:**

| Archivo | Debe quedarse |
|---------|---------------|
| `public/plantillas/plantilla_facturacion.csv` | Si — template de carga |
| `public/plantillas/plantilla_movimiento_bancario.csv` | Si — template de carga |

### Archivos CSV que SI deben quedarse

Las plantillas en `public/plantillas/` son templates de carga masiva usados por el modulo de conciliaciones. Deben permanecer.

---

## 8. HALLAZGOS CRITICOS Y ACCIONES PENDIENTES

### Prioridad CRITICA

| # | Accion | Responsable |
|---|--------|-------------|
| 1 | Hacer repo privado o migrar a Gitea | Consultor/Cliente |
| 2 | Rotar TODAS las API Keys y passwords de BD | Cliente |
| 3 | Remover `.env.production` del tracking de git | Cliente |
| 4 | Remover `public/backupdb.sql` del tracking | Cliente |

### Prioridad ALTA

| # | Accion | Responsable |
|---|--------|-------------|
| 5 | Push de rama develop al remoto | Cliente |
| 6 | Configurar proteccion de ramas en Gitea | Consultor |
| 7 | Configurar secrets en Gitea para pipelines | Consultor |
| 8 | Limpiar credenciales de `docs/liquidacion-quincenal.md` | Cliente |
| 9 | Extraer host/user de BD de 12+ migraciones a env() | Cliente |

### Prioridad MEDIA

| # | Accion | Responsable |
|---|--------|-------------|
| 10 | Eliminar `app/Controllers/TestDB.php` | Cliente |
| 11 | Eliminar `info.txt` del repo | Cliente |
| 12 | Eliminar ramas backup locales (backup-antes-de-e73, backup-antes-de-volver-a-ayer) | Cliente |
| 13 | Mover email from a variable de entorno (3 archivos) | Cliente |
| 14 | Crear usuario readonly de BD para dashboards/reportes | Consultor |

---

*Documento generado el 2026-04-05. Preparado como entregable del proceso de hardening del repositorio kpi.*
