package api

import (
	"github.com/MESH-Research/commons-connect/cc-search/opensearch"
	"github.com/MESH-Research/commons-connect/cc-search/types"
	"github.com/gin-gonic/gin"
)

func setupTestRouter() *gin.Engine {
	conf := types.Config{
		SearchEndpoint: "http://localhost:9200",
		APIKey:         "12345",
		IndexName:      "test",
		ClientMode:     "noauth",
	}
	searcher := opensearch.GetSearcher(conf)
	return SetupRouter(searcher, conf)
}
