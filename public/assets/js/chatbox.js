/**
 * Chatbox Widget JavaScript
 * Quản lý giao tiếp real-time giữa khách hàng và admin
 */

class ChatboxWidget {
  constructor() {
    this.conversationId = null;
    this.lastMessageId = 0;
    this.pollInterval = null;
    this.isOpen = false;
    this.unreadCount = 0;
    this.basePath = "/Ecom_PM"; // Base path for API calls

    this.init();
  }

  init() {
    this.render();
    this.attachEventListeners();
    this.loadConversation();
    this.setupPageUnloadHandler();
  }

  render() {
    const chatboxHTML = `
            <div class="chatbox-widget">
                <!-- Button to toggle chatbox -->
                <button class="chatbox-button" id="chatbox-toggle">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <span class="badge" id="chatbox-badge" style="display: none;">0</span>
                </button>
                
                <!-- Chatbox container -->
                <div class="chatbox-container" id="chatbox-container">
                    <div class="chatbox-header">
                        <div class="chatbox-header-info">
                            <h3>Hỗ trợ trực tuyến</h3>
                            <div class="chatbox-status">Chúng tôi luôn sẵn sàng hỗ trợ bạn</div>
                        </div>
                        <button class="chatbox-close" id="chatbox-close">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 6L6 18M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="chatbox-messages" id="chatbox-messages">
                        <div class="chatbox-empty">
                            <div class="chatbox-empty-icon">💬</div>
                            <div class="chatbox-empty-text">Xin chào!</div>
                            <div class="chatbox-empty-subtext">Chúng tôi có thể giúp gì cho bạn?</div>
                        </div>
                    </div>
                    
                    <div class="chatbox-input-area">
                        <div class="chatbox-input-container">
                            <textarea 
                                class="chatbox-input" 
                                id="chatbox-input" 
                                placeholder="Nhập tin nhắn..."
                                rows="1"
                            ></textarea>
                            <button class="chatbox-send-button" id="chatbox-send" disabled>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

    document.body.insertAdjacentHTML("beforeend", chatboxHTML);
  }

  attachEventListeners() {
    // Toggle chatbox
    document.getElementById("chatbox-toggle").addEventListener("click", () => {
      this.toggle();
    });

    document.getElementById("chatbox-close").addEventListener("click", () => {
      this.close();
    });

    // Send message
    const input = document.getElementById("chatbox-input");
    const sendButton = document.getElementById("chatbox-send");

    input.addEventListener("input", () => {
      this.adjustTextareaHeight();
      sendButton.disabled = input.value.trim() === "";
    });

    input.addEventListener("keypress", (e) => {
      if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        this.sendMessage();
      }
    });

    sendButton.addEventListener("click", () => {
      this.sendMessage();
    });
  }

  adjustTextareaHeight() {
    const textarea = document.getElementById("chatbox-input");
    textarea.style.height = "auto";
    textarea.style.height = Math.min(textarea.scrollHeight, 100) + "px";
  }

  async loadConversation() {
    try {
      const response = await fetch(`${this.basePath}/chat/conversation`, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
        },
      });

      const data = await response.json();

      if (data.success && data.data) {
        this.conversationId = data.data.conversation.conversation_id;
        this.renderMessages(data.data.messages);

        if (data.data.messages.length > 0) {
          this.lastMessageId =
            data.data.messages[data.data.messages.length - 1].message_id;
        }

        // Start polling for new messages
        this.startPolling();
      }
    } catch (error) {
      console.error("Error loading conversation:", error);
    }
  }

  async sendMessage() {
    const input = document.getElementById("chatbox-input");
    const message = input.value.trim();

    if (!message || !this.conversationId) return;

    try {
      const response = await fetch(`${this.basePath}/chat/send`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          conversation_id: this.conversationId,
          message: message,
        }),
      });

      const data = await response.json();

      if (data.success && data.data) {
        input.value = "";
        this.adjustTextareaHeight();
        document.getElementById("chatbox-send").disabled = true;

        this.addMessage(data.data.message);
        this.lastMessageId = data.data.message.message_id;
      }
    } catch (error) {
      console.error("Error sending message:", error);
      alert("Không thể gửi tin nhắn. Vui lòng thử lại.");
    }
  }

  startPolling() {
    // Check for new messages every 3 seconds
    this.pollInterval = setInterval(() => {
      this.checkNewMessages();
    }, 3000);
  }

  stopPolling() {
    if (this.pollInterval) {
      clearInterval(this.pollInterval);
      this.pollInterval = null;
    }
  }

  async checkNewMessages() {
    if (!this.conversationId) return;

    try {
      const response = await fetch(
        `${this.basePath}/chat/new-messages?conversation_id=${this.conversationId}&after_id=${this.lastMessageId}`
      );

      const data = await response.json();

      if (data.success && data.data && data.data.messages.length > 0) {
        data.data.messages.forEach((msg) => {
          this.addMessage(msg);
          this.lastMessageId = msg.message_id;

          // Show notification if chatbox is closed
          if (!this.isOpen && msg.sender_type === "admin") {
            this.showNotification();
          }
        });
      }

      // Update unread count
      if (data.data && data.data.unread_count !== undefined) {
        this.updateUnreadBadge(data.data.unread_count);
      }
    } catch (error) {
      console.error("Error checking new messages:", error);
    }
  }

  renderMessages(messages) {
    const container = document.getElementById("chatbox-messages");

    if (messages.length === 0) {
      return;
    }

    // Clear empty state
    container.innerHTML = "";

    messages.forEach((msg) => {
      this.addMessage(msg, false);
    });

    this.scrollToBottom();
  }

  addMessage(message, scroll = true) {
    const container = document.getElementById("chatbox-messages");

    // Remove empty state if exists
    const emptyState = container.querySelector(".chatbox-empty");
    if (emptyState) {
      emptyState.remove();
    }

    const messageDiv = document.createElement("div");
    messageDiv.className = `chat-message ${message.sender_type}`;

    const time = this.formatTime(message.created_at);

    messageDiv.innerHTML = `
            <div class="message-bubble">
                ${
                  message.sender_type === "admin"
                    ? `<div class="message-sender">${
                        message.sender_name || "Hỗ trợ viên"
                      }</div>`
                    : ""
                }
                <div class="message-text">${this.escapeHtml(
                  message.message
                )}</div>
                <div class="message-time">${time}</div>
            </div>
        `;

    container.appendChild(messageDiv);

    if (scroll) {
      this.scrollToBottom();
    }
  }

  toggle() {
    if (this.isOpen) {
      this.close();
    } else {
      this.open();
    }
  }

  open() {
    document.getElementById("chatbox-container").classList.add("active");
    this.isOpen = true;
    this.scrollToBottom();
    this.updateUnreadBadge(0);

    // Mark messages as read
    if (this.conversationId) {
      this.markMessagesAsRead();
    }
  }

  close() {
    document.getElementById("chatbox-container").classList.remove("active");
    this.isOpen = false;
  }

  async markMessagesAsRead() {
    try {
      await fetch(`${this.basePath}/chat/mark-read`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          conversation_id: this.conversationId,
        }),
      });
    } catch (error) {
      console.error("Error marking messages as read:", error);
    }
  }

  scrollToBottom() {
    const container = document.getElementById("chatbox-messages");
    setTimeout(() => {
      container.scrollTop = container.scrollHeight;
    }, 100);
  }

  updateUnreadBadge(count) {
    this.unreadCount = count;
    const badge = document.getElementById("chatbox-badge");

    if (count > 0) {
      badge.textContent = count > 99 ? "99+" : count;
      badge.style.display = "flex";
    } else {
      badge.style.display = "none";
    }
  }

  showNotification() {
    this.unreadCount++;
    this.updateUnreadBadge(this.unreadCount);

    // Optional: Show browser notification
    if ("Notification" in window && Notification.permission === "granted") {
      new Notification("Tin nhắn mới", {
        body: "Bạn có tin nhắn mới từ hỗ trợ viên",
        icon: "/public/assets/images/logo.png",
      });
    }
  }

  formatTime(datetime) {
    const date = new Date(datetime);
    const now = new Date();
    const diff = now - date;

    // Less than 1 minute
    if (diff < 60000) {
      return "Vừa xong";
    }

    // Less than 1 hour
    if (diff < 3600000) {
      const minutes = Math.floor(diff / 60000);
      return `${minutes} phút trước`;
    }

    // Less than 1 day
    if (diff < 86400000) {
      const hours = Math.floor(diff / 3600000);
      return `${hours} giờ trước`;
    }

    // Format as time
    return date.toLocaleTimeString("vi-VN", {
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML.replace(/\n/g, "<br>");
  }

  setupPageUnloadHandler() {
    // Lắng nghe sự kiện trước khi thoát trang
    window.addEventListener("beforeunload", () => {
      // Chỉ xóa nếu chưa đăng nhập (không có user_id trong session)
      // Kiểm tra bằng cách gọi API với sendBeacon để đảm bảo request được gửi
      if (this.conversationId) {
        this.clearGuestConversation();
      }
    });
  }

  clearGuestConversation() {
    // Sử dụng sendBeacon để đảm bảo request được gửi ngay cả khi trang đang đóng
    const data = JSON.stringify({
      conversation_id: this.conversationId,
    });

    // Thử dùng sendBeacon trước (tốt hơn cho beforeunload)
    const blob = new Blob([data], { type: "application/json" });
    const sent = navigator.sendBeacon(
      `${this.basePath}/chat/clear-guest`,
      blob
    );

    // Nếu sendBeacon không được hỗ trợ, dùng fetch với keepalive
    if (!sent) {
      fetch(`${this.basePath}/chat/clear-guest`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: data,
        keepalive: true,
      }).catch((err) =>
        console.error("Error clearing guest conversation:", err)
      );
    }
  }

  destroy() {
    this.stopPolling();
    const widget = document.querySelector(".chatbox-widget");
    if (widget) {
      widget.remove();
    }
  }
}

// Initialize chatbox when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
  window.chatbox = new ChatboxWidget();

  // Request notification permission
  if ("Notification" in window && Notification.permission === "default") {
    Notification.requestPermission();
  }
});
