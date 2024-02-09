package e2e_tests

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"net/http/httptest"
	"testing"

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
