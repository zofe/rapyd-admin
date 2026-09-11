<?php

namespace Zofe\Rapyd\Modules\Workflow\Traits;

use Illuminate\Support\Collection;
use Workflow;
use Zofe\Rapyd\Compilers\RawStringBladeCompiler;

/**
 * @author Boris Koumondji <brexis@yahoo.fr>
 */
trait WorkflowTrait
{
    public function workflow_apply($transition, $workflow = null, array $context = [])
    {
        if (is_array($workflow)) {
            $context = $workflow;
            $workflow = null;
        }

        return Workflow::get($this, $workflow)->apply($this, $transition, $context);
    }

    public function workflow_can($transition, $workflow = null)
    {
        try {
            return Workflow::get($this, $workflow)->can($this, $transition);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function workflow_transitions($workflow = null)
    {
        $workflow = Workflow::get($this, $workflow);
        try {
            return $workflow->getEnabledTransitions($this);
        } catch (\Exception $e) {
            return [];
        }

    }

    public function workflow_get($workflow = null)
    {
        return Workflow::get($this, $workflow);
    }

    public function workflow_transition_blocked($workflow = null)
    {
        $workflow = Workflow::get($this, $workflow);
        try {
            $marking = $workflow->getMarking($this);
        } catch (\Exception $e) {
            // Se il workflow non è definito, ritorna un array vuoto
            return [];
        }


        // Ottieni gli stati correnti
        $currentState = array_keys($marking->getPlaces())[0];

        // Ottieni tutte le transizioni dalla definizione del workflow
        $transitions = $workflow->getDefinition()->getTransitions();

        // Array per raccogliere i messaggi
        $messages = [];

        foreach ($transitions as $transition) {
            if (in_array($currentState, $transition->getFroms())) {
                $blockerList = $workflow->buildTransitionBlockerList($this, $transition->getName());
                if (!$blockerList->isEmpty()) {
                    foreach ($blockerList as $blocker) {
                        $messages[$blocker->getMessage()][] = $transition->getName();
                    }
                }
            }
        }

        // Riorganizza i messaggi per azioni
        $result = [];
        foreach ($messages as $message => $actions) {
            foreach ($actions as $action) {
                if (!isset($result[$action])) {
                    $result[$action] = [];
                }
                $result[$action][] = $message;
            }
        }

        return $result;
    }

    public function workflow_metadata($meta, $place_or_transition, $as_array = false, $workflow=null)
    {
        $dot_meta = strpos($meta, '.');
        $workflow = Workflow::get($this, $workflow);

        if (is_string($place_or_transition)) {
            foreach ($workflow->getDefinition()->getTransitions() as $t) {
                if ($t->getName() === $place_or_transition) {
                    $place_or_transition = $t;
                    break;
                }
            }
        }

        if ($dot_meta) {
            //dd($meta, $metadata, [substr($meta, 0, $dot_meta) => $metadata]);
            $metadata = $workflow->getMetadataStore()->getMetadata(substr($meta, 0, $dot_meta), $place_or_transition);
        } else {
            $metadata = $workflow->getMetadataStore()->getMetadata($meta, $place_or_transition);
        }

        if ($dot_meta) {
            $result = \Illuminate\Support\Arr::get([substr($meta, 0, $dot_meta) => $metadata], $meta);
        } elseif ($as_array) {
            $result = (array) $metadata;
        } else {
            $result = $metadata;
        }

        if (is_string($result)) {
            $result = RawStringBladeCompiler::render($result);
        }

        return $result;
    }

    public static function workflow_count_transition_blocked_from(Collection $items, $workflow=null): int
    {
        return $items
            ->flatMap(fn($item) =>
            collect($item->workflow_transition_blocked($workflow))
                ->flatMap(fn(array $subarray) => $subarray)
            )
            ->filter(fn(string $value) => trim($value) !== '')
            ->count();
    }

    public static function workflow_count_incomplete_from(Collection $items, $workflow=null): int
    {
        return $items
            ->map(fn($item) => $item->workflow_metadata('final', $item->status))
            ->filter(fn($value) => $value !== true)
            ->count();
    }

}
