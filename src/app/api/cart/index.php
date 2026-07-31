<?php
header('Content-Type: application/json');
header('Content-Type: application/json'); 
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0"); 
header("Cache-Control: post-check=0, pre-check=0", false); 
header("Pragma: no-cache");
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../lib/Database.php';
require_once __DIR__ . '/../../../lib/Media.php';

$userId = $_SESSION["user_id"] ?? 1;
$db = new Database();

function getTotals($db, $userId) {
    $row = $db->query("SELECT COUNT(*) AS items, SUM(g.price) AS price, SUM(g.price * g.discount / 100) AS discount FROM cart c JOIN game g ON g.id = c.game_id WHERE c.user_id = ?", [$userId])->fetch();
    $price = (float)$row["price"];
    $discount = (float)$row["discount"];
    return ["items" => (int)$row["items"], "price" => round($price, 2), "discount" => round($discount, 2), "subtotal" => round($price - $discount, 2)];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);
    $action = $input["action"] ?? "add";
    $gameId = (int)($input["game_id"] ?? 0);

    if ($action === "add") {
        $db->query("DELETE FROM cart WHERE user_id = ? AND game_id = ?", [$userId, $gameId]);
        $db->query("INSERT INTO cart (user_id, game_id) VALUES (?, ?)", [$userId, $gameId]);
    } else if ($action === "remove") {
        $db->query("DELETE FROM cart WHERE user_id = ? AND game_id = ?", [$userId, $gameId]);
    } else if ($action === "move-to-wishlist") {
        $db->query("DELETE FROM wishlist WHERE user_id = ? AND game_id = ?", [$userId, $gameId]);
        $db->query("INSERT INTO wishlist (user_id, game_id) VALUES (?, ?)", [$userId, $gameId]);
        $db->query("DELETE FROM cart WHERE user_id = ? AND game_id = ?", [$userId, $gameId]);
    } else if ($action === "checkout") {
        $paid = getTotals($db, $userId);
        $db->query("DELETE FROM wishlist WHERE user_id = ? AND game_id IN (SELECT game_id FROM cart WHERE user_id = ?)", [$userId, $userId]);
        $db->query("DELETE FROM cart WHERE user_id = ?", [$userId]);
        echo json_encode(["status" => "ok", "paid" => $paid["subtotal"], "bought" => $paid["items"]]);
        exit;
    }
    echo json_encode(["status" => "ok"]);
    exit;
}

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
// Read the limit from the URL, default to 12 if missing, cap at 50 for security
$limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 50) : 12;
$rows = $db->query("SELECT g.id, g.title, g.price, g.discount, g.image_path, g.fallback_art FROM cart c JOIN game g ON g.id = c.game_id WHERE c.user_id = ? ORDER BY c.added_at DESC LIMIT $limit OFFSET $offset", [$userId])->fetchAll();
if (!$rows) { http_response_code(204); exit; }

$items = [];
foreach ($rows as $row) {
    $gameId = (int)$row["id"];
    $categories = $db->query("SELECT c.name FROM category c JOIN game_category gc ON c.id = gc.category_id WHERE gc.game_id = ?", [$gameId])->fetchAll();
    $platforms = $db->query("SELECT p.name FROM platform p JOIN game_platform gp ON p.id = gp.platform_id WHERE gp.game_id = ?", [$gameId])->fetchAll();
    $price = (float)$row["price"];
    $discount = (int)$row["discount"];
    $items[] = [
        "id" => $gameId,
        "title" => $row["title"],
        "cover" => $row["image_path"] ? Media::url($row["image_path"]) : "",
        "fallback_art" => $row["fallback_art"],
        "categories" => array_column($categories, "name"),
        "platforms" => array_map("strtolower", array_column($platforms, "name")),
        "price" => round($price, 2),
        "discount" => $discount,
        "final_price" => round($price * (100 - $discount) / 100, 2)
    ];
}
echo json_encode(["items" => $items, "totals" => getTotals($db, $userId)]);
?>