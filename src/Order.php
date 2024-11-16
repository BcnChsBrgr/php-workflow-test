<?php

namespace App;

final class Order
{
    public function __construct(
        public int $id,
        public string $state = OrderWorkflow::STATE_BASKET_OPEN,
        private array $products = []
    ) {
    }

    public function getId(): int 
    {
        return $this->id;
    }

    public function setProducts(array $products = []): void 
    {
        $this->products = $products;
    }

    public function setState(string $state): void 
    {
        $this->state = $state;
    }

    public function getProducts(): array 
    {
        return $this->products;
    }

    public function getState(): string 
    {
        return $this->state;
    }
}