<?php

namespace App;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderWorkflowListener implements EventSubscriberInterface
{
    public function onLeave(Event $event): void 
    {
        printf(sprintf(
            'Order (id: %s) transition %s, from %s to %s. %s',
            $event->getSubject()->getId(),
            $event->getTransition()->getName(),
            implode(', ', array_keys($event->getMarking()->getPlaces())),
            implode(', ', $event->getTransition()->getTos()),
            PHP_EOL,
        ));
    }

    public static function getSubscribedEvents(): array 
    {
        return [
            'workflow.order.leave' => 'onLeave'
        ];
    } 
}