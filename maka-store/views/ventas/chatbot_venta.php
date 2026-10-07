<?php
// views/ventas/chatbot_venta.php
// Nuevo formulario para la gestión de ventas con Chatbot
// Versión corregida para asegurar la carga correcta del JS

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Incluir header.php
// Asumimos que header.php abre la etiqueta <body> y carga Bootstrap CSS y Font Awesome CSS.
include __DIR__ . '/../../partials/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4">Asistente de Ventas con Chatbot</h2>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <p class="lead">Usa el asistente de ventas flotante para registrar nuevas ventas paso a paso. Haz clic en el ícono de robot en la esquina inferior derecha.</p>

    </div>

<div class="modal fade" id="salesChatbotModal" tabindex="-1" aria-labelledby="salesChatbotModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="salesChatbotModalLabel">Asistente de Ventas 🤖</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="chatbox" class="p-2 border rounded mb-3" style="height: 300px; overflow-y: auto; background-color: #f8f9fa;">
          </div>
        <div class="input-group">
          <input type="text" id="userInput" class="form-control" placeholder="Escribe tu mensaje...">
          <button class="btn btn-primary" id="sendMessageBtn">Enviar</button>
        </div>
      </div>
    </div>
  </div>
</div>

<button class="btn btn-success rounded-circle shadow" id="openChatbotBtn" style="position: fixed; bottom: 20px; right: 20px; width: 60px; height: 60px; font-size: 24px; display: flex; align-items: center; justify-content: center; z-index: 1050;">
    <i class="fas fa-robot"></i>
</button>

<?php
// Incluir el footer.php.
// Es CRUCIAL que footer.php contenga las inclusiones de jQuery y Bootstrap JavaScript
// ANTES de que termine el cuerpo (</body>) y de nuestro script personalizado.
include __DIR__ . '/../../partials/footer.php';
?>

<script>
    $(document).ready(function() {
        console.log("Script del chatbot cargado y jQuery listo."); // Mensaje de depuración

        const chatbox = $('#chatbox');
        const userInput = $('#userInput');
        const sendMessageBtn = $('#sendMessageBtn');
        const salesChatbotModal = $('#salesChatbotModal');
        const openChatbotBtn = $('#openChatbotBtn');

        // Función para añadir mensajes al chatbox
        function addMessage(sender, message, type = 'text') {
            const messageClass = sender === 'user' ? 'text-end' : 'text-start';
            const bubbleClass = sender === 'user' ? 'bg-primary text-white' : 'bg-light text-dark';
            let content;

            if (type === 'text') {
                content = `<p class="mb-0">${message}</p>`;
            } else if (type === 'html') {
                content = message; // Asume que el mensaje ya es HTML
            }

            chatbox.append(`
                <div class="message ${messageClass} mb-2">
                    <div class="d-inline-block rounded py-2 px-3 ${bubbleClass}" style="max-width: 80%;">
                        ${content}
                    </div>
                </div>
            `);
            chatbox.scrollTop(chatbox[0].scrollHeight); // Auto-scroll al final
        }

        // Función para enviar mensaje al bot
        function sendMessageToBot(message) {
            addMessage('user', message);
            userInput.val(''); // Limpiar input

            // Deshabilitar input y botón mientras se espera respuesta
            userInput.prop('disabled', true);
            sendMessageBtn.prop('disabled', true);
            chatbox.append('<div id="loading-indicator" class="text-center text-muted">Escribiendo...</div>');
            chatbox.scrollTop(chatbox[0].scrollHeight);

            $.ajax({
                url: '../../api/ventas/chatbot_api.php', // Ruta correcta a tu API
                method: 'POST',
                data: { message: message },
                dataType: 'json',
                success: function(response) {
                    $('#loading-indicator').remove();
                    addMessage('bot', response.bot_message);
                    userInput.prop('disabled', false);
                    sendMessageBtn.prop('disabled', false);
                    userInput.focus(); // Volver a enfocar el input

                    // Si la acción es 'end' o 'error', permitir al usuario reiniciar o cerrar
                    if (response.action === 'end' || response.action === 'error') {
                        addMessage('bot', 'Puedes cerrar este chat o enviar "INICIAR_CONVERSACION" para una nueva venta.');
                    }
                },
                error: function(xhr, status, error) {
                    $('#loading-indicator').remove();
                    console.error("Error al comunicarse con el chatbot:", status, error);
                    addMessage('bot', 'Lo siento, hubo un error de comunicación. Por favor, intenta de nuevo o refresca la página.');
                    userInput.prop('disabled', false);
                    sendMessageBtn.prop('disabled', false);
                    userInput.focus();
                }
            });
        }

        // Eventos
        openChatbotBtn.on('click', function() {
            salesChatbotModal.modal('show');
            // Al abrir el modal, enviar un mensaje inicial para activar el bot
            if (chatbox.children().length === 0 || chatbox.children().last().text().includes('nueva venta')) {
                // Solo si está vacío o si el último mensaje sugiere iniciar una nueva venta (por ejemplo, después de una finalización/error)
                sendMessageToBot('INICIAR_CONVERSACION');
            }
        });

        sendMessageBtn.on('click', function() {
            const message = userInput.val().trim();
            if (message) {
                sendMessageToBot(message);
            }
        });

        userInput.on('keypress', function(e) {
            if (e.which === 13) { // Tecla Enter
                sendMessageBtn.click();
            }
        });

        // Limpiar el chatbox al cerrar el modal (opcional, para una nueva conversación limpia)
        salesChatbotModal.on('hidden.bs.modal', function () {
            // Puedes decidir si quieres limpiar o mantener la conversación
            // chatbox.empty(); // Descomenta si quieres limpiar el historial cada vez que cierras
        });
    });
</script>