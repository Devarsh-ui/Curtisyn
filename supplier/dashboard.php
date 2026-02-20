<?php
require_once __DIR__ . '/../config/database.php';
// Redirect to new dashboard
header('Location: ' . BASE_URL . 'supplier/dashboard-new.php');
exit();
