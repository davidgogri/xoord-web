// public_html/maka-store/js/ventas_chatbot.js

document.addEventListener('DOMContentLoaded', function() {
    const chatDisplay = document.getElementById('chat-display');
    const chatbotInput = document.getElementById('chatbot-input');
    const sendButton = document.getElementById('send-chat-message');
    const microphoneBtn = document.getElementById('microphone-btn');
    const speakerBtn = document.getElementById('speaker-btn');
    const chatbotModalElement = document.getElementById('chatbotModal'); // El elemento del modal

    let recognition; // Para SpeechRecognition (Voz a Texto)
    let synth = window.speechSynthesis; // Para Text-to-Speech (Texto a Voz)
    let isSpeaking = true; // Controla si el bot debe hablar (por defecto, sí)

    // --- Funciones de Interfaz del Chat ---

    /**
     * Añade un mensaje al display del chat.
     * @param {string} sender 'user' o 'bot'.
     * @param {string} text El texto del mensaje.
     */
    function appendMessage(sender, text) {
        const messageClass = sender === 'user' ? 'user-message' : 'bot-message';
        const messageHtml = `<div class="message ${messageClass}">${text}</div>`;
        chatDisplay.innerHTML += messageHtml;
        chatDisplay.scrollTop = chatDisplay.scrollHeight; // Auto-scroll al final
    }

    /**
     * Convierte texto a voz y lo reproduce.
     * @param {string} text El texto que el bot dirá.
     */
    function speakText(text) {
        if (!isSpeaking) return; // Si el sonido está desactivado, no hace nada

        if (synth.speaking) {
            synth.cancel(); // Detener cualquier habla anterior para que no se superpongan
        }
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'es-ES'; // Configura el idioma a español
        // Opcional: Puedes intentar seleccionar una voz específica si hay múltiples disponibles en español
        // let voices = synth.getVoices();
        // utterance.voice = voices.find(voice => voice.lang === 'es-ES' && voice.name.includes('Google español'));

        synth.speak(utterance);
    }

    /**
     * Envía el mensaje del usuario al backend del chatbot via AJAX.
     * @param {string} message El mensaje a enviar.
     */
    function sendMessageToBot(message) {
        // No mostramos el mensaje 'INICIAR_CONVERSACION' en el chat, es un trigger interno
        if (message !== 'INICIAR_CONVERSACION') {
            appendMessage('user', message);
        }
        chatbotInput.value = ''; // Limpiar input después de enviar

        // Deshabilitar input y botones para evitar múltiples envíos mientras el bot responde
        chatbotInput.disabled = true;
        sendButton.disabled = true;
        microphoneBtn.disabled = true;

        // Realizar la petición AJAX al nuevo endpoint del chatbot
        // La URL es relativa a la raíz de tu sitio (public_html/maka-store/)
        fetch('api/ventas/chatbot_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded', // Necesario para enviar datos como un formulario
            },
            // Codifica el mensaje para que sea seguro en la URL
            body: 'message=' + encodeURIComponent(message)
        })
        .then(response => {
            // Verifica si la respuesta es JSON antes de parsear
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.indexOf("application/json") !== -1) {
                return response.json();
            } else {
                // Si no es JSON, podría ser un error de PHP no manejado o HTML
                return response.text().then(text => {
                    throw new Error('Respuesta no JSON: ' + text);
                });
            }
        })
        .then(data => {
            if (data.bot_message) {
                appendMessage('bot', data.bot_message);
                speakText(data.bot_message);
            } else {
                // Manejo si la respuesta JSON no tiene 'bot_message'
                appendMessage('bot', 'El bot no pudo generar una respuesta. Intenta de nuevo.');
                speakText('El bot no pudo generar una respuesta. Intenta de nuevo.');
            }


            // Re-habilitar los controles
            chatbotInput.disabled = false;
            sendButton.disabled = false;
            microphoneBtn.disabled = false;
            chatbotInput.focus(); // Vuelve a enfocar el input

            // Lógica para reiniciar o finalizar la conversación si el bot lo indica
            if (data.action === 'end' || data.action === 'restart') {
                // Opcional: Podrías limpiar el chat por completo aquí para una nueva conversación
                // chatDisplay.innerHTML = '';
                // O mostrar un mensaje de "Inicia una nueva venta"
                // No es necesario ya que el bot ya lo ha dicho en el último mensaje
            }
        })
        .catch(error => {
            console.error('Error al comunicarse con el chatbot:', error);
            appendMessage('bot', 'Lo siento, hubo un error de conexión. Por favor, intenta de nuevo.');
            speakText('Lo siento, hubo un error de conexión. Por favor, intenta de nuevo.');
            // Asegurarse de re-habilitar los controles incluso en caso de error
            chatbotInput.disabled = false;
            sendButton.disabled = false;
            microphoneBtn.disabled = false;
            chatbotInput.focus();
        });
    }

    // --- Event Listeners para la Interfaz ---

    // Al hacer clic en el botón de enviar
    sendButton.addEventListener('click', function() {
        const message = chatbotInput.value.trim();
        if (message) { // Solo enviar si hay texto
            sendMessageToBot(message);
        }
    });

    // Al presionar Enter en el campo de texto
    chatbotInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendButton.click(); // Simula un clic en el botón de enviar
        }
    });

    // --- Speech-to-Text (Reconocimiento de Voz) ---

    // Comprueba si el navegador soporta SpeechRecognition
    // 'webkitSpeechRecognition' es para navegadores basados en Chromium
    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        recognition = new SpeechRecognition();
        recognition.continuous = false; // Reconoce una frase y se detiene
        recognition.lang = 'es-ES'; // Idioma para el reconocimiento

        microphoneBtn.addEventListener('click', function() {
            try {
                recognition.start();
                microphoneBtn.innerHTML = '<i class="fas fa-microphone-alt text-danger"></i> Hablando...';
                microphoneBtn.classList.add('recording'); // Añade una clase para animación
            } catch (e) {
                console.error('Error al iniciar el reconocimiento de voz:', e);
                // Si ya está escuchando y se intenta iniciar de nuevo, puede lanzar un error
                appendMessage('bot', 'Ya estoy escuchando o hubo un error al iniciar el micrófono. Por favor, intenta de nuevo.');
                speakText('Ya estoy escuchando o hubo un error al iniciar el micrófono. Por favor, intenta de nuevo.');
            }
        });

        // Cuando el reconocimiento de voz devuelve un resultado
        recognition.onresult = function(event) {
            const transcript = event.results[0][0].transcript; // Obtiene el texto reconocido
            chatbotInput.value = transcript; // Muestra el texto en el input
            sendMessageToBot(transcript); // Envía el texto al bot
        };

        // Cuando el reconocimiento de voz termina
        recognition.onend = function() {
            microphoneBtn.innerHTML = '<i class="fas fa-microphone-alt"></i>'; // Vuelve al icono normal
            microphoneBtn.classList.remove('recording'); // Quita la clase de animación
        };

        // Si ocurre un error en el reconocimiento de voz
        recognition.onerror = function(event) {
            console.error('Error de reconocimiento de voz:', event.error);
            microphoneBtn.innerHTML = '<i class="fas fa-microphone-alt"></i>';
            microphoneBtn.classList.remove('recording');
            appendMessage('bot', 'No pude entender lo que dijiste. Intenta de nuevo o escribe tu respuesta.');
            speakText('No pude entender lo que dijiste. Intenta de nuevo o escribe tu respuesta.');
        };
    } else {
        // Si el navegador no soporta SpeechRecognition, oculta el botón del micrófono
        microphoneBtn.style.display = 'none';
        console.warn('Speech Recognition no soportado en este navegador.');
    }

    // --- Text-to-Speech (Síntesis de Voz) ---

    // Alternar el estado de reproducción de voz del bot
    speakerBtn.addEventListener('click', function() {
        isSpeaking = !isSpeaking; // Invierte el estado
        if (isSpeaking) {
            speakerBtn.innerHTML = '<i class="fas fa-volume-up"></i>';
            speakerBtn.title = 'Desactivar sonido del bot';
            // speakText('Sonido activado.'); // Opcional: el bot puede confirmar
        } else {
            synth.cancel(); // Detener cualquier habla actual del bot
            speakerBtn.innerHTML = '<i class="fas fa-volume-mute"></i>';
            speakerBtn.title = 'Activar sonido del bot';
        }
    });

    // --- Inicialización del Chatbot al abrir el Modal ---

    // Evento que se dispara cuando el modal se ha hecho visible para el usuario
    chatbotModalElement.addEventListener('shown.bs.modal', function () {
        chatDisplay.innerHTML = ''; // Limpiar el display de chat para una nueva conversación
        appendMessage('bot', 'Hola, soy tu asistente de ventas Maka-Store. ¿En qué puedo ayudarte hoy?');
        speakText('Hola, soy tu asistente de ventas Maka-Store. ¿En qué puedo ayudarte hoy?');
        chatbotInput.focus(); // Poner el foco en el input del chat
    });

    // Evento que se dispara cuando el modal se ha ocultado (cerrado)
    chatbotModalElement.addEventListener('hidden.bs.modal', function () {
        // Opcional: Aquí puedes limpiar el estado del chatbot en el backend
        // (por ejemplo, enviando un mensaje especial al chatbot_api.php para resetear la sesión)
        // O por simplicidad, la próxima vez que se abra el modal, se iniciará un nuevo chat.
        if (synth.speaking) {
            synth.cancel(); // Detener el habla si el modal se cierra
        }
        if (recognition && recognition.recognizing) {
            recognition.stop(); // Detener el reconocimiento si el modal se cierra
        }
    });
});