<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Support;

use FavoriteCMS\Core\Request;

final class CsrfGuard
{
    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            @session_start();
        }

        if (empty($_SESSION['_token'])) {
            if (function_exists('csrf_token')) {
                $_SESSION['_token'] = csrf_token();
            } else {
                $_SESSION['_token'] = bin2hex(random_bytes(32));
            }
        }

        return (string)$_SESSION['_token'];
    }

    public static function input(): string
    {
        $token = self::token();
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verify(Request $request): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            @session_start();
        }

        $submitted = (string)($request->post('_token', '') ?: $request->post('csrf_token', ''));
        if ($submitted === '') {
            $submitted = (string)($_POST['_token'] ?? $_POST['csrf_token'] ?? '');
        }
        if ($submitted === '') {
            $submitted = (string)$request->header('X-CSRF-TOKEN', '');
        }
        if ($submitted === '') {
            $submitted = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        }
        if ($submitted === '') {
            $rawBody = '';
            if (method_exists($request, 'getContent')) {
                $rawBody = (string)$request->getContent();
            } elseif (isset($GLOBALS['_test_raw_input'])) {
                $rawBody = (string)$GLOBALS['_test_raw_input'];
            } else {
                $rawBody = (string)@file_get_contents('php://input');
            }
            if ($rawBody !== '') {
                $json = json_decode($rawBody, true);
                if (is_array($json)) {
                    $submitted = (string)($json['_token'] ?? $json['csrf_token'] ?? '');
                }
            }
        }

        $sessionToken = (string)($_SESSION['_token'] ?? $_SESSION['csrf_token'] ?? '');
        if ($submitted === '' || $sessionToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $submitted);
    }
}

