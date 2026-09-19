<?php
// ai/ai-chat-widget.php
// ------------------------------------------------------------
// ONE FILE that renders the widget on GET and serves JSON chat
// replies on POST. Requires ai/secrets.php for API credentials.
// ------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Load secrets
    $secretsFile = __DIR__ . '/secrets.php';
    if (!file_exists($secretsFile)) {
        echo json_encode(['error' => 'Secrets file missing.']); exit;
    }
    $secrets = include $secretsFile;
    $apiKey  = $secrets['GROQ_API_KEY'] ?? '';
    $model   = $secrets['GROQ_MODEL'] ?? 'llama-3.1-8b-instant';
    $baseUrl = rtrim($secrets['GROQ_BASE'] ?? 'https://api.groq.com/openai/v1', '/');

    if (!$apiKey) {
        echo json_encode(['error' => 'API key missing.']); exit;
    }

    // Read JSON input
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    $messages = $input['messages'] ?? [['role' => 'user', 'content' => 'Hello']];

    if (!is_array($messages) || empty($messages)) {
        echo json_encode(['error' => 'Invalid messages payload.']); exit;
    }

    // Call Groq Chat Completions
    $payload = json_encode([
        'model'    => $model,
        'messages' => $messages,
    ]);

    $ch = curl_init($baseUrl . "/chat/completions");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer {$apiKey}",
            "Content-Type: application/json",
        ],
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        $err = curl_error($ch);
        curl_close($ch);
        echo json_encode(['error' => "Curl error: $err"]); exit;
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($httpCode < 200 || $httpCode >= 300) {
        $msg = $data['error']['message'] ?? "HTTP $httpCode";
        echo json_encode(['error' => "Upstream error: $msg", 'status' => $httpCode]); exit;
    }

    $reply = $data['choices'][0]['message']['content'] ?? null;
    if (!$reply) {
        echo json_encode(['error' => 'Invalid response from API', 'raw' => $data]); exit;
    }

    echo json_encode(['reply' => $reply]);
    exit;
}

// ----- If not POST: render the widget (safe to include on any page) -----
?>
<style>
#chat-widget {
  position: fixed; bottom: 20px; right: 20px; width: 320px; max-height: 500px;
  background: #fff; border: 1px solid #ccc; border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.2); display: flex; flex-direction: column;
  font-family: Arial, sans-serif; overflow: hidden; z-index: 1000;
}
#chat-header { background: #007bff; color: #fff; padding: 10px; text-align: center; font-weight: bold; }
#chat-messages { flex: 1; padding: 10px; overflow-y: auto; font-size: 14px; }
.message { margin: 6px 0; }
.message b { color:#333; }
#chat-input { display: flex; border-top: 1px solid #ccc; }
#chat-input input { flex: 1; border: none; padding: 10px; font-size: 14px; outline: none; }
#chat-input button { border: none; background: #007bff; color: #fff; padding: 10px 15px; cursor: pointer; }
#chat-input button[disabled] { opacity:.7; cursor:not-allowed; }
</style>

<div id="chat-widget" aria-live="polite">
  <div id="chat-header">💬 AI Assistant</div>
  <div id="chat-messages"></div>
  <div id="chat-input">
    <input type="text" id="chat-text" placeholder="Type a message..." />
    <button id="chat-send">Send</button>
  </div>
</div>

<script>
(function(){
  const widgetUrl = "<?php echo htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'); ?>"; // post to this same file
  const messagesDiv = document.getElementById("chat-messages");
  const input = document.getElementById("chat-text");
  const sendBtn = document.getElementById("chat-send");

  function appendMessage(role, text){
    const wrap = document.createElement('div');
    wrap.className = 'message';
    const name = document.createElement('b');
    name.textContent = (role === 'user' ? 'You' : 'Bot') + ': ';
    const span = document.createElement('span');
    span.textContent = text; // XSS-safe
    wrap.appendChild(name);
    wrap.appendChild(span);
    messagesDiv.appendChild(wrap);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
  }

  async function sendMessage(){
    const msg = input.value.trim();
    if(!msg) return;
    appendMessage('user', msg);
    input.value = '';
    sendBtn.disabled = true;

    try {
      const res = await fetch(widgetUrl, {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({messages: [{role:"user", content: msg}]})
      });
      const data = await res.json();
      if (data.reply) {
        appendMessage('assistant', data.reply);
      } else {
        appendMessage('assistant', data.error || "Unexpected response.");
      }
    } catch (e){
      appendMessage('assistant', "Error connecting.");
    } finally {
      sendBtn.disabled = false;
      input.focus();
    }
  }

  sendBtn.addEventListener("click", sendMessage);
  input.addEventListener("keypress", e => { if(e.key === "Enter") sendMessage(); });
})();
</script>
