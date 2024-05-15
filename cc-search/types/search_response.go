package types

type SearchResponse struct {
	Total     int64      `json:"total"`
	Page      int        `json:"page"`
	PerPage   int        `json:"per_page"`
	RequestID string     `json:"request_id"`
	Hits      []Document `json:"hits"`
}
