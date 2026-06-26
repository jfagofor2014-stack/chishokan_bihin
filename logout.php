<?php
require_once __DIR__ . '/auth.php';
start_session();
session_unset();
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');
header('Location: index.php');
exit;
