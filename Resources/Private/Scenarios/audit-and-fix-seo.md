# Audit & Fix SEO

## Context
SEO metadata (title, metaDescription, uriPathSegment) is critical for search
engine visibility. Pages without these properties are flagged as issues.
For background, use `get_context('document-vs-content')`.

## Workflow

### Step 0: Find your personal workspace
```
workspace_list_workspaces()
```
Find the entry with `classification: "PERSONAL"`. Use its name as the
`workspaceName` in all node addresses. MUST use this workspace, MUST NOT
create a new one.

### Step 1: Run the SEO audit
```
analysis_audit_seo()
```
Returns an object with `totalIssues` and an `issues` array. Each issue includes
the page's nodeAddress, nodeTypeName, and a `missingFields` array listing which
properties are absent.

### Step 2: Fix each issue
For each issue in the list:
```
content_update_content(
  node_address=<issue's nodeAddress>,
  property_name=<missing field name>,
  property_value="<appropriate value>"
)
```
MUST call `content_update_content` once per missing field per page.

### Step 3: Verify fixes
```
analysis_audit_seo()
```
SHOULD return `totalIssues: 0` after all fixes are applied.

## Constraints
- MUST use your personal workspace for all operations; MUST NOT call
  `workspace_create_workspace` to create a new workspace
- MUST NOT write to `live`
- MUST wait 2-3 seconds after each update for eventual consistency
- metaDescription SHOULD be 50-160 characters
- title SHOULD be descriptive and unique
