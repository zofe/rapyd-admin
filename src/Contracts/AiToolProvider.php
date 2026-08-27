<?php

namespace Zofe\Rapyd\Contracts;

interface AiToolProvider
{
    /**
     * Returns the AI tools this module exposes.
     *
     * Implement this in a module's ServiceProvider or in a dedicated
     * *AiToolProvider class, then register via AiRegistry::register($this).
     *
     * @return AiTool[]
     */
    public function tools(): array;
}
