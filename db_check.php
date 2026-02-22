<?php
require 'config/database.php';
$db = (new Database())->connect();

$stmt = $db->query("SELECT * FROM categories WHERE name LIKE '%Curtain%'");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$result = [
    'categories' => $categories
];

file_put_contents('db_out.json', json_encode($result, JSON_PRETTY_PRINT));
