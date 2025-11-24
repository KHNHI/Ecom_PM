console.log("=== CHAT VIEW DEBUG ===");
console.log("Conversation ID:", conversationId);
console.log("Last Message ID:", lastMessageId);
console.log("Polling interval:", pollInterval);

// Override checkNewMessages để debug
const originalCheckNewMessages = checkNewMessages;
window.checkNewMessages = async function () {
  console.log("🔄 Checking for new messages...");
  console.log(
    "URL:",
    `/Ecom_PM/admin/index.php?url=chat/new-messages&conversation_id=${conversationId}&after_id=${lastMessageId}`
  );

  try {
    const response = await fetch(
      `/Ecom_PM/admin/index.php?url=chat/new-messages&conversation_id=${conversationId}&after_id=${lastMessageId}`
    );
    console.log("Response status:", response.status);

    const result = await response.json();
    console.log("Result:", result);

    if (result.success && result.data && result.data.length > 0) {
      console.log("✅ Found new messages:", result.data.length);
      result.data.forEach((msg) => {
        console.log("Adding message:", msg);
      });
    } else {
      console.log("ℹ️ No new messages");
    }

    // Call original function
    return originalCheckNewMessages();
  } catch (error) {
    console.error("❌ Error:", error);
  }
};
