// Package search provides functionality for interacting with the OpenSearch API.
package search

import (
	"context"
	"encoding/json"
	"fmt"
	"io"
	"strings"

	opensearchapi "github.com/opensearch-project/opensearch-go/v2/opensearchapi"

	"github.com/MESH-Research/commons-connect/cc-search/types"
)

func RawSearch(searcher types.Searcher, query string) (string, error) {
	req := opensearchapi.SearchRequest{
		Index: []string{searcher.IndexName},
		Body:  strings.NewReader(query),
	}
	response, err := req.Do(context.Background(), searcher.Client)
	if err != nil {
		return ``, err
	}
	defer response.Body.Close()
	body, err := io.ReadAll(response.Body)
	if err != nil {
		return ``, err
	}
	return string(body), nil
}

func BasicSearch(searcher types.Searcher, query string) (*types.SearchResult, error) {
	QueryJSON := fmt.Sprintf(`{
		"query": {
			"multi_match": {
				"query": "%s"
			}
		}
	}`, query)

	req := opensearchapi.SearchRequest{
		Index: []string{searcher.IndexName},
		Body:  strings.NewReader(QueryJSON),
	}
	response, err := req.Do(context.Background(), searcher.Client)
	if err != nil {
		return nil, err
	}
	defer response.Body.Close()
	body, err := io.ReadAll(response.Body)
	if err != nil {
		return nil, err
	}
	var searchResult types.SearchResult
	err = json.Unmarshal(body, &searchResult)
	if err != nil {
		return nil, err
	}
	return &searchResult, nil
}

func Search(searcher types.Searcher, params types.SearchParams) (types.SearchResponse, error) {
	query := buildQuery(params)
	req := opensearchapi.SearchRequest{
		Index: []string{searcher.IndexName},
		Body:  strings.NewReader(query),
	}
	response, err := req.Do(context.Background(), searcher.Client)
	if err != nil {
		return types.SearchResponse{}, err
	}
	defer response.Body.Close()
	body, err := io.ReadAll(response.Body)
	if err != nil {
		return types.SearchResponse{}, err
	}
	var searchResult types.SearchResult
	err = json.Unmarshal(body, &searchResult)
	if err != nil {
		return types.SearchResponse{}, err
	}

	return searchResultToResponse(&searchResult, params), nil
}

func TypeAheadSearch(searcher types.Searcher, query string) ([]types.Document, error) {
	queryJson := fmt.Sprintf(`{
		"fields": ["title", "primary_url", "other_urls"],
		"size": 5,
		"query": {
			"multi_match": {
				"query": "%s",
				"type": "bool_prefix",
				"fields": ["title"]
			}
		}
	}`, query)
	req := opensearchapi.SearchRequest{
		Index: []string{searcher.IndexName},
		Body:  strings.NewReader(queryJson),
	}
	response, err := req.Do(context.Background(), searcher.Client)
	if err != nil {
		return nil, err
	}
	defer response.Body.Close()
	body, err := io.ReadAll(response.Body)
	if err != nil {
		return nil, err
	}
	var searchResult types.SearchResult
	err = json.Unmarshal(body, &searchResult)
	if err != nil {
		return nil, err
	}
	docs := searchResultToDocuments(&searchResult)
	for i := range docs {
		docs[i].FilterByJSON([]string{"title", "primary_url", "other_urls"})
	}
	return docs, nil
}

type queryData struct {
	From   int               `json:"from,omitempty"`
	Size   int               `json:"size,omitempty"`
	Fields []string          `json:"fields,omitempty"`
	Sort   map[string]string `json:"sort,omitempty"`
	Query  struct {
		Bool struct {
			Must       []interface{} `json:"must,omitempty"`
			DateFilter []interface{} `json:"filter,omitempty"`
		} `json:"bool,omitempty"`
	} `json:"query"`
}

type multiMatchQuery struct {
	MultiMatch struct {
		Query     string   `json:"query,omitempty"`
		Fields    []string `json:"fields,omitempty"`
		Fuzziness string   `json:"fuzziness,omitempty"`
	} `json:"multi_match,omitempty"`
}

type dateQuery struct {
	Range struct {
		PublicationDate struct {
			GTE string `json:"gte,omitempty"`
			LTE string `json:"lte,omitempty"`
		} `json:"publication_date,omitempty"`
	} `json:"range"`
}

func buildQuery(params types.SearchParams) string {
	queryData := queryData{}
	if params.PerPage > 0 {
		queryData.Size = params.PerPage
	} else {
		queryData.Size = 20
	}
	if params.Page > 0 {
		queryData.From = (params.Page - 1) * queryData.Size
	}
	queryData.Fields = params.ReturnFields
	if params.Query != "" {
		baseQuery := multiMatchQuery{}
		baseQuery.MultiMatch.Query = params.Query
		baseQuery.MultiMatch.Fuzziness = "AUTO"
		queryData.Query.Bool.Must = append(
			queryData.Query.Bool.Must,
			baseQuery,
		)
	}
	if len(params.ExactMatch) > 0 {
		for field, value := range params.ExactMatch {
			queryData.Query.Bool.Must = append(
				queryData.Query.Bool.Must,
				struct {
					Term map[string]struct {
						Value string `json:"value,omitempty"`
					} `json:"term,omitempty"`
				}{
					Term: map[string]struct {
						Value string `json:"value,omitempty"`
					}{
						field: {
							Value: value,
						},
					},
				})
		}
	}
	if params.StartDate != "" || params.EndDate != "" {
		dateQuery := dateQuery{}
		if params.StartDate != "" {
			dateQuery.Range.PublicationDate.GTE = params.StartDate
		}
		if params.EndDate != "" {
			dateQuery.Range.PublicationDate.LTE = params.EndDate
		}
		queryData.Query.Bool.DateFilter = append(
			queryData.Query.Bool.DateFilter,
			dateQuery,
		)
	}
	if params.SortField != "" {
		queryData.Sort = make(map[string]string)
		switch params.SortDirection {
		case "asc":
			queryData.Sort[params.SortField] = "asc"
		case "desc":
			queryData.Sort[params.SortField] = "desc"
		default:
			queryData.Sort[params.SortField] = "asc"
		}
	}
	queryJSON, _ := json.Marshal(queryData)
	return string(queryJSON)
}

func searchResultToDocuments(searchResult *types.SearchResult) []types.Document {
	documents := make([]types.Document, 0)
	for _, hit := range searchResult.Hits.Hits {
		documents = append(documents, hit.Source)
	}
	return documents
}

func searchResultToResponse(searchResult *types.SearchResult, searchParams types.SearchParams) types.SearchResponse {
	documents := make([]types.Document, 0)

	for _, hit := range searchResult.Hits.Hits {
		if len(searchParams.ReturnFields) > 0 {
			hit.Source.FilterByJSON(searchParams.ReturnFields)
		}
		newDocument := hit.Source
		newDocument.ID = hit.ID
		documents = append(documents, newDocument)
	}

	return types.SearchResponse{
		Total:     searchResult.Hits.Total.Value,
		Page:      searchParams.Page,
		PerPage:   searchParams.PerPage,
		RequestID: searchParams.RequestID,
		Hits:      documents,
	}
}
