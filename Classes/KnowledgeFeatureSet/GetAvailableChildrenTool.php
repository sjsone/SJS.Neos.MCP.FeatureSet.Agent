<?php

declare(strict_types=1);

namespace SJS\Neos\MCP\FeatureSet\Agent\KnowledgeFeatureSet;

use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use SJS\Flow\MCP\Domain\Connection\ServerContext;
use SJS\Flow\MCP\Domain\MCP\Tool;
use SJS\Flow\MCP\Domain\MCP\Tool\Annotations;
use SJS\Flow\MCP\Domain\MCP\Tool\Content;
use SJS\Flow\MCP\JsonSchema\ObjectSchema;
use SJS\Flow\MCP\JsonSchema\StringSchema;
use SJS\Neos\MCP\FeatureSet\CR\Trait\ContentRepositoryTool;

class GetAvailableChildrenTool extends Tool
{
    use ContentRepositoryTool;

    public function __construct()
    {
        parent::__construct(
            name: 'get_available_children',
            description: 'Returns which NodeTypes can be placed under a given node, '
                . 'plus what is commonly used there based on existing content. '
                . 'Combines constraint definitions with real-world usage patterns. '
                . 'For background on constraints, use get_context(\'constraints\').',
            inputSchema: new ObjectSchema(properties: [
                'node_address' => (new ObjectSchema(
                    description: 'The node_address returned from other tools',
                    properties: [
                        'contentRepositoryId' => new StringSchema(),
                        'workspaceName' => new StringSchema(),
                        'dimensionSpacePoint' => new ObjectSchema(),
                        'aggregateId' => new StringSchema(),
                    ]
                ))->required(),
            ]),
            annotations: new Annotations(
                title: 'Get Available Children',
                readOnlyHint: true
            )
        );
    }

    /**
     * @param array<string,mixed> $input
     */
    public function run(ServerContext $serverContext, array $input): Content
    {
        $nodeAddress = $this->retrieveNodeAddress($input);

        $contentRepository = $this->getContentRepository($serverContext);
        $nodeTypeManager = $contentRepository->getNodeTypeManager();
        $graph = $contentRepository->getContentGraph(WorkspaceName::forLive());

        $subgraph = $graph->getSubgraph(
            $nodeAddress->dimensionSpacePoint,
            VisibilityConstraints::default()
        );

        $parentNode = $subgraph->findNodeById($nodeAddress->aggregateId);
        if ($parentNode === null) {
            throw new \InvalidArgumentException('Parent node not found');
        }

        $parentNodeType = $nodeTypeManager->getNodeType($parentNode->nodeTypeName);

        // Get constraint-defined allowed child types from the node type configuration
        $allowedTypes = [];
        if ($parentNodeType !== null) {
            $nodeTypeConfig = $parentNodeType->getFullConfiguration();
            $childNodes = $nodeTypeConfig['childNodes'] ?? [];
            foreach ($childNodes as $childNodeName => $childNodeConfig) {
                $constraints = $childNodeConfig['constraints']['nodeTypes'] ?? [];
                foreach ($constraints as $constraint => $allowed) {
                    // Constraints map NodeType names to allow/deny booleans
                    if ($allowed) {
                        $allowedTypes[] = $constraint;
                    }
                }
            }
        }

        // Also check all registered NodeTypes to see which ones the parent allows
        $allNodeTypes = $nodeTypeManager->getNodeTypes(false);
        foreach ($allNodeTypes as $candidateNodeType) {
            if ($parentNodeType !== null && $parentNodeType->allowsChildNodeType($candidateNodeType)) {
                $allowedTypes[] = (string) $candidateNodeType->name;
            }
        }

        // Get what's actually used: existing children's NodeTypes
        $existingChildTypes = [];
        $childNodes = $subgraph->findChildNodes(
            $parentNode->aggregateId,
            FindChildNodesFilter::create()
        );
        foreach ($childNodes as $childNode) {
            $typeName = (string) $childNode->nodeTypeName;
            if (!in_array($typeName, $existingChildTypes, true)) {
                $existingChildTypes[] = $typeName;
            }
        }

        return Content::structuredWithFallback([
            'nodeAggregateId' => (string) $parentNode->aggregateId,
            'nodeTypeName' => (string) $parentNode->nodeTypeName,
            'allowedNodeTypes' => array_values(array_unique($allowedTypes)),
            'existingChildTypes' => $existingChildTypes,
        ]);
    }
}
