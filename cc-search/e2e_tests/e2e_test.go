package e2e_tests

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"net/http/httptest"
	"testing"
	"time"

	"github.com/MESH-Research/commons-connect/cc-search/config"
	"github.com/MESH-Research/commons-connect/cc-search/search"
	"github.com/MESH-Research/commons-connect/cc-search/types"
	"github.com/go-playground/assert/v2"
)

func TestPing(t *testing.T) {
	conf := config.GetConfig()
	router := setupTestRouter()
	w := httptest.NewRecorder()
	req, _ := http.NewRequest("GET", "/v1/ping", nil)
	token := fmt.Sprintf("Bearer %s", conf.APIKey)
	req.Header.Set("Authorization", token)
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)
	assert.Equal(t, "pong", w.Body.String())
}

func TestGetIndex(t *testing.T) {
	conf := config.GetConfig()
	router := setupTestRouter()
	w := httptest.NewRecorder()
	req, _ := http.NewRequest("GET", "/v1/index", nil)
	token := fmt.Sprintf("Bearer %s", conf.APIKey)
	req.Header.Set("Authorization", token)
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)
	var info types.OSIndexSettings
	err := json.Unmarshal(w.Body.Bytes(), &info)
	if err != nil {
		t.Fatalf("Error decoding index info: %v", err)
	}
}

func TestResetIndex(t *testing.T) {
	conf := config.GetConfig()
	router := setupTestRouter()
	w := httptest.NewRecorder()
	req, _ := http.NewRequest("POST", "/v1/index", nil)
	token := fmt.Sprintf("Bearer %s", conf.AdminAPIKey)
	req.Header.Set("Authorization", token)
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)
}

func TestNewDocument(t *testing.T) {
	conf := config.GetConfig()
	router := setupTestRouter()
	data := getTestFileReader("single_test_doc.json")
	w := httptest.NewRecorder()
	req, _ := http.NewRequest("POST", "/v1/documents", data)
	req.Header.Set("Authorization", fmt.Sprintf("Bearer %s", conf.APIKey))
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)
	var responseJSON map[string]interface{}
	err := json.NewDecoder(w.Body).Decode(&responseJSON)
	if err != nil {
		t.Fatalf("Error decoding response: %v", err)
	}
	if responseJSON["ID"] == `` {
		t.Fatalf("Response did not contain an ID")
	}
}

func TestUpdateDocument(t *testing.T) {
	conf := config.GetConfig()
	searcher := search.GetSearcher(conf)
	newDocument := getSingleTestDocument("single_test_doc.json")
	indexedDocument, err := search.IndexDocument(searcher, newDocument)
	if err != nil {
		t.Fatalf("Error indexing document: %v", err)
	}
	indexedDocument.Title = `Updated Title`

	router := setupTestRouter()
	w := httptest.NewRecorder()
	req, err := http.NewRequest("PUT", "/v1/documents/"+indexedDocument.ID, nil)
	if err != nil {
		t.Fatalf("Error creating request: %v", err)
	}
	req.Header.Set("Authorization", fmt.Sprintf("Bearer %s", conf.APIKey))
	buf := new(bytes.Buffer)
	err = json.NewEncoder(buf).Encode(indexedDocument)
	if err != nil {
		t.Fatalf("Error encoding document: %v", err)
	}
	req.Body = io.NopCloser(buf)
	router.ServeHTTP(w, req)

	assert.Equal(t, 200, w.Code)

	updatedDocument, err := search.GetDocument(searcher, indexedDocument.ID)
	if err != nil {
		t.Fatalf("Error getting document: %v", err)
	}
	if updatedDocument.Title != indexedDocument.Title {
		t.Fatalf("Expected title %s, got %s", indexedDocument.Title, updatedDocument.Title)
	}
}

func TestGetDocument(t *testing.T) {
	conf := config.GetConfig()
	searcher := search.GetSearcher(conf)
	newDocument := getSingleTestDocument("single_test_doc.json")
	indexedDocument, err := search.IndexDocument(searcher, newDocument)
	if err != nil {
		t.Fatalf("Error indexing document: %v", err)
	}

	router := setupTestRouter()
	w := httptest.NewRecorder()
	req, err := http.NewRequest("GET", "/v1/documents/"+indexedDocument.ID, nil)
	if err != nil {
		t.Fatalf("Error creating request: %v", err)
	}
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)
	var doc types.Document
	err = json.NewDecoder(w.Body).Decode(&doc)
	if err != nil {
		t.Fatalf("Error decoding document: %v", err)
	}
	if doc.ID != indexedDocument.ID {
		t.Fatalf("Expected document ID %s, got %s", indexedDocument.ID, doc.ID)
	}
	if doc.PrimaryURL != indexedDocument.PrimaryURL {
		t.Fatalf("Expected document URL %s, got %s", indexedDocument.PrimaryURL, doc.PrimaryURL)
	}
	if doc.Title != indexedDocument.Title {
		t.Fatalf("Expected document title %s, got %s", indexedDocument.Title, doc.Title)
	}
}

func TestGetDocumentWithFilteredFields(t *testing.T) {
	conf := config.GetConfig()
	searcher := search.GetSearcher(conf)
	newDocument := getSingleTestDocument("single_test_doc.json")
	indexedDocument, err := search.IndexDocument(searcher, newDocument)
	if err != nil {
		t.Fatalf("Error indexing document: %v", err)
	}

	router := setupTestRouter()
	w := httptest.NewRecorder()
	req, _ := http.NewRequest("GET", "/v1/documents/"+indexedDocument.ID+"?fields=title,description", nil)
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)
	var doc types.Document
	err = json.NewDecoder(w.Body).Decode(&doc)
	if err != nil {
		t.Fatalf("Error decoding document: %v", err)
	}
	if doc.Title != indexedDocument.Title {
		t.Fatalf("Expected document title %s, got %s", indexedDocument.Title, doc.Title)
	}
	if doc.Description != indexedDocument.Description {
		t.Fatalf("Expected document description %s, got %s", indexedDocument.Description, doc.Description)
	}
	if doc.PrimaryURL != `` {
		t.Fatalf("Expected no URL, got %s", doc.PrimaryURL)
	}
	if doc.Content != `` {
		t.Fatalf("Expected no content, got %s", doc.Content)
	}
}

func TestDeleteDocument(t *testing.T) {
	conf := config.GetConfig()
	searcher := search.GetSearcher(conf)
	newDocument := getSingleTestDocument("single_test_doc.json")
	indexedDocument, err := search.IndexDocument(searcher, newDocument)
	if err != nil {
		t.Fatalf("Error indexing document: %v", err)
	}

	router := setupTestRouter()
	w := httptest.NewRecorder()
	req, _ := http.NewRequest("DELETE", "/v1/documents/"+indexedDocument.ID, nil)
	req.Header.Set("Authorization", fmt.Sprintf("Bearer %s", conf.APIKey))
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)
}

func TestBulkIndexDocument(t *testing.T) {
	conf := config.GetConfig()
	router := setupTestRouter()
	data := getTestFileReader("small_test_doc_collection.json")
	w := httptest.NewRecorder()
	req, _ := http.NewRequest("POST", "/v1/documents/bulk", data)
	req.Header.Set("Authorization", fmt.Sprintf("Bearer %s", conf.APIKey))
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)

	var responseJSON []types.Document
	err := json.NewDecoder(w.Body).Decode(&responseJSON)
	if err != nil {
		t.Fatalf("Error decoding response: %v", err)
	}
	if len(responseJSON) != 20 {
		t.Fatalf("Expected 20 documents, got %d", len(responseJSON))
	}
	for _, doc := range responseJSON {
		if doc.ID == `` {
			t.Fatalf("Response did not contain an ID")
		}
		if doc.Title == `` {
			t.Fatalf("Response did not contain a title")
		}
		if doc.PrimaryURL == `` {
			t.Fatalf("Response did not contain a URL")
		}
		if doc.Content != `` {
			t.Fatalf("Response contained content")
		}
	}
}

func TestBasicSearch(t *testing.T) {
	conf := config.GetConfig()
	router := setupTestRouter()
	resetIndex()
	data := getTestFileReader("small_test_doc_collection.json")
	w := httptest.NewRecorder()
	req, _ := http.NewRequest("POST", "/v1/documents/bulk", data)
	req.Header.Set("Authorization", fmt.Sprintf("Bearer %s", conf.APIKey))
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)

	req, _ = http.NewRequest("GET", "/v1/search?q=art", nil)
	w = httptest.NewRecorder()
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)
	var results []types.Document
	err := json.NewDecoder(w.Body).Decode(&results)
	if err != nil {
		t.Fatalf("Error decoding search results: %v", err)
	}
	if len(results) < 1 {
		t.Fatalf("Expected at least one result, got %d", len(results))
	}
}

func TestExactMatchSearch(t *testing.T) {
	conf := config.GetConfig()
	router := setupTestRouter()
	resetIndex()
	data := getTestFileReader("small_test_doc_collection.json")
	w := httptest.NewRecorder()
	req, _ := http.NewRequest("POST", "/v1/documents/bulk", data)
	req.Header.Set("Authorization", fmt.Sprintf("Bearer %s", conf.APIKey))
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)
	// Pause to allow indexing to complete
	time.Sleep(2 * time.Second)
	req, _ = http.NewRequest("GET", "/v1/search?owner_username=reginald", nil)
	w = httptest.NewRecorder()
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)
	var results []types.Document
	err := json.NewDecoder(w.Body).Decode(&results)
	if err != nil {
		t.Fatalf("Error decoding search results: %v", err)
	}
	if len(results) != 2 {
		t.Fatalf("Expected 2 results, got %d", len(results))
	}
}

func TestFilteredSearch(t *testing.T) {
	conf := config.GetConfig()
	router := setupTestRouter()
	resetIndex()
	data := getTestFileReader("small_test_doc_collection.json")
	w := httptest.NewRecorder()
	req, _ := http.NewRequest("POST", "/v1/documents/bulk", data)
	req.Header.Set("Authorization", fmt.Sprintf("Bearer %s", conf.APIKey))
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)
	// Pause to allow indexing to complete
	time.Sleep(2 * time.Second)
	req, _ = http.NewRequest("GET", "/v1/search?owner_username=reginald&fields=title,owner_username", nil)
	w = httptest.NewRecorder()
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)
	var results []types.Document
	err := json.NewDecoder(w.Body).Decode(&results)
	if err != nil {
		t.Fatalf("Error decoding search results: %v", err)
	}
	if len(results) != 2 {
		t.Fatalf("Expected 2 results, got %d", len(results))
	}
	for _, doc := range results {
		if doc.Title == `` {
			t.Fatalf("Expected title, got empty string")
		}
		if doc.OwnerUsername == `` {
			t.Fatalf("Expected owner username, got empty string")
		}
		if doc.PrimaryURL != `` {
			t.Fatalf("Expected no URL, got %s", doc.PrimaryURL)
		}
	}
}

func TestSearchSortedByDate(t *testing.T) {
	conf := config.GetConfig()
	router := setupTestRouter()
	resetIndex()
	data := getTestFileReader("small_test_doc_collection.json")
	w := httptest.NewRecorder()
	req, _ := http.NewRequest("POST", "/v1/documents/bulk", data)
	req.Header.Set("Authorization", fmt.Sprintf("Bearer %s", conf.APIKey))
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)
	// Pause to allow indexing to complete
	time.Sleep(2 * time.Second)
	req, _ = http.NewRequest("GET", "/v1/search?q=art&sort_by=publication_date&sort_dir=desc", nil)
	w = httptest.NewRecorder()
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)
	var results []types.Document
	err := json.NewDecoder(w.Body).Decode(&results)
	if err != nil {
		t.Fatalf("Error decoding search results: %v", err)
	}
	if len(results) == 0 {
		t.Fatalf("No results returned")
	}
	for i := 0; i < len(results)-1; i++ {
		if results[i].PublicationDate < results[i+1].PublicationDate {
			t.Fatalf("Results not sorted by date")
		}
	}
}

func TestSearchDateRange(t *testing.T) {
	conf := config.GetConfig()
	router := setupTestRouter()
	resetIndex()
	data := getTestFileReader("small_test_doc_collection.json")
	w := httptest.NewRecorder()
	req, _ := http.NewRequest("POST", "/v1/documents/bulk", data)
	req.Header.Set("Authorization", fmt.Sprintf("Bearer %s", conf.APIKey))
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)
	// Pause to allow indexing to complete
	time.Sleep(2 * time.Second)
	req, _ = http.NewRequest("GET", "/v1/search?q=art&start_date=2021-01-01&end_date=2021-12-31", nil)
	w = httptest.NewRecorder()
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)
	var results []types.Document
	err := json.NewDecoder(w.Body).Decode(&results)
	if err != nil {
		t.Fatalf("Error decoding search results: %v", err)
	}
	if len(results) == 0 {
		t.Fatalf("No results returned")
	}
	for _, doc := range results {
		if doc.PublicationDate < `2021-01-01` || doc.PublicationDate > `2021-01-31` {
			t.Fatalf("Document outside of date range")
		}
	}
}
