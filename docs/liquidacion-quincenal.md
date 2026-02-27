# Módulo de Liquidación Quincenal de Bitácora

## Resumen

Módulo para liquidar quincenas de honorarios basado en horas trabajadas vs horas meta. Diana o Edison hacen clic en "Liquidar" cuando giran nómina → se cortan todas las actividades en progreso → se calcula el % de cumplimiento → se envía email definitivo a cada usuario.

---

## Reglas de Negocio

### Cálculo de Horas Meta

- **Días hábiles** = Lunes a Viernes, excluyendo festivos colombianos (tabla `dias_festivos`)
- Los días hábiles solo se usan para **calcular la meta**, NO para limitar cuándo se puede trabajar
- El trabajo puede registrarse **cualquier día a cualquier hora** (incluyendo fines de semana y festivos)

### Jornadas y Eficiencia

| Jornada   | Horas/día | Eficiencia | Fórmula meta                        |
|-----------|-----------|------------|-------------------------------------|
| Completa  | 8h        | 80%        | `días_hábiles × 8 × 0.80`          |
| Media     | 4h        | 90%        | `días_hábiles × 4 × 0.90`          |

### Porcentaje de Pago

```
porcentaje = (horas_trabajadas / horas_meta) × 100
```

- Verde: ≥ 100%
- Amarillo: ≥ 80% y < 100%
- Rojo: < 80%

### Periodos

- **Primer periodo**: inicia en `BITACORA_PRIMERA_QUINCENA` del `.env` (2026-03-01 00:00:00)
- **Periodos siguientes**: inician automáticamente desde `fecha_corte` de la última liquidación
- **El corte NO es calendario fijo**: es cuando el admin presiona "Liquidar"

### Corte de Actividades en Progreso

Al liquidar, las actividades `en_progreso` se **parten en dos**:
1. La parte anterior al corte se **finaliza** con `hora_fin = ahora`
2. Se crea una **nueva actividad** `(cont.) descripción original` con `hora_inicio = ahora` y estado `en_progreso`, que cuenta para el siguiente periodo

---

## Arquitectura

### Base de Datos

#### Columnas agregadas a `users`

| Columna          | Tipo                              | Default     | Descripción                        |
|------------------|-----------------------------------|-------------|------------------------------------|
| `jornada`        | `ENUM('completa','media')`        | `completa`  | Tipo de jornada del usuario        |
| `admin_bitacora` | `TINYINT(1)`                      | `0`         | Permiso para liquidar y ver tab    |

#### Tabla `dias_festivos`

| Columna       | Tipo           | Notas                    |
|---------------|----------------|--------------------------|
| `id_festivo`  | INT PK AUTO    |                          |
| `fecha`       | DATE UNIQUE    | Fecha del festivo        |
| `descripcion` | VARCHAR(150)   | Nombre del festivo       |
| `anio`        | SMALLINT       | Año (índice)             |
| `created_at`  | TIMESTAMP      |                          |

#### Tabla `liquidaciones_bitacora`

| Columna         | Tipo          | Notas                                 |
|-----------------|---------------|---------------------------------------|
| `id_liquidacion`| INT PK AUTO   |                                       |
| `fecha_inicio`  | DATETIME      | Inicio del periodo liquidado          |
| `fecha_corte`   | DATETIME      | Momento exacto del corte              |
| `dias_habiles`  | INT           | Días hábiles del periodo              |
| `ejecutado_por` | INT UNSIGNED  | FK → users.id_users                   |
| `notas`         | TEXT NULL      | Observaciones opcionales              |
| `created_at`    | TIMESTAMP     |                                       |

#### Tabla `detalle_liquidacion`

| Columna                   | Tipo            | Notas                           |
|---------------------------|-----------------|---------------------------------|
| `id_detalle`              | INT PK AUTO     |                                 |
| `id_liquidacion`          | INT FK          | ON DELETE CASCADE               |
| `id_usuario`              | INT UNSIGNED FK |                                 |
| `jornada`                 | ENUM            | Jornada al momento del corte    |
| `dias_habiles`            | INT             |                                 |
| `horas_meta`              | DECIMAL(8,2)    | Meta calculada                  |
| `horas_trabajadas`        | DECIMAL(8,2)    | Horas efectivas del periodo     |
| `porcentaje_cumplimiento` | DECIMAL(6,2)    | % de pago                       |
| `created_at`              | TIMESTAMP       |                                 |

### Migración

**Archivo**: `migrations/2026_02_27_liquidacion_bitacora.php`

Script PHP CLI independiente (no usa CI4 migrations). Ejecuta con:
```bash
php migrations/2026_02_27_liquidacion_bitacora.php
```

Lee credenciales del `.env` con parser manual (`leerEnv()`). Crea tablas, columnas, inserta 18 festivos colombianos 2026, habilita admins.

---

## Archivos del Módulo

### Modelos

| Archivo | Tabla | Métodos clave |
|---------|-------|---------------|
| `app/Models/DiaFestivoModel.php` | `dias_festivos` | `getFestivosAnio($anio)`, `contarDiasHabiles($desde, $hasta)` |
| `app/Models/LiquidacionModel.php` | `liquidaciones_bitacora` | `getUltimaLiquidacion()`, `getHistorial()`, `getDetalle($id)` |
| `app/Models/DetalleLiquidacionModel.php` | `detalle_liquidacion` | CRUD estándar CI4 |

#### Métodos agregados a modelos existentes

- **`BitacoraActividadModel::getTotalMinutosRango($idUsuario, $desde, $hasta)`**: Suma `duracion_minutos` de actividades finalizadas en rango de fechas
- **`BitacoraActividadModel::getTodasEnProgreso()`**: Todas las actividades con `estado = 'en_progreso'`
- **`UserModel`**: Agregados `jornada` y `admin_bitacora` a `$allowedFields`

### Controlador

**Archivo**: `app/Controllers/BitacoraController.php`

| Método | Tipo | Descripción |
|--------|------|-------------|
| `esAdminBitacora()` | private | Verifica `admin_bitacora=1` en sesión |
| `liquidacion()` | GET | Vista principal con preview del periodo |
| `ejecutarLiquidacion()` | POST AJAX | Ejecuta el corte (transacción BD) |
| `detalleLiquidacion($id)` | GET AJAX | Detalle de liquidación pasada |
| `generarHTMLLiquidacion()` | private | Template email de liquidación |
| `festivos($anio)` | GET | Vista CRUD de festivos |
| `guardarFestivo()` | POST AJAX | Insertar festivo |
| `eliminarFestivo($id)` | POST AJAX | Eliminar festivo |

### Rutas

**Archivo**: `app/Config/Routes.php` (dentro del grupo `bitacora`, filtro `auth`)

```
GET  bitacora/liquidacion                  → liquidacion()
POST bitacora/liquidacion/ejecutar         → ejecutarLiquidacion()
GET  bitacora/liquidacion/detalle/(:num)   → detalleLiquidacion($1)
GET  bitacora/festivos                     → festivos()
GET  bitacora/festivos/(:num)              → festivos($1)
POST bitacora/festivos/guardar             → guardarFestivo()
POST bitacora/festivos/eliminar/(:num)     → eliminarFestivo($1)
```

### Vistas

| Archivo | Descripción |
|---------|-------------|
| `app/Views/bitacora/liquidacion.php` | Preview periodo, tabla usuarios, botón liquidar con modal, historial |
| `app/Views/bitacora/festivos.php` | Navegación por año, formulario agregar, lista con botón eliminar |

### Layout

**Archivo**: `app/Views/bitacora/layout.php`

Tab "Liquidación" (icono `bi-calculator`) visible solo si `$session->get('admin_bitacora')` es verdadero. Ubicado entre "Equipo" y "Centros".

### Sesión

**Archivo**: `app/Controllers/AuthController.php`

`admin_bitacora` se guarda en sesión al hacer login (`$usuario['admin_bitacora'] ?? 0`).

### Emails

**Archivo**: `app/Libraries/NotificadorBitacora.php`

| Método | Descripción |
|--------|-------------|
| `calcularProgresoQuincenal($usuario)` | Calcula avance quincenal (horas trabajadas vs meta) |
| `generarSeccionProgresoQuincenal($progreso)` | HTML con barra de progreso visual para email diario |
| `enviarEmail()` | Ahora `public` (antes `protected`) para uso desde el controlador |

El email diario incluye una sección **"Progreso Quincenal"** al final, con:
- Periodo (desde última liquidación hasta hoy)
- Días hábiles y tipo de jornada
- Horas acumuladas / Meta
- Barra de progreso con color (rojo/amarillo/verde)

---

## Variable de Entorno

```env
BITACORA_PRIMERA_QUINCENA = 2026-03-01 00:00:00
```

Solo se usa para el **primer periodo**. Después de la primera liquidación, el sistema usa automáticamente `fecha_corte` de la última liquidación.

---

## Flujo de Liquidación (paso a paso)

1. Admin abre `/bitacora/liquidacion`
2. Ve el preview: periodo actual, días hábiles, tabla con cada usuario (horas, meta, %)
3. Presiona "Liquidar Periodo" → se abre modal de confirmación
4. Opcionalmente escribe notas → presiona "Confirmar Liquidación"
5. Backend (en transacción):
   - Determina `fecha_inicio` (última liquidación o `.env`)
   - Busca actividades `en_progreso` → las finaliza con `hora_fin = ahora`
   - Crea actividades `(cont.)` para el siguiente periodo
   - Cuenta días hábiles con `DiaFestivoModel::contarDiasHabiles()`
   - Crea registro en `liquidaciones_bitacora`
   - Por cada usuario con bitácora habilitada:
     - Suma minutos del periodo (`getTotalMinutosRango`)
     - Calcula meta según jornada
     - Calcula porcentaje
     - Inserta en `detalle_liquidacion`
   - Envía email de liquidación a cada usuario (con CC a los de `BITACORA_REPORT_EMAILS`)
6. Frontend muestra alerta de éxito y recarga la página

---

## Festivos Colombianos 2026 (seed)

| Fecha       | Descripción                |
|-------------|----------------------------|
| 2026-01-01  | Año Nuevo                  |
| 2026-01-12  | Día de los Reyes Magos     |
| 2026-03-23  | Día de San José            |
| 2026-04-02  | Jueves Santo               |
| 2026-04-03  | Viernes Santo              |
| 2026-05-01  | Día del Trabajo            |
| 2026-05-18  | Ascensión del Señor        |
| 2026-06-08  | Corpus Christi             |
| 2026-06-15  | Sagrado Corazón            |
| 2026-06-29  | San Pedro y San Pablo      |
| 2026-07-20  | Día de la Independencia    |
| 2026-08-07  | Batalla de Boyacá          |
| 2026-08-17  | Asunción de la Virgen      |
| 2026-10-12  | Día de la Raza             |
| 2026-11-02  | Todos los Santos           |
| 2026-11-16  | Independencia de Cartagena |
| 2026-12-08  | Inmaculada Concepción      |
| 2026-12-25  | Navidad                    |

**Importante**: Cada año hay que agregar los festivos del año siguiente (vía la UI de festivos o vía script). Varios festivos colombianos se mueven al lunes siguiente por la Ley Emiliani.

---

## Usuarios Configurados

| Usuario | Jornada | Admin Bitácora |
|---------|---------|----------------|
| Edison Ernesto Cuervo Salazar | Completa | Sí |
| Diana Patricia Cuestas Navia | Completa | Sí |
| Solangel | Media | No |
| Lizeth Natalia Jiménez | Completa | No |
| Eleyson Augusto Segura | Completa | No |

---

## Troubleshooting

### El tab "Liquidación" no aparece
- Verificar que `admin_bitacora = 1` en la tabla `users`
- El usuario debe cerrar sesión y volver a iniciar para que se actualice la sesión

### La sección "Progreso Quincenal" no aparece en el email diario
- Verificar que `BITACORA_PRIMERA_QUINCENA` está en el `.env` del servidor
- Si no hay días hábiles en el periodo (ej: fin de semana), no se muestra
- Revisar logs: `tail -f /var/www/kpi/writable/logs/log-*.log`

### Error al liquidar
- La operación usa transacción BD: si falla, todo se revierte
- Verificar que el usuario tiene `admin_bitacora = 1`
- Revisar que existan festivos para el año actual en `dias_festivos`

### Festivos del año siguiente
- Acceder a `/bitacora/festivos/2027` (o el año que corresponda)
- Agregar cada festivo manualmente, o crear un script similar al seed de 2026
- Tener en cuenta la Ley Emiliani: algunos festivos se trasladan al lunes siguiente

### Credenciales de producción BD
- Host: `db-mysql-cycloid-do-user-18794030-0.h.db.ondigitalocean.com`
- Puerto: `25060`
- Usuario: `cycloid_userdb`
- BD: `kpicycloid`
- SSL requerido

### Scripts de migración
- Se ejecutan por CLI: `php migrations/nombre_script.php`
- Leen `.env` con parser manual (`leerEnv()`) — no usan `parse_ini_file`
- Siempre verifican si tablas/columnas ya existen antes de crear (idempotentes)
