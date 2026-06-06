<?php

declare(strict_types=1);

namespace SJS\Neos\MCP\FeatureSet\Agent\AnalysisFeatureSet;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindRootNodeAggregatesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use SJS\Flow\MCP\Domain\Connection\ServerContext;
use SJS\Flow\MCP\Domain\MCP\Tool;
use SJS\Flow\MCP\Domain\MCP\Tool\Annotations;
use SJS\Flow\MCP\Domain\MCP\Tool\Content;
use SJS\Flow\MCP\JsonSchema\ObjectSchema;
use SJS\Flow\MCP\JsonSchema\StringSchema;
use SJS\Neos\MCP\FeatureSet\CR\Trait\ContentRepositoryTool;

class FindContentPatternsTool extends Tool
{
    use ContentRepositoryTool;

    public function __construct()
    {
        parent::__construct(
            name: 'find_content_patterns',
            description: 'Analyze how a given NodeType is used across the site: '
                . 'common parent NodeTypes, typical property values, and usage count. '
                . 'Useful for understanding conventions before creating new content. '
                . 'For background, use get_context(\'node-types\').',
            inputSchema: new ObjectSchema(properties: [
                'nodeType' => (new StringSchema(
                    description: 'NodeType name to analyze, e.g. "Neos.Demo:Content.Hero"'
                ))->required(),
            ]),
            annotations: new Annotations(
                title: 'Find Content Patterns',
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

        $contentRepository = $this->getContentRepository($serverContext);
        $graph = $contentRepository->getContentGraph(WorkspaceName::forLive());
        $nodeTypeManager = $contentRepository->getNodeTypeManager();

        $totalCount = 0;
        $parentTypes = [];    // parent NodeTypeName => count
        $propertyValues = []; // propertyName => [value => count]

        $rootAggregates = $graph->findRootNodeAggregates(
            FindRootNodeAggregatesFilter::create()
        );

        foreach ($rootAggregates as $rootAggregate) {
            foreach ($rootAggregate->occupiedDimensionSpacePoints as $originDsp) {
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
                    $this->analyzeSubtree($subtree, $nodeTypeName, $nodeTypeManager, $totalCount, $parentTypes, $propertyValues);
                }
            }
        }

        // Sort property values by frequency per property
        foreach ($propertyValues as $propName => &$values) {
            arsort($values);
        }
        unset($values);

        arsort($parentTypes);

        return Content::structuredWithFallback([
            'nodeType' => (string) $nodeTypeName,
            'totalInstances' => $totalCount,
            'commonParentTypes' => $parentTypes,
            'propertyPatterns' => $propertyValues,
        ]);
    }

    /**
     * @param array<string, int> $parentTypes
     * @param array<string, array<string, int>> $propertyValues
     */
    private function analyzeSubtree(
        \Neos\ContentRepository\Core\Projection\ContentGraph\Subtree $subtree,
        NodeTypeName $targetType,
        \Neos\ContentRepository\Core\NodeType\NodeTypeManager $nodeTypeManager,
        int &$totalCount,
        array &$parentTypes,
        array &$propertyValues
    ): void {
        $node = $subtree->node;
        $nodeType = $nodeTypeManager->getNodeType($node->nodeTypeName);

        if ($nodeType !== null && $nodeType->isOfType($targetType)) {
            $totalCount++;

            // Track which NodeType this node is — include the specific subtype
            $specificType = (string) $node->nodeTypeName;
            if (!isset($parentTypes[$specificType])) {
                $parentTypes[$specificType] = 0;
            }
            $parentTypes[$specificType]++;

            // Collect property values
            foreach ($node->properties as $propName => $propValue) {
                if ($propName[0] === '_' || $propValue === null) {
                    continue;
                }
                if (!isset($propertyValues[$propName])) {
                    $propertyValues[$propName] = [];
                }
                $stringValue = is_string($propValue) ? $propValue : json_encode($propValue);
                if (is_string($propValue) && strlen($propValue) > 80) {
                    $stringValue = substr($propValue, 0, 80) . '...';
                }
                if (!isset($propertyValues[$propName][$stringValue])) {
                    $propertyValues[$propName][$stringValue] = 0;
                }
                $propertyValues[$propName][$stringValue]++;
            }
        }

        foreach ($subtree->children as $child) {
            $this->analyzeSubtree($child, $targetType, $nodeTypeManager, $totalCount, $parentTypes, $propertyValues);
        }
    }
}
