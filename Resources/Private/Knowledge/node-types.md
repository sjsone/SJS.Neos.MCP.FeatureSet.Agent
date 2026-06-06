# Node Types

NodeTypes define the schema for content in Neos. Every node has exactly one
NodeType, and NodeTypes can inherit from each other.

## Common Base Types

| NodeType | Purpose |
|----------|---------|
| `Neos.Neos:Document` | Pages with URLs. Rendered with a template. |
| `Neos.Neos:Content` | Content blocks placed on pages. |
| `Neos.Neos:ContentCollection` | Containers that hold content nodes. |

## NodeType Configuration

NodeTypes are primarily defined in `NodeTypes/` YAML files. They declare:
- **Properties** — named values with types (string, integer, reference, etc.)
- **Child nodes** — allowed child NodeTypes and constraints
- **Super types** — parent NodeTypes from which this one inherits

## Working with NodeTypes

Use `get_node_type` to inspect a NodeType's full configuration including
properties, default values, constraints, and super types.

Use `list_node_types` to see all available NodeTypes on the site.
