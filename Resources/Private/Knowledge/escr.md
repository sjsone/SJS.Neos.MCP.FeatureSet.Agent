# Event Sourced Content Repository (ESCR)

Neos 9 uses an Event Sourced Content Repository. Every change to content is
recorded as an immutable event rather than directly modifying database rows.

## Key Concepts

- **Events are the source of truth.** Nodes are projections built by replaying
  events from the event store.
- **Commands produce events.** You never create/update/delete a node directly.
  You issue a command, the system validates it, and events are published.
- **Projections** (ContentGraph, Workspace, etc.) are read models updated by
  reacting to events. They are eventually consistent.

## Implications for Tool Usage

- After creating or modifying content, allow a few seconds for the projection
  to catch up before reading back the result.
- Workspaces isolate changesets. Changes in a personal workspace are invisible
  to other users until published.
- NodeAggregateId identifies a logical node across all dimensions and variants.
- A single logical node may have different properties per dimension (e.g.,
  different titles per language).
