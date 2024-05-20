package api

import (
	"encoding/json"
	"log"
	"net/http"
	"strconv"
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
	indexedDocument.FilterOut([]string{"Content"})
	c.JSON(http.StatusOK, indexedDocument)
}

func handleBulkNewDocuments(c *gin.Context) {
	searcher := c.MustGet("searcher").(types.Searcher)
	body := c.Request.Body
	var newDocuments []types.Document
	err := json.NewDecoder(body).Decode(&newDocuments)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}
	for _, document := range newDocuments {
		if document.ID != "" {
			c.JSON(http.StatusBadRequest, gin.H{"error": "ID should not be provided for new documents"})
			return
		}
	}
	indexedDocuments, err := search.BulkIndexDocuments(
		searcher,
		newDocuments,
	)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	for i := range indexedDocuments {
		indexedDocuments[i].FilterOut([]string{"Content"})
	}
	c.JSON(http.StatusOK, indexedDocuments)
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

func handleDeleteNode(c *gin.Context) {
	node := c.Query("network_node")
	if node == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Network node is required"})
		return
	}
	searcher := c.MustGet("searcher").(types.Searcher)
	err := search.DeleteNode(searcher, node)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	c.JSON(http.StatusOK, gin.H{"message": "Node deleted"})
}

func handleSearch(c *gin.Context) {
	searcher := c.MustGet("searcher").(types.Searcher)
	params := types.SearchParams{
		ExactMatch: make(map[string]string),
	}
	queryVals := c.Request.URL.Query()
	for key, val := range queryVals {
		switch key {
		case "q":
			params.Query = val[0]
		case "fields":
			params.ReturnFields = strings.Split(val[0], ",")
		case "search_fields":
			params.SearchFields = strings.Split(val[0], ",")
		case "start_date":
			params.StartDate = val[0]
		case "end_date":
			params.EndDate = val[0]
		case "sort_dir":
			params.SortDirection = val[0]
		case "sort_by":
			params.SortField = val[0]
		case "page":
			page, err := strconv.Atoi(val[0])
			if err != nil {
				c.JSON(http.StatusBadRequest, gin.H{"error": "Invalid page value"})
				return
			}
			params.Page = page
		case "per_page":
			perPage, err := strconv.Atoi(val[0])
			if err != nil {
				c.JSON(http.StatusBadRequest, gin.H{"error": "Invalid per_page value"})
				return
			}
			params.PerPage = perPage
		case "username":
			params.ExactMatch["contributors.username"] = val[0]
		default:
			params.ExactMatch[key] = val[0]
		}
	}
	result, err := search.Search(searcher, params)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	c.JSON(http.StatusOK, result)
}

func handleTypeAheadSearch(c *gin.Context) {
	searcher := c.MustGet("searcher").(types.Searcher)
	query := c.Query("q")
	if query == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Query is required"})
		return
	}
	result, err := search.TypeAheadSearch(searcher, query)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	c.JSON(http.StatusOK, result)
}

func handleAuthCheck(c *gin.Context) {
	c.JSON(http.StatusOK, gin.H{"message": "Authenticated"})
}

func handleAdminAuthCheck(c *gin.Context) {
	c.JSON(http.StatusOK, gin.H{"message": "Authenticated as admin"})
}
