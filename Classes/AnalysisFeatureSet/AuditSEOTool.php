<?php

declare(strict_types=1);

namespace SJS\Neos\MCP\FeatureSet\Agent\AnalysisFeatureSet;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindRootNodeAggregatesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
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

class AuditSEOTool extends Tool
{
    use ContentRepositoryTool;

    public function __construct()
    {
        parent::__construct(
            name: 'audit_seo',
            description: 'Find pages with missing or inadequate SEO metadata: '
                . 'title, metaDescription, and urlPathSegment. '
                . 'Returns a list of pages with issues so the agent can fix them '
                . 'with update_content or update_document. '
                . 'For background, use get_context(\'document-vs-content\').',
            inputSchema: new ObjectSchema(properties: [
                'scope' => new StringSchema(
                    description: 'NodeAggregateId of a document to scope the audit. Defaults to entire site.'
                ),
            ]),
            annotations: new Annotations(
                title: 'Audit SEO',
                readOnlyHint: true
            )
        );
    }

    /**
     * @param array<string,mixed> $input
     */
    public function run(ServerContext $serverContext, array $input): Content
    {
        $contentRepository = $this->getContentRepository($serverContext);
        $graph = $contentRepository->getContentGraph(WorkspaceName::forLive());

        $issues = [];

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
                            NodeTypeNames::with(NodeTypeName::fromString('Neos.Neos:Document'))
                        )
                    )
                );

                if ($subtree !== null) {
                    $this->auditSubtree($subtree, $issues);
                }
            }
        }

        return Content::structuredWithFallback([
            'totalIssues' => count($issues),
            'issues' => $issues,
        ]);
    }

    /**
     * @param array<int, array<string,mixed>> $issues
     */
    private function auditSubtree(
        \Neos\ContentRepository\Core\Projection\ContentGraph\Subtree $subtree,
        array &$issues
    ): void {
        $node = $subtree->node;

        $pageIssues = [];

        $title = $node->getProperty('title');
        if ($title === null || (is_string($title) && trim($title) === '')) {
            $pageIssues[] = 'missing title';
        }

        $metaDescription = $node->getProperty('metaDescription');
        if ($metaDescription === null || (is_string($metaDescription) && trim($metaDescription) === '')) {
            $pageIssues[] = 'missing metaDescription';
        }

        $urlPathSegment = $node->getProperty('uriPathSegment');
        if ($urlPathSegment === null || (is_string($urlPathSegment) && trim($urlPathSegment) === '')) {
            $pageIssues[] = 'missing uriPathSegment';
        }

        if (!empty($pageIssues)) {
            $issues[] = [
                'nodeAddress' => NodeAddress::fromNode($node),
                'nodeTypeName' => (string) $node->nodeTypeName,
                'name' => (string) $node->name,
                'title' => $title,
                'missingFields' => $pageIssues,
            ];
        }

        foreach ($subtree->children as $child) {
            $this->auditSubtree($child, $issues);
        }
    }
}
