<?php

namespace Zofe\Rapyd\Contracts;

interface AiActivityProvider
{
    /**
     * What the AI did in this module, for the people who use the application.
     *
     * The twin of AiToolProvider: tools say what the model may read, activities say what it
     * has done. Implement it in a module's ServiceProvider or in a dedicated class, then
     * register via AiRegistry::registerActivities($this) — guarded by class_exists, as for
     * the tools, so the module works without ai-module.
     *
     * @return AiActivity[]
     */
    public function activities(): array;
}
