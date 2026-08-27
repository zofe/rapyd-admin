<?php

namespace Zofe\Rapyd\Contracts;

final class AiTool
{
    /**
     * @param string   $name        Unique snake_case identifier — used as the function name in API calls.
     * @param string   $description Plain-language description shown to the AI to decide when to call this tool.
     * @param array    $inputSchema JSON Schema (draft-7) describing the accepted parameters.
     * @param \Closure $handler     Receives the validated input array, returns string|array result.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly array $inputSchema,
        private readonly \Closure $handler,
    ) {}

    /**
     * Returns the tool definition in Anthropic Messages API format.
     * AiService converts to OpenAI/Ollama format as needed.
     */
    public function toDefinition(): array
    {
        return [
            'name'         => $this->name,
            'description'  => $this->description,
            'input_schema' => $this->inputSchema,
        ];
    }

    /**
     * Executes the tool handler and returns a result the AI can read.
     * Arrays are returned as-is; AiService serialises them before passing
     * back to the model as a tool_result block.
     */
    public function execute(array $input): mixed
    {
        return ($this->handler)($input);
    }
}
