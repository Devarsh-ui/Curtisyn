<?php

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/database.php';

function requireAuth() {
    if (!isLoggedIn()) {
        redirect(BASE_URL . 'login.php');
    }
}

function requireRole($role) {
    requireAuth();
    if (!hasRole($role)) {
        redirect(BASE_URL . 'index.php');
    }
}

function requireAdmin() {
    requireRole('admin');
}

function requireEmployee() {
    requireAuth();
    $role = getUserRole();
    if ($role !== 'employee' && $role !== 'admin') {
        redirect(BASE_URL . 'index.php');
    }
}

function requireSupplier() {
    requireRole('supplier');
}

function requireCustomer() {
    requireAuth();
    if (getUserRole() !== 'customer') {
        redirect(BASE_URL . 'index.php');
    }
}

function logout() {
    session_unset();
    session_destroy();
    redirect(BASE_URL . 'index.php');
}
