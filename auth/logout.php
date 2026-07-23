<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/session.php';

$_SESSION = [];
session_destroy();

header('Location: ' . BASE_URL . '/pages/login.php');
exit;
