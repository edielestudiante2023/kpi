# Guia de Contribucion — KPI

## Flujo de ramas

```
main          <- Produccion. Solo codigo validado y estable.
develop       <- Integracion. Cambios se unen aqui antes de ir a main.
feature/xxx   <- Nuevas funcionalidades. Se crean desde develop.
hotfix/xxx    <- Correcciones urgentes. Se crean desde main.
```

### Nueva funcionalidad

```bash
git checkout develop
git pull origin develop
git checkout -b feature/nombre-descriptivo
# ... trabajar ...
git push -u origin feature/nombre-descriptivo
# Crear PR a develop en GitHub/Gitea
```

### Hotfix urgente

```bash
git checkout main
git pull origin main
git checkout -b hotfix/descripcion-bug
# ... corregir ...
git push -u origin hotfix/descripcion-bug
# Crear PR a main + PR a develop
```

---

## Convencion de commits

Usar prefijos semanticos:

| Prefijo | Uso |
|---------|-----|
| `feat:` | Nueva funcionalidad |
| `fix:` | Correccion de bug |
| `docs:` | Solo documentacion |
| `refactor:` | Cambio de codigo sin cambiar funcionalidad |
| `chore:` | Mantenimiento, dependencias, configuracion |
| `test:` | Agregar o modificar tests |

**Ejemplo:** `feat: agregar liquidacion quincenal a bitacora`

---

## Convencion de nombres de ramas

| Tipo | Formato | Ejemplo |
|------|---------|---------|
| Feature | `feature/modulo-descripcion` | `feature/bitacora-exportar-pdf` |
| Hotfix | `hotfix/descripcion-bug` | `hotfix/login-sesion-expirada` |
| Release | `release/vX.Y.Z` | `release/v2.1.0` |

---

## Reglas

1. **No push directo a main** — Todo cambio via Pull Request
2. **No credenciales en codigo** — Usar variables de entorno (`.env`)
3. **No archivos temporales** — No commitear logs, cache, uploads, dumps SQL
4. **No operaciones destructivas en produccion** — No DROP, TRUNCATE, DELETE masivo sin aprobacion
5. **No force push** — Nunca `git push --force` a main o develop

---

## Proceso de revision

1. Crear PR con descripcion clara de los cambios
2. Pipeline CI/CD ejecuta automaticamente:
   - Validacion de sintaxis PHP (`php -l`)
   - Escaneo de vulnerabilidades (Trivy)
   - Analisis estatico de seguridad (Semgrep)
   - Busqueda de credenciales hardcodeadas
3. Revision manual por al menos un miembro del equipo
4. Merge solo si el pipeline pasa y hay aprobacion

---

## Estructura de codigo

- **Controllers:** Logica de request/response, validacion de input
- **Models:** Acceso a base de datos, queries
- **Libraries:** Logica de negocio compleja (evaluacion de formulas, notificaciones, IA)
- **Views:** Templates HTML con logica minima
- **Commands:** Tareas programadas (cron jobs)
- **Filters:** Middleware (autenticacion, tracking de sesion)
