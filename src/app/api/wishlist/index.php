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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);
    $action = $input["action"] ?? "add";
    $gameId = (int)($input["game_id"] ?? 0);

    if ($action === "add") {
        $db->query("DELETE FROM wishlist WHERE user_id = ? AND game_id = ?", [$userId, $gameId]);
        $db->query("INSERT INTO wishlist (user_id, game_id) VALUES (?, ?)", [$userId, $gameId]);
    } else if ($action === "remove") {
        $db->query("DELETE FROM wishlist WHERE user_id = ? AND game_id = ?", [$userId, $gameId]);
    } else if ($action === "add-to-cart") {
        $db->query("DELETE FROM cart WHERE user_id = ? AND game_id = ?", [$userId, $gameId]);
        $db->query("INSERT INTO cart (user_id, game_id) VALUES (?, ?)", [$userId, $gameId]);
    }
    echo json_encode(["status" => "ok"]);
    exit;
}

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
// Read the limit from the URL, default to 12 if missing, cap at 50 for security
$limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 50) : 12;
$rows = $db->query("SELECT g.id, g.title, g.price, g.discount, g.image_path, g.fallback_art, g.release_date FROM wishlist w JOIN game g ON g.id = w.game_id WHERE w.user_id = ? ORDER BY w.added_at DESC LIMIT $limit OFFSET $offset", [$userId])->fetchAll();

$items = [];
foreach ($rows as $row) {
    $gameId = (int)$row["id"];
    $categories = $db->query("SELECT c.name FROM category c JOIN game_category gc ON c.id = gc.category_id WHERE gc.game_id = ?", [$gameId])->fetchAll();
    $platforms = $db->query("SELECT p.name FROM platform p JOIN game_platform gp ON p.id = gp.platform_id WHERE gp.game_id = ?", [$gameId])->fetchAll();
    $review = $db->query("SELECT COUNT(*) AS total, SUM(enjoy) AS good FROM review WHERE game_id = ?", [$gameId])->fetch();
    $status = 1;
    if ($review["total"] > 0) {
        $percent = $review["good"] / $review["total"];
        if ($percent >= 0.7) $status = 2;
        else if ($percent < 0.4) $status = 0;
    }
    $inCart = $db->query("SELECT COUNT(*) AS total FROM cart WHERE user_id = ? AND game_id = ?", [$userId, $gameId])->fetch()["total"] > 0;
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
        "final_price" => round($price * (100 - $discount) / 100, 2),
        "review_status" => $status,
        "release_date" => $row["release_date"] ? date("d M Y", strtotime($row["release_date"])) : "TBA",
        "in_cart" => $inCart
    ];
}
echo json_encode(["items" => $items]);
?>