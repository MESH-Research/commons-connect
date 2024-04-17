## Search Query Examples

GET /search?q={search text} - Basic search (searches all indexed fields)

```
"query": {
			"multi_match": {
				"query": "%s"
			}
		}
```

GET /search?a=x - Field a matches x exactly

```
"query": {
			"term": {
				"a": {
					"value": "%s"
				}
			}
		}
```

GET /search?a=x&b=y - Field a matches x exactly and field b matches y exactly

```
"query": {
			"bool": {
				"must": [
					{
						"term": {
							"a": {
								"value": "%s"
							}
						}
					},
					{
						"term": {
							"b": {
								"value": "%s"
							}
						}
					}
				]
			}
		}
```

GET /search?fields=a,b,c - Return only fields a,b,c

```
"fields": [
			"a",
			"b",
			"c"
		],
"query": {
			"multi_match": {
				"query": "%s"
			}
		}
```

GET /search?search_fields=a,b,c - Search only fields a,b,c

```
"query" : {
	"multi_match": {
		"query": "%s",
		"fields": ["a", "b", "c"]
	}
}
```

GET /typeahead?q={search text} - Typeahead search matching only title field

```


