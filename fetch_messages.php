<?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION["user_id"])) {
    exit();
}

$current_user = (int) $_SESSION["user_id"];
$property_id = isset($_GET["property_id"]) ? (int) $_GET["property_id"] : 0;
$receiver_id = isset($_GET["receiver_id"]) ? (int) $_GET["receiver_id"] : 0;

if ($receiver_id <= 0) {
    echo "<div class='text-center py-lg text-danger'>Conversation not found.</div>";
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT m.message, m.sender_id, m.sent_at, u.full_name
        FROM messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE m.property_id = ?
        AND (
              (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = ?)
        )
        ORDER BY m.sent_at ASC
    ");

    $stmt->execute([
        $property_id,
        $current_user,
        $receiver_id,
        $receiver_id,
        $current_user
    ]);

    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$messages) {
        echo "<div class='text-center py-2xl text-muted'>
                <div style='font-size: 2rem; margin-bottom: 1rem;'>💬</div>
                <p>No messages yet. Start the conversation!</p>
              </div>";
        exit();
    }

    foreach ($messages as $msg) {
        $isMine = ($msg["sender_id"] == $current_user);
        $messageClass = $isMine ? "chat-message sent" : "chat-message received";
        $bubbleClass = $isMine ? "bg-primary text-white" : "bg-alt text-primary";
        $timeAlign = $isMine ? "text-right" : "text-left";
        
        echo "<div class='{$messageClass} mb-lg gap-md items-start animate-fade-in'>";
        
        // Avatar Placeholder
        echo "<div class='chat-avatar flex-shrink-0 w-10 h-10 rounded-full bg-dark flex items-center justify-center text-white font-bold text-tiny' style='width: 32px; height: 32px;'>";
        echo strtoupper(substr($msg["full_name"], 0, 1));
        echo "</div>";
        
        // Message Content
        echo "<div class='chat-content max-w-xs md:max-w-md'>";
        echo "<div class='chat-bubble p-md rounded-lg shadow-sm {$bubbleClass}' style='border-radius: ".($isMine ? "18px 18px 4px 18px" : "18px 18px 18px 4px").";'>";
        echo "<p class='mb-0' style='color: inherit;'>".nl2br(htmlspecialchars($msg["message"]))."</p>";
        echo "</div>";
        echo "<div class='mt-xs px-xs {$timeAlign}'>";
        echo "<span class='text-tiny text-muted'>".date("H:i", strtotime($msg["sent_at"]))."</span>";
        echo "</div>";
        echo "</div>";
        
        echo "</div>";
    }

} catch (PDOException $e) {
    echo "<div class='text-center py-lg text-danger'>Unable to load messages.</div>";
}
?>
