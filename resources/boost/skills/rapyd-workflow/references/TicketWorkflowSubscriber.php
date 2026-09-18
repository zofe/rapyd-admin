<?php

namespace App\Modules\Support\Listeners;

use App\Modules\Support\Models\Ticket;

/**
 * The rules and the effects of the `ticket` workflow, as event listeners of zerodahero/laravel-workflow.
 * Registered once: Event::subscribe(TicketWorkflowSubscriber::class) in the module's service provider
 * (a package) or in AppServiceProvider::boot() (an app module).
 *
 * Events, per workflow name and transition / place:
 *   workflow.ticket.guard.{transition}      can it run? $event->setBlocked(true, 'why') refuses it
 *                                           (an empty message hides the button, a message shows it disabled with the reason)
 *   workflow.ticket.transition.{transition} runs INSIDE the transition: throw to abort it (the model keeps its state)
 *   workflow.ticket.completed.{transition}  after the marking changed (the model is not saved yet: the caller saves)
 *   workflow.ticket.enter.{place} / leave.{place}
 */
class TicketWorkflowSubscriber
{
    /** A guard: a ticket is resolved only with a resolution note. */
    public function onGuardResolve($event): void
    {
        /** @var Ticket $ticket */
        $ticket = $event->getSubject();
        if (blank($ticket->resolution)) {
            $event->setBlocked(true, 'write the resolution first');
        }
    }

    /** A guard that hides a transition: closed by the customer only when it is theirs. */
    public function onGuardClose($event): void
    {
        $ticket = $event->getSubject();
        $user = auth()->user();
        if ($user && ! $user->hasRoleOrPermission('admin|edit tickets') && $ticket->user_id !== $user->id) {
            $event->setBlocked(true, '');
        }
    }

    /** An effect: when the ticket is closed, tell the customer. */
    public function onClosed($event): void
    {
        $ticket = $event->getSubject();
        $ticket->closed_at = now();
        // Mail::to($ticket->user)->queue(new TicketClosed($ticket));
    }

    public function subscribe($events): void
    {
        $events->listen('workflow.ticket.guard.resolve', self::class . '@onGuardResolve');
        $events->listen('workflow.ticket.guard.close', self::class . '@onGuardClose');
        $events->listen('workflow.ticket.completed.close', self::class . '@onClosed');
    }
}
