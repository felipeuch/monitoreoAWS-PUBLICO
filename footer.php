<div class="ia-float-wrapper">
    <button id="iaFloatButton" class="ia-float-button" title="Asistente IA" aria-label="Abrir asistente IA">
        <span class="ia-bot-orbit" aria-hidden="true"></span>
        <span class="ia-bot-face" aria-hidden="true">
            <span class="ia-bot-antenna"></span>
            <span class="ia-bot-eyes">
                <span></span>
                <span></span>
            </span>
            <span class="ia-bot-mouth"></span>
            <span class="ia-bot-scan"></span>
        </span>
        <span class="ia-bot-label">AI</span>
    </button>

	<div id="iaFloatPanel" class="ia-float-panel">
        <div class="ia-float-header">
            <div>
                <strong>Asistente IA</strong>
                <div class="ia-float-subtitle">Consulta rápida del sistema</div>
            </div>
            <button id="iaCloseButton" class="ia-float-close" type="button">×</button>
        </div>

        <div class="ia-float-quick">
            <div class="ia-float-quick">
                <button type="button" onclick="enviarPreguntaFlotante('Dame un resumen de la actividad reciente del sistema.')">Resumen</button>
                <button type="button" onclick="enviarPreguntaFlotante('¿Qué equipos están activos actualmente?')">Activos</button>
                <button type="button" onclick="enviarPreguntaFlotante('¿Qué equipos están inactivos actualmente?')">Inactivos</button>
                <button type="button" onclick="enviarPreguntaFlotante('¿Qué cambios de estado recientes hubo en el sistema?')">Cambios</button>
                <button type="button" onclick="enviarPreguntaFlotante('Muéstrame los equipos con métricas disponibles para revisar.')">Métricas</button>
            </div>
            <div id="iaFloatMessages" class="ia-float-messages">
    <div class="ia-float-msg ia-float-msg-assistant">
        Bienvenido, administrador. Soy Nexa IA, tu asistente inteligente de monitoreo y estoy encantada de ayudarte.

        Puedo apoyarte con:
        - un resumen de la actividad reciente
        - equipos inactivos
        - cambios de estado detectados
        - actividad administrativa del sistema
        - estado general de la plataforma

        Mis sugerencias para hoy son:
        - Resumen reciente
        - Equipos inactivos
        - Cambios recientes
        - Actividad administrativa
    </div>
</div>
        </div>

        <form id="iaFloatForm" class="ia-float-form">
            <textarea id="iaFloatInput" placeholder="Escribe tu consulta..." required></textarea>
            <div class="ia-float-actions">
                <button type="submit" class="btn btn-primary">Enviar</button>
                <a href="asistente_ia.php" class="btn btn-secondary">Abrir completo</a>
            </div>
        </form>
    </div>
</div>

<div class="notificaciones-wrapper">
    <button id="btnNotificaciones" class="notificaciones-btn" type="button" title="Notificaciones">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
        <span id="contadorNotificaciones" class="notificaciones-contador" style="display:none;">0</span>
    </button>

    <div id="panelNotificaciones" class="notificaciones-panel" style="display:none;">
        <div class="notificaciones-header">
            <strong>Notificaciones</strong>
            <div class="notificaciones-header-actions">
                <button id="marcarTodasLeidas" type="button">Marcar leídas</button>
                <button id="eliminarLeidas" type="button">Eliminar leídas</button>
            </div>
        </div>

        <div id="listaNotificaciones" class="notificaciones-lista">
            <p style="padding:14px;">Cargando...</p>
        </div>
    </div>
</div>


<footer>
    <div class="container">
        <p>Cloud Monitoring © <?php echo date("Y"); ?> - Proyecto de monitoreo en AWS</p>
    </div>
</footer>

<script>
const iaFloatButton = document.getElementById("iaFloatButton");
const iaFloatPanel = document.getElementById("iaFloatPanel");
const iaCloseButton = document.getElementById("iaCloseButton");
const iaFloatForm = document.getElementById("iaFloatForm");
const iaFloatInput = document.getElementById("iaFloatInput");
const iaFloatMessages = document.getElementById("iaFloatMessages");

/* =========================
   BURBUJA FLOTANTE IA
========================= */
if (iaFloatButton && iaFloatPanel && iaCloseButton && iaFloatForm && iaFloatInput && iaFloatMessages) {
    iaFloatButton.addEventListener("click", function () {
        iaFloatPanel.classList.toggle("active");
    });

    iaCloseButton.addEventListener("click", function () {
        iaFloatPanel.classList.remove("active");
    });

    function agregarMensajeFlotante(mensaje, tipo = "assistant", temporal = false) {
        const div = document.createElement("div");
        div.className = "ia-float-msg " + (tipo === "user" ? "ia-float-msg-user" : "ia-float-msg-assistant");
        div.textContent = mensaje;

        if (temporal) {
            div.dataset.temporal = "true";
        }

        iaFloatMessages.appendChild(div);
        iaFloatMessages.scrollTop = iaFloatMessages.scrollHeight;
    }

    function removerTemporalFlotante() {
        const temporal = iaFloatMessages.querySelector('[data-temporal="true"]');
        if (temporal) {
            temporal.remove();
        }
    }

    window.enviarPreguntaFlotante = function (texto) {
        iaFloatInput.value = texto;
        iaFloatForm.dispatchEvent(new Event("submit"));
    };

    iaFloatForm.addEventListener("submit", async function (e) {
        e.preventDefault();

        const pregunta = iaFloatInput.value.trim();
        if (pregunta === "") return;

        agregarMensajeFlotante(pregunta, "user");
        iaFloatInput.value = "";
        agregarMensajeFlotante("Pensando...", "assistant", true);

        try {
            const response = await fetch("procesar_asistente_ia.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "pregunta=" + encodeURIComponent(pregunta)
            });

            const data = await response.json();
            removerTemporalFlotante();

            if (data.error) {
                agregarMensajeFlotante("Error: " + data.error, "assistant");
            } else {
                agregarMensajeFlotante(data.respuesta, "assistant");
            }
        } catch (error) {
            removerTemporalFlotante();
            agregarMensajeFlotante("Ocurrió un error al procesar la consulta.", "assistant");
        }
    });
}

/* =========================
   CAMPANITA DE NOTIFICACIONES
========================= */
async function cargarNotificaciones() {
    try {
        const response = await fetch("obtener_notificaciones.php");
        const data = await response.json();

        const contador = document.getElementById("contadorNotificaciones");
        const lista = document.getElementById("listaNotificaciones");

        if (!contador || !lista) return;

        if (data.total_no_leidas > 0) {
            contador.textContent = data.total_no_leidas;
            contador.style.display = "inline-block";
        } else {
            contador.style.display = "none";
        }

        if (!data.notificaciones || data.notificaciones.length === 0) {
            lista.innerHTML = "<p class='notificaciones-empty'>No hay notificaciones.</p>";
            return;
        }

        lista.innerHTML = "";

        data.notificaciones.forEach(n => {
            const item = document.createElement("div");
            item.className = "notificacion-item " + (n.estado === "No leida" ? "no-leida" : "");
            item.dataset.notificacionId = n.id;

            item.innerHTML = `
                <button class="notificacion-eliminar" type="button" title="Eliminar notificación" aria-label="Eliminar notificación" data-id="${n.id}">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3 6h18"></path>
                        <path d="M8 6V4h8v2"></path>
                        <path d="M19 6l-1 14H6L5 6"></path>
                        <path d="M10 11v5"></path>
                        <path d="M14 11v5"></path>
                    </svg>
                </button>
                <div class="notificacion-contenido">
                    <div class="notificacion-titulo">${n.titulo}</div>
                    <div class="notificacion-mensaje">${n.mensaje}</div>
                    <div class="notificacion-meta">${n.nivel} · ${n.modulo ?? ""} · ${n.fecha_creacion}</div>
                </div>
            `;

            lista.appendChild(item);
        });
    } catch (error) {
        console.error("Error cargando notificaciones:", error);
    }
}

async function eliminarNotificacionIndividual(id, item) {
    if (!id || !item) return;

    item.classList.add("eliminando");

    try {
        const response = await fetch("eliminar_notificacion.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "id=" + encodeURIComponent(id)
        });
        const data = await response.json();

        if (!data.ok) {
            item.classList.remove("eliminando");
            return;
        }

        setTimeout(() => {
            item.remove();
            const quedanItems = listaNotificaciones ? listaNotificaciones.querySelector(".notificacion-item") : null;

            if (!quedanItems && listaNotificaciones) {
                listaNotificaciones.innerHTML = "<p class='notificaciones-empty'>No hay notificaciones.</p>";
            }

            cargarNotificaciones();
        }, 180);
    } catch (error) {
        item.classList.remove("eliminando");
        console.error("Error al eliminar notificación:", error);
    }
}

const btnNotificaciones = document.getElementById("btnNotificaciones");
const panelNotificaciones = document.getElementById("panelNotificaciones");
const listaNotificaciones = document.getElementById("listaNotificaciones");

if (btnNotificaciones && panelNotificaciones) {
    btnNotificaciones.addEventListener("click", function () {
        const visible = panelNotificaciones.classList.contains("abierto");

        if (visible) {
            panelNotificaciones.classList.remove("abierto");
            btnNotificaciones.classList.remove("activo");
            setTimeout(() => {
                if (!panelNotificaciones.classList.contains("abierto")) {
                    panelNotificaciones.style.display = "none";
                }
            }, 190);
            return;
        }

        panelNotificaciones.style.display = "block";
        requestAnimationFrame(() => {
            panelNotificaciones.classList.add("abierto");
            btnNotificaciones.classList.add("activo");
        });
        cargarNotificaciones();
    });
}

if (listaNotificaciones) {
    listaNotificaciones.addEventListener("click", function (event) {
        const botonEliminar = event.target.closest(".notificacion-eliminar");
        if (!botonEliminar) return;

        event.preventDefault();
        event.stopPropagation();

        const item = botonEliminar.closest(".notificacion-item");
        eliminarNotificacionIndividual(botonEliminar.dataset.id, item);
    });
}

const marcarTodasLeidas = document.getElementById("marcarTodasLeidas");

if (marcarTodasLeidas) {
    marcarTodasLeidas.addEventListener("click", async function () {
        try {
            await fetch("marcar_notificaciones_leidas.php", {
                method: "POST"
            });

            cargarNotificaciones();
        } catch (error) {
            console.error("Error al marcar notificaciones como leídas:", error);
        }
    });
}


const eliminarLeidas = document.getElementById("eliminarLeidas");

if (eliminarLeidas) {
    eliminarLeidas.addEventListener("click", async function () {
        const resultado = await Swal.fire({
            title: "Eliminar notificaciones leídas",
            text: "Se eliminarán todas las notificaciones que ya fueron marcadas como leídas.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Eliminar leídas",
            cancelButtonText: "Cancelar",
            reverseButtons: true,
            confirmButtonColor: "#dc2626",
            cancelButtonColor: "#334155",
            background: "#0f172a",
            color: "#e5e7eb"
        });

        if (!resultado.isConfirmed) {
            return;
        }

        try {
            const response = await fetch("eliminar_notificaciones_leidas.php", {
                method: "POST"
            });
            const data = await response.json();

            if (!data.ok) {
                return;
            }

            Swal.fire({
                position: "center",
                icon: "success",
                title: "Notificaciones leídas eliminadas",
                showConfirmButton: false,
                timer: 1700,
                timerProgressBar: true,
                background: "#0f172a",
                color: "#e5e7eb"
            });
            cargarNotificaciones();
        } catch (error) {
            console.error("Error al eliminar notificaciones leídas:", error);
        }
    });
}



cargarNotificaciones();
setInterval(cargarNotificaciones, 60000);
</script>

</body>
</html>
