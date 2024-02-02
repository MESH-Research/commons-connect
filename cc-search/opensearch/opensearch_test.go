package opensearch

import (
	"encoding/json"
	"log"
	"reflect"
	"testing"

	opensearch "github.com/opensearch-project/opensearch-go/v2"

	"github.com/MESH-Research/commons-connect/cc-search/config"
	"github.com/MESH-Research/commons-connect/cc-search/types"
)

var testSettingsJSON = `{
	"settings": {
		"index": {
			"number_of_shards": 1,
			"number_of_replicas": 2
		}
	},
	"mappings": {
		"properties": {
			"title": { "type" : "text" },
			"author": { "type" : "text" },
			"year": { "type" : "integer" },
			"username": { "type" : "keyword" }
		}
	}
}`

var testDocumentJSON = `{
	"title": "Searching Openly",
	"author": "Mike Thicke",
	"year": "2014",
	"username": "mthicke"
}`

func cleanSetup() *opensearch.Client {
	client, err := getClientNoAuth("http://localhost:9200")
	if err != nil {
		log.Fatalf("Error getting client: %v", err)
	}
	_ = DeleteIndex(client, "test")
	return client
}

func freshIndex(client *opensearch.Client) string {
	testIndexSettings, err := parseIndexSettings([]byte(testSettingsJSON))
	if err != nil {
		log.Fatalf("Error parsing index settings: %v", err)
	}
	newIndexName, err := CreateIndex(client, "test", testIndexSettings)
	if err != nil {
		log.Fatalf("Error creating index: %v", err)
	}
	return newIndexName
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
	client := cleanSetup()
	newIndexName := freshIndex(client)
	err := DeleteIndex(client, newIndexName)
	if err != nil {
		t.Errorf("Expected no error when deleting existing index, got %v", err)
	}
	err = DeleteIndex(client, newIndexName)
	if err == nil {
		t.Errorf("Expected error when deleting non-existing index, got nil")
	}
}

func TestCreateIndex(t *testing.T) {
	client := cleanSetup()
	testIndexSettings, err := parseIndexSettings([]byte(testSettingsJSON))
	if err != nil {
		t.Errorf("Error parsing index settings: %v", err)
	}
	newIndexName, err := CreateIndex(client, "test", testIndexSettings)
	if err != nil {
		t.Errorf("Error creating index: %v", err)
	}
	if newIndexName != "test" {
		t.Errorf("Expected test, got %s", newIndexName)
	}
}

func TestIndexDocument(t *testing.T) {
	client := cleanSetup()
	testIndexName := freshIndex(client)
	var testDocument types.Document
	err := json.Unmarshal([]byte(testDocumentJSON), &testDocument)
	if err != nil {
		t.Errorf("Error unmarshalling JSON: %v", err)
	}
	docID, err := IndexDocument(client, testIndexName, testDocument)
	if err != nil {
		t.Errorf("Error indexing document: %v", err)
	}
	if reflect.TypeOf(docID).String() != "string" {
		t.Errorf("Expected string, got %v", reflect.TypeOf(docID))
	}
	if docID == "" {
		t.Errorf("Expected non-empty string, got empty string")
	}
}

func TestGetAWSClient(t *testing.T) {
	config.Init()
	conf := config.GetConfig()
	client, err := getClientUserPass(
		conf.GetString("os_endpoint"),
		conf.GetString("os_user"),
		conf.GetString("os_pass"),
	)
	if err != nil {
		t.Errorf("Error getting client: %v", err)
	}
	if client == nil {
		t.Errorf("Expected non-nil client, got nil")
	}
}

func TestBasicSearch(t *testing.T) {
	client := cleanSetup()
	testIndexName := freshIndex(client)
	var testDocument types.Document
	err := json.Unmarshal([]byte(testDocumentJSON), &testDocument)
	if err != nil {
		t.Errorf("Error unmarshalling JSON: %v", err)
	}
	_, err = IndexDocument(client, testIndexName, testDocument)
	if err != nil {
		t.Errorf("Error indexing document: %v", err)
	}
	result, err := BasicSearch(client, testIndexName, "searching")
	if err != nil {
		t.Errorf("Error searching: %v", err)
	}
	if result == nil {
		t.Errorf("Expected non-nil result, got nil")
	}
}
