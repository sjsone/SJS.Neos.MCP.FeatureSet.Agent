# Documents vs Content

Neos distinguishes between two fundamental node categories:

## Documents

- Extend `Neos.Neos:Document`
- Have a URL (routable page)
- Form the site's information architecture (page tree)
- Examples: Page, Blog, Product, Category

## Content

- Extend `Neos.Neos:Content`
- Placed inside a document (usually via a ContentCollection)
- No independent URL — displayed as part of the parent document
- Examples: Text, Image, Hero, TwoColumn

## Content Collections

- Extend `Neos.Neos:ContentCollection`
- Containers that hold Content nodes in order
- Every document has at least one (typically named `main`)
- Created automatically when a document is created

## Tool Selection

- Document operations: `add_document`, `list_documents`, `get_site_landscape`
- Content operations: `add_content`, `update_content`, `content_tree`, etc.
- ContentCollections: treated like content nodes for most operations
