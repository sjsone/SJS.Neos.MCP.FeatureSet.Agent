# Create a Landing Page

## Context
Landing pages are structured documents with teaser, main content, and optional
footer sections. They typically use `Neos.Demo:Document.LandingPage` and contain
multi-column layouts with headlines and text.
For background, use `get_context('document-vs-content')`.

## Workflow

### Step 0: Find your personal workspace
```
workspace_list_workspaces()
```
Find the entry with `classification: "PERSONAL"`. Use its name as the
`workspaceName` in all node addresses. MUST use this workspace, MUST NOT
create a new one.

### Step 1: Find the parent page
```
knowledge_get_site_landscape()
```
Shows the page tree. Identify where to place the new landing page.

### Step 2: Check constraints
```
knowledge_get_available_children(node_address={<parent's nodeAddress>})
```
Confirms LandingPage is an allowed child type.

### Step 3: Study existing landing pages
```
knowledge_find_similar_content(nodeType="Neos.Demo:Document.LandingPage")
```
Shows existing landing page patterns to replicate.

### Step 4: Create the landing page document
```
document_add_document(
  node_address=<any valid nodeAddress>,
  node_type_name="Neos.Demo:Document.LandingPage",
  parent_node_aggregate_id=<parent's aggregateId>,
  node_properties={
    "title": "...",
    "uriPathSegment": "..."
  }
)
```
MUST use `document_add_document`. MUST NOT call `workspace_create_workspace`.

### Step 5: Add content sections
Use `content_content_tree` to find the `main` content collection. Add content
nodes following the pattern from Step 3. Common structure:
- `Neos.Demo:Content.Columns.Two` or `Columns.Three` for layout
- `Neos.Demo:Content.Headline` for section headings
- `Neos.Demo:Content.Text` for body text

### Step 6: Set SEO metadata
```
content_update_content(
  node_address=<new page's nodeAddress>,
  property_name="metaDescription",
  property_value="..."
)
```

## Constraints
- MUST use your personal workspace for all operations; MUST NOT call
  `workspace_create_workspace` to create a new workspace
- MUST NOT write to `live`
- MUST allow 2-3 seconds after each create for eventual consistency
- MUST ensure uriPathSegment is unique under the parent
- SHOULD set metaDescription (50-160 characters)
- SHOULD study existing landing pages before creating to match site conventions
