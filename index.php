<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth/session.php';

header('Location: ' . BASE_URL . '/pages/' . (is_logged_in() ? 'dashboard/index.php' : 'login/index.php'));
exit;
