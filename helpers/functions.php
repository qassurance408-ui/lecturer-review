<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }   

    function redirect($location) {
        header("Location: " . $location);
        exit();
    }

    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    function requireLogin() {
        if (!isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            redirect('login.php');
        }
    }

    function getFlashMessage() {
        if (isset($_SESSION['message'])) {
            $message = $_SESSION['message'];
            $type = $_SESSION['message_type'] ?? 'info';
            unset($_SESSION['message'], $_SESSION['message_type']);
            return "<div class='alert alert-{$type}'>" . htmlspecialchars($message) . "</div>";
        }
        return '';
    }
    
    function setFlashMessage($message, $type = 'info') {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
    }
?>