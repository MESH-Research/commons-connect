package api

import (
	"fmt"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/MESH-Research/commons-connect/cc-search/config"
	"github.com/go-playground/assert/v2"
)

func TestValidateToken(t *testing.T) {
	conf := config.GetConfig()
	router := setupTestRouter()

	w := httptest.NewRecorder()
	req, _ := http.NewRequest("GET", "/v1/ping", nil)
	token := fmt.Sprintf("Bearer %s", conf.APIKey)
	req.Header.Set("Authorization", token)
	router.ServeHTTP(w, req)

	assert.Equal(t, 200, w.Code)
}
