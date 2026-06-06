# SJS.Neos.MCP.FeatureSet.Agent

Provides Neos/CMS-specific MCP tools for AI agents: site orientation, context
documentation, content analysis, and curated workflow scenarios.

## FeatureSets

- **Knowledge** — Lazy-load context docs, site landscape exploration, constraint
  checking, and example harvesting.
- **Analysis** — SEO audits and content pattern discovery.
- **Scenarios** — Curated multi-shot examples showing correct tool call sequences
  for common CMS tasks.

## Tools

### Knowledge (`knowledge_*`)

| Tool | Purpose |
| ------ | --------- |
| `get_context` | Load knowledge docs by topic (ESCR, NodeTypes, etc.) |
| `get_site_landscape` | Compact page tree with property deviations |
| `get_available_children` | Constraint-aware child type listing |
| `find_similar_content` | Find existing content by NodeType |

### Analysis (`analysis_*`)

| Tool | Purpose |
| ------ | --------- |
| `audit_seo` | Find pages with missing SEO metadata |
| `find_content_patterns` | Analyze how a NodeType is used across the site |

### Scenarios (`scenario_*`)

| Tool | Purpose |
| ------ | --------- |
| `list_scenarios` | List available workflow scenarios |
| `get_scenario` | Load a scenario with step-by-step tool call examples |

## Knowledge Documents

Markdown files in `Resources/Private/Knowledge/` are loaded on-demand by the
`get_context` tool, providing agents with critical architectural context about
Neos CMS (ESCR, NodeTypes, dimensions, etc.).

## Scenario Documents

Markdown files in `Resources/Private/Scenarios/` provide curated multi-shot
examples with correct tool call sequences for common CMS tasks. Each scenario
follows a consistent structure with Context, Workflow, and Constraints sections
using MUST/MUST NOT/SHOULD spec language. Use `list_scenarios` to browse the
catalog and `get_scenario` to load a specific workflow.
