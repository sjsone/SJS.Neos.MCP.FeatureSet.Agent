<?php

declare(strict_types=1);

namespace SJS\Neos\MCP\FeatureSet\Agent\ScenarioFeatureSet;

use SJS\Flow\MCP\Domain\Connection\ServerContext;
use SJS\Flow\MCP\Domain\MCP\Tool\Annotations;
use SJS\Flow\MCP\Domain\MCP\Tool\Content;
use SJS\Flow\MCP\JsonSchema\ObjectSchema;
use SJS\Flow\MCP\JsonSchema\StringSchema;
use SJS\Neos\MCP\FeatureSet\Agent\Tool\AbstractKnowledgeTool;

class GetScenarioTool extends AbstractKnowledgeTool
{
    public function __construct()
    {
        parent::__construct(
            name: 'get_scenario',
            description: 'Load a scenario — a curated multi-shot example showing the correct '
                . 'tool call sequence for a specific task. Use list_scenarios to see what is available.',
            inputSchema: new ObjectSchema(properties: [
                'name' => (new StringSchema(
                    description: 'The scenario name, e.g. "create-blog-post"'
                ))->required(),
            ]),
            annotations: new Annotations(
                title: 'Get Scenario',
                readOnlyHint: true
            )
        );
    }

    /**
     * @param array<string,mixed> $input
     */
    public function run(ServerContext $serverContext, array $input): Content
    {
        $name = $input['name'] ?? '';

        if ($name === '' || !preg_match('/^[a-z0-9-]+$/', $name)) {
            return Content::text("Invalid scenario name. Use list_scenarios to see available names.");
        }

        try {
            return Content::text($this->loadMarkdownFromDirectory('Scenarios', $name . '.md'));
        } catch (\InvalidArgumentException $e) {
            return Content::text("Scenario not found: {$name}. Use list_scenarios to see available scenarios.");
        }
    }
}
