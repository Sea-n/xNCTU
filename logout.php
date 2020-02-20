<?php
session_start();

$_SESSION = [];

if (ini_get("session.use_cookies")) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 9487,
		$params["path"], $params["domain"],
		$params["secure"], $params["httponly"]
	);
}

session_destroy();

$uri = $_GET['r'] ?? '/';
if (!preg_match('#^/[a-z]*(\?[a-z0-9=&]*)?$#i', $uri))
	$uri = '/';
header("Location: $uri");
?>
Logout success.
