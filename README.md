# KPI — Sistema de Gestion de Indicadores de Desempeno

**Empresa:** Cycloid Talent
**Stack:** PHP 8.4 + CodeIgniter 4.7 + MySQL 8 + PWA

Plataforma interna para definir, medir y evaluar indicadores clave de rendimiento (KPIs) por equipo, area y cargo. Incluye modulos de actividades/tickets, bitacora de tiempo (PWA), conciliacion bancaria, y asistente de IA.

---

## Stack tecnologico

| Componente | Tecnologia |
|------------|-----------|
| Backend | PHP 8.4 + CodeIgniter 4.7.2 |
| Base de datos | MySQL 8 (DigitalOcean Managed, SSL required) |
| Servidor web | Nginx (Ubuntu 24.04, Hetzner LXC) |
| Email | SendGrid API v3 |
| IA | OpenAI GPT-3.5-turbo (generacion de indicadores y actividades) |
| PWA | Bitacora de tiempo (manifest + service worker + push notifications) |
| Push | Web Push API (minishlink/web-push) |
| Excel | PhpSpreadsheet (importacion CSV, exportaciones) |

---

## Modulos principales (10)

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

---

## Roles de usuario

| Rol | Acceso |
|-----|--------|
| superadmin | Todo el sistema + gestion de usuarios + configuracion global |
| admin | Dashboard administrativo + gestion de equipos y areas |
| jefatura | Indicadores de equipo + aprobacion de formulas + historial jerarquico |
| trabajador | Mis indicadores + diligenciar formulas + bitacora de tiempo |

---

## Estructura del proyecto

```
kpi/
├── app/
│   ├── Commands/          # 5 comandos spark (cron jobs)
│   ├── Config/            # Routes.php, Database.php, OpenAI.php, Filters.php
│   ├── Controllers/       # 35 controladores
│   ├── Filters/           # Auth, SesionTrackingFilter
│   ├── Helpers/           # bitacora_helper
│   ├── Libraries/         # 5 librerias (Evaluador, Notificadores, OpenAI, Push)
│   ├── Models/            # 37 modelos
│   └── Views/             # 124 vistas (17 subdirectorios por modulo)
├── docs/                  # Documentacion tecnica
├── migrations/            # 23 migraciones SQL
├── public/                # Punto de entrada web (index.php) + PWA assets
├── tests/                 # Tests PHPUnit
├── writable/              # Logs, cache, sesiones, uploads
├── .env                   # Variables de entorno (NO commitear)
├── .env.example           # Template de variables
├── CONTRIBUTING.md        # Guia de contribucion
├── composer.json          # Dependencias PHP
└── spark                  # CLI de CodeIgniter
```

---

## Requisitos previos

- PHP >= 8.4 con extensiones: intl, mbstring, mysqlnd, curl, json
- MySQL 8+
- Composer 2+
- Servidor web: Nginx o Apache (apuntar a `public/`)

---

## Instalacion local

```bash
# 1. Clonar el repositorio
git clone https://github.com/edielestudiante2023/kpi.git
cd kpi

# 2. Instalar dependencias
composer install

# 3. Configurar entorno
cp .env.example .env
# Editar .env con credenciales locales

# 4. Crear la base de datos
mysql -u root -e "CREATE DATABASE kpicycloid"

# 5. Ejecutar migraciones (en orden cronologico desde migrations/)

# 6. Iniciar servidor de desarrollo
php spark serve
```

---

## Variables de entorno

Ver `.env.example` para la lista completa. Las principales:

| Variable | Descripcion |
|----------|-------------|
| `database.default.*` | Conexion MySQL principal |
| `SENDGRID_API_KEY` | API Key de SendGrid para emails |
| `OPENAI_API_KEY` | API Key de OpenAI para generacion IA |
| `OPENAI_MODEL` | Modelo de OpenAI (default: gpt-3.5-turbo) |
| `BITACORA_REPORT_EMAILS` | Destinatarios del reporte diario de bitacora |
| `VAPID_PUBLIC_KEY` / `VAPID_PRIVATE_KEY` | Claves para push notifications |

---

## Cron jobs (tareas programadas)

| Comando | Frecuencia | Descripcion |
|---------|-----------|-------------|
| `php spark bitacora:resumen-diario` | Diario | Envia reporte diario de actividades de bitacora |
| `php spark bitacora:notificar-activas` | Cada 30 min | Notifica actividades activas o vencidas |
| `php spark actividades:recordatorio-revision` | Diario | Recuerda items en revision a creadores (max 2/dia) |
| `php spark resumen-diario` | Diario | Resumen diario general |

---

## Deploy

El proyecto se despliega en un LXC en Hetzner via SSH:

- **Servidor:** server1.cycloidtalent.com (66.29.154.174)
- **Ruta:** `/www/wwwroot/kpi/`
- **BD produccion:** DigitalOcean Managed MySQL (SSL required)

---

## Documentacion adicional

- [docs/liquidacion-quincenal.md](docs/liquidacion-quincenal.md) — Logica de liquidacion quincenal de bitacora
- [docs/HARDENING-kpi.md](docs/HARDENING-kpi.md) — Documento de hardening del repositorio
