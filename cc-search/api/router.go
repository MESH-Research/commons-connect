package api

import (
	"net/http"

	"github.com/MESH-Research/commons-connect/cc-search/types"
	"github.com/gin-gonic/gin"
)

func SetupRouter(searcher types.Searcher, conf types.Config) *gin.Engine {
	router := gin.Default()

	router.Use(OSMiddleware(searcher))
	router.Use(ConfigMiddleware(conf))

	router.GET("/", func(c *gin.Context) {
		c.String(http.StatusOK, "OK")
	})

	v1 := router.Group("/v1")

	v1.GET("/ping", validateAPIToken, handlePing)

	v1.GET("/index", validateAPIToken, handleGetIndex)
	v1.POST("/index", validateAdminAPIToken, handleResetIndex)

	v1.GET("/documents/:id", handleGetDocument)
	v1.POST("/documents", validateAPIToken, handleNewDocument)
	v1.PUT("/documents/:id", validateAdminAPIToken, handleUpdateDocument)

	return router
}

func OSMiddleware(searcher types.Searcher) gin.HandlerFunc {
	return func(c *gin.Context) {
		c.Set("searcher", searcher)
		c.Next()
	}
}

func ConfigMiddleware(conf types.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		c.Set("config", conf)
		c.Next()
	}
}
