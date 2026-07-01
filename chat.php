<?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$current_user = (int) $_SESSION["user_id"];
$role = $_SESSION["role"];
$receiver_id = null;
$property_id = 0;
$chatUserName = "";
$propertyTitle = "";

/* =========================
   SUPPORT CHAT MODE
========================= */
if (isset($_GET["support"])) {
    if ($role === "admin") {
        $receiver_id = isset($_GET["receiver_id"]) ? (int)$_GET["receiver_id"] : (isset($_GET["user_id"]) ? (int)$_GET["user_id"] : 0);
        if (!$receiver_id || $receiver_id === $current_user) die("Invalid user selection.");
        $stmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id=?");
        $stmt->execute([$receiver_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) die("User not found.");
        $chatUserName = $user["full_name"];
        $chatTitle = "Customer Support";
    } else {
        $stmt = $pdo->prepare("SELECT user_id, full_name FROM users WHERE role='admin' AND account_status = 'active' ORDER BY last_seen DESC LIMIT 1");
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$admin) die("Admin account not found.");
        $receiver_id = (int) $admin["user_id"];
        $chatUserName = $admin["full_name"];
        $chatTitle = "Customer Support";
    }
} else {
    if (!isset($_GET["property_id"])) { header("Location: index.php"); exit(); }
    $property_id = (int) $_GET["property_id"];
    $stmt = $pdo->prepare("SELECT p.title, u.user_id AS landlord_id FROM properties p JOIN users u ON p.landlord_id = u.user_id WHERE p.property_id = ?");
    $stmt->execute([$property_id]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$property) die("Property not found.");
    $landlord_id = (int) $property["landlord_id"];
    $propertyTitle = $property["title"];
    if ($role === "client") {
        $receiver_id = $landlord_id;
    } elseif ($role === "landlord") {
        if (isset($_GET["client_id"])) {
            $receiver_id = (int) $_GET["client_id"];
        } else {
            $stmt = $pdo->prepare("SELECT sender_id FROM messages WHERE property_id = ? AND sender_id != ? ORDER BY sent_at DESC LIMIT 1");
            $stmt->execute([$property_id, $current_user]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            $receiver_id = $client ? (int)$client["sender_id"] : $landlord_id;
        }
    }
    $userStmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $userStmt->execute([$receiver_id]);
    $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
    $chatUserName = $userData["full_name"] ?? "User";
    $chatTitle = "Property Inquiry";
}

if (!$receiver_id) die("Chat receiver not defined.");

// Mark messages as read
if ($property_id == 0) {
    $markRead = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE property_id = 0 AND receiver_id = ? AND sender_id = ?");
    $markRead->execute([$current_user, $receiver_id]);
} else {
    $markRead = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE property_id = ? AND receiver_id = ?");
    $markRead->execute([$property_id, $current_user]);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Chat - StaySphere</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .chat-box-container { height: 600px; display: flex; flex-direction: column; }
        .chat-messages { flex: 1; overflow-y: auto; background-image: radial-gradient(var(--color-border) 0.5px, transparent 0.5px); background-size: 20px 20px; }
        .typing-indicator { font-style: italic; font-size: 0.8rem; color: var(--color-text-secondary); height: 20px; }
    </style>
</head>
<body class="bg-white">

<?php include 'includes/navbar.php'; ?>

<div class="container" style="max-width: 1100px; margin: 0 auto; padding: 0 20px;">
    <h1 class="page-title mb-lg" style="font-size: 1.875rem;">Live Chat - <?= htmlspecialchars($chatTitle) ?></h1>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-lg">
        <div class="lg:col-span-1">
            <div class="card mb-lg sticky top-lg">
                <div class="card-header"><h5>Conversation</h5></div>
                <div class="card-body">
                    <div class="mb-lg">
                        <label class="text-tiny uppercase tracking-wider text-muted mb-xs">Recipient</label>
                        <div class="flex items-center gap-md">
                            <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold"><?= substr($chatUserName, 0, 1) ?></div>
                            <div class="font-bold"><?= htmlspecialchars($chatUserName) ?></div>
                        </div>
                    </div>
                    <?php if($propertyTitle): ?>
                    <div class="mb-lg">
                        <label class="text-tiny uppercase tracking-wider text-muted mb-xs">Property</label>
                        <a href="property_details.php?id=<?= $property_id ?>" class="font-bold text-primary block"><?= htmlspecialchars($propertyTitle) ?></a>
                    </div>
                    <?php endif; ?>
                    <div class="p-md bg-alt rounded-md border">
                        <p class="text-tiny mb-0 text-secondary">StaySphere Encrypted Chat. Keep all transactions within the platform for your safety.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-3">
            <div class="card chat-box-container shadow-lg">
                <div class="card-header bg-primary text-white py-md flex justify-between items-center">
                    <h6 class="mb-0 text-white"><?= htmlspecialchars($chatUserName) ?></h6>
                    <div id="online-status" class="text-tiny px-sm py-xs bg-white text-primary rounded-full font-bold">Offline</div>
                </div>
                
                <div id="chat-box" class="chat-messages p-lg bg-alt">
                    <div class="text-center py-2xl"><div class="spinner mx-auto"></div><p class="text-muted mt-md">Synchronizing messages...</p></div>
                </div>

                <div class="p-md bg-white border-t">
                    <div id="typing-indicator" class="typing-indicator mb-xs px-sm"></div>
                    <form id="chat-form" class="flex gap-md">
                        <input type="text" id="message" class="form-control" placeholder="Type a message..." autocomplete="off" required>
                        <button type="submit" class="btn btn-primary px-xl">Send</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const propertyId = <?= $property_id ?>;
const receiverId = <?= $receiver_id ?>;
const chatBox = document.getElementById("chat-box");
const messageInput = document.getElementById("message");
const chatForm = document.getElementById("chat-form");
const typingIndicator = document.getElementById("typing-indicator");

function loadMessages(){
    fetch(`fetch_messages.php?property_id=${propertyId}&receiver_id=${receiverId}`)
    .then(res => res.text())
    .then(data => {
        const isAtBottom = chatBox.scrollHeight - chatBox.scrollTop <= chatBox.clientHeight + 100;
        chatBox.innerHTML = data;
        if (isAtBottom) chatBox.scrollTop = chatBox.scrollHeight;
    });
}

function checkTyping() {
    fetch(`check_typing.php?property_id=${propertyId}&receiver_id=${receiverId}`)
    .then(res => res.text())
    .then(data => {
        typingIndicator.innerText = (data === "typing") ? "<?= htmlspecialchars($chatUserName) ?> is typing..." : "";
    });
}

function checkOnlineStatus() {
    fetch(`check_online_status.php?user_id=${receiverId}`)
    .then(res => res.text())
    .then(status => {
        const statusEl = document.getElementById("online-status");
        if (!statusEl) return;
        if (status.trim() === "online") {
            statusEl.textContent = "Online";
            statusEl.classList.remove("bg-white", "text-primary");
            statusEl.classList.add("bg-success", "text-white");
        } else {
            statusEl.textContent = "Offline";
            statusEl.classList.remove("bg-success", "text-white");
            statusEl.classList.add("bg-white", "text-primary");
        }
    })
    .catch(() => {
        const statusEl = document.getElementById("online-status");
        if (statusEl) {
            statusEl.textContent = "Offline";
            statusEl.classList.remove("bg-success", "text-white");
            statusEl.classList.add("bg-white", "text-primary");
        }
    });
}

let typingTimer;
messageInput.addEventListener("input", () => {
    fetch("update_typing.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `property_id=${propertyId}&is_typing=1`
    });
    clearTimeout(typingTimer);
    typingTimer = setTimeout(() => {
        fetch("update_typing.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `property_id=${propertyId}&is_typing=0`
        });
    }, 3000);
});

chatForm.addEventListener("submit", (e) => {
    e.preventDefault();
    const msg = messageInput.value.trim();
    if(!msg) return;
    fetch("send_message.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ property_id: propertyId, receiver_id: receiverId, message: msg })
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === "success") {
            messageInput.value = "";
            loadMessages();
            setTimeout(() => chatBox.scrollTop = chatBox.scrollHeight, 50);
        }
    });
});

setInterval(loadMessages, 3000);
setInterval(checkTyping, 2000);
setInterval(checkOnlineStatus, 5000);
loadMessages();
checkOnlineStatus();
</script>
</body>
</html>
