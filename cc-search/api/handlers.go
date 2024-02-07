package api

import (
	"encoding/json"
	"log"
	"net/http"

	"github.com/gin-gonic/gin"

	"github.com/MESH-Research/commons-connect/cc-search/opensearch"
	"github.com/MESH-Research/commons-connect/cc-search/types"
)

func handlePing(c *gin.Context) {
	c.String(http.StatusOK, "pong")
}

func handleNewDocument(c *gin.Context) {
	searcher := c.MustGet("searcher").(types.Searcher)
	body := c.Request.Body
	var newDocument types.Document
	err := json.NewDecoder(body).Decode(&newDocument)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}
	if newDocument.ID != "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "ID should not be provided for new documents"})
		return
	}
	indexedDocument, err := opensearch.IndexDocument(
		searcher,
		newDocument,
	)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	log.Println("Received document ID: ", indexedDocument.ID)
	c.JSON(http.StatusOK, filterDocument(newDocument))
}

func handleUpdateDocument(c *gin.Context) {
	body := c.Request.Body
	var updatedDocument types.Document
	err := json.NewDecoder(body).Decode(&updatedDocument)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}
	if updatedDocument.ID == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "ID is required"})
		return
	}
	log.Println("Received document ID: ", updatedDocument.ID)
	c.JSON(http.StatusOK, filterDocument(updatedDocument))
}

func handleGetDocument(c *gin.Context) {
	id := c.Param("id")
	if id == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "ID is required"})
		return
	}
	log.Println("Received document ID: ", id)
	document := types.Document{
		ID:         id,
		Title:      "Test Document",
		PrimaryURL: "https://example.com",
	}
	c.JSON(http.StatusOK, filterDocument(document))
}

// Helper function to filter out unnecessary fields from the document for
// the response.
func filterDocument(originalDocument types.Document) types.FilteredDocument {
	filteredDocument := types.FilteredDocument{
		ID:         originalDocument.ID,
		Title:      originalDocument.Title,
		PrimaryURL: originalDocument.PrimaryURL,
	}
	return filteredDocument
}
