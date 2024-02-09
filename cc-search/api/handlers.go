package api

import (
	"encoding/json"
	"log"
	"net/http"
	"strings"

	"github.com/gin-gonic/gin"

	"github.com/MESH-Research/commons-connect/cc-search/search"
	"github.com/MESH-Research/commons-connect/cc-search/types"
)

func handlePing(c *gin.Context) {
	c.String(http.StatusOK, "pong")
}

func handleGetIndex(c *gin.Context) {
	searcher := c.MustGet("searcher").(types.Searcher)
	info, err := search.GetIndexInfo(&searcher)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	c.JSON(http.StatusOK, info)
}

func handleResetIndex(c *gin.Context) {
	searcher := c.MustGet("searcher").(types.Searcher)
	err := search.ResetIndex(&searcher)
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
	indexedDocument, err := search.IndexDocument(
		searcher,
		newDocument,
	)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	log.Println("Received document ID: ", indexedDocument.ID)
	indexedDocument.Filter([]string{"ID", "Title", "PrimaryURL"})
	c.JSON(http.StatusOK, indexedDocument)
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
	err = search.UpdateDocument(searcher, updatedDocument)
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
	document, err := search.GetDocument(searcher, id)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	fieldsQuery := c.Query("fields")
	fields := strings.Split(fieldsQuery, ",")
	if len(fields) > 0 && fields[0] != "" {
		document.FilterByJSON(fields)
	}
	c.JSON(http.StatusOK, document)
}

func handleDeleteDocument(c *gin.Context) {
	id := c.Param("id")
	if id == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "ID is required"})
		return
	}
	searcher := c.MustGet("searcher").(types.Searcher)
	err := search.DeleteDocument(searcher, id)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	c.JSON(http.StatusOK, gin.H{"message": "Document deleted"})
}

func handleSearch(c *gin.Context) {
	searcher := c.MustGet("searcher").(types.Searcher)
	query := c.Query("q")
	if query == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Query is required"})
		return
	}
	result, err := search.BasicSearch(searcher, query)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	c.JSON(http.StatusOK, result)
}
