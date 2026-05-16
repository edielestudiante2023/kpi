<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual del usuario – CRM – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; }
        .manual-wrap { max-width: 1200px; margin: 0 auto; }
        .toc {
            position: sticky; top: 16px;
            background: #fff; border-radius: 8px; padding: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            max-height: calc(100vh - 32px); overflow-y: auto;
        }
        .toc h6 { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6c757d; margin-bottom: 10px; }
        .toc a { display: block; padding: 4px 8px; font-size: 0.85rem; color: #495057; text-decoration: none; border-radius: 4px; }
        .toc a:hover { background: #e7f1ff; color: #0d6efd; }
        .toc a.active { background: #0d6efd; color: #fff; font-weight: 600; }
        .toc ul { list-style: none; padding-left: 12px; margin: 4px 0; }
        .seccion {
            background: #fff; border-radius: 8px; padding: 24px 28px;
            margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            scroll-margin-top: 16px;
        }
        .seccion h2 {
            color: #2c3e50; font-size: 1.4rem; font-weight: 700;
            margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #0d6efd;
        }
        .seccion h3 {
            color: #2c3e50; font-size: 1.1rem; font-weight: 600;
            margin-top: 22px; margin-bottom: 10px;
        }
        .paso {
            background: #f8f9fa; border-left: 4px solid #0d6efd;
            padding: 10px 14px; border-radius: 4px; margin-bottom: 10px;
            position: relative;
        }
        .paso-num {
            position: absolute; left: -16px; top: -10px;
            background: #0d6efd; color: #fff; width: 28px; height: 28px;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.85rem; box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        .paso h5 { font-size: 0.95rem; margin-bottom: 4px; color: #2c3e50; }
        .paso p { margin-bottom: 4px; font-size: 0.92rem; }
        .tip {
            background: #d1ecf1; border-left: 4px solid #0c5460;
            padding: 10px 14px; border-radius: 4px; margin: 10px 0;
            font-size: 0.9rem;
        }
        .tip strong { color: #0c5460; }
        .warning {
            background: #fff3cd; border-left: 4px solid #856404;
            padding: 10px 14px; border-radius: 4px; margin: 10px 0;
            font-size: 0.9rem;
        }
        .warning strong { color: #856404; }
        .ejemplo {
            background: #e8f5e9; border-left: 4px solid #198754;
            padding: 10px 14px; border-radius: 4px; margin: 10px 0;
            font-size: 0.9rem;
        }
        .ejemplo strong { color: #198754; }
        kbd {
            background: #2c3e50; color: #fff; padding: 2px 6px;
            border-radius: 3px; font-size: 0.78rem;
        }
        .ruta-ui {
            display: inline-block; background: #e9ecef; padding: 1px 8px;
            border-radius: 3px; font-family: monospace; font-size: 0.85rem;
            color: #495057;
        }
        .badge-mini { font-size: 0.65rem; }
        .seccion ol { padding-left: 22px; }
        .seccion ol li { margin-bottom: 5px; font-size: 0.92rem; }
        .seccion ul li { font-size: 0.92rem; margin-bottom: 4px; }
        .admin-only {
            background: #fff3cd; padding: 2px 6px; border-radius: 3px;
            font-size: 0.7rem; color: #856404; font-weight: 600;
        }
    </style>
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container-fluid py-3 manual-wrap">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h4 mb-0"><i class="bi bi-book me-2"></i>Manual del usuario — CRM</h1>
        <a href="<?= base_url('crm/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver al CRM
        </a>
    </div>

    <p class="text-muted small mb-4">
        Guía paso a paso para usar el módulo CRM de Cycloid Talent. Cubre desde el primer login hasta
        cerrar una oportunidad ganada. Si algo no aparece o no funciona, revisa la sección
        <a href="#permisos">Permisos y visibilidad</a> o la sección <a href="#faq">Preguntas frecuentes</a>.
    </p>

    <div class="row g-3">
        <!-- Tabla de contenidos -->
        <div class="col-md-3">
            <nav class="toc" id="toc">
                <h6>Contenido</h6>
                <a href="#intro">1. Qué es el CRM</a>
                <a href="#acceso">2. Cómo entrar</a>
                <a href="#habilitar">3. Niveles de acceso</a>
                <a href="#empresas">4. Empresas</a>
                <ul>
                    <li><a href="#empresa-crear">4.1 Crear</a></li>
                    <li><a href="#empresa-editar">4.2 Editar / inactivar</a></li>
                    <li><a href="#empresa-eliminar">4.3 Eliminar</a></li>
                </ul>
                <a href="#contactos">5. Contactos</a>
                <a href="#oportunidades">6. Oportunidades</a>
                <ul>
                    <li><a href="#opo-crear">6.1 Crear</a></li>
                    <li><a href="#opo-kanban">6.2 Mover en Kanban</a></li>
                    <li><a href="#opo-editar">6.3 Editar</a></li>
                    <li><a href="#opo-ganar">6.4 Marcar ganada</a></li>
                    <li><a href="#opo-perder">6.5 Marcar perdida</a></li>
                </ul>
                <a href="#interacciones">7. Interacciones</a>
                <ul>
                    <li><a href="#inter-completada">7.1 Registrar una llamada</a></li>
                    <li><a href="#inter-tarea">7.2 Tarea pendiente</a></li>
                    <li><a href="#inter-completar">7.3 Completar / eliminar</a></li>
                </ul>
                <a href="#dashboard">8. Leer el dashboard</a>
                <a href="#config">9. Configuración <span class="admin-only">ADMIN</span></a>
                <a href="#permisos">10. Permisos y visibilidad</a>
                <a href="#snapshots">11. Snapshots semanales</a>
                <a href="#otto">12. OTTO Coach Comercial (IA)</a>
                <ul>
                    <li><a href="#otto-uso">12.1 Cómo usarlo</a></li>
                    <li><a href="#otto-presets">12.2 Los 5 análisis</a></li>
                    <li><a href="#otto-widget">12.3 Widget flotante</a></li>
                </ul>
                <a href="#faq">13. Preguntas frecuentes</a>
            </nav>
        </div>

        <!-- Contenido -->
        <div class="col-md-9">

        <!-- 1. INTRO -->
        <section class="seccion" id="intro">
            <h2>1. ¿Qué es el CRM?</h2>
            <p>El CRM (Customer Relationship Management) es el módulo donde el equipo comercial gestiona
            el <strong>pipeline de ventas</strong>: desde el primer contacto con un prospecto hasta cerrar
            la venta. Sirve para:</p>
            <ul>
                <li>Tener centralizadas todas las <strong>empresas</strong> (clientes potenciales o reales) y sus <strong>contactos</strong>.</li>
                <li>Llevar el control de las <strong>oportunidades</strong> de negocio con su valor estimado, etapa y fecha de cierre.</li>
                <li>Registrar cada <strong>interacción</strong> (llamadas, reuniones, correos, tareas pendientes con recordatorio).</li>
                <li>Ver el <strong>pipeline visual</strong> en un Kanban donde puedes arrastrar oportunidades entre etapas.</li>
                <li>Medir el <strong>desempeño comercial</strong>: funnel, tasa de conversión, ranking de vendedores, etc.</li>
            </ul>
            <div class="tip">
                <strong>💡 Concepto clave:</strong> Una <em>oportunidad</em> es un trato concreto que estás tratando de cerrar
                con una empresa específica. Una empresa puede tener varias oportunidades (presentes y pasadas).
            </div>
        </section>

        <!-- 2. ACCESO -->
        <section class="seccion" id="acceso">
            <h2>2. Cómo entrar al CRM</h2>
            <ol>
                <li>Inicia sesión en <span class="ruta-ui">kpi.cycloidtalent.com</span> como de costumbre.</li>
                <li>En el menú superior verás el dropdown <kbd><i class="bi bi-briefcase"></i> CRM</kbd>. <strong>Todos los usuarios logueados tienen acceso automáticamente</strong> (salvo el contador, que está explícitamente excluido).</li>
                <li>Al abrir el dropdown verás:
                    <ul>
                        <li><strong>Dashboard:</strong> métricas y KPIs (página de inicio recomendada).</li>
                        <li><strong>OTTO Coach Comercial:</strong> asistente IA que responde "¿avanzamos?" y "¿qué hacer para crecer?".</li>
                        <li><strong>Pipeline (Kanban):</strong> tablero visual con drag-drop.</li>
                        <li><strong>Oportunidades (lista):</strong> tabla con filtros.</li>
                        <li><strong>Nueva oportunidad:</strong> formulario para crear.</li>
                        <li><strong>Empresas:</strong> CRUD de cuentas + contactos.</li>
                        <li><strong>Snapshots:</strong> fotos del pipeline para comparar avance semana a semana.</li>
                        <li><strong>Configuración</strong> (solo admin): etapas, fuentes, motivos de pérdida.</li>
                        <li><strong>Manual de usuario</strong> — este documento.</li>
                    </ul>
                </li>
            </ol>
            <div class="warning">
                <strong>⚠️ Si no ves el menú CRM:</strong> probablemente eres rol Contador (5), que está
                explícitamente excluido del módulo. Cualquier otro usuario logueado tiene acceso automático
                a nivel <em>Usuario normal</em>; ver sección 3 para los niveles de privilegio.
            </div>
        </section>

        <!-- 3. HABILITAR USUARIOS -->
        <section class="seccion" id="habilitar">
            <h2>3. Niveles de acceso al CRM</h2>
            <p>El CRM tiene <strong>acceso automático</strong> para cualquier usuario logueado, excepto el
            contador (rol 5). Ya no hace falta marcar ningún flag para entrar. Pero hay <strong>dos niveles
            de visibilidad</strong>:</p>

            <div class="ejemplo">
                <strong>Usuario normal (vendedor)</strong> — todos lo son por defecto. Ve el dropdown CRM completo,
                crea y edita oportunidades, registra interacciones y consume todo el módulo. <strong>Pero solo ve
                las oportunidades de las que es responsable</strong>. Es el rol natural del equipo de ventas.
            </div>

            <div class="warning">
                <strong>Admin CRM</strong> — gerencia comercial / jefes de ventas. Ve TODAS las oportunidades de
                todo el equipo, puede reasignar responsables y configurar etapas, fuentes y motivos de pérdida.
                <br><br>Son admin CRM:
                <ul class="mb-0 mt-1">
                    <li>Cualquier usuario con rol 1 (Superadmin) o 2 (Admin) del sistema — <strong>automáticamente</strong>.</li>
                    <li>Cualquier otro usuario al que un superadmin le marque el flag <code>crm_admin</code> manualmente.</li>
                </ul>
            </div>

            <h3>3.1 Dar nivel Admin CRM a un usuario <span class="admin-only">SOLO SUPERADMIN</span></h3>
            <p>Si necesitas que un usuario que NO tiene rol Admin del sistema vea TODO el pipeline:</p>
            <div class="paso"><span class="paso-num">1</span>
                <h5>Edita el usuario</h5>
                <p>Menú superior → <kbd>Usuarios</kbd> → <kbd>Lista de Usuarios</kbd> → botón <kbd>Editar</kbd>.</p>
            </div>
            <div class="paso"><span class="paso-num">2</span>
                <h5>Marca "Admin CRM"</h5>
                <p>Baja hasta la sección <strong>Acceso a módulos</strong> al final del formulario. Marca la
                casilla <strong>Admin CRM</strong>.</p>
                <p class="text-muted small mt-1">
                    La casilla <strong>CRM habilitado</strong> que aparece al lado ya <em>no es necesaria</em>:
                    el acceso al módulo es automático para todos. La dejamos por si en el futuro se vuelve a
                    necesitar restringir, pero hoy no tiene efecto práctico.
                </p>
            </div>
            <div class="paso"><span class="paso-num">3</span>
                <h5>Guarda y avísale</h5>
                <p>Haz clic en <kbd>Guardar cambios</kbd>. La persona debe <strong>cerrar sesión y volver a entrar</strong>
                para que su sesión cargue el nuevo nivel.</p>
            </div>

            <div class="tip">
                <strong>💡 Recomendación:</strong> mantén pocos Admin CRM (Edison + 1-2 jefes de ventas).
                Demasiados admins quita el efecto de "ver solo lo mío" del vendedor y la responsabilidad por dueño se diluye.
            </div>

            <div class="warning">
                <strong>⚠️ Contador (rol 5) NO entra al CRM</strong> sin importar los flags. El módulo está
                pensado solo para roles operativos y comerciales.
            </div>
        </section>

        <!-- 4. EMPRESAS -->
        <section class="seccion" id="empresas">
            <h2>4. Empresas</h2>
            <p>Una "empresa" en el CRM es una <strong>cuenta comercial</strong> — puede ser un prospecto (aún no compra)
            o un cliente activo. Antes de crear una oportunidad necesitas tener la empresa registrada.</p>

            <h3 id="empresa-crear">4.1 Crear una empresa</h3>
            <div class="paso"><span class="paso-num">1</span>
                <h5>Ve al listado de empresas</h5>
                <p>Menú <kbd>CRM</kbd> → <kbd>Empresas</kbd>. Verás la tabla con las que ya existen.</p>
            </div>
            <div class="paso"><span class="paso-num">2</span>
                <h5>Haz clic en "+ Nueva empresa"</h5>
                <p>Botón azul arriba a la derecha.</p>
            </div>
            <div class="paso"><span class="paso-num">3</span>
                <h5>Llena el formulario</h5>
                <p>Solo la <strong>Razón social</strong> es obligatoria. El resto son útiles pero opcionales:</p>
                <ul>
                    <li><strong>Razón social*</strong> — Nombre comercial completo (ej: "ACME Constructora S.A.S.").</li>
                    <li><strong>NIT</strong> — Sin guion ni dígito de verificación, o como prefieras (es texto libre).</li>
                    <li><strong>Sector</strong> — Texto libre. Sugerencias: SST, PH, Talento, Otro.</li>
                    <li><strong>Tamaño</strong> — Micro / Pequeña / Mediana / Grande.</li>
                    <li><strong>Ciudad, Teléfono, Email principal, Sitio web</strong>.</li>
                    <li><strong>Fuente del lead</strong> — De dónde llegó (Referido, LinkedIn, etc.).</li>
                    <li><strong>Responsable</strong> — El "dueño" de la cuenta. Por defecto eres tú.</li>
                    <li><strong>Notas</strong> — Texto libre con contexto adicional.</li>
                </ul>
            </div>
            <div class="paso"><span class="paso-num">4</span>
                <h5>Guarda</h5>
                <p>Haz clic en <kbd>Crear empresa</kbd>. Quedarás automáticamente en la ficha de la empresa.</p>
            </div>

            <h3 id="empresa-editar">4.2 Editar o inactivar</h3>
            <p>Desde la ficha de la empresa, botón <kbd>Editar</kbd>. Para inactivar (en lugar de eliminar)
            desmarca la casilla <strong>Empresa activa</strong> al final del formulario. Una empresa inactiva
            no aparece como opción al crear nuevas oportunidades, pero sus oportunidades existentes siguen ahí.</p>

            <h3 id="empresa-eliminar">4.3 Eliminar</h3>
            <div class="warning">
                <strong>⚠️ Solo se puede eliminar una empresa si NO tiene oportunidades asociadas.</strong>
                Si tiene, el sistema te lo impide. En ese caso, inactívala (paso 4.2).
            </div>
        </section>

        <!-- 5. CONTACTOS -->
        <section class="seccion" id="contactos">
            <h2>5. Contactos</h2>
            <p>Los contactos son las <strong>personas dentro de una empresa</strong> con quienes hablas
            (gerente de RRHH, encargado de compras, etc.). Se gestionan desde la ficha de la empresa.</p>

            <div class="paso"><span class="paso-num">1</span>
                <h5>Entra a la ficha de la empresa</h5>
                <p><kbd>CRM</kbd> → <kbd>Empresas</kbd> → clic en el nombre de la empresa.</p>
            </div>
            <div class="paso"><span class="paso-num">2</span>
                <h5>En la tarjeta "Contactos" (columna derecha) clic en el botón "+"</h5>
                <p>Se abre un modal.</p>
            </div>
            <div class="paso"><span class="paso-num">3</span>
                <h5>Llena los datos</h5>
                <ul>
                    <li><strong>Nombre*</strong> — Obligatorio.</li>
                    <li>Cargo, teléfono, email, notas — opcionales.</li>
                    <li><strong>Es decisor</strong> — Marca esta casilla si esta persona es quien aprueba la compra. Aparecerá con badge "DECISOR" para identificarlo rápido.</li>
                </ul>
            </div>
            <div class="paso"><span class="paso-num">4</span>
                <h5>Guarda</h5>
                <p>Aparece en la lista. Puedes editarlo (lápiz) o eliminarlo (papelera) en cualquier momento.</p>
            </div>
            <div class="tip">
                <strong>💡 Tip:</strong> Al crear una oportunidad, podrás elegir uno de estos contactos como
                "Contacto principal". Si no agregas contactos primero, queda en blanco (se puede agregar después).
            </div>
        </section>

        <!-- 6. OPORTUNIDADES -->
        <section class="seccion" id="oportunidades">
            <h2>6. Oportunidades</h2>
            <p>El corazón del CRM. Una oportunidad es un <strong>negocio concreto</strong> que estás trabajando
            con una empresa, con un valor estimado, una etapa (en qué punto del proceso está) y una fecha
            esperada de cierre.</p>

            <h3 id="opo-crear">6.1 Crear una oportunidad</h3>
            <div class="paso"><span class="paso-num">1</span>
                <h5>Abre el formulario</h5>
                <p>Tres caminos posibles:</p>
                <ul>
                    <li>Menú <kbd>CRM</kbd> → <kbd>Nueva oportunidad</kbd>.</li>
                    <li>Desde el <strong>Pipeline (Kanban)</strong> → botón <kbd>+ Nueva oportunidad</kbd> arriba a la derecha.</li>
                    <li>Desde la <strong>ficha de una empresa</strong> → botón <kbd>+ Nueva oportunidad</kbd> arriba a la derecha (queda asociada automáticamente a esa empresa).</li>
                </ul>
            </div>
            <div class="paso"><span class="paso-num">2</span>
                <h5>Selecciona la empresa</h5>
                <p>Escribe al menos 2 letras del nombre o del NIT. El buscador trae los resultados desde la base.
                Si la empresa <strong>no existe</strong>, primero créala (sección 4) y luego vuelve.</p>
            </div>
            <div class="paso"><span class="paso-num">3</span>
                <h5>Contacto principal (opcional)</h5>
                <p>Al elegir empresa, el segundo selector se llena con los contactos de esa empresa. Elige el principal.</p>
            </div>
            <div class="paso"><span class="paso-num">4</span>
                <h5>Llena los datos del negocio</h5>
                <ul>
                    <li><strong>Título*</strong> — Descripción corta. Ej: "Implementación SST 2026" o "Consultoría PH edificio Centro 92".</li>
                    <li><strong>Descripción</strong> — Detalle del alcance, opcional.</li>
                    <li><strong>Valor estimado*</strong> — En pesos colombianos. Acepta puntos como separador de miles (ej: "12.000.000"). El sistema lo normaliza al guardar.</li>
                    <li><strong>Etapa*</strong> — En qué punto del pipeline está. Por defecto "Prospecto" para nuevas.</li>
                    <li><strong>Probabilidad (%)</strong> — Tu estimación de cierre. Al cambiar la etapa se sugiere el default de esa etapa, pero puedes ajustarlo.</li>
                    <li><strong>Fecha de cierre estimada</strong> — Cuándo crees que se va a cerrar (firma o pierde). Útil para forecasting.</li>
                    <li><strong>Responsable</strong> — Quién lleva el negocio. Por defecto eres tú.</li>
                </ul>
            </div>
            <div class="paso"><span class="paso-num">5</span>
                <h5>Guardar</h5>
                <p>El sistema genera un código único tipo <code>OPP-20260515-0001</code> y te lleva a la vista detalle de la oportunidad.</p>
            </div>

            <h3 id="opo-kanban">6.2 Mover una oportunidad en el Kanban</h3>
            <p>El Kanban es la vista <strong>visual del pipeline</strong>: cada columna es una etapa y cada tarjeta es una oportunidad.</p>
            <div class="paso"><span class="paso-num">1</span>
                <h5>Abre el Pipeline</h5>
                <p>Menú <kbd>CRM</kbd> → <kbd>Pipeline (Kanban)</kbd>.</p>
            </div>
            <div class="paso"><span class="paso-num">2</span>
                <h5>Arrastra una tarjeta</h5>
                <p>Click sostenido sobre una tarjeta y arrastra a otra columna. Al soltar, el sistema:</p>
                <ul>
                    <li>Actualiza la etapa.</li>
                    <li>Ajusta la probabilidad al default de la nueva etapa (por ej. al pasar a "Calificado" sube a 30%).</li>
                    <li>Registra el cambio en el historial (queda auditado quién lo movió y cuándo).</li>
                    <li>Muestra un toast verde "Etapa actualizada".</li>
                </ul>
            </div>
            <div class="warning">
                <strong>⚠️ Si arrastras a "Perdida":</strong> se abre un modal pidiendo el <strong>motivo de pérdida</strong> (obligatorio). Sin motivo, el cambio no se aplica.
            </div>
            <div class="tip">
                <strong>💡 Tip:</strong> En la parte superior de cada columna verás el <strong>contador</strong> de oportunidades y el <strong>valor total</strong> de esa etapa. Se actualizan en tiempo real al arrastrar.
            </div>

            <h3 id="opo-editar">6.3 Editar una oportunidad</h3>
            <p>Desde la vista detalle (clic en el código o título), botón <kbd>Editar</kbd>. Puedes cambiar
            cualquier campo, incluyendo el responsable (admin CRM puede reasignar a otro vendedor) y la etapa.</p>

            <h3 id="opo-ganar">6.4 Marcar una oportunidad como ganada</h3>
            <div class="paso"><span class="paso-num">1</span>
                <h5>Abre la oportunidad</h5>
                <p>Desde el Kanban o la lista, haz clic en su código.</p>
            </div>
            <div class="paso"><span class="paso-num">2</span>
                <h5>Botón verde "Marcar ganada"</h5>
                <p>Arriba a la derecha. Confirma en el diálogo.</p>
            </div>
            <div class="paso"><span class="paso-num">3</span>
                <h5>Listo</h5>
                <p>El sistema:</p>
                <ul>
                    <li>Mueve la oportunidad a la columna "Ganada" en el Kanban.</li>
                    <li>Llena automáticamente la <strong>fecha de cierre real</strong> con hoy.</li>
                    <li>Sube la probabilidad a 100%.</li>
                    <li>Registra en el historial.</li>
                </ul>
            </div>
            <div class="tip">
                <strong>💡 Alternativa:</strong> Arrastrar la tarjeta directamente a la columna "Ganada" en el Kanban hace exactamente lo mismo.
            </div>

            <h3 id="opo-perder">6.5 Marcar una oportunidad como perdida</h3>
            <p>Igual que ganar, pero el botón rojo es <kbd>Marcar perdida</kbd>. Abre un modal donde debes
            seleccionar un <strong>motivo</strong> (Precio, Timing, Competencia, No respondió, etc.) y opcionalmente un comentario.
            El motivo es obligatorio porque sirve para análisis (¿por qué perdemos negocios?).</p>
        </section>

        <!-- 7. INTERACCIONES -->
        <section class="seccion" id="interacciones">
            <h2>7. Interacciones (timeline)</h2>
            <p>Cada vez que tienes contacto con la empresa o el contacto, debes registrarlo en el sistema.
            Esto construye un <strong>timeline</strong> que cualquiera del equipo (con permiso) puede ver para entender
            el historial del negocio.</p>
            <p><strong>Tipos de interacción:</strong> Llamada · Reunión · Correo · WhatsApp · Propuesta enviada · Nota · Tarea</p>

            <h3 id="inter-completada">7.1 Registrar una interacción que YA ocurrió (caso común)</h3>
            <p>Después de hacer una llamada o tener una reunión:</p>
            <div class="paso"><span class="paso-num">1</span>
                <h5>Abre la oportunidad</h5>
                <p>Desde el Kanban o la lista.</p>
            </div>
            <div class="paso"><span class="paso-num">2</span>
                <h5>En la tarjeta "Interacciones" clic en "+ Agregar"</h5>
            </div>
            <div class="paso"><span class="paso-num">3</span>
                <h5>Selecciona tipo y llena los datos</h5>
                <ul>
                    <li><strong>Tipo*</strong> — Ej: Llamada.</li>
                    <li><strong>Estado*</strong> — Deja "Completada" (la interacción ya ocurrió).</li>
                    <li><strong>Asunto*</strong> — Resumen corto. Ej: "Primera llamada de descubrimiento".</li>
                    <li><strong>Detalle</strong> — Notas largas: qué dijeron, próximos pasos, etc.</li>
                    <li><strong>Contacto</strong> — Con cuál persona hablaste (opcional).</li>
                    <li><strong>Fecha</strong> — Cuándo ocurrió. Si lo dejas vacío, queda con ahora.</li>
                </ul>
            </div>
            <div class="paso"><span class="paso-num">4</span>
                <h5>Guardar</h5>
                <p>Aparece arriba del timeline con su tipo, color, fecha y autor. Y la oportunidad queda
                marcada como "con actividad reciente" para análisis de estancamiento.</p>
            </div>

            <h3 id="inter-tarea">7.2 Programar una tarea pendiente con recordatorio</h3>
            <p>Cuando agendas un seguimiento futuro: "llamar el viernes a confirmar", "enviar propuesta mañana", etc.</p>
            <div class="paso"><span class="paso-num">1</span>
                <h5>Mismo botón "+ Agregar" en Interacciones</h5>
            </div>
            <div class="paso"><span class="paso-num">2</span>
                <h5>Llena los campos</h5>
                <ul>
                    <li><strong>Tipo:</strong> Tarea (o Reunión, Llamada, etc.).</li>
                    <li><strong>Estado:</strong> Pendiente (programada).</li>
                    <li><strong>Asunto:</strong> Lo que tienes que hacer.</li>
                    <li><strong>Programada para*:</strong> Fecha + hora cuando debes hacerlo.</li>
                    <li><strong>Recordatorio:</strong> Cuándo quieres que el sistema te recuerde (puede ser horas antes).</li>
                </ul>
            </div>
            <div class="paso"><span class="paso-num">3</span>
                <h5>Guarda</h5>
                <p>Aparece en el timeline con badge amarillo "PENDIENTE" y también en tu Dashboard, en la sección "Mis tareas pendientes".</p>
            </div>
            <div class="warning">
                <strong>⚠️ Nota técnica:</strong> El envío automático de correos/notificaciones del recordatorio
                aún no está activado (Fase 2). Por ahora el recordatorio queda registrado pero no se dispara
                un email. Mientras tanto, revisa tu dashboard al inicio de cada día para ver las tareas pendientes.
            </div>

            <h3 id="inter-completar">7.3 Completar o eliminar una interacción</h3>
            <ul>
                <li><strong>Marcar una tarea como completada:</strong> en el timeline, junto a la interacción pendiente verás un botón verde con check (✓). Clic → se llena automáticamente la fecha de completada y cambia de "Pendiente" a "Completada".</li>
                <li><strong>Eliminar una interacción:</strong> botón rojo con papelera. Pide confirmación. Útil si te equivocaste al registrarla.</li>
            </ul>
            <div class="tip">
                <strong>💡 También puedes registrar interacciones a nivel de empresa</strong> (sin oportunidad asociada), desde la ficha de la empresa. Útil cuando recién estás explorando un prospecto antes de tener un negocio concreto.
            </div>
        </section>

        <!-- 8. DASHBOARD -->
        <section class="seccion" id="dashboard">
            <h2>8. Cómo leer el Dashboard</h2>
            <p>Menú <kbd>CRM</kbd> → <kbd>Dashboard</kbd>. Es la página principal donde ves el estado del pipeline.</p>

            <h3>KPIs (tarjetas arriba)</h3>
            <ul>
                <li><strong>Pipeline abierto:</strong> cantidad y valor total de oportunidades aún sin cerrar. Es tu "embudo" potencial.</li>
                <li><strong>Ganadas:</strong> cantidad y valor de las que ya cerraste exitosamente.</li>
                <li><strong>Perdidas:</strong> cantidad de las que se cayeron.</li>
                <li><strong>Tasa de conversión:</strong> ganadas ÷ (ganadas + perdidas) × 100. Mide tu efectividad de cierre.</li>
            </ul>

            <h3>Funnel (gráfico de barras horizontales)</h3>
            <p>Muestra <strong>cuántas oportunidades hay en cada etapa abierta</strong>. Lo ideal es ver un embudo:
            muchas en Prospecto, menos en Calificado, menos en Propuesta, etc. Si ves una etapa muy llena
            y la siguiente vacía, hay un cuello de botella ahí.</p>

            <h3>Valor por etapa (donut)</h3>
            <p>El <strong>valor monetario total</strong> de las oportunidades abiertas, dividido por etapa.
            Útil para responder "¿cuánto plata tengo en negociación vs en propuesta?".</p>

            <h3>Cierres últimos 6 meses (línea)</h3>
            <p>Dos series: <span class="badge bg-success">Ganadas</span> y <span class="badge bg-danger">Perdidas</span>
            por mes. Permite ver tendencias: ¿este mes vamos mejor que el anterior?</p>

            <h3>Ranking por responsable</h3>
            <p>Tabla con los vendedores ordenados por <strong>valor ganado</strong> (de mayor a menor).
            Muestra también la proporción G/A (ganadas/abiertas en curso).</p>
            <div class="tip">
                <strong>💡 Vendedor común vs admin:</strong> Si eres vendedor sin admin, el dashboard te muestra
                solo <em>tus</em> oportunidades. Edison u otro admin CRM ve los datos consolidados de todo el equipo.
            </div>

            <h3>Mis tareas pendientes (al final)</h3>
            <p>Lista de tareas/recordatorios que <strong>tú</strong> tienes pendientes (interacciones tipo "Tarea" en estado "Pendiente"). Empieza el día revisando esta sección.</p>
        </section>

        <!-- 9. CONFIGURACIÓN -->
        <section class="seccion" id="config">
            <h2>9. Configuración del CRM <span class="admin-only">SOLO ADMIN</span></h2>
            <p>Solo los usuarios con flag <em>Admin CRM</em> (o superadmin/admin del sistema) ven la sección de Configuración.</p>

            <h3>9.1 Etapas del pipeline</h3>
            <p><kbd>CRM</kbd> → <kbd>Configuración</kbd> → <kbd>Etapas</kbd>.</p>
            <p>Aquí editas las columnas que aparecen en el Kanban. Por defecto vienen:
            Prospecto → Calificado → Propuesta → Negociación → Ganada → Perdida.</p>
            <p>Para cada etapa puedes definir:</p>
            <ul>
                <li><strong>Nombre y orden:</strong> el orden determina la posición en el Kanban (menor = más a la izquierda).</li>
                <li><strong>Tipo:</strong> "abierta" (aparece en el flujo normal), "ganada" o "perdida" (etapas de cierre, van al final del Kanban con estilo distinto).</li>
                <li><strong>Probabilidad default:</strong> el % sugerido al mover una oportunidad a esta etapa.</li>
                <li><strong>Color:</strong> usado en badges y en la columna del Kanban.</li>
                <li><strong>Activa:</strong> si la desactivas, no aparece en el Kanban pero las oportunidades que ya estaban ahí se mantienen.</li>
            </ul>
            <div class="warning">
                <strong>⚠️ No puedes eliminar una etapa que tenga oportunidades.</strong> Primero
                mueve las oportunidades a otra etapa, luego elimínala.
            </div>

            <h3>9.2 Fuentes de lead</h3>
            <p><kbd>CRM</kbd> → <kbd>Configuración</kbd> → <kbd>Fuentes</kbd>. Catálogo de orígenes:
            Referido, LinkedIn, Web, Llamada en frío, Evento. Puedes agregar las que necesites.
            Cada empresa puede marcar de qué fuente vino, lo que sirve para análisis (¿qué canal nos trae más clientes?).</p>

            <h3>9.3 Motivos de pérdida</h3>
            <p><kbd>CRM</kbd> → <kbd>Configuración</kbd> → <kbd>Motivos</kbd>. Razones estandarizadas:
            Precio, Timing, Competencia, No respondió. Es el motivo que se pide al marcar una oportunidad como
            perdida. Tener motivos consistentes permite responder "¿por qué perdemos negocios?".</p>
        </section>

        <!-- 10. PERMISOS -->
        <section class="seccion" id="permisos">
            <h2>10. Permisos y visibilidad</h2>
            <p class="small text-muted">
                Cualquier usuario logueado entra al CRM como <strong>Usuario normal</strong> automáticamente.
                Se eleva a <strong>Admin CRM</strong> solo si tiene rol 1/2 del sistema o el flag
                <code>crm_admin = 1</code>. El contador (rol 5) NUNCA ve el módulo.
            </p>
            <table class="table table-sm table-bordered">
                <thead class="table-dark">
                    <tr><th>Acción</th><th>Usuario normal (acceso automático)</th><th>Admin CRM (rol 1/2 o crm_admin)</th></tr>
                </thead>
                <tbody>
                    <tr><td>Ver dashboard</td><td>✅ (solo sus datos)</td><td>✅ (todos)</td></tr>
                    <tr><td>Ver Kanban / lista de oportunidades</td><td>✅ (solo las suyas)</td><td>✅ (todas)</td></tr>
                    <tr><td>Crear oportunidad</td><td>✅ (queda como responsable)</td><td>✅ (puede asignar a otro)</td></tr>
                    <tr><td>Editar oportunidad</td><td>Solo las suyas</td><td>Todas</td></tr>
                    <tr><td>Eliminar oportunidad</td><td>Solo las suyas</td><td>Todas</td></tr>
                    <tr><td>Crear / editar empresas y contactos</td><td>✅</td><td>✅</td></tr>
                    <tr><td>Registrar interacciones</td><td>✅</td><td>✅</td></tr>
                    <tr><td>Generar / ver snapshots semanales</td><td>✅</td><td>✅</td></tr>
                    <tr><td>Eliminar snapshots</td><td>❌</td><td>✅</td></tr>
                    <tr><td>Usar OTTO Coach Comercial (IA)</td><td>✅</td><td>✅</td></tr>
                    <tr><td>Configurar etapas / fuentes / motivos</td><td>❌</td><td>✅</td></tr>
                </tbody>
            </table>
            <p class="small text-muted">
                <strong>OTTO (asistente IA) siempre lee el pipeline completo</strong> sin importar quién pregunta —
                eso es por diseño para que pueda comparar y dar análisis agregados. Si quieres ver solo lo tuyo,
                pregúntale a OTTO específicamente "¿cómo va [tu nombre]?".
            </p>
        </section>

        <!-- 11. SNAPSHOTS -->
        <section class="seccion" id="snapshots">
            <h2>11. Snapshots semanales</h2>
            <p>Un <strong>snapshot</strong> es una <strong>foto congelada del pipeline en un momento dado</strong> — los KPIs principales (oportunidades abiertas, valor pipeline, ganadas/perdidas año, conversión, ciclo promedio, estancadas) más el desglose por etapa, por responsable y motivos de pérdida.</p>
            <p>Sirven para dos cosas:</p>
            <ul>
                <li><strong>Comparar avance en el tiempo:</strong> ¿cómo estamos esta semana vs la semana pasada? ¿el mes pasado?</li>
                <li><strong>Alimentar al asistente IA (OTTO):</strong> sin snapshots no puede responder honestamente "¿avanzamos?". Con 1 snapshot solo puede describir; con 2 o más, puede medir progreso real.</li>
            </ul>

            <h3>11.1 Cómo generar un snapshot</h3>
            <div class="paso"><span class="paso-num">1</span>
                <h5>Entra a la pantalla</h5>
                <p>Menú <kbd>CRM</kbd> → <kbd>Snapshots</kbd>.</p>
            </div>
            <div class="paso"><span class="paso-num">2</span>
                <h5>Clic en "Generar snapshot ahora"</h5>
                <p>Botón azul arriba a la derecha. Se abre un modal.</p>
            </div>
            <div class="paso"><span class="paso-num">3</span>
                <h5>Opcional: anota un contexto</h5>
                <p>El campo "Notas" sirve para recordar por qué generaste ese snapshot. Ejemplos:</p>
                <ul>
                    <li><em>"Antes de la junta del 20 de mayo"</em></li>
                    <li><em>"Cierre de mes"</em></li>
                    <li><em>"Snapshot semanal regular — lunes"</em></li>
                </ul>
            </div>
            <div class="paso"><span class="paso-num">4</span>
                <h5>Confirmar</h5>
                <p>El sistema calcula todos los KPIs leyendo del estado vivo y guarda el snapshot. Te lleva al detalle automáticamente.</p>
            </div>

            <div class="tip">
                <strong>💡 Disciplina recomendada:</strong> generar un snapshot <strong>cada lunes en la mañana</strong> (después de la reunión semanal del equipo comercial). Así OTTO puede comparar semana a semana de manera consistente.
            </div>

            <h3>11.2 Cómo leer el detalle de un snapshot</h3>
            <p>Al entrar al detalle (<kbd>Ver</kbd> en la tabla de historial) verás:</p>
            <ul>
                <li><strong>KPIs principales con deltas vs snapshot anterior</strong> — flechas ▲ verdes (crece) o ▼ rojas (baja) y texto descriptivo. Si es el primer snapshot, dice "primera vez".</li>
                <li><strong>Pipeline por etapa</strong> — tabla con cantidad y valor total por etapa abierta.</li>
                <li><strong>Ranking por responsable</strong> — quién tiene cuánto pipeline y cuánto valor ganado en el año.</li>
                <li><strong>Top motivos de pérdida del año</strong> — patrones recurrentes (Precio, Timing, etc.).</li>
            </ul>

            <div class="warning">
                <strong>⚠️ Los snapshots NO se actualizan.</strong> Una vez generado, queda congelado para siempre como referencia histórica. Si te equivocaste o quieres uno fresco, genera otro — no hay límite (pero el espacio de comparación de OTTO ordena por fecha).
            </div>
        </section>

        <!-- 12. OTTO -->
        <section class="seccion" id="otto">
            <h2>12. OTTO Coach Comercial (IA)</h2>
            <p>OTTO es un <strong>asistente IA</strong> (basado en Claude Sonnet 4.6) entrenado para responder dos preguntas críticas del equipo comercial:</p>
            <ol>
                <li><strong>¿Avanzamos?</strong> — comparando el estado actual con snapshots anteriores y siendo honesto sobre el progreso real.</li>
                <li><strong>¿Qué hacer para crecer?</strong> — proponiendo acciones concretas, priorizadas y accionables.</li>
            </ol>
            <p>Es la <strong>misma persona OTTO</strong> que ya existe en Conciliaciones, pero con un "modo coach comercial" que tiene acceso a las tablas del CRM en lugar de las financieras. Comparten presupuesto mensual ($5 USD/mes para ambos modos).</p>

            <div class="tip">
                <strong>💡 OTTO NUNCA inventa cifras.</strong> Si no puede obtener un dato con sus herramientas, lo dice claramente. Eso lo hace confiable pero también significa que su utilidad depende de que tengas datos: empresas, oportunidades, interacciones y al menos un snapshot.
            </div>

            <h3 id="otto-uso">12.1 Cómo usarlo</h3>
            <p>Hay dos puntos de acceso:</p>

            <div class="paso"><span class="paso-num">A</span>
                <h5>Pantalla dedicada</h5>
                <p>Menú <kbd>CRM</kbd> → <kbd>OTTO Coach Comercial</kbd>. Verás 5 tarjetas de análisis preconfigurado, una barra de consumo del mes y el historial de conversaciones recientes. Es la mejor entrada para arrancar.</p>
            </div>
            <div class="paso"><span class="paso-num">B</span>
                <h5>Widget flotante</h5>
                <p>Botón circular oscuro en la esquina inferior derecha (visible en cualquier pantalla del CRM). Lo abres con un clic y arranca con chips de inicio rápido y un campo de texto libre para preguntarle lo que quieras.</p>
            </div>

            <h3 id="otto-presets">12.2 Los 5 análisis predefinidos</h3>
            <p>Sirven como "atajos" para preguntas comunes. Cada uno llama una secuencia específica de herramientas y entrega una respuesta estructurada:</p>

            <div class="paso"><span class="paso-num">1</span>
                <h5>📈 ¿Avanzamos esta semana?</h5>
                <p>OTTO compara los <strong>dos snapshots más recientes</strong>, analiza cada delta y entrega:</p>
                <ul>
                    <li><strong>Veredicto:</strong> AVANZAMOS / ESTANCADOS / RETROCEDIMOS (una sola línea)</li>
                    <li>Lo que mejoró (con evidencia numérica)</li>
                    <li>Lo que empeoró o se mantiene</li>
                    <li>2-3 acciones sugeridas para la próxima semana</li>
                </ul>
                <p class="text-muted small"><strong>Requisito:</strong> al menos 2 snapshots generados.</p>
            </div>

            <div class="paso"><span class="paso-num">2</span>
                <h5>🎯 ¿Qué oportunidades atacar primero?</h5>
                <p>Combina tres tools (top por valor ponderado, próximas a cierre, estancadas) y entrega:</p>
                <ul>
                    <li>Top 3 oportunidades a cerrar YA (alto valor × probabilidad × cierre cercano)</li>
                    <li>Top 3 en riesgo de morir (alto valor + estancadas)</li>
                    <li>Acción concreta por cada una</li>
                </ul>
            </div>

            <div class="paso"><span class="paso-num">3</span>
                <h5>👥 Diagnóstico del equipo</h5>
                <p>Ranking del equipo en el mes y en el año, identifica top performer, vendedor en alza y vendedor sin movimiento. Da recomendaciones <strong>constructivas</strong> (apoyo, reasignación) — no acusatorias.</p>
            </div>

            <div class="paso"><span class="paso-num">4</span>
                <h5>🚧 Cuellos de botella del pipeline</h5>
                <p>Detecta en qué etapa se atascan las oportunidades y por qué se caen. Mapa del funnel + estancadas + motivos de pérdida recurrentes → 3 acciones correctivas específicas.</p>
            </div>

            <div class="paso"><span class="paso-num">5</span>
                <h5>🚀 Plan de crecimiento del mes</h5>
                <p>El más completo — síntesis ejecutiva con:</p>
                <ul>
                    <li>Resumen ejecutivo (situación + tendencia)</li>
                    <li>Objetivos numéricos realistas del mes (basados en pipeline ponderado)</li>
                    <li>3 acciones priorizadas con responsable, plazo, impacto en COP esperado</li>
                    <li>Riesgos y KPIs a vigilar cada semana</li>
                </ul>
            </div>

            <h3 id="otto-widget">12.3 Widget flotante</h3>
            <p>El círculo oscuro en la esquina inferior derecha es el <strong>widget de OTTO</strong>. Visible en todas las pantallas del CRM.</p>
            <div class="paso"><span class="paso-num">1</span>
                <h5>Abrir</h5>
                <p>Un clic en el círculo. Se despliega un panel de chat.</p>
            </div>
            <div class="paso"><span class="paso-num">2</span>
                <h5>Empezar rápido con un chip</h5>
                <p>Los 5 chips de bienvenida son atajos a los mismos análisis predefinidos. Útil cuando estás navegando y se te ocurre una pregunta concreta.</p>
            </div>
            <div class="paso"><span class="paso-num">3</span>
                <h5>Pregunta libre</h5>
                <p>En el campo de texto puedes escribir cualquier pregunta sobre el pipeline. Ejemplos:</p>
                <ul>
                    <li><em>"¿Cómo va Edison este mes?"</em></li>
                    <li><em>"Cuéntame todo sobre la oportunidad OPP-20260515-0003"</em></li>
                    <li><em>"¿Tenemos algo con la empresa ACME?"</em></li>
                    <li><em>"¿Qué porcentaje del pipeline está en Negociación?"</em></li>
                </ul>
            </div>
            <div class="paso"><span class="paso-num">4</span>
                <h5>Continuar conversación</h5>
                <p>La conversación se guarda en tu navegador (localStorage) y la próxima vez que abras el widget retoma donde quedaste. Si quieres empezar de cero, usa el botón circular <i class="bi bi-arrow-clockwise"></i> arriba.</p>
            </div>

            <div class="warning">
                <strong>⚠️ Límite mensual:</strong> el consumo está topado en <strong>$5 USD al mes</strong> (compartido con OTTO financiero). Si se alcanza, OTTO deja de responder hasta el próximo mes — verás el aviso. La mayoría de consultas cuestan fracciones de centavo gracias al prompt caching, así que el límite suele sobrar.
            </div>

            <div class="tip">
                <strong>💡 Tip pro:</strong> antes de la junta semanal del equipo comercial, lanza el preset "<strong>📈 ¿Avanzamos esta semana?</strong>" — su respuesta es el resumen ideal para arrancar la reunión.
            </div>
        </section>

        <!-- 13. FAQ -->
        <section class="seccion" id="faq">
            <h2>13. Preguntas frecuentes</h2>

            <h3>No me aparece el dropdown CRM en el menú superior.</h3>
            <p>Pasa una de tres cosas:</p>
            <ul>
                <li>Eres rol <strong>Contador (5)</strong> — está explícitamente excluido del CRM. No hay flag que lo desbloquee.</li>
                <li>Tu sesión es vieja — <strong>cierra sesión y vuelve a entrar</strong> para que cargue los permisos actualizados.</li>
                <li>El despliegue aún no llegó al servidor — confirma con Edison.</li>
            </ul>
            <p>Cualquier otro usuario logueado debería ver el dropdown CRM automáticamente.</p>

            <h3>Veo el CRM pero no veo las oportunidades de mis compañeros.</h3>
            <p>Es por diseño: los vendedores solo ven las suyas. Si necesitas ver todas, pide ser
            <em>Admin CRM</em> (o si eres jefe, pide rol Admin del sistema).</p>

            <h3>¿Puedo mover una oportunidad ya cerrada (ganada o perdida) de vuelta a una etapa abierta?</h3>
            <p>Sí. Arrástrala en el Kanban a una columna abierta. El sistema limpia automáticamente
            la fecha de cierre real y el motivo de pérdida, y la oportunidad vuelve a estar "en juego".
            Todo el cambio queda registrado en el historial.</p>

            <h3>¿Qué pasa si elimino una empresa con contactos pero sin oportunidades?</h3>
            <p>Se permite, y los contactos asociados se eliminan en cascada. Por eso el sistema solo bloquea
            el borrado si hay oportunidades (esas tienen valor histórico y no deberían perderse).</p>

            <h3>¿La probabilidad cambia automáticamente al cambiar de etapa?</h3>
            <p>Cuando arrastras en el Kanban, sí: se ajusta al default de la nueva etapa.
            Cuando editas el formulario, te pregunta si quieres actualizar la probabilidad.
            Siempre puedes ponerle el valor que quieras manualmente.</p>

            <h3>¿Cómo agrego un campo nuevo (ej: industria, número de empleados)?</h3>
            <p>No es configurable desde la UI — requiere una modificación del código (modelo, migración, vistas).
            Pídeselo al equipo de desarrollo.</p>

            <h3>¿Se mandan correos automáticos de los recordatorios?</h3>
            <p>Aún no. Está planeado para la Fase 2 del módulo (junto con alertas de oportunidades estancadas
            y resumen semanal del pipeline al gerente). Por ahora, los recordatorios se ven en el dashboard.</p>

            <h3>¿Las oportunidades ganadas se convierten automáticamente en clientes / cuentas de cobro?</h3>
            <p>Aún no. La promoción de "tercero" desde una oportunidad ganada es parte de la Fase 3.
            Por ahora, al ganar una oportunidad, crea manualmente el tercero en
            <kbd>Conciliaciones → Terceros</kbd> con los datos de la empresa.</p>

            <h3>¿Puedo importar empresas desde un Excel?</h3>
            <p>Por ahora no hay carga masiva. Está en la lista de mejoras futuras. Si tienes una base
            grande para migrar, dilo al equipo de desarrollo para evaluar un import puntual.</p>

            <h3>OTTO me dice "solo hay 1 snapshot, no se puede comparar". ¿Qué hago?</h3>
            <p>Necesitas al menos 2 snapshots para que OTTO pueda decir si avanzamos. Ve a
            <kbd>CRM → Snapshots</kbd> y genera uno. Como el primero ya existe, este será el segundo
            y OTTO ya podrá comparar. La disciplina sugerida es generar uno cada lunes.</p>

            <h3>OTTO se quedó sin presupuesto antes de fin de mes. ¿Qué hago?</h3>
            <p>Esperar al próximo mes (se reinicia automáticamente el día 1) o pedir al equipo de
            desarrollo que suba el límite <code>IA_BUDGET_MES_USD</code> en el .env. Cada consulta normalmente
            cuesta menos de $0.05, así que con $5/mes alcanza para ~100 consultas (depende de la complejidad).</p>

            <h3>¿OTTO ve las oportunidades de TODOS o solo las mías?</h3>
            <p>OTTO ve y analiza el pipeline COMPLETO de todo el equipo, sin importar quién consulta.
            Esto es porque la utilidad del coach comercial es agregar y comparar globalmente. No filtra
            por responsable (a diferencia del Kanban y la lista). Si necesitas analizar solo lo tuyo,
            pregúntale específicamente "¿cómo va [tu nombre]?".</p>

            <h3>¿OTTO puede crear o modificar oportunidades?</h3>
            <p>No. Solo <strong>lee</strong>. Sus herramientas son consultas exclusivamente. Si te dice
            "deberías mover X a Calificado", tú tienes que ir y arrastrarlo en el Kanban manualmente.
            Es un coach, no un operador.</p>

            <h3>¿Las conversaciones con OTTO quedan guardadas?</h3>
            <p>Sí. Todas se guardan en la BD. Puedes verlas en <kbd>CRM → OTTO Coach Comercial</kbd>
            (tabla "Conversaciones recientes" abajo). El widget flotante también las recupera vía
            localStorage del navegador.</p>

            <h3>¿Por qué a veces el widget muestra un OTTO financiero en vez del comercial?</h3>
            <p>Porque estás navegando por una pantalla de Conciliaciones. El widget detecta la URL
            y cambia de modo automáticamente. Si estás en <code>/crm/*</code> es comercial; si estás en
            <code>/conciliaciones/*</code> es financiero. Las conversaciones de cada modo se guardan por separado.</p>
        </section>

        <div class="text-center text-muted small py-3">
            ¿Algo no quedó claro o falta un caso? Avísale a Edison para que el manual se actualice.
        </div>

        </div><!-- /col-9 -->
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Marcar la sección activa en el TOC mientras se hace scroll
(function() {
    const links = document.querySelectorAll('.toc a');
    const sections = document.querySelectorAll('.seccion');
    function actualizarActivo() {
        let idActiva = null;
        sections.forEach(s => {
            const top = s.getBoundingClientRect().top;
            if (top < 120) idActiva = s.id;
        });
        links.forEach(a => {
            a.classList.toggle('active', a.getAttribute('href') === '#' + idActiva);
        });
    }
    window.addEventListener('scroll', actualizarActivo);
    actualizarActivo();
})();
</script>
</body>
</html>
