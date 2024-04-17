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

func Search(searcher types.Searcher, params types.SearchParams) ([]types.Document, error) {
	query := buildQuery(params)
	req := opensearchapi.SearchRequest{
		Index: []string{searcher.IndexName},
		Body:  strings.NewReader(query),
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
	docs, err := searchResultToDocuments(&searchResult), nil
	if err != nil {
		return nil, err
	}
	if len(params.ReturnFields) > 0 {
		for i := range docs {
			docs[i].FilterByJSON(params.ReturnFields)
		}
	}
	return docs, nil
}

type queryData struct {
	Fields []string          `json:"fields,omitempty"`
	Sort   map[string]string `json:"sort,omitempty"`
	Query  struct {
		MultiMatch *multiMatchQuery `json:"multi_match,omitempty"`
		Bool       *boolQuery       `json:"bool,omitempty"`
		Dates      *dateQuery       `json:"range,omitempty"`
	} `json:"query"`
}

type multiMatchQuery struct {
	Query  string   `json:"query,omitempty"`
	Fields []string `json:"fields,omitempty"`
}

type boolQuery struct {
	Must []struct {
		Term map[string]struct {
			Value string `json:"value,omitempty"`
		} `json:"term,omitempty"`
	} `json:"must,omitempty"`
}

type dateQuery struct {
	TimeStamp struct {
		GTE string `json:"gte,omitempty"`
		LTE string `json:"lte,omitempty"`
	} `json:"timestamp,omitempty"`
}

func buildQuery(params types.SearchParams) string {
	queryData := queryData{}
	queryData.Fields = params.ReturnFields
	if params.Query != "" {
		queryData.Query.MultiMatch = &multiMatchQuery{
			Query:  params.Query,
			Fields: params.SearchFields,
		}
	}
	if len(params.ExactMatch) > 0 {
		queryData.Query.Bool = &boolQuery{}
		for field, value := range params.ExactMatch {
			queryData.Query.Bool.Must = append(queryData.Query.Bool.Must, struct {
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
		queryData.Query.Dates = &dateQuery{}
		queryData.Query.Dates.TimeStamp.GTE = params.StartDate
		queryData.Query.Dates.TimeStamp.LTE = params.EndDate
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
