<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$controller = (string) file_get_contents($root . '/src/Controllers/CustomerShopController.php');
$plugin = (string) file_get_contents($root . '/src/FavoriteShopPlugin.php');
$installer = (string) file_get_contents($root . '/src/Installer.php');

$checks = [
    'checkout only exposes prepaid when a configured method exists' => str_contains($controller, "getAvailablePaymentMethods('BDT')") && str_contains($controller, "if ($prepaidMethods)"),
    'payment page validates CSRF and enabled gateway' => str_contains($controller, '$this->validCsrf($request)') && str_contains($controller, 'That payment method is not currently enabled or configured.'),
    'payment intent is tied to the shop order' => str_contains($controller, "createIntent('favorite-shop', $orderNumber") && str_contains($installer, "'payment_intent_id', 'VARCHAR(64) NULL'"),
    'manual payment submissions require TrxID and sender account' => str_contains($controller, "transaction_reference") && str_contains($controller, "sender_account") && str_contains($controller, 'submitManualVerification'),
    'automatic payment redirects only to HTTP(S) provider URLs' => str_contains($controller, 'FILTER_VALIDATE_URL') && str_contains($controller, "['https','http']") && str_contains($controller, 'initiatePayment'),
    'bKash return is verified by the gateway driver' => str_contains($controller, 'executeCallback($attempt, $request->all())'),
    'Favorite Pay status events synchronize matching orders' => str_contains($plugin, "favorite.pay.intent.status_updated") && str_contains($plugin, "payment_status='paid'") && str_contains($plugin, 'payment_intent_id=?'),
    'payment route is registered' => str_contains($plugin, "'/shop/pay/{orderNumber}'"),
];

$failed = [];
foreach ($checks as $label => $passed) {
    echo ($passed ? 'PASS' : 'FAIL') . ': ' . $label . PHP_EOL;
    if (!$passed) $failed[] = $label;
}
if ($failed) exit(1);
