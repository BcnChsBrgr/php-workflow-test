<?php

namespace App;

use Symfony\Component\Workflow\Workflow;

final class OrderService 
{
    public function __construct(
        private readonly Workflow $orderWorkflow,
        private Order $order,
        private array $message = [],
    ) {
    }

    public function setOrder(Order $order): void 
    {
        $this->order = $order;
    }

    public function addItem(array $products): Order
    {
        $changedOrder = $this->order;
        $changedOrder->setProducts($products);
        return $this->transition($changedOrder, OrderWorkflow::TRANSITION_BASKET_UPDATE);
    }

    public function getItems(): array
    {
        return $this->order->getProducts();
    }

    public function submitBasket(): Order 
    {
        try {
            $changeOrder = $this->order;

            if ($this->canSubmitBasket($changeOrder)) {
                // copy order to final order table
                // copying order lines to final order lines
                $this->transition($changeOrder, OrderWorkflow::TRANSITION_BASKET_SUBMIT);
            } else {
                $this->transition($changeOrder, OrderWorkflow::TRANSITION_BASKET_SUBMIT_FAILED); 
            }
            
            return $this->order;
        } catch (Throwable $e) {
            // performing db rollback
            return $this->order;
        }
    }

    public function resubmitOrder(array $items): Order 
    {
        try {
            $changedOrder = $this->order;
            $changedOrder->setProducts($items);
            $this->canSubmitBasket($changedOrder, OrderWorkflow::TRANSITION_BASKET_RESUBMIT);
            $this->transition($changedOrder, OrderWorkflow::TRANSITION_BASKET_RESUBMIT);
        } catch (Throwable $e) {
            return $this->order;
        }

        return $this->order;
    }

    public function supplierPendingBasket(string $action, array $products = []): Order 
    {
        try {
            $changedOrder = $this->order;
            if ($action === OrderWorkflow::TRANSITION_PENDING_ORDER && $this->orderWorkflow->can($changedOrder, OrderWorkflow::TRANSITION_PENDING_ORDER)) {
                $changedOrder->setProducts($products);

                $this->transition($changedOrder, OrderWorkflow::TRANSITION_PENDING_ORDER);
            } elseif($this->orderWorkflow->can($changedOrder, $action)) {
                $this->transition($changedOrder, $action); // supplier rejected
            }

            return $this->order;
        } catch (Throwable $e) {
            return $this->order;
        }
    }

    protected function canSubmitBasket(Order $currentOrder, string $currentTransition = OrderWorkflow::TRANSITION_BASKET_SUBMIT): bool
    {
        if (!$this->orderWorkflow->can($currentOrder, $currentTransition)) {
            return false;
        }

        if (count($currentOrder->getProducts()) === 0) {
            $this->message[] = 'no active product';
            return false;
        }
        
        // check bulk purchase order?
        // check cost control?
        // check min order?
        // check order authorization?
        return true;
    }

    private function transition(Order $changedOrder, string $transition): Order
    {
        if (!$this->orderWorkflow->can($changedOrder, $transition)) {
            echo "FAILED: Order (id {$changedOrder->id}) is transitioning $transition. " . PHP_EOL;
            return $this->order;
        }
        
        $state = $this->order->getState();
        $this->orderWorkflow->apply($changedOrder, $transition);
        
        // don't need to print the below message, the listener is doing for us
        //echo "Order (id {$changedOrder->id}) is transitioning $transition. ({$state}->{$changedOrder->getState()})<br />" . PHP_EOL;
        
        return $this->order = $changedOrder;
    }
}