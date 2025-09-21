<?php
// ✅ Start session safely with proper cookie settings
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,       // session cookie (expires when browser closes)
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']), // only send cookie over HTTPS if available
        'httponly' => true,    // JS cannot access cookie
        'samesite' => 'Lax'    // prevent CSRF on cross-site requests
    ]);
    session_start();
}

// ✅ Require login for protected pages
function require_login() {
    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
        exit;
    }
}

// ✅ Get current logged in user
function current_user() {
    return $_SESSION['user'] ?? null;
}

// ✅ Login user (store in session)
function login_user($user) {
    $_SESSION['user'] = $user;
}

// ✅ Logout user
function logout_user() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
