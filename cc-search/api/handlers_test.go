package api

import (
	"bytes"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/MESH-Research/commons-connect/cc-search/types"
)

func TestHandleNewDocument(t *testing.T) {
	router := setupTestRouter()

	newDocument := types.Document{
		Title:       "Test Document",
		PrimaryURL:  "https://example.com",
		Description: "This is a test document",
	}

	w := httptest.NewRecorder()
	b, err := json.Marshal(newDocument)
	if err != nil {
		t.Fatalf("Error encoding document: %v", err)
	}
	body := bytes.NewReader(b)
	req, _ := http.NewRequest("POST", "/v1/documents", body)
	req.Header.Set("Authorization", "Bearer 12345")
	router.ServeHTTP(w, req)
}
