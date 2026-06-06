# Node Type Constraints

NodeTypes define which child NodeTypes are allowed at specific positions.

## How Constraints Work

1. Each NodeType declares `childNodes` in its configuration
2. Each child node can have `constraints` limiting which NodeTypes are allowed
3. Constraints can allow specific NodeTypes or all subtypes of a base type

## Constraint-Aware Operations

Before creating content, check what's allowed:

- Use `get_available_children` to see which NodeTypes can be placed under a
  specific node.
- Use `get_node_type` to inspect the constraint configuration for a given
  NodeType.

## Common Constraint Patterns

- A `ContentCollection` typically allows most `Neos.Neos:Content` subtypes
- A `Document` page body area typically allows structural content (Grid,
  TwoColumn, etc.)
- Named child nodes (like `sidebar` or `footer`) often have more restrictive
  constraints

## Constraint Violations

The Content Repository will reject commands that violate constraints. Always
check `get_available_children` before creating content in an unfamiliar
structure.
