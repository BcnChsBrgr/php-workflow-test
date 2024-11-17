<?php

namespace App;

use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Event\LeaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderWorkflowListener implements EventSubscriberInterface
{
    public function onLeave(LeaveEvent $event): void 
    {
        printf(sprintf(
            'Order (id: %s) transition %s, from %s to %s. %s',
            $event->getSubject()->getId(),
            $event->getTransition()->getName(),
            implode(', ', array_keys($event->getMarking()->getPlaces())),
            implode(', ', $event->getTransition()->getTos()),
            '<br />' . PHP_EOL,
        ));
    }

    public function onOrderSubmition(GuardEvent $event): void 
    {
        if ($this->checkBasketInvalidation($event)) {
            printf(sprintf('Order (id %s) transition %s rejected from %s to %s.%s', 
                $event->getSubject()->getId(),
                $event->getTransition()->getName(),
                implode(', ', array_keys($event->getMarking()->getPlaces())),
                OrderWorkflow::STATE_BASKET_QUARINTINED,
                '<br />' . PHP_EOL,
            ));
            $event->setBlocked(true);
            $event->getSubject()->setState(OrderWorkflow::STATE_BASKET_QUARINTINED);
        }
    }

    private function checkBasketInvalidation(GuardEvent $event): bool 
    {
        if (count($event->getSubject()->getProducts()) === 0) {
            return true;
        }

        return false;
    }

    public static function getSubscribedEvents(): array 
    {
        // format: workflow.<workflow_name>.leave => function name
        return [
            'workflow.order_processing_workflow.leave' => 'onLeave',
            'workflow.order_processing_workflow.guard.' . OrderWorkflow::TRANSITION_BASKET_SUBMIT => 'onOrderSubmition',
        ];
    } 
}