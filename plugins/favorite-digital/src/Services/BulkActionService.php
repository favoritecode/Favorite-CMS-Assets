<?php

declare(strict_types=1);

namespace FavoriteCMS\Digital\Services;

use FavoriteCMS\Core\Request;
use FavoriteCMS\Core\Response;
use FavoriteCMS\Models\User;
use Throwable;

class BulkActionService
{
    /**
     * Process a bulk action request safely across an array of record IDs.
     *
     * @param Request $request
     * @param array<string, callable(int): void> $actionHandlers Map of allowed action name => callback(int $id)
     * @param string $defaultRedirect Fallback redirect path if none provided
     * @param array<string, string> $actionLabels Optional human-readable labels for actions
     * @param string $requiredCapability Capability required to execute bulk action (defaults to 'manage_options')
     * @return Response
     */
    public function handle(
        Request $request,
        array $actionHandlers,
        string $defaultRedirect,
        array $actionLabels = [],
        string $requiredCapability = 'manage_options'
    ): Response {
        $redirectUrl = $this->resolveRedirectUrl($request, $defaultRedirect);

        // 1. Enforce POST only
        if ($request->method() !== 'POST') {
            $_SESSION['flash_error'] = 'Invalid request method. Bulk actions require POST.';
            return Response::redirect($redirectUrl);
        }

        // 2. Validate CSRF token
        if (!$this->validateCsrf($request)) {
            $_SESSION['flash_error'] = 'Security token expired or invalid (CSRF failure). Please try again.';
            return Response::redirect($redirectUrl);
        }

        // 3. Authenticate user
        $userId = (int)($_SESSION['auth_user_id'] ?? 0);
        if ($userId <= 0 && !isset($GLOBALS['_test_current_user'])) {
            return Response::redirect('/admin/login');
        }

        $currentUser = $this->resolveCurrentUser($userId);
        if ($currentUser && method_exists($currentUser, 'isActive') && !$currentUser->isActive()) {
            $_SESSION['flash_error'] = 'Your account is inactive or suspended.';
            return Response::redirect('/admin/login');
        }

        // 4. Authorize capability
        if (!$this->authorizeUser($currentUser, $requiredCapability)) {
            $_SESSION['flash_error'] = 'You do not have permission to perform bulk actions.';
            return Response::redirect($redirectUrl);
        }

        // 5. Validate and allowlist action
        $action = trim((string)$request->post('bulk_action', ''));
        if ($action === '' || !isset($actionHandlers[$action])) {
            $_SESSION['flash_error'] = 'Please select a valid bulk action from the dropdown.';
            return Response::redirect($redirectUrl);
        }

        // 6. Sanitize and validate record IDs
        $ids = $this->sanitizeIds($request->post('ids'));
        if (empty($ids)) {
            $_SESSION['flash_error'] = 'No items were selected for the bulk action.';
            return Response::redirect($redirectUrl);
        }

        // 7. Execute bulk action per item with atomic error catching
        $handler = $actionHandlers[$action];
        $actionLabel = $actionLabels[$action] ?? ucfirst(str_replace('_', ' ', $action));

        $successCount = 0;
        $failedCount  = 0;
        $errors       = [];

        foreach ($ids as $id) {
            try {
                $handler($id);
                $successCount++;
            } catch (Throwable $e) {
                $failedCount++;
                $errors[] = "#{$id}: " . $e->getMessage();
            }
        }

        // 8. Set accurate, informative flash notices
        if ($successCount > 0 && $failedCount === 0) {
            $_SESSION['flash_success'] = "{$actionLabel} completed successfully for {$successCount} item(s).";
        } elseif ($successCount > 0 && $failedCount > 0) {
            $_SESSION['flash_success'] = "{$actionLabel} applied to {$successCount} item(s).";
            $errorSummary = implode('; ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $errorSummary .= '; and ' . (count($errors) - 5) . ' more error(s)';
            }
            $_SESSION['flash_error'] = "{$failedCount} item(s) failed: {$errorSummary}";
        } else {
            $errorSummary = implode('; ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $errorSummary .= '; and ' . (count($errors) - 5) . ' more error(s)';
            }
            $_SESSION['flash_error'] = "Bulk {$actionLabel} failed: {$errorSummary}";
        }

        return Response::redirect($redirectUrl);
    }

    /**
     * Sanitize input IDs into an array of positive integers.
     *
     * @param mixed $raw
     * @return int[]
     */
    public function sanitizeIds(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = explode(',', $raw);
        }

        if (!is_array($raw)) {
            return [];
        }

        $clean = [];
        foreach ($raw as $val) {
            $id = (int)$val;
            if ($id > 0) {
                $clean[] = $id;
            }
        }

        return array_values(array_unique($clean));
    }

    protected function validateCsrf(Request $request): bool
    {
        $submittedToken = (string)$request->post('_token', '');
        $sessionToken   = (string)($_SESSION['_token'] ?? '');

        if ($submittedToken === '' || $sessionToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $submittedToken);
    }

    protected function authorizeUser(?object $currentUser, string $capability = 'manage_options'): bool
    {
        if ($currentUser && method_exists($currentUser, 'can')) {
            return $currentUser->can($capability);
        }

        if (function_exists('current_user_can')) {
            try {
                return current_user_can($capability);
            } catch (Throwable) {
                return false;
            }
        }

        return false;
    }

    protected function resolveCurrentUser(int $userId): ?object
    {
        if (isset($GLOBALS['_test_current_user']) && is_object($GLOBALS['_test_current_user'])) {
            return $GLOBALS['_test_current_user'];
        }

        if ($userId > 0 && class_exists(User::class)) {
            try {
                return User::find($userId);
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }

    protected function resolveRedirectUrl(Request $request, string $default): string
    {
        $redirect = trim((string)$request->post('redirect_to', ''));
        if ($redirect === '') {
            $server = $request->server();
            $redirect = trim((string)($server['HTTP_REFERER'] ?? ''));
        }

        // Only allow local admin paths for safe redirection
        if ($redirect !== '' && str_starts_with($redirect, '/admin/page/')) {
            return $redirect;
        }

        return $default;
    }
}

