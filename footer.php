<div class="ia-float-wrapper">
    <button id="iaFloatButton" class="ia-float-button" title="Asistente IA">
        IA
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
            <button type="button" onclick="enviarPreguntaFlotante('Dame un resumen de la actividad reciente del sistema.')">Resumen</button>
            <button type="button" onclick="enviarPreguntaFlotante('¿Qué equipos están inactivos actualmente?')">Inactivos</button>
            <button type="button" onclick="enviarPreguntaFlotante('¿Qué cambios de estado recientes hubo en el sistema?')">Cambios</button>
        </div>

        <div id="iaFloatMessages" class="ia-float-messages">
            <div class="ia-float-msg ia-float-msg-assistant">
                Hola, puedo ayudarte con resúmenes, cambios recientes y estado actual.
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

if (iaFloatButton && iaFloatPanel && iaCloseButton && iaFloatForm && iaFloatInput && iaFloatMessages) {
iaFloatButton.addEventListener("click", function () {
    iaFloatPanel.classList.toggle("active");
});

iaCloseButton.addEventListener("click", function () {
    iaFloatPanel.classList.remove("active");
});
    iaCloseButton.addEventListener("click", function () {
        iaFloatPanel.style.display = "none";
    });

    function agregarMensajeFlotante(mensaje, tipo = "assistant", temporal = false) {
        const div = document.createElement("div");
        div.className = "ia-float-msg " + (tipo === "user" ? "ia-float-msg-user" : "ia-float-msg-assistant");
        div.textContent = mensaje;
        if (temporal) div.dataset.temporal = "true";
        iaFloatMessages.appendChild(div);
        iaFloatMessages.scrollTop = iaFloatMessages.scrollHeight;
    }

    function removerTemporalFlotante() {
        const temporal = iaFloatMessages.querySelector('[data-temporal="true"]');
        if (temporal) temporal.remove();
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
</script>

</body>
</html>
