<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$orders = \App\Model\Order::where('payment_method', 'square')
    ->where('order_type', 'pos')
    ->get(['id', 'created_at', 'order_amount', 'user_id']);

echo json_encode([
    'pos_orders_count' => $orders->count(),
    'orders' => $orders->toArray()
]);
