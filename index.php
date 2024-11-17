<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Order;
use App\OrderService;
use App\OrderWorkflow;
use App\OrderWorkflowListener;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

$dispatcher = new EventDispatcher();
$listener = new OrderWorkflowListener();
$dispatcher->addListener('workflow.order_processing_workflow.leave', [$listener, 'onLeave']);
// create basket -> like add items to basket
$order = new Order(1);
$orderWorkflow = OrderWorkflow::getWorkflow($dispatcher);
$orderService = new OrderService($orderWorkflow, $order);

// add item will not change state
// transition: update_basket
// state change from `open` to `open`
$orderService->addItem(
    $orderService->getItems() + [
    ['product' => 1234, 'description' => 'soya milk - 250ml', 'qty' => 1],
    ['product' => 1235, 'description' => 'soya milk - 1kg', 'qty' => 2],
]);
// state change from `open` to `await for supplier`
// supplier needs to accept
$orderService->submitBasket();
$changedItems = $orderService->getItems();
foreach($changedItems as $item) {
    if ($item['product'] == 1236) {
        $item['qty'] = 0;
    }
}
$changedItems[] = [
    'product' => 1236, 
    'description' => 'Skimmed milk - 250ml',
    'qty' => 3,
    'addedBySupplier' => true,
];
$changedItems[] = [
    'product' => 1237, 
    'description' => 'Skimmed milk - 250ml',
    'qty' => 4,
    'addedBySupplier' => true,
];
$orderService->supplierPendingBasket(
    OrderWorkflow::TRANSITION_PENDING_ORDER, // supplier click accept button
    $changedItems, // supplier change ordered items when accept the order 
);

// set new order
$secondOrder = new Order(2);
$orderService->setOrder($secondOrder);
// submit new basket, should change to basket quarintined (reason: no items in basket)
$orderService->submitBasket(); 
$orderService->resubmitOrder([
    ['product' => 1234, 'description' => 'soya milk - 250ml', 'qty' => 1],
    ['product' => 1235, 'description' => 'soya milk - 1kg', 'qty' => 2],
]);

$orderService->supplierPendingBasket(OrderWorkflow::TRANSITION_PENDING_ORDER_REJECTED);