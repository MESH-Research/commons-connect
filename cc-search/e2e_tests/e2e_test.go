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

	"github.com/stretchr/testify/assert"

	"github.com/MESH-Research/commons-connect/cc-search/config"
	"github.com/MESH-Research/commons-connect/cc-search/search"
	"github.com/MESH-Research/commons-connect/cc-search/types"
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
	var responseIndex map[string]interface{}
	responseBody, err := io.ReadAll(w.Body)
	if err != nil {
		t.Fatalf("Error reading response body: %v", err)
	}
	err = json.Unmarshal(responseBody, &responseIndex)
	if err != nil {
		t.Fatalf("Error decoding response: %v", err)
	}
	assert.Contains(t, responseIndex, "dev-search")
	assert.NotContains(t, responseIndex, "foo")
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
	indexedDocument := getSingleTestDocument("single_test_doc.json")
	w := httptest.NewRecorder()
	req, _ := http.NewRequest("POST", "/v1/documents", data)
	req.Header.Set("Authorization", fmt.Sprintf("Bearer %s", conf.APIKey))
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)
	var responseDoc types.Document
	err := json.NewDecoder(w.Body).Decode(&responseDoc)
	if err != nil {
		t.Fatalf("Error decoding response: %v", err)
	}
	if responseDoc.ID == `` {
		t.Fatalf("Response did not contain an ID")
	}
	if responseDoc.InternalID == `` {
		t.Fatalf("Response did not contain an internal ID")
	}
	req, _ = http.NewRequest("GET", "/v1/documents/"+responseDoc.ID, nil)
	w = httptest.NewRecorder()
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)
	var retrievedDoc types.Document
	err = json.NewDecoder(w.Body).Decode(&retrievedDoc)
	if err != nil {
		t.Fatalf("Error decoding document: %v", err)
	}
	if retrievedDoc.ID != responseDoc.ID {
		t.Fatalf("Expected document ID %s, got %s", responseDoc.ID, retrievedDoc.ID)
	}
	if retrievedDoc.PrimaryURL != indexedDocument.PrimaryURL {
		t.Fatalf("Expected document URL %s, got %s", indexedDocument.PrimaryURL, retrievedDoc.PrimaryURL)
	}
	if retrievedDoc.Owner.Username != indexedDocument.Owner.Username {
		t.Fatalf("Expected document owner %s, got %s", indexedDocument.Owner.Username, retrievedDoc.Owner.Username)
	}
	if retrievedDoc.Owner.URL != indexedDocument.Owner.URL {
		t.Fatalf("Expected document owner URL %s, got %s", indexedDocument.Owner.URL, retrievedDoc.Owner.URL)
	}
	if retrievedDoc.InternalID != responseDoc.InternalID {
		t.Fatalf("Expected internal ID %s, got %s", responseDoc.InternalID, retrievedDoc.InternalID)
	}
	assert.Equal(t, len(indexedDocument.Contributors), len(retrievedDoc.Contributors))
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
	if updatedDocument.InternalID != indexedDocument.InternalID {
		t.Fatalf("Expected internal ID %s, got %s", indexedDocument.InternalID, updatedDocument.InternalID)
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
	if doc.Owner.Username != indexedDocument.Owner.Username {
		t.Fatalf("Expected document owner %s, got %s", indexedDocument.Owner.Username, doc.Owner.Username)
	}
	if doc.Owner.URL != indexedDocument.Owner.URL {
		t.Fatalf("Expected document owner URL %s, got %s", indexedDocument.Owner.URL, doc.Owner.URL)
	}
	if doc.Contributors[0].Username != indexedDocument.Contributors[0].Username {
		t.Fatalf("Expected document other user %s, got %s", indexedDocument.Contributors[0].Username, doc.Contributors[0].Username)
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
		if doc.InternalID == `` {
			t.Fatalf("Response did not contain an internal ID")
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
	// Pause to allow indexing to complete
	time.Sleep(1 * time.Second)
	req, _ = http.NewRequest("GET", "/v1/search?q=art", nil)
	w = httptest.NewRecorder()
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)
	var response types.SearchResponse
	err := json.NewDecoder(w.Body).Decode(&response)
	if err != nil {
		t.Fatalf("Error decoding search response: %v", err)
	}
	if len(response.Hits) < 1 {
		t.Fatalf("Expected at least one result, got %d", len(response.Hits))
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
	time.Sleep(1 * time.Second)
	req, _ = http.NewRequest("GET", "/v1/search?owner.username=reginald", nil)
	w = httptest.NewRecorder()
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)
	var response types.SearchResponse
	err := json.NewDecoder(w.Body).Decode(&response)
	if err != nil {
		t.Fatalf("Error decoding search response: %v", err)
	}
	if len(response.Hits) != 2 {
		t.Fatalf("Expected 2 results, got %d", len(response.Hits))
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
	time.Sleep(1 * time.Second)
	req, _ = http.NewRequest("GET", "/v1/search?owner.username=reginald&fields=title,owner", nil)
	w = httptest.NewRecorder()
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)
	var response types.SearchResponse
	err := json.NewDecoder(w.Body).Decode(&response)
	if err != nil {
		t.Fatalf("Error decoding search response: %v", err)
	}
	if len(response.Hits) != 2 {
		t.Fatalf("Expected 2 results, got %d", len(response.Hits))
	}
	for _, doc := range response.Hits {
		if doc.Title == `` {
			t.Fatalf("Expected title, got empty string")
		}
		if doc.Owner.Username == `` {
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
	time.Sleep(1 * time.Second)
	req, _ = http.NewRequest("GET", "/v1/search?q=art&sort_by=publication_date&sort_dir=desc", nil)
	w = httptest.NewRecorder()
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)
	var response types.SearchResponse
	err := json.NewDecoder(w.Body).Decode(&response)
	if err != nil {
		t.Fatalf("Error decoding search response: %v", err)
	}
	if len(response.Hits) == 0 {
		t.Fatalf("No results returned")
	}
	for i := 0; i < len(response.Hits)-1; i++ {
		if response.Hits[i].PublicationDate < response.Hits[i+1].PublicationDate {
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
	time.Sleep(1 * time.Second)
	req, _ = http.NewRequest("GET", "/v1/search?q=art&start_date=2022-01-01&end_date=2022-05-31", nil)
	w = httptest.NewRecorder()
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)
	var response types.SearchResponse
	err := json.NewDecoder(w.Body).Decode(&response)
	if err != nil {
		t.Fatalf("Error decoding search response: %v", err)
	}
	if len(response.Hits) == 0 {
		t.Fatalf("No results returned")
	}
	for _, doc := range response.Hits {
		if doc.PublicationDate < `2022-01-01` || doc.PublicationDate > `2022-05-31` {
			t.Fatalf("Document outside of date range")
		}
	}
}

func TestSearchPagination(t *testing.T) {
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
	time.Sleep(1 * time.Second)
	req, _ = http.NewRequest("GET", "/v1/search?q=art", nil)
	w = httptest.NewRecorder()
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)
	var response types.SearchResponse
	err := json.NewDecoder(w.Body).Decode(&response)
	if err != nil {
		t.Fatalf("Error decoding search response: %v", err)
	}
	totalResults := len(response.Hits)
	remainingResults := totalResults
	for i := 1; remainingResults < 0; i += 1 {
		req, _ = http.NewRequest("GET", fmt.Sprintf("/v1/search?q=art&page=%d&per_page=5", i), nil)
		w = httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, 200, w.Code)
		var pageResponse types.SearchResponse
		err = json.NewDecoder(w.Body).Decode(&pageResponse)
		if err != nil {
			t.Fatalf("Error decoding search results: %v", err)
		}
		if remainingResults >= 5 {
			if len(pageResponse.Hits) != 5 {
				t.Fatalf("Expected 5 results, got %d", len(pageResponse.Hits))
			}
		} else {
			if len(pageResponse.Hits) != remainingResults {
				t.Fatalf("Expected %d results, got %d", remainingResults, len(pageResponse.Hits))
			}
		}
		remainingResults -= len(pageResponse.Hits)
	}
}

func TestTypeAheadSearch(t *testing.T) {
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
	time.Sleep(1 * time.Second)
	req, _ = http.NewRequest("GET", "/v1/typeahead?q=on", nil)
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
		if doc.Title == `` {
			t.Fatalf("Expected title, got empty string")
		}
		if doc.PrimaryURL == `` {
			t.Fatalf("Expected URL, got empty string")
		}
		if doc.Content != `` {
			t.Fatalf("Expected no content, got %s", doc.Content)
		}
	}
}

func TestUsernameSearch(t *testing.T) {
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
	time.Sleep(1 * time.Second)
	req, _ = http.NewRequest("GET", "/v1/search?username=reginald", nil)
	w = httptest.NewRecorder()
	router.ServeHTTP(w, req)
	assert.Equal(t, 200, w.Code)
	var response types.SearchResponse
	err := json.NewDecoder(w.Body).Decode(&response)
	if err != nil {
		t.Fatalf("Error decoding search response: %v", err)
	}
	if len(response.Hits) == 0 {
		t.Fatalf("No results returned")
	}
	for _, doc := range response.Hits {
		if doc.Owner.Username != `reginald` {
			t.Fatalf("Expected owner username reginald, got %s", doc.Owner.Username)
		}
	}
}
