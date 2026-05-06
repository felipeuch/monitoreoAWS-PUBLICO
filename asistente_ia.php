<?php
include("verificar_sesion.php");
include("header.php");
?>

<div class="page-card">
    <h2 class="page-title">Asistente IA</h2>
    <p class="page-subtitle">
        Consulta información resumida del sistema de monitoreo mediante inteligencia artificial.
    </p>

    <div class="actions" style="margin-bottom: 20px;">
        <button class="btn btn-secondary" onclick="enviarPreguntaRapida('Dame un resumen de la actividad reciente del sistema.')">Resumen reciente</button>
        <button class="btn btn-secondary" onclick="enviarPreguntaRapida('¿Qué equipos están inactivos actualmente?')">Equipos inactivos</button>
        <button class="btn btn-secondary" onclick="enviarPreguntaRapida('¿Qué cambios de estado recientes hubo en el sistema?')">Cambios recientes</button>
        <button class="btn btn-secondary" onclick="enviarPreguntaRapida('¿Qué actividad administrativa reciente se registró?')">Actividad administrativa</button>
    </div>

    <div class="page-card" style="background: rgba(15, 23, 42, 0.65); margin-bottom: 20px;">
        <div id="chatBox" style="min-height: 280px; max-height: 450px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px;">
            <div class="soft-box">
                <strong>Asistente IA:</strong>
                <p style="margin-top: 8px;">
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
                </p>
            </div>
        </div>
    </div>

    <div class="page-card" style="background: rgba(15, 23, 42, 0.65);">
        <form id="formAsistente">
            <label for="pregunta">Escribe tu consulta</label>
            <textarea id="pregunta" name="pregunta" placeholder="Ejemplo: Dame un resumen del estado actual del sistema." required></textarea>

            <div class="actions">
                <button type="submit" class="btn btn-primary">Enviar</button>
            </div>
        </form>
    </div>
</div>

<script>
const formAsistente = document.getElementById("formAsistente");
const chatBox = document.getElementById("chatBox");
const preguntaInput = document.getElementById("pregunta");

function agregarMensaje(remitente, mensaje, tipo = "normal") {
    const div = document.createElement("div");
    div.className = "soft-box";

    if (tipo === "usuario") {
        div.style.background = "rgba(37, 99, 235, 0.18)";
        div.style.border = "1px solid rgba(56, 189, 248, 0.20)";
    }

    div.innerHTML = `<strong>${remitente}:</strong><p style="margin-top: 8px; white-space: pre-line;">${mensaje}</p>`;
    chatBox.appendChild(div);
    chatBox.scrollTop = chatBox.scrollHeight;
}

function enviarPreguntaRapida(texto) {
    preguntaInput.value = texto;
    formAsistente.dispatchEvent(new Event("submit"));
}

formAsistente.addEventListener("submit", async function(e) {
    e.preventDefault();

    const pregunta = preguntaInput.value.trim();
    if (pregunta === "") return;

    agregarMensaje("Tú", pregunta, "usuario");
    preguntaInput.value = "";

    agregarMensaje("Asistente IA", "Pensando...");

    try {
        const response = await fetch("procesar_asistente_ia.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "pregunta=" + encodeURIComponent(pregunta)
        });

        const data = await response.json();

        chatBox.removeChild(chatBox.lastChild);

        if (data.error) {
            agregarMensaje("Asistente IA", "Error: " + data.error);
        } else {
            agregarMensaje("Asistente IA", data.respuesta);
        }
    } catch (error) {
        chatBox.removeChild(chatBox.lastChild);
        agregarMensaje("Asistente IA", "Ocurrió un error al procesar la consulta.");
    }
});
</script>

<?php include("footer.php"); ?>
