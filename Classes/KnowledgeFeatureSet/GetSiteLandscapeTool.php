<?php

declare(strict_types=1);

namespace SJS\Neos\MCP\FeatureSet\Agent\KnowledgeFeatureSet;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
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

class GetSiteLandscapeTool extends Tool
{
    use ContentRepositoryTool;

    private const DEFAULT_MAX_CHILDREN = 30;

    public function __construct()
    {
        parent::__construct(
            name: 'get_site_landscape',
            description: 'Returns a compact overview of the document (page) tree. '
                . 'Shows pages with their NodeType and properties that differ from '
                . 'NodeType defaults. Capped at ' . self::DEFAULT_MAX_CHILDREN . ' children per branch '
                . '(set maxChildren to 0 for unlimited). '
                . 'For background on concepts, use get_context(\'document-vs-content\').',
            inputSchema: new ObjectSchema(properties: [
                'scope' => new StringSchema(
                    description: 'NodeAggregateId of a document to scope the tree. Defaults to root.'
                ),
                'maxChildren' => new IntegerSchema(
                    description: 'Max children per branch. Default ' . self::DEFAULT_MAX_CHILDREN . '. Set to 0 for full.',
                    default: self::DEFAULT_MAX_CHILDREN,
                ),
            ]),
            annotations: new Annotations(
                title: 'Get Site Landscape',
                readOnlyHint: true
            )
        );
    }

    /**
     * @param array<string,mixed> $input
     */
    public function run(ServerContext $serverContext, array $input): Content
    {
        $maxChildren = $input['maxChildren'] ?? self::DEFAULT_MAX_CHILDREN;
        if ($maxChildren <= 0) {
            $maxChildren = PHP_INT_MAX;
        }

        $contentRepository = $this->getContentRepository($serverContext);
        $graph = $contentRepository->getContentGraph(WorkspaceName::forLive());
        $nodeTypeManager = $contentRepository->getNodeTypeManager();

        // Find all document-type aggregates
        $documentTypeName = NodeTypeName::fromString('Neos.Neos:Document');
        $subNodeTypes = $nodeTypeManager->getSubNodeTypes($documentTypeName, false);

        /** @var array<string, array{node: Node, nodeType: ?NodeType}> $nodesById */
        $nodesById = [];
        /** @var array<string, string|null> $parentMap childId => parentId */
        $parentMap = [];

        // Collect all document aggregates and their nodes
        $allTypes = array_merge([$documentTypeName], array_map(fn($nt) => $nt->name, $subNodeTypes));
        foreach ($allTypes as $typeName) {
            $aggregates = $graph->findNodeAggregatesByType($typeName);
            foreach ($aggregates as $aggregate) {
                $aggId = (string) $aggregate->nodeAggregateId;
                if (isset($nodesById[$aggId])) {
                    continue;
                }

                // Get the first occupied DSP to find a representative node
                foreach ($aggregate->occupiedDimensionSpacePoints as $dsp) {
                    $node = $aggregate->getNodeByOccupiedDimensionSpacePoint($dsp);
                    if ($node !== null) {
                        $nodeType = $nodeTypeManager->getNodeType($node->nodeTypeName);
                        $nodesById[$aggId] = ['node' => $node, 'nodeType' => $nodeType];

                        // Find parent
                        $subgraph = $graph->getSubgraph(
                            $node->dimensionSpacePoint,
                            VisibilityConstraints::default()
                        );
                        $parentNode = $subgraph->findParentNode($aggregate->nodeAggregateId);
                        $parentMap[$aggId] = $parentNode !== null ? (string) $parentNode->aggregateId : null;

                        break;
                    }
                }
            }
        }

        // Build child mapping
        /** @var array<string, array<int, string>> $childrenMap parentId => [childIds] */
        $childrenMap = [];
        foreach ($parentMap as $childId => $parentId) {
            if ($parentId !== null) {
                $childrenMap[$parentId][] = $childId;
            }
        }

        // Build landscape tree — root entries are those with no parent in our document set
        $roots = [];
        foreach ($nodesById as $id => $data) {
            $parentId = $parentMap[$id] ?? null;
            if ($parentId === null || !isset($nodesById[$parentId])) {
                $roots[] = $this->buildPageEntry(
                    $id,
                    $nodesById,
                    $childrenMap,
                    $nodeTypeManager,
                    $maxChildren,
                    0
                );
            }
        }

        return Content::structuredWithFallback([
            'pages' => $roots,
        ]);
    }

    /**
     * @param array<string, array{node: Node, nodeType: ?NodeType}> $nodesById
     * @param array<string, array<int, string>> $childrenMap
     * @return array<string,mixed>
     */
    private function buildPageEntry(
        string $aggregateId,
        array $nodesById,
        array $childrenMap,
        \Neos\ContentRepository\Core\NodeType\NodeTypeManager $nodeTypeManager,
        int $maxChildren,
        int $depth,
    ): array {
        $data = $nodesById[$aggregateId];
        $node = $data['node'];
        $nodeType = $data['nodeType'];

        $entry = [
            'aggregateId' => $aggregateId,
            'nodeTypeName' => (string) $node->nodeTypeName,
            'name' => (string) $node->name,
            'title' => $node->getProperty('title') ?? '',
            'urlPathSegment' => $node->getProperty('uriPathSegment') ?? '',
            'nodeAddress' => NodeAddress::fromNode($node),
            'deviations' => $this->getPropertyDeviations($node, $nodeType),
            'children' => [],
        ];

        // Add children (capped)
        $childIds = $childrenMap[$aggregateId] ?? [];
        $childEntries = [];
        $count = 0;
        foreach ($childIds as $childId) {
            if ($count >= $maxChildren) {
                break;
            }
            if (!isset($nodesById[$childId])) {
                continue;
            }
            $childEntries[] = $this->buildPageEntry(
                $childId,
                $nodesById,
                $childrenMap,
                $nodeTypeManager,
                $maxChildren,
                $depth + 1,
            );
            $count++;
        }

        $entry['children'] = $childEntries;
        if ($count >= $maxChildren && count($childIds) > $maxChildren) {
            $entry['childrenTruncated'] = true;
        }

        return $entry;
    }

    /**
     * Get properties that deviate from NodeType defaults.
     *
     * @return array<string,mixed>
     */
    private function getPropertyDeviations(Node $node, ?NodeType $nodeType): array
    {
        if ($nodeType === null) {
            return [];
        }

        $defaults = $nodeType->getDefaultValuesForProperties();
        $deviations = [];

        foreach ($node->properties as $propertyName => $propertyValue) {
            $defaultValue = $defaults[$propertyName] ?? null;
            if ($propertyValue !== $defaultValue && $propertyValue !== null) {
                $deviations[$propertyName] = $propertyValue;
            }
        }

        return $deviations;
    }
}
