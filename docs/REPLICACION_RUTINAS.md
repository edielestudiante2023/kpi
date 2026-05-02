# Documento de Replicación — Módulo "Rutinas de Trabajo"

**Proyecto origen**: `c:\xampp\htdocs\kpi` · **Framework**: CodeIgniter 4 · **BD**: MySQL 8 (InnoDB, utf8mb4) · **PHP**: ^8.4
**URL de entrada**: `http://localhost/kpi/public/rutinas/calendario`

---

## 1. Inventario de archivos

### 1.1 Archivos propios del módulo

| # | Ruta absoluta | Líneas | Propósito |
|---|---|---|---|
| 1 | `app/Controllers/RutinasController.php` | 405 | Controlador único: CRUD actividades, CRUD asignaciones, vista calendario, vista pública con token y endpoint AJAX de check. |
| 2 | `app/Commands/RutinasEnviarDiario.php` | 45 | Comando CLI `rutinas:enviar-diario` para cron — dispara `NotificadorRutinas::enviarRecordatoriosDiarios()`. |
| 3 | `app/Libraries/NotificadorRutinas.php` | 149 | Genera token, HTML del correo, envía email por SendGrid a cada usuario con actividades del día. |
| 4 | `app/Models/RutinaActividadModel.php` | 22 | Modelo maestro de actividades (`rutinas_actividades`). |
| 5 | `app/Models/RutinaAsignacionModel.php` | 18 | Modelo de asignación usuario↔actividad (`rutinas_asignaciones`). |
| 6 | `app/Models/RutinaRegistroModel.php` | 20 | Modelo de ejecución diaria (`rutinas_registros`). |
| 7 | `app/Views/rutinas/calendario.php` | 198 | Vista principal tipo matriz actividades×días con puntaje diario, semanal y mensual. |
| 8 | `app/Views/rutinas/list_actividades.php` | 91 | Listado DataTable de actividades con botones editar/eliminar. |
| 9 | `app/Views/rutinas/add_actividad.php` | 75 | Formulario nueva actividad. |
| 10 | `app/Views/rutinas/edit_actividad.php` | 80 | Formulario edición de actividad. |
| 11 | `app/Views/rutinas/list_asignaciones.php` | 134 | Formulario multi-asignación (usuario + N actividades) + tabla con botón quitar. |
| 12 | `app/Views/rutinas/checklist_publico.php` | 189 | Página mobile-first (sin auth) — checkboxes que disparan fetch POST al endpoint de update. |
| 13 | `app/Views/rutinas/checklist_error.php` | 26 | Vista de error para token inválido / no-hábil / usuario no encontrado. |
| 14 | `migrations/2026_04_11_rutinas_tables.php` | 130 | Script PDO standalone que crea las 3 tablas, idempotente (`INFORMATION_SCHEMA` check). |

### 1.2 Archivos del proyecto modificados (referencian el módulo)

| Ruta | Líneas afectadas | Qué añade |
|---|---|---|
| `app/Config/Routes.php` | 425–448 | Grupo `rutinas/*` autenticado + 2 rutas públicas `rutinas/checklist/*`. |
| `app/Config/Filters.php` | 85, 101 | Exceptúa `rutinas/checklist/*` de los filtros `auth` y `sesiontracking`. |
| `app/Views/partials/nav.php` | 254–272 | Dropdown "Rutinas" en barra superior (Calendario, Actividades, Asignaciones). |

### 1.3 Archivos compartidos del sistema que consume

| Ruta | Relación |
|---|---|
| `app/Views/partials/nav.php` | Include `<?= $this->include('partials/nav') ?>` en todas las vistas autenticadas. |
| `app/Views/components/back_to_dashboard.php` | `<?= view('components/back_to_dashboard') ?>` (botón volver). |
| `app/Models/UserModel.php` | Usado para listar usuarios activos en el formulario de asignaciones y resolver nombre/correo del checklist. |
| Tabla `users` | Lectura directa: `id_users`, `nombre_completo`, `correo`, `activo`. |

### 1.4 Cron / scripts externos

| Archivo | Propósito |
|---|---|
| `muestra_crones.txt` | Solo referencias documentales (Google Apps Script de un sistema anterior de rutinas — no se migra). |

---

## 2. Rutas del aplicativo

### 2.1 Rutas autenticadas (grupo `rutinas/*`, filtro `auth`)

| Método | URL | Controller::método | Parámetros |
|---|---|---|---|
| GET | `/rutinas/actividades` | `RutinasController::listActividades` | — |
| GET | `/rutinas/actividades/add` | `RutinasController::addActividad` | — |
| POST | `/rutinas/actividades/add` | `RutinasController::addActividadPost` | `nombre, descripcion, frecuencia, peso` |
| GET | `/rutinas/actividades/edit/{id}` | `RutinasController::editActividad/$1` | `id_actividad` (num) |
| POST | `/rutinas/actividades/edit/{id}` | `RutinasController::editActividadPost/$1` | `nombre, descripcion, frecuencia, peso, activa` |
| GET | `/rutinas/actividades/delete/{id}` | `RutinasController::deleteActividad/$1` | `id_actividad` |
| GET | `/rutinas/asignaciones` | `RutinasController::listAsignaciones` | — |
| POST | `/rutinas/asignaciones/add` | `RutinasController::addAsignacionPost` | `id_users`, `actividades[]` |
| GET | `/rutinas/asignaciones/delete/{id}` | `RutinasController::deleteAsignacion/$1` | `id_asignacion` |
| GET | `/rutinas/calendario` | `RutinasController::calendario` | `?mes=N&anio=YYYY&usuario=id` |

### 2.2 Rutas públicas (sin auth, exceptuadas en `Filters.php`)

| Método | URL | Controller::método | Qué hace |
|---|---|---|---|
| GET | `/rutinas/checklist/{userId}/{fecha}/{token}` | `RutinasController::checklistPublico/$1/$2/$3` | Valida token HMAC-like, renderiza checklist diario. |
| POST | `/rutinas/checklist/update` | `RutinasController::updateChecklistPublico` | Inserta registro completado (respuesta JSON). Body: `user_id, fecha, token, id_actividad`. |

Patrones CI4: `(:num)`, `(:segment)`, `(:any)`.

### 2.3 Comandos CLI

| Comando | Clase | Uso |
|---|---|---|
| `php spark rutinas:enviar-diario [YYYY-MM-DD]` | `App\Commands\RutinasEnviarDiario` | Envía email diario con enlace tokenizado. Si es sábado/domingo aborta. |

---

## 3. Estructura de base de datos

### 3.1 Tablas propias

**`rutinas_actividades`** — catálogo maestro
```sql
CREATE TABLE rutinas_actividades (
    id_actividad INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL COMMENT 'Nombre corto de la actividad',
    descripcion TEXT NULL COMMENT 'Descripción detallada de qué hacer',
    frecuencia ENUM('L-V','diaria') DEFAULT 'L-V' COMMENT 'L-V = lunes a viernes, diaria = incluye fines de semana',
    peso DECIMAL(5,2) DEFAULT 1.00 COMMENT 'Peso para cálculo de puntaje de cumplimiento',
    activa TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**`rutinas_asignaciones`** — N:M usuarios ↔ actividades
```sql
CREATE TABLE rutinas_asignaciones (
    id_asignacion INT AUTO_INCREMENT PRIMARY KEY,
    id_users INT(10) UNSIGNED NOT NULL COMMENT 'FK a users.id_users',
    id_actividad INT NOT NULL COMMENT 'FK a rutinas_actividades.id_actividad',
    activa TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_actividad (id_users, id_actividad),
    KEY fk_ra_actividad (id_actividad),
    CONSTRAINT fk_ra_users     FOREIGN KEY (id_users)     REFERENCES users(id_users)                 ON DELETE CASCADE,
    CONSTRAINT fk_ra_actividad FOREIGN KEY (id_actividad) REFERENCES rutinas_actividades(id_actividad) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**`rutinas_registros`** — check diario (una fila por actividad/usuario/día completado)
```sql
CREATE TABLE rutinas_registros (
    id_registro INT AUTO_INCREMENT PRIMARY KEY,
    id_users INT(10) UNSIGNED NOT NULL COMMENT 'FK a users.id_users',
    id_actividad INT NOT NULL COMMENT 'FK a rutinas_actividades.id_actividad',
    fecha DATE NOT NULL COMMENT 'Fecha del día de la rutina',
    completada TINYINT(1) DEFAULT 0 COMMENT '0=pendiente, 1=completada',
    hora_completado DATETIME NULL COMMENT 'Timestamp exacto cuando marcó el check',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_actividad_fecha (id_users, id_actividad, fecha),
    KEY idx_fecha (fecha),
    KEY fk_rr_actividad (id_actividad),
    CONSTRAINT fk_rr_users     FOREIGN KEY (id_users)     REFERENCES users(id_users)                 ON DELETE CASCADE,
    CONSTRAINT fk_rr_actividad FOREIGN KEY (id_actividad) REFERENCES rutinas_actividades(id_actividad) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

> **Sin datos semilla**: todas las actividades se crean desde la UI.

### 3.2 Tabla del sistema consultada

**`users`** (no propiedad del módulo) — campos accedidos:

| Campo | Tipo | Uso |
|---|---|---|
| `id_users` | `INT(10) UNSIGNED PK` | FK en las 3 tablas; filtro de asignaciones/registros. |
| `nombre_completo` | `VARCHAR` | Nombre en UI + email. |
| `correo` | `VARCHAR` | Destinatario SendGrid. |
| `activo` | `TINYINT(1)` | Filtro `u.activo = 1` en notificador y selector de asignaciones. |

Relación: FK `ON DELETE CASCADE` desde `rutinas_asignaciones.id_users` y `rutinas_registros.id_users`.

---

## 4. Flujo funcional

### 4.1 Ciclo de vida de una rutina

1. **Admin crea actividad** → `rutinas_actividades` (nombre, descripcion, frecuencia `L-V|diaria`, peso, activa=1).
2. **Admin asigna** actividades a un usuario → `rutinas_asignaciones(id_users, id_actividad, activa=1)`. Protección: existe UNIQUE key `(id_users, id_actividad)` + chequeo previo en PHP.
3. **Cron 7:00 AM L-V** ejecuta `spark rutinas:enviar-diario` → por cada usuario con ≥1 asignación activa, calcula `token = substr(sha256("{id}|{YYYY-MM-DD}|rutinas2026"), 0, 24)` y envía email con enlace `/rutinas/checklist/{id}/{fecha}/{token}`.
4. **Usuario abre link** (sin login) → `checklistPublico()` valida token con `hash_equals`, que sea L-V, y que exista el usuario.
5. **Usuario marca checkbox** → `fetch POST /rutinas/checklist/update` con `{user_id, fecha, token, id_actividad}`. Backend revalida token + asignación activa e inserta (o ignora si ya existía) en `rutinas_registros` con `completada=1, hora_completado=NOW()`.
6. **Vista calendario** (autenticada) muestra matriz actividades×días hábiles del mes; cruza `rutinas_registros` para pintar ✓/✗/—.

### 4.2 Estados y transiciones

- `rutinas_actividades.activa`: `0|1` — activar/desactivar desde `edit_actividad.php`.
- `rutinas_asignaciones.activa`: `0|1` — siempre `1` al crearse; eliminación via `deleteAsignacion` hace borrado físico (no soft delete).
- `rutinas_registros.completada`: `0|1` — se inserta directamente en `1`; no hay reversión desde la UI pública (por diseño: marcar es irreversible desde el checklist).

### 4.3 Métodos principales (resumen 1-línea)

| Método | Resumen |
|---|---|
| `listActividades()` | Lista todas ordenadas por nombre. |
| `addActividad[Post]()` | GET muestra form; POST valida (`required`, `in_list[L-V,diaria]`, `decimal`) e inserta. |
| `editActividad[Post]($id)` | GET carga actividad; POST actualiza (incluye campo `activa`). |
| `deleteActividad($id)` | Delete físico (cascada borra asignaciones y registros). |
| `listAsignaciones()` | JOIN manual `rutinas_asignaciones + users + rutinas_actividades`; pasa lista completa para DataTable + catálogos para form. |
| `addAsignacionPost()` | Itera array `actividades[]`, omite duplicados (query pre-check), inserta. |
| `deleteAsignacion($id)` | Delete físico de una asignación. |
| `calendario()` | Calcula días hábiles del mes (`date('N') ≤ 5`), trae asignaciones+registros del usuario elegido, construye `puntajeDiario[fecha] = sum(peso_hechas)/sum(peso_total) * 100`, agrega por semana ISO, calcula promedio mensual solo sobre días pasados. |
| `generarTokenRutina($uid, $fecha)` | `substr(sha256("{uid}|{fecha}|rutinas2026"), 0, 24)` — secreto hardcoded. |
| `checklistPublico($uid, $fecha, $token)` | Valida token + L-V + existencia; renderiza vista mobile. |
| `updateChecklistPublico()` | AJAX: revalida token+asignación, inserta registro si no existe; devuelve JSON `{success:true/false}`. |

### 4.4 Fórmulas de puntaje

```
puntajeDiario[fecha]   = round( sum(peso de actividades completadas ese día)
                              / sum(peso total de actividades asignadas activas) * 100 )

acumuladoSemanal[sem]  = round( avg(puntajeDiario) sobre días hábiles de esa semana )

acumuladoMensual       = round( avg(puntajeDiario) sobre días hábiles <= hoy )
```

Umbrales de color: `>=90% verde · >=60% amarillo · <60% rojo` (semanal y mensual); diario: `>=100% verde · >=60% amarillo · <60% rojo`.

### 4.5 Contrato AJAX del checklist público

**Request**
```
POST /rutinas/checklist/update
Content-Type: multipart/form-data
user_id=123&fecha=2026-04-21&token=abcd...&id_actividad=5
```

**Response (success)** `200 application/json`
```json
{"success": true}
```

**Response (error)**
```json
{"success": false, "message": "Token inválido"}
{"success": false, "message": "Actividad no asignada"}
```

### 4.6 Integración con otros módulos

- **Auth** (`App\Filters\Auth`): todo el grupo `rutinas/*` requiere sesión excepto `rutinas/checklist/*`.
- **SesionTracking** (`App\Filters\SesionTrackingFilter`): también exceptúa `rutinas/checklist/*`.
- **Nav compartido** (`partials/nav`): contiene el dropdown "Rutinas" con 3 items; usa `strpos($currentUrl, 'rutinas')` para resaltar.
- **Componente `components/back_to_dashboard`**: botón visual en todas las vistas autenticadas.

---

## 5. Dependencias externas

### 5.1 PHP / Composer (`composer.json`)

| Paquete | Versión | Uso en el módulo |
|---|---|---|
| `codeigniter4/framework` | `^4` | Framework base. |
| `sendgrid/sendgrid` | `^8.1` | `NotificadorRutinas::enviarEmail()` — `\SendGrid\Mail\Mail`, `\SendGrid`. |
| `php` | `^8.4` | Arrow functions, null-safe, etc. |

**.env requerido:**
```dotenv
SENDGRID_API_KEY=SG.xxxxxxxxx
```

### 5.2 CDN frontend (por vista)

| Vista | Libs |
|---|---|
| `calendario.php` | Bootstrap 5.3.0, Bootstrap Icons 1.10.0, Bootstrap JS bundle 5.3.0 |
| `list_actividades.php` / `list_asignaciones.php` | Bootstrap 5.3.0, Bootstrap Icons 1.10.0, DataTables 1.13.4 (core + bootstrap5), jQuery 3.6.0 |
| `add_actividad.php` / `edit_actividad.php` | Bootstrap 5.3.0, Bootstrap Icons 1.10.0 |
| `checklist_publico.php` | FontAwesome 6.4.0 (solo CSS propio inline, sin Bootstrap) |
| `checklist_error.php` | CSS inline únicamente |

**URLs exactas**:
- `https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css`
- `https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css`
- `https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css`
- `https://code.jquery.com/jquery-3.6.0.min.js`
- `https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js`
- `https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js`
- `https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css`

### 5.3 Assets locales

Ninguno propio — el módulo no añade nada en `public/js/` ni `public/css/`. Todo el CSS/JS vive inline en las vistas.

---

## 6. Patrones especiales

### 6.1 Token público sin DB (stateless)

```php
private function generarTokenRutina(int $userId, string $fecha): string
{
    return substr(hash('sha256', $userId . '|' . $fecha . '|rutinas2026'), 0, 24);
}
// Validación:
hash_equals($esperado, $token)   // comparación timing-safe
```
- **Sal fija**: literal `rutinas2026`. Para replicar con seguridad superior, reemplazar por `env('RUTINAS_SECRET')`.
- **Vigencia**: de facto 1 día (la fecha es parte del hash). No hay tabla de tokens.
- **Sin CSRF** en `rutinas/checklist/update` (filter global CSRF está comentado en el proyecto origen — si el proyecto gemelo tiene CSRF activo, se debe exceptuar explícitamente esta ruta).

### 6.2 Sistema de email (SendGrid)

- **Librería**: `App\Libraries\NotificadorRutinas`.
- **Template**: HTML inline generado en `generarHTML()` — estilos inline por compatibilidad con clientes de correo. Branding: `#1c2437` (fondo header) + `#bd9751` (acento dorado).
- **Click tracking deshabilitado** (`setClickTracking(false, false)`) para no reescribir el enlace tokenizado.
- **Remitente fijo**: `notificacion.cycloidtalent@cycloidtalent.com` (debe existir como sender verificado en SendGrid).

### 6.3 Cron (ejecución programada)

Este módulo **no trae su propio cron wrapper** — se ejecuta vía `spark` CLI:

```bash
# Cron Linux (ejecutar 7:00 L-V)
0 7 * * 1-5 cd /var/www/app && php spark rutinas:enviar-diario >> /var/log/rutinas.log 2>&1
```

El propio `NotificadorRutinas` aborta internamente si la fecha es sábado/domingo (devuelve `omitidos: -1`), pero es buena práctica filtrar también desde cron.

### 6.4 Idempotencia de migración

`migrations/2026_04_11_rutinas_tables.php` es **script PDO standalone** (no usa el sistema de migraciones de CI4). Verifica `INFORMATION_SCHEMA.TABLES` antes de crear. Invocación:
```bash
php migrations/2026_04_11_rutinas_tables.php local        # localhost
php migrations/2026_04_11_rutinas_tables.php production   # lee .env.production
```

### 6.5 No hay exportación (PDF/Word/Excel)

El módulo no exporta a ningún formato. La matriz del calendario es solo visual.

### 6.6 Sin versionamiento ni firmas/aprobaciones

No aplica — no hay workflow de aprobación ni historial de cambios.

---

## 7. Orden de implementación (réplica desde cero)

### Paso 1 — Base de datos
Crear las 3 tablas usando la migración. La FK a `users(id_users)` exige que esa tabla exista y `id_users` sea `INT UNSIGNED`.
```bash
php migrations/2026_04_11_rutinas_tables.php local
```

### Paso 2 — Dependencia Composer
```bash
composer require sendgrid/sendgrid:^8.1
```
Añadir a `.env`:
```dotenv
SENDGRID_API_KEY=...
```

### Paso 3 — Configuración del framework
1. Añadir las 12 rutas en `app/Config/Routes.php` (bloque `RUTINAS DE TRABAJO`, líneas 425–448).
2. Añadir `'rutinas/checklist/*'` a los arrays `except` de `auth` y `sesiontracking` en `app/Config/Filters.php`.

### Paso 4 — Modelos (3 archivos)
Crear `app/Models/RutinaActividadModel.php`, `RutinaAsignacionModel.php`, `RutinaRegistroModel.php`. Sólo el primero usa `useTimestamps = true`.

### Paso 5 — Librería de notificación
Crear `app/Libraries/NotificadorRutinas.php`. Cambiar `fromEmail` si el sender verificado de SendGrid es distinto.

### Paso 6 — Controlador
Crear `app/Controllers/RutinasController.php`. Ajustar si `BaseController` del proyecto gemelo tiene constructor distinto.

### Paso 7 — Vistas (7 archivos)
Crear carpeta `app/Views/rutinas/` con los 7 archivos. Si el proyecto gemelo no tiene `partials/nav` o `components/back_to_dashboard`, reemplazar esos includes o crearlos.

### Paso 8 — Comando CLI
Crear `app/Commands/RutinasEnviarDiario.php`. Verificar con:
```bash
php spark list
php spark rutinas:enviar-diario 2026-04-21
```

### Paso 9 — Navegación
Añadir el dropdown "Rutinas" al nav compartido del proyecto (ver `nav.php` líneas 254–272 como referencia).

### Paso 10 — Cron
Registrar el cron en el servidor: `0 7 * * 1-5 php spark rutinas:enviar-diario`.

### Paso 11 — Verificación E2E
1. Crear actividad → debe aparecer en `/rutinas/actividades`.
2. Asignar actividad a un usuario → aparece en `/rutinas/asignaciones`.
3. `/rutinas/calendario` muestra matriz con el usuario.
4. `php spark rutinas:enviar-diario 2026-04-21` en un día L-V → llega correo.
5. Abrir enlace del correo → aparece checklist; marcar → toast verde + `rutinas_registros` tiene la fila.
6. Recargar calendario → celda de esa actividad/día muestra ✓ verde.

---

## Resumen ejecutivo

| Aspecto | Valor |
|---|---|
| Archivos nuevos | 14 (1 controller · 1 command · 1 library · 3 models · 7 views · 1 migration) |
| Archivos modificados | 3 (Routes · Filters · nav) |
| Tablas BD nuevas | 3 (`rutinas_actividades`, `rutinas_asignaciones`, `rutinas_registros`) |
| Tablas BD consultadas | 1 (`users`) |
| Rutas totales | 12 (10 auth + 2 públicas) |
| Dependencia nueva | `sendgrid/sendgrid ^8.1` |
| Variables .env | `SENDGRID_API_KEY` |
| Cron requerido | `php spark rutinas:enviar-diario` 7:00 AM L-V |
| LOC total del módulo | ~1.450 |
