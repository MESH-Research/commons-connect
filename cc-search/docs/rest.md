## REST Endpoints

/index
- GET /index - Get information about the index {auth: api_key}
- POST /index - Reset the index {auth: admin_api_key}

/documents
- GET /documents/{id} - Get document by id
- GET /documents/{id}?fields=a,b,c - Return only fields a,b,c
- POST /documents - Index new document {auth: api_key}
- POST /documents/bulk - Bulk index new documents {auth: api_key}
- PUT /documents/{id} - Update existing document {auth: api_key}
- DELETE /documents/{id} - Delete existing document {auth: api_key}

/search
- GET /search?q={search text} - Basic search (searches all indexed fields)
- GET /search?a=x - Field a matches x exactly
- GET /search?fields=a,b,c - Return only fields a,b,c
- GET /search?search_fields=a,b,c - Search only fields a,b,c
- GET /search?page=0&per_page=10 - Return 10 results per page, and show page 0 of results

/typeahead
- GET /typeahead?q={search text} - Typeahead search matching only title field

## Authorization

Authorization is done using a Bearer Token set in the header of the REST request. It should have the form `Authorization: Bearer 12345`. The required api_key or admin_api_key is currently a global configuration.

## Index documents

To index a document, POST to /documents with a request body of the form:

```json
{
	"title": "On Open Scholarship",
	"description": "An essay on the nature of open scholarship and the role of the library in supporting it.",
	"owner_name": "Reginald Gibbons",
	"other_names": [
		"Edwina Gibbons",
		"Obadiah Gibbons",
		"Lila Gibbons"
	],
	"owner_username": "reginald",
	"other_usernames": [
		"edwina",
		"obadiah",
		"lila"
	],
	"primary_url": "http://works.kcommons.org/records/1234",
	"other_urls": [
		"http://works.hcommons.org/records/1234",
		"http://works.mla.kcommons.org/records/1234",
		"http://works.hastac.kcommons.org/records/1234"
	],
	"thumbnail_url": "http://works.kcommons.org/records/1234/thumbnail.png",
	"content": "This is the content of the essay. It is a long essay, and it is very interesting. It is also very well-written and well-argued and well-researched and well-documented and well-cited",
	"publication_date": "2018-01-01",
	"language": "en",
	"content_type": "deposit",
	"network_node": "works"
}
```

A successful request will return a 200 response code along with:

```json
{
    "_id": "2E9SqY0Bdd2QL-HGeUuA",
    "title": "On Open Scholarship",
    "primary_url": "http://works.kcommons.org/records/1234"
}
```

To bulk index documents, POST to /documents/bulk with a request body like:

```json
[
	{
		"title":  "On Open Scholarship"
		...
	},
	{
		"title": "The Art of Programming"
		...
	}
]
```

A successful indexing will return with a 200 response code and a body like:

```json
[
	{
		"_id": "2E9SqY0Bdd2QL-HGeUuA",
		"title": "On Open Scholarship",
		"primary_url": "http://works.kcommons.org/records/1234"
	},
	{
		"_id": "234jdfg3w4rerf23dsf",
		"title": "The Art of Programming",
		"primary_url": "http://example.com/machine-learning"
	},
]
```