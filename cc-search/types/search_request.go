package types

type SearchRequest struct {
	DocvalueFields []string               `json:"docvalue_fields"`
	Fields         []string               `json:"fields"`
	Explain        bool                   `json:"explain"`
	From           int                    `json:"from"`
	MinScore       int                    `json:"min_score"`
	Query          map[string]interface{} `json:"query"`
	Size           int                    `json:"size"`
	Source         bool                   `json:"_source"`
	Stats          string                 `json:"stats"`
	TerminateAfter int                    `json:"terminate_after"`
	Timeout        string                 `json:"timeout"`
	Version        bool                   `json:"version"`
}
