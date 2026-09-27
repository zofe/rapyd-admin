<?php

namespace Zofe\Rapyd\Contracts;

/**
 * One thing the AI did in the application, said in the words of the domain: "Tickets
 * classified", 8 845. It is what an administrator reads on the AI page, next to what it
 * cost — not the tools, not the prompts, the work done.
 */
final class AiActivity
{
    /**
     * @param string   $key      Unique snake_case identifier
     * @param string   $label    What was done, in plain words ("Tickets classified")
     * @param int      $count    How many
     * @param ?string  $detail   One line more, if it helps ("from 26 categories the AI proposed")
     * @param ?string  $context  The context of the ledger rows of this activity, to put the cost next to it
     * @param ?\DateTimeInterface $lastAt  When it last happened
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly int $count,
        public readonly ?string $detail = null,
        public readonly ?string $context = null,
        public readonly ?\DateTimeInterface $lastAt = null,
    ) {
    }

    /** @return array{key: string, label: string, count: int, detail: ?string, context: ?string, last_at: ?string} */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'count' => $this->count,
            'detail' => $this->detail,
            'context' => $this->context,
            'last_at' => $this->lastAt?->format('Y-m-d H:i'),
        ];
    }
}
