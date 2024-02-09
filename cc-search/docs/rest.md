## REST Endpoints

/index
- GET /index - Get information about the index
- POST /index - Reset the index

/documents
- GET /documents/{id} - Get document by id
- GET /documents/{id}?fields=a,b,c - Return only fields a,b,c
- POST /documents - Index new document(s)
- PUT /documents/{id} - Update existing document
- DELETE /documents/{id} - Delete existing document

/search
- GET /search?q={search text} - Basic search (searches all indexed fields)
- GET /search?a=x - Field a matches x exactly
- GET /search?fields=a,b,c - Return only fields a,b,c
- GET /search?search_fields=a,b,c - Search only fields a,b,c
- GET /search?page=0&per_page=10 - Return 10 results per page, and show page 0 of results

/typeahead
- GET /typeahead?q={search text} - Typeahead search matching only title field