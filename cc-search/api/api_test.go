package api

import (
	"github.com/MESH-Research/commons-connect/cc-search/config"
	"github.com/MESH-Research/commons-connect/cc-search/opensearch"
	"github.com/gin-gonic/gin"
)

func setupTestRouter() *gin.Engine {
	conf := config.GetConfig()
	conf.ClientMode = `noauth`
	searcher := opensearch.GetSearcher(conf)
	return SetupRouter(searcher, conf)
}
