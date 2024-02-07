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

	v1.GET("/ping", validateToken, handlePing)

	v1.POST("/documents", validateToken, handleNewDocument)

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
