<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

session_unset();
session_destroy();

redirect(BASE_URL . 'index.php');

