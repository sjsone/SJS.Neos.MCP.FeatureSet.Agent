<?php

declare(strict_types=1);

namespace SJS\Neos\MCP\FeatureSet\Agent\KnowledgeFeatureSet;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindRootNodeAggregatesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use SJS\Flow\MCP\Domain\Connection\ServerContext;
use SJS\Flow\MCP\Domain\MCP\Tool;
use SJS\Flow\MCP\Domain\MCP\Tool\Annotations;
use SJS\Flow\MCP\Domain\MCP\Tool\Content;
use SJS\Flow\MCP\JsonSchema\IntegerSchema;
use SJS\Flow\MCP\JsonSchema\ObjectSchema;
use SJS\Flow\MCP\JsonSchema\StringSchema;
use SJS\Neos\MCP\FeatureSet\CR\Trait\ContentRepositoryTool;

class FindSimilarContentTool extends Tool
{
    use ContentRepositoryTool;

    private const DEFAULT_LIMIT = 20;

    public function __construct()
    {
        parent::__construct(
            name: 'find_similar_content',
            description: 'Find existing content nodes by NodeType name. '
                . 'Useful for discovering examples and patterns already in use on the site. '
                . 'For background on NodeTypes, use get_context(\'node-types\').',
            inputSchema: new ObjectSchema(properties: [
                'nodeType' => (new StringSchema(
                    description: 'NodeType name to search for, e.g. "Neos.Demo:Content.Hero"'
                ))->required(),
                'limit' => new IntegerSchema(
                    description: 'Maximum results. Default ' . self::DEFAULT_LIMIT,
                    default: self::DEFAULT_LIMIT,
                ),
            ]),
            annotations: new Annotations(
                title: 'Find Similar Content',
                readOnlyHint: true
            )
        );
    }

    /**
     * @param array<string,mixed> $input
     */
    public function run(ServerContext $serverContext, array $input): Content
    {
        $nodeTypeName = NodeTypeName::fromString($input['nodeType']);
        $limit = max(1, $input['limit'] ?? self::DEFAULT_LIMIT);

        $contentRepository = $this->getContentRepository($serverContext);
        $graph = $contentRepository->getContentGraph(WorkspaceName::forLive());
        $nodeTypeManager = $contentRepository->getNodeTypeManager();

        $results = [];

        $rootAggregates = $graph->findRootNodeAggregates(
            FindRootNodeAggregatesFilter::create()
        );

        foreach ($rootAggregates as $rootAggregate) {
            if (count($results) >= $limit) {
                break;
            }

            foreach ($rootAggregate->occupiedDimensionSpacePoints as $originDsp) {
                if (count($results) >= $limit) {
                    break;
                }

                $dsp = DimensionSpacePoint::fromArray($originDsp->coordinates);
                $subgraph = $graph->getSubgraph($dsp, VisibilityConstraints::default());

                $subtree = $subgraph->findSubtree(
                    entryNodeAggregateId: $rootAggregate->nodeAggregateId,
                    filter: FindSubtreeFilter::create(
                        nodeTypes: NodeTypeCriteria::createWithAllowedNodeTypeNames(
                            NodeTypeNames::with($nodeTypeName)
                        )
                    )
                );

                if ($subtree !== null) {
                    $this->collectNodes($subtree, $results, $limit, $nodeTypeName, $nodeTypeManager);
                }
            }
        }

        return Content::structuredWithFallback($results);
    }

    /**
     * @param array<int, array<string,mixed>> $results
     */
    private function collectNodes(
        Subtree $subtree,
        array &$results,
        int $limit,
        NodeTypeName $targetType,
        \Neos\ContentRepository\Core\NodeType\NodeTypeManager $nodeTypeManager
    ): void {
        if (count($results) >= $limit) {
            return;
        }

        $node = $subtree->node;
        $nodeType = $nodeTypeManager->getNodeType($node->nodeTypeName);

        if ($nodeType !== null && $nodeType->isOfType($targetType)) {
            $results[] = [
                'nodeAddress' => NodeAddress::fromNode($node),
                'nodeTypeName' => (string) $node->nodeTypeName,
                'name' => (string) $node->name,
                'title' => $this->findTitleProperty($node),
                'properties' => $this->summarizeProperties($node),
            ];
        }

        foreach ($subtree->children as $child) {
            if (count($results) >= $limit) {
                break;
            }
            $this->collectNodes($child, $results, $limit, $targetType, $nodeTypeManager);
        }
    }

    private function findTitleProperty(Node $node): ?string
    {
        foreach (['title', 'headline', 'header', 'text'] as $candidate) {
            $value = $node->getProperty($candidate);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }
        return null;
    }

    /**
     * @return array<string,mixed>
     */
    private function summarizeProperties(Node $node): array
    {
        $summary = [];
        foreach ($node->properties as $key => $value) {
            if ($key[0] === '_') {
                continue; // skip internal properties
            }
            if (is_string($value) && strlen($value) > 100) {
                $summary[$key] = substr($value, 0, 100) . '...';
            } elseif (!is_object($value) && $value !== null) {
                $summary[$key] = $value;
            }
        }
        return $summary;
    }
}
