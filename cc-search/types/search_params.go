package types

type SearchParams struct {
	Query         string
	ExactMatch    map[string]string
	ReturnFields  []string
	SearchFields  []string
	StartDate     string
	EndDate       string
	SortDirection string
	SortField     string
	Page          int
	PerPage       int
	RequestID     string
}
