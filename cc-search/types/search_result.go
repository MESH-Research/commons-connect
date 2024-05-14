package types

// SearchResult is the struct that represents the response from an Elasticsearch search
type SearchResult struct {
	Took     int64 `json:"took"`
	TimedOut bool  `json:"timed_out"`
	Shards   struct {
		Total      int64 `json:"total"`
		Successful int64 `json:"successful"`
		Skipped    int64 `json:"skipped"`
		Failed     int64 `json:"failed"`
	} `json:"_shards"`
	Hits struct {
		Total struct {
			Value    int64  `json:"value"`
			Relation string `json:"relation"`
		} `json:"total"`
		MaxScore float64 `json:"max_score"`
		Hits     []struct {
			Index  string   `json:"_index"`
			ID     string   `json:"_id"`
			Score  float64  `json:"_score"`
			Source Document `json:"_source"`
		} `json:"hits"`
	} `json:"hits"`
}
