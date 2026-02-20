<?php
require_once __DIR__ . '/../config/database.php';
// Redirect to new dashboard
header('Location: ' . BASE_URL . 'admin/dashboard-new.php');
exit();
