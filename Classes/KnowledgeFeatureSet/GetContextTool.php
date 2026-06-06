<?php

declare(strict_types=1);

namespace SJS\Neos\MCP\FeatureSet\Agent\KnowledgeFeatureSet;

use SJS\Flow\MCP\Domain\Connection\ServerContext;
use SJS\Flow\MCP\Domain\MCP\Tool\Annotations;
use SJS\Flow\MCP\Domain\MCP\Tool\Content;
use SJS\Flow\MCP\JsonSchema\ObjectSchema;
use SJS\Flow\MCP\JsonSchema\StringSchema;
use SJS\Neos\MCP\FeatureSet\Agent\Tool\AbstractKnowledgeTool;

class GetContextTool extends AbstractKnowledgeTool
{
    private const TOPICS = [
        'escr' => 'escr.md',
        'node-types' => 'node-types.md',
        'document-vs-content' => 'document-vs-content.md',
        'dimensions' => 'dimensions.md',
        'workspaces' => 'workspaces.md',
        'constraints' => 'constraints.md',
    ];

    public function __construct()
    {
        parent::__construct(
            name: 'get_context',
            description: 'Load detailed knowledge documentation about Neos CMS concepts. '
                . 'Use this to understand the architecture before operating on content. '
                . 'Valid topics: ' . implode(', ', array_keys(self::TOPICS)) . '. '
                . 'For help choosing a topic, see also: get_site_landscape, get_available_children.',
            inputSchema: new ObjectSchema(properties: [
                'topic' => (new StringSchema(
                    description: 'The knowledge topic to load',
                    enum: array_keys(self::TOPICS),
                ))->required(),
            ]),
            annotations: new Annotations(
                title: 'Get Context',
                readOnlyHint: true
            )
        );
    }

    /**
     * @param array<string,mixed> $input
     */
    public function run(ServerContext $serverContext, array $input): Content
    {
        $topic = $input['topic'] ?? '';

        if (!array_key_exists($topic, self::TOPICS)) {
            $valid = implode(', ', array_keys(self::TOPICS));
            return Content::text("Unknown topic '{$topic}'. Valid topics: {$valid}");
        }

        return $this->knowledgeContent(self::TOPICS[$topic]);
    }
}
