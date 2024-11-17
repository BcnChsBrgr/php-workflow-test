<?php

namespace App;

use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class OrderWorkflow
{
    public const STATE_BASKET_OPEN = 'open';
    public const STATE_BASKET_REOPEN = 'reopen';
    public const STATE_BASKET_QUARINTINED = 'basket_quarintined';
    public const STATE_BASKET_SUBMIT = 'awaiting_for_supplier';
    public const STATE_ACCEPT_BY_SUPPLIER = 'accept_by_supplier';
    public const STATE_REJECT_BY_SUPPLIER = 'reject_by_supplier';
    public const STATE_ORDER_DISPATCH = 'order_dispatch';
    public const STATE_ORDER_INVOICED = 'order_invoiced';

    public const TRANSITION_BASKET_UPDATE = 'update_basket';
    public const TRANSITION_BASKET_SUBMIT = 'submit_basket';
    public const TRANSITION_BASKET_SUBMIT_FAILED = 'submit_basket_failed';
    public const TRANSITION_BASKET_RESUBMIT = 'resubmit_basket';
    public const TRANSITION_PENDING_ORDER = 'pending_order';
    public const TRANSITION_PENDING_ORDER_REJECTED = 'pending_order_rejected';
    

    public static function getWorkflow($dispatcher): Workflow
    {
        $definitionBuilder = (new DefinitionBuilder())
        ->addPlaces([
            self::STATE_BASKET_OPEN,
            self::STATE_BASKET_REOPEN,
            self::STATE_BASKET_SUBMIT,
            self::STATE_BASKET_QUARINTINED,
            self::STATE_ACCEPT_BY_SUPPLIER,
            self::STATE_REJECT_BY_SUPPLIER,
            self::STATE_ORDER_DISPATCH,
            self::STATE_ORDER_INVOICED,
        ])
        ->addTransition(new Transition(self::TRANSITION_BASKET_UPDATE, self::STATE_BASKET_OPEN, self::STATE_BASKET_OPEN))
        ->addTransition(new Transition(self::TRANSITION_BASKET_SUBMIT, self::STATE_BASKET_OPEN, [self::STATE_BASKET_SUBMIT, self::STATE_BASKET_QUARINTINED]))
        ->addTransition(new Transition(self::TRANSITION_BASKET_SUBMIT_FAILED, self::STATE_BASKET_OPEN, self::STATE_BASKET_QUARINTINED))
        ->addTransition(new Transition(self::TRANSITION_BASKET_RESUBMIT, self::STATE_BASKET_QUARINTINED, [self::STATE_BASKET_SUBMIT, self::STATE_BASKET_QUARINTINED]))
        ->addTransition(new Transition(self::TRANSITION_PENDING_ORDER, self::STATE_BASKET_SUBMIT, [self::STATE_ACCEPT_BY_SUPPLIER, self::STATE_REJECT_BY_SUPPLIER]))
        ->addTransition(new Transition(self::TRANSITION_PENDING_ORDER_REJECTED, self::STATE_BASKET_SUBMIT, self::STATE_REJECT_BY_SUPPLIER))
        ;

        return new Workflow(
            $definitionBuilder->build(),
            new MethodMarkingStore(true, 'state'),
            $dispatcher,
            name: 'order_processing_workflow'
        );
    }
}