<?php

namespace App;

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

    public static function getSubscribedEvents(): array 
    {
        return [
            'workflow.order_processing_workflow.leave' => 'onLeave'
        ];
    } 
}