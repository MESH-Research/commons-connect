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
