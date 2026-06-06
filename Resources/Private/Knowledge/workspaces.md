# Workspaces and Publishing

Workspaces provide isolated editing environments. Changes in one workspace are
not visible in others until published.

## Built-in Workspaces

| Workspace | Purpose |
|-----------|---------|
| `live` | The published, publicly visible content. Read-only for tools. |
| `user-{username}` | Personal workspace. Created automatically per user. |

## Workspace Operations

- `create_workspace` — Create a new named workspace
- `publish_workspace` — Publish all changes to the base workspace
- `list_workspaces` — See all available workspaces
- `list_workspace_changes` — See what's changed in a workspace

## Important Rules

- MUST use your personal workspace for all changes. Use `list_workspaces` to
  find the personal workspace — it has `classification: "PERSONAL"` and matches
  your username.
- MUST NOT call `create_workspace` to create a new workspace. The personal
  workspace already exists and is the correct place for your changes.
- MUST NOT write to `live` directly.
- Publishing is all-or-nothing for a workspace — all pending changes are
  published together.
- After publishing, allow a moment for projections to update.

## Workspace Discovery

To find your workspace:
```
workspace_list_workspaces()
```
Look for the entry with `classification: "PERSONAL"` — this is your workspace.
Use its name (the key of the entry) as the `workspaceName` in all node addresses.
