package search

// Note: these tests require the local opensearch instance to be running. So
// run `lando start` before running these tests.

import (
	"encoding/json"
	"log"
	"testing"

	"github.com/MESH-Research/commons-connect/cc-search/config"
	"github.com/MESH-Research/commons-connect/cc-search/types"
)

var testSettingsJSON = `{
	"settings": {
		"index": {
			"number_of_shards": "1",
			"number_of_replicas": "2"
		}
	},
	"mappings": {
		"properties": {
			"title": { "type" : "text" },
			"description": { "type" : "text" },
			"owner_name": { "type" : "integer" },
			"owner_username": { "type" : "keyword" },
			"primary_url": { "type" : "text" }
		}
	}
}`

var testDocumentJSON = `{
	"title": "Searching Openly",
	"author": "Mike Thicke",
	"year": "2014",
	"username": "mthicke"
}`

func cleanSetup() types.Searcher {
	client, err := GetClientNoAuth("http://localhost:9200")
	if err != nil {
		log.Fatalf("Error getting client: %v", err)
	}
	searcher := types.Searcher{
		Client:    client,
		IndexName: "test",
	}
	_ = DeleteIndex(&searcher)
	return searcher
}

func resetIndex(searcher *types.Searcher) {
	testIndexSettings, err := getIndexSettings()
	if err != nil {
		log.Fatalf("Error parsing index settings: %v", err)
	}
	searcher.IndexName = "test"
	err = CreateIndex(searcher, testIndexSettings)
	if err != nil {
		log.Fatalf("Error creating index: %v", err)
	}
}

func TestGetIndexSettings(t *testing.T) {
	settings, err := getIndexSettings()
	if err != nil {
		t.Errorf("Error getting index settings: %v", err)
	}
	if settings.Mappings.Properties["title"].Type != "text" {
		t.Errorf("Expected text, got %s", settings.Mappings.Properties["title"].Type)
	}
}

func TestDeleteIndex(t *testing.T) {
	searcher := cleanSetup()
	resetIndex(&searcher)
	err := DeleteIndex(&searcher)
	if err != nil {
		t.Errorf("Expected no error when deleting existing index, got %v", err)
	}
	searcher.IndexName = "nonexistent"
	err = DeleteIndex(&searcher)
	if err == nil {
		t.Errorf("Expected error when deleting non-existing index, got nil")
	}
}

func TestCreateIndex(t *testing.T) {
	searcher := cleanSetup()
	testIndexSettings, err := getIndexSettings()
	if err != nil {
		t.Errorf("Error parsing index settings: %v", err)
	}
	err = CreateIndex(&searcher, testIndexSettings)
	if err != nil {
		t.Errorf("Error creating index: %v", err)
	}
	if searcher.IndexName != "test" {
		t.Errorf("Expected test, got %s", searcher.IndexName)
	}
}

func TestIndexDocument(t *testing.T) {
	searcher := cleanSetup()
	resetIndex(&searcher)
	var testDocument types.Document
	err := json.Unmarshal([]byte(testDocumentJSON), &testDocument)
	if err != nil {
		t.Errorf("Error unmarshalling JSON: %v", err)
	}
	doc, err := IndexDocument(searcher, testDocument)
	if err != nil {
		t.Errorf("Error indexing document: %v", err)
	}
	if doc == nil {
		t.Errorf("Expected non-nil document, got nil")
		return
	}
	if doc.ID == "" {
		t.Errorf("Expected non-empty ID, got empty")
	}
}

func TestGetAWSClient(t *testing.T) {
	config.Init()
	conf := config.GetConfig()
	client, err := GetClientUserPass(
		conf.SearchEndpoint,
		conf.User,
		conf.Password,
	)
	if err != nil {
		t.Errorf("Error getting client: %v", err)
	}
	if client == nil {
		t.Errorf("Expected non-nil client, got nil")
	}
}

func TestBasicSearch(t *testing.T) {
	searcher := cleanSetup()
	resetIndex(&searcher)
	var testDocument types.Document
	err := json.Unmarshal([]byte(testDocumentJSON), &testDocument)
	if err != nil {
		t.Errorf("Error unmarshalling JSON: %v", err)
	}
	_, err = IndexDocument(searcher, testDocument)
	if err != nil {
		t.Errorf("Error indexing document: %v", err)
	}
	result, err := BasicSearch(searcher, "searching")
	if err != nil {
		t.Errorf("Error searching: %v", err)
	}
	if result == nil {
		t.Errorf("Expected non-nil result, got nil")
	}
}

func TestBuildQuery(t *testing.T) {
	query := buildQuery(
		types.SearchParams{
			Query: "searching",
		},
	)
	if query == "" {
		t.Errorf("Expected non-empty query, got empty")
	}

	query = buildQuery(
		types.SearchParams{
			ExactMatch: map[string]string{
				"author": "Mike Thicke",
				"year":   "2014",
			},
		},
	)
	if query == "" {
		t.Errorf("Expected non-empty query, got empty")
	}
	var unmarshalledQuery interface{}
	err := json.Unmarshal([]byte(query), &unmarshalledQuery)
	if err != nil {
		t.Errorf("Error unmarshalling query: %v", err)
	}

	query = buildQuery(
		types.SearchParams{
			Query:         "searching",
			SortField:     "modified_date",
			SortDirection: "desc",
		},
	)
	if query == "" {
		t.Errorf("Expected non-empty query, got empty")
	}
	err = json.Unmarshal([]byte(query), &unmarshalledQuery)
	if err != nil {
		t.Errorf("Error unmarshalling query: %v", err)
	}
}
