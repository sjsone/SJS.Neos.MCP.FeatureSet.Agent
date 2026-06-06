<?php

declare(strict_types=1);

namespace SJS\Neos\MCP\FeatureSet\Agent\ScenarioFeatureSet;

use SJS\Flow\MCP\Domain\Connection\ServerContext;
use SJS\Flow\MCP\Domain\MCP\Tool;
use SJS\Flow\MCP\Domain\MCP\Tool\Annotations;
use SJS\Flow\MCP\Domain\MCP\Tool\Content;
use SJS\Flow\MCP\JsonSchema\ObjectSchema;
use SJS\Neos\MCP\FeatureSet\Agent\Trait\KnowledgeResourceTrait;

class ListScenariosTool extends Tool
{
    use KnowledgeResourceTrait;

    public function __construct()
    {
        parent::__construct(
            name: 'list_scenarios',
            description: 'Returns a list of available scenarios — curated multi-shot examples '
                . 'showing correct tool call sequences for common CMS tasks. '
                . 'Use get_scenario to load a specific scenario.',
            inputSchema: new ObjectSchema(properties: []),
            annotations: new Annotations(
                title: 'List Scenarios',
                readOnlyHint: true
            )
        );
    }

    /**
     * @param array<string,mixed> $input
     */
    public function run(ServerContext $serverContext, array $input): Content
    {
        $indexJson = $this->loadMarkdownFromDirectory('Scenarios', 'index.json');
        $catalog = json_decode($indexJson, true);
        if (!is_array($catalog)) {
            throw new \RuntimeException('Failed to parse scenario index.json');
        }
        return Content::structuredWithFallback([
            'scenarios' => $catalog,
        ]);
    }
}
