<?php 
session_start();
require_once "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

/* =========================
   FETCH CONVERSATIONS
========================= */

$stmt = $pdo->prepare("
SELECT 
    property_id,
    CASE 
        WHEN sender_id = ? THEN receiver_id
        ELSE sender_id
    END AS other_user,
    MAX(sent_at) AS last_message_time
FROM messages
WHERE (sender_id = ? OR receiver_id = ?)
AND (? != 'admin' OR property_id = 0)
GROUP BY property_id, other_user
ORDER BY last_message_time DESC
");

$stmt->execute([$user_id, $user_id, $user_id, $_SESSION["role"]]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title >Messages - StaySphere</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-white">

<?php include 'includes/navbar.php'; ?>

<div class="container" style="max-width: 1280px; margin: 0 auto; padding: 0 20px;">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h1 class="page-title" style="font-size: 1.875rem;"><?php echo ($_SESSION["role"] === "admin") ? "Support Inbox" : "Messages"; ?></h1>
            <p class="text-muted">Manage your ongoing conversations and inquiries.</p>
        </div>
        <div class="page-header-actions" style="display: flex; gap: 10px; align-items: center;">
            <input type="text" id="searchInput" placeholder="Search by property or name..." class="form-control" style="margin-left: auto; width: 250px; padding: 8px 12px; border-radius: 4px; border: 1px solid #ddd;">
            <button onclick="history.back()" class="btn btn-secondary">← Back</button>
            <?php if ($_SESSION["role"] == "client"): ?>
                <a href="client/dashboard.php" class="btn btn-primary">Dashboard</a>
            <?php elseif ($_SESSION["role"] == "landlord"): ?>
                <a href="landlord/dashboard.php" class="btn btn-primary">Dashboard</a>
            <?php elseif ($_SESSION["role"] == "admin"): ?>
                <a href="admin/dashboard.php" class="btn btn-dark">Admin Panel</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-lg">
        <div class="card-header bg-alt">
            <h5 class="mb-0">Recent Conversations</h5>
        </div>
        <div class="card-body p-0">
            <?php if ($conversations): ?>
                <div class="conversation-list">
                    <?php foreach ($conversations as $conv): ?>
                        <?php
                        $other_user = $conv["other_user"];
                        $unreadCount = 0;
                        
                        if ($conv["property_id"] == 0) {
                            $title = "Customer Support";
                            $link = "chat.php?support=1&receiver_id=".$other_user;
                            
                            $previewStmt = $pdo->prepare("SELECT message FROM messages WHERE property_id = 0 AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) ORDER BY sent_at DESC LIMIT 1");
                            $previewStmt->execute([$user_id, $other_user, $other_user, $user_id]);
                            $lastMessage = $previewStmt->fetch(PDO::FETCH_ASSOC);
                            
                            $unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE property_id = 0 AND sender_id = ? AND receiver_id = ? AND is_read = 0");
                            $unreadStmt->execute([$other_user, $user_id]);
                            $unreadCount = $unreadStmt->fetchColumn();
                        } else {
                            $propStmt = $pdo->prepare("SELECT title FROM properties WHERE property_id = ?");
                            $propStmt->execute([$conv["property_id"]]);
                            $property = $propStmt->fetch(PDO::FETCH_ASSOC);
                            $title = $property["title"] ?? "Property";
                            $link = "chat.php?property_id=".$conv["property_id"]."&client_id=".$other_user;
                            
                            $previewStmt = $pdo->prepare("SELECT message FROM messages WHERE property_id = ? AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) ORDER BY sent_at DESC LIMIT 1");
                            $previewStmt->execute([$conv["property_id"], $user_id, $other_user, $other_user, $user_id]);
                            $lastMessage = $previewStmt->fetch(PDO::FETCH_ASSOC);
                            
                            $unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE property_id = ? AND sender_id = ? AND receiver_id = ? AND is_read = 0");
                            $unreadStmt->execute([$conv["property_id"], $other_user, $user_id]);
                            $unreadCount = $unreadStmt->fetchColumn();
                        }

                        $userStmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
                        $userStmt->execute([$other_user]);
                        $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
                        $otherUserName = $userData["full_name"] ?? "User";
                        ?>

                        <a href="<?= $link ?>" class="flex items-center p-lg border-b hover:bg-alt transition-colors no-underline text-inherit">
                            <div class="w-12 h-12 bg-primary text-white rounded-full flex items-center justify-center font-bold mr-lg flex-shrink-0">
                                <?= substr($otherUserName, 0, 1) ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-center mb-xs">
                                    <h6 class="mb-0 font-bold truncate pr-lg"><?= htmlspecialchars($title) ?></h6>
                                    <small class="text-muted whitespace-nowrap"><?= date("M j, g:i a", strtotime($conv["last_message_time"])) ?></small>
                                </div>
                                <div class="flex justify-between items-center">
                                    <div class="truncate">
                                        <span class="text-tiny text-primary font-bold"><?= htmlspecialchars($otherUserName) ?>:</span>
                                        <span class="text-tiny text-muted"><?= htmlspecialchars(substr($lastMessage["message"] ?? "No messages yet", 0, 80)) ?><?= strlen($lastMessage["message"] ?? "") > 80 ? "..." : "" ?></span>
                                    </div>
                                    <?php if ($unreadCount > 0): ?>
                                        <span class="badge bg-danger ml-md"><?= $unreadCount ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-2xl">
                    <p class="text-muted mb-lg">No conversations found.</p>
                    <a href="index.php" class="btn btn-outline">Start Browsing</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const conversations = document.querySelectorAll('.conversation-list a');
    
    conversations.forEach(conv => {
        const propertyTitle = conv.querySelector('h6').textContent.toLowerCase();
        const userName = conv.querySelector('.text-primary.font-bold').textContent.toLowerCase();
        
        if (propertyTitle.includes(searchTerm) || userName.includes(searchTerm)) {
            conv.style.display = '';
        } else {
            conv.style.display = 'none';
        }
    });
    
    // Show "no results" message if all conversations are hidden
    const conversationList = document.querySelector('.conversation-list');
    const visibleConversations = Array.from(conversations).filter(conv => conv.style.display !== 'none').length;
    
    if (conversationList && visibleConversations === 0 && searchTerm.length > 0) {
        if (!document.getElementById('noSearchResults')) {
            const noResultsDiv = document.createElement('div');
            noResultsDiv.id = 'noSearchResults';
            noResultsDiv.className = 'text-center py-2xl';
            noResultsDiv.innerHTML = '<p class="text-muted">No conversations match your search.</p>';
            conversationList.appendChild(noResultsDiv);
        }
    } else {
        const noResultsDiv = document.getElementById('noSearchResults');
        if (noResultsDiv) noResultsDiv.remove();
    }
});

// Auto-refresh the inbox
setInterval(function() {
    const searchInput = document.getElementById('searchInput');
    const currentSearchTerm = searchInput ? searchInput.value : '';
    
    fetch(window.location.href)
    .then(response => response.text())
    .then(html => {
        let parser = new DOMParser();
        let doc = parser.parseFromString(html, "text/html");
        let newList = doc.querySelector(".conversation-list");
        let currentList = document.querySelector(".conversation-list");
        if(newList && currentList){
            currentList.innerHTML = newList.innerHTML;
            
            // Reapply search filter if there's an active search
            if(currentSearchTerm.length > 0 && searchInput) {
                searchInput.value = currentSearchTerm;
                const conversations = currentList.querySelectorAll('a');
                
                conversations.forEach(conv => {
                    const propertyTitle = conv.querySelector('h6').textContent.toLowerCase();
                    const userName = conv.querySelector('.text-primary.font-bold').textContent.toLowerCase();
                    
                    if (propertyTitle.includes(currentSearchTerm.toLowerCase()) || userName.includes(currentSearchTerm.toLowerCase())) {
                        conv.style.display = '';
                    } else {
                        conv.style.display = 'none';
                    }
                });
                
                // Show "no results" message if all conversations are hidden
                const visibleConversations = Array.from(conversations).filter(conv => conv.style.display !== 'none').length;
                
                if (visibleConversations === 0) {
                    if (!document.getElementById('noSearchResults')) {
                        const noResultsDiv = document.createElement('div');
                        noResultsDiv.id = 'noSearchResults';
                        noResultsDiv.className = 'text-center py-2xl';
                        noResultsDiv.innerHTML = '<p class="text-muted">No conversations match your search.</p>';
                        currentList.appendChild(noResultsDiv);
                    }
                } else {
                    const noResultsDiv = document.getElementById('noSearchResults');
                    if (noResultsDiv) noResultsDiv.remove();
                }
            }
        }
    });
}, 5000);
</script>

</body>
</html>
