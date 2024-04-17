package types

// See: https://opensearch.org/docs/latest/api-reference/search/
// See: https://www.elastic.co/guide/en/elasticsearch/reference/current/search-fields.html

// SearchRequest is the request body for a search request.
type SearchRequest struct {
	DocvalueFields []string    `json:"docvalue_fields"` // Fields to return as doc values
	Fields         []string    `json:"fields"`          // Fields to return
	Explain        bool        `json:"explain"`         // Include an explanation of how scoring of the results was computed
	From           int         `json:"from"`            // The offset from the first result you want to fetch
	MinScore       int         `json:"min_score"`       // Exclude documents which have a score lower than the minimum specified
	Query          SearchQuery `json:"query"`           // The query definition using the Query DSL
	Size           int         `json:"size"`            // The number of hits to return
	Source         bool        `json:"_source"`         // True or false to return the _source field or not
	Stats          string      `json:"stats"`           // Specific 'tag' of the request for logging and statistical purposes
	TerminateAfter int         `json:"terminate_after"` // The maximum number of documents to collect for each shard, upon reaching which the query execution will terminate early
	Timeout        string      `json:"timeout"`         // Timeout to wait for the query to complete
	Version        bool        `json:"version"`         // Specify whether to return document version as part of a hit
}
