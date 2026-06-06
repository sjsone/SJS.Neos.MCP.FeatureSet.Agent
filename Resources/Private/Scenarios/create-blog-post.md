# Create a Blog Post

## Context
Blog posts use `Neos.Demo:Document.BlogPosting` and MUST be placed under a Blog
page. Properties include: title, uriPathSegment, datePublished, authorName,
abstract. For background on the content model, use `get_context('document-vs-content')`.

## Workflow

### Step 0: Find your personal workspace
```
workspace_list_workspaces()
```
Returns all workspaces. Find the one with `classification: "PERSONAL"` — this
is your workspace. Use its name as the `workspaceName` in all node addresses
throughout this workflow. MUST use this workspace, MUST NOT create a new one.

### Step 1: Find the Blog page
```
document_list_documents(nodeType="Neos.Demo:Document.Blog")
```
Returns the Blog page's nodeAddress and aggregateId. The aggregateId is required
for Step 4.

### Step 2: Check allowed child types
```
knowledge_get_available_children(node_address={<Blog's nodeAddress>})
```
Confirms BlogPosting is an allowed child type under the Blog page.

### Step 3: Study existing blog posts
```
knowledge_find_similar_content(nodeType="Neos.Demo:Document.BlogPosting")
```
Shows existing posts' property values and structure. The agent SHOULD match the
conventions of the site.

### Step 4: Create the blog posting document
```
document_add_document(
  node_address=<any valid nodeAddress from the site>,
  node_type_name="Neos.Demo:Document.BlogPosting",
  parent_node_aggregate_id=<Blog's aggregateId>,
  node_properties={
    "title": "...",
    "uriPathSegment": "...",
    "authorName": "...",
    "abstract": "..."
  }
)
```
MUST use `document_add_document`. MUST NOT call `workspace_create_workspace` —
that creates a workspace, not a page.

### Step 5: Set the publication date
```
content_update_content(
  node_address=<new blog post's nodeAddress>,
  property_name="datePublished",
  property_value="2025-06-05T12:00:00+00:00"
)
```
MUST use ISO 8601 format. The property type is DateTimeImmutable.

### Step 6: Add content to the main collection
Use `content_content_tree` with the new blog post's nodeAddress to find the
`main` content collection. Then use `content_add_content` to add Headline and
Text nodes to the main collection.

## Constraints
- MUST use your personal workspace for all operations; MUST NOT call
  `workspace_create_workspace` to create a new workspace
- MUST allow 2-3 seconds after each create/update for eventual consistency
- MUST ensure uriPathSegment is unique under the parent page
- MUST NOT write to the `live` workspace
- SHOULD set hiddenInMenu to true for blog postings (matches existing convention)
- SHOULD fetch URL content via WebFetch before creating the post to extract
  title, author, date, and body text
