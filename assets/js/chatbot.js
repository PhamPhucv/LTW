
$(document).ready(function () {
    const $toggleBtn = $('#chatbotToggleBtn');
    const $window = $('#chatbotWindow');
    const $closeBtn = $('#chatbotCloseBtn');
    const $clearBtn = $('#chatbotClearBtn');
    const $messages = $('#chatbotMessages');
    const $form = $('#chatbotForm');
    const $input = $('#chatbotInput');
    const $sendBtn = $('#chatbotSendBtn');
    const $chips = $('#chatbotChips');

    const STORAGE_KEY_HISTORY = 'crm_chatbot_history';
    const STORAGE_KEY_STATE = 'crm_chatbot_open';

    function initChatbot() {
        const savedHistory = sessionStorage.getItem(STORAGE_KEY_HISTORY);
        if (savedHistory) {
            try {
                const history = JSON.parse(savedHistory);
                history.forEach(msg => appendMessageUI(msg.sender, msg.text, msg.time, false));
            } catch (e) {
                renderDefaultGreeting();
            }
        } else {
            renderDefaultGreeting();
        }

        const isOpen = sessionStorage.getItem(STORAGE_KEY_STATE) === '1';
        if (isOpen) {
            openChat();
        }

        scrollToBottom();
    }

    function renderDefaultGreeting() {
        $messages.empty();
        const welcomeText = "Xin chào! 👋 Tôi là <strong>CRM Assistant</strong>.<br>"
                          + "Tôi có thể giúp bạn xem thống kê nhanh, tra cứu thông tin khách hàng hoặc hướng dẫn sử dụng hệ thống.<br>"
                          + "Hãy thử bấm vào các gợi ý bên dưới nhé!";
        appendMessageUI('bot', welcomeText, getCurrentTime(), true);
    }

    function openChat() {
        $window.removeClass('chatbot-hidden');
        sessionStorage.setItem(STORAGE_KEY_STATE, '1');
        setTimeout(() => {
            $input.focus();
            scrollToBottom();
        }, 200);
    }

    function closeChat() {
        $window.addClass('chatbot-hidden');
        sessionStorage.setItem(STORAGE_KEY_STATE, '0');
    }

    function toggleChat() {
        if ($window.hasClass('chatbot-hidden')) {
            openChat();
        } else {
            closeChat();
        }
    }

    function getCurrentTime() {
        const now = new Date();
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        return `${h}:${m}`;
    }

    function appendMessageUI(sender, text, time, saveToStorage = true) {
        time = time || getCurrentTime();
        const isBot = sender === 'bot';

        const $msg = $(`
            <div class="chat-msg ${isBot ? 'bot' : 'user'}">
                <div class="chat-bubble">${text}</div>
                <div class="chat-time">${time}</div>
            </div>
        `);

        $messages.append($msg);
        scrollToBottom();

        if (saveToStorage) {
            saveMessageToStorage(sender, text, time);
        }
    }

    function saveMessageToStorage(sender, text, time) {
        let history = [];
        const saved = sessionStorage.getItem(STORAGE_KEY_HISTORY);
        if (saved) {
            try { history = JSON.parse(saved); } catch (e) { history = []; }
        }
        history.push({ sender, text, time });

        if (history.length > 30) {
            history = history.slice(-30);
        }
        sessionStorage.setItem(STORAGE_KEY_HISTORY, JSON.stringify(history));
    }

    function scrollToBottom() {
        $messages.scrollTop($messages[0].scrollHeight);
    }

    function showTypingIndicator() {
        const $typing = $(`
            <div id="chatbotTyping" class="chat-msg bot">
                <div class="typing-indicator">
                    <span></span><span></span><span></span>
                </div>
            </div>
        `);
        $messages.append($typing);
        scrollToBottom();
    }

    function removeTypingIndicator() {
        $('#chatbotTyping').remove();
    }

    function updateChips(quickReplies) {
        if (!Array.isArray(quickReplies) || quickReplies.length === 0) return;
        $chips.empty();
        quickReplies.forEach(qr => {
            const $chip = $('<span class="chat-chip"></span>').text(qr).attr('data-query', qr);
            $chips.append($chip);
        });
    }

    function sendMessage(messageText) {
        const text = (messageText || $input.val()).trim();
        if (!text) return;

        $input.val('');
        appendMessageUI('user', $('<div>').text(text).html(), getCurrentTime(), true);

        $sendBtn.prop('disabled', true);
        showTypingIndicator();

        $.ajax({
            url: 'api/chatbot.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ message: text }),
            dataType: 'json'
        }).done(function (res) {
            removeTypingIndicator();
            if (res.success && res.data && res.data.reply) {
                appendMessageUI('bot', res.data.reply, getCurrentTime(), true);
                if (res.data.quick_replies) {
                    updateChips(res.data.quick_replies);
                }
            } else {
                appendMessageUI('bot', 'Đã có lỗi xảy ra hoặc phản hồi không hợp lệ.', getCurrentTime(), true);
            }
        }).fail(function (xhr) {
            removeTypingIndicator();
            let errMsg = 'Không thể kết nối đến máy chủ. Vui lòng thử lại!';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errMsg = xhr.responseJSON.message;
            }
            appendMessageUI('bot', `⚠️ ${errMsg}`, getCurrentTime(), true);
        }).always(function () {
            $sendBtn.prop('disabled', false);
            $input.focus();
            scrollToBottom();
        });
    }

    $toggleBtn.on('click', toggleChat);
    $closeBtn.on('click', closeChat);

    $clearBtn.on('click', function () {
        if (confirm('Bạn có muốn xóa toàn bộ lịch sử trò chuyện này không?')) {
            sessionStorage.removeItem(STORAGE_KEY_HISTORY);
            renderDefaultGreeting();
        }
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        sendMessage();
    });

    $chips.on('click', '.chat-chip', function () {
        const query = $(this).attr('data-query');
        if (query) {
            sendMessage(query);
        }
    });

    initChatbot();
});
