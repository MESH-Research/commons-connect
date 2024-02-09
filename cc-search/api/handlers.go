package api

import (
	"encoding/json"
	"log"
	"net/http"
	"reflect"
	"strings"

	"github.com/gin-gonic/gin"

	"github.com/MESH-Research/commons-connect/cc-search/opensearch"
	"github.com/MESH-Research/commons-connect/cc-search/types"
)

func handlePing(c *gin.Context) {
	c.String(http.StatusOK, "pong")
}

func handleGetIndex(c *gin.Context) {
	searcher := c.MustGet("searcher").(types.Searcher)
	info, err := opensearch.GetIndexInfo(&searcher)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	c.JSON(http.StatusOK, info)
}

func handleResetIndex(c *gin.Context) {
	searcher := c.MustGet("searcher").(types.Searcher)
	err := opensearch.ResetIndex(&searcher)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	c.JSON(http.StatusOK, gin.H{"message": "Index reset"})
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
	c.JSON(http.StatusOK, filterDocument(*indexedDocument, []string{"ID", "Title", "PrimaryURL"}))
}

func handleUpdateDocument(c *gin.Context) {
	searcher := c.MustGet("searcher").(types.Searcher)
	body := c.Request.Body
	id := c.Param("id")
	var updatedDocument types.Document
	err := json.NewDecoder(body).Decode(&updatedDocument)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}
	updatedDocument.ID = id
	err = opensearch.UpdateDocument(searcher, updatedDocument)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	c.JSON(http.StatusOK, gin.H{"message": "Document updated"})
}

func handleGetDocument(c *gin.Context) {
	id := c.Param("id")
	if id == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "ID is required"})
		return
	}
	searcher := c.MustGet("searcher").(types.Searcher)
	document, err := opensearch.GetDocument(searcher, id)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	fieldsQuery := c.Query("fields")
	fields := strings.Split(fieldsQuery, ",")
	if len(fields) > 0 && fields[0] != "" {
		document = filterDocument(*document, fields)
	}
	c.JSON(http.StatusOK, document)
}

// Helper function to filter out unnecessary fields from the document for
// the response.
func filterDocument(originalDocument types.Document, fields []string) *types.Document {
	filteredDocument := types.Document{}
	for _, field := range fields {
		fieldValue := reflect.ValueOf(originalDocument).FieldByName(field)
		if !fieldValue.IsValid() {
			continue
		}
		reflect.ValueOf(&filteredDocument).Elem().FieldByName(field).Set(fieldValue)
	}
	return &filteredDocument
}
