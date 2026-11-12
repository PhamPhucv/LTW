<?php

?>

<button type="button" id="chatbotToggleBtn" class="chatbot-toggle-btn" title="Trợ lý ảo CRM Pro">
    <i class="bi bi-robot" id="chatbotToggleIcon"></i>
    <span class="badge-pulse"></span>
</button>

<div id="chatbotWindow" class="chatbot-window chatbot-hidden">

    <div class="chatbot-header">
        <div class="d-flex align-items-center gap-2">
            <div class="bot-avatar">
                <i class="bi bi-robot"></i>
            </div>
            <div>
                <div class="fw-bold" style="font-size: 0.95rem; line-height: 1.2;">CRM Assistant</div>
                <div class="text-white-50 small d-flex align-items-center" style="font-size: 0.75rem;">
                    <span class="bot-status-dot"></span> Trực tuyến 24/7
                </div>
            </div>
        </div>
        <div class="chatbot-header-actions d-flex align-items-center gap-1">
            <button type="button" id="chatbotClearBtn" title="Xóa lịch sử chat">
                <i class="bi bi-trash3"></i>
            </button>
            <button type="button" id="chatbotCloseBtn" title="Thu nhỏ">
                <i class="bi bi-dash-lg"></i>
            </button>
        </div>
    </div>

    <div id="chatbotMessages" class="chatbot-body">

    </div>

    <div id="chatbotChips" class="chatbot-chips">
        <span class="chat-chip" data-query="📊 Thống kê nhanh">📊 Thống kê</span>
        <span class="chat-chip" data-query="⭐ Khách VIP">⭐ Khách VIP</span>
        <span class="chat-chip" data-query="🔍 Tìm khách Nam">🔍 Tìm khách</span>
        <span class="chat-chip" data-query="❓ Hướng dẫn sử dụng">❓ Hướng dẫn</span>
        <span class="chat-chip" data-query="🗑️ Thùng rác">🗑️ Thùng rác</span>
    </div>

    <div class="chatbot-footer">
        <form id="chatbotForm" class="m-0">
            <div class="chatbot-input-group">
                <input
                    type="text"
                    id="chatbotInput"
                    placeholder="Hỏi về khách hàng, thống kê, cách dùng..."
                    autocomplete="off"
                >
                <button type="submit" id="chatbotSendBtn" title="Gửi câu hỏi">
                    <i class="bi bi-send-fill"></i>
                </button>
            </div>
        </form>
    </div>
</div>
