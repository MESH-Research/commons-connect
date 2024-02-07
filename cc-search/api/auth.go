package api

import (
	"crypto/rand"
	"fmt"
	"log"
	"net/http"
	"strings"

	"github.com/MESH-Research/commons-connect/cc-search/types"
	"github.com/gin-gonic/gin"
)

// Helper function to generate a random token. This token is just
// sent to stdout and not used anywhere. To be used by the api it
// needs to be copied to the CC_SEARCH_API_TOKEN environment variable.
func GenerateToken(tokenLength int) {
	token := make([]byte, tokenLength)
	_, err := rand.Read(token)
	if err != nil {
		panic(err)
	}
	fmt.Printf("%x", token)
}

func validateToken(c *gin.Context) {
	conf := c.MustGet("config").(types.Config)
	if conf.APIKey == "" {
		log.Println("Failed token validation: No token set in config or ENV")
		c.AbortWithStatusJSON(http.StatusInternalServerError, gin.H{"error": "No token set"})
	}

	token := c.GetHeader("Authorization")
	if len(strings.Split(token, " ")) != 2 {
		log.Println("Failed token validation: Misformatted bearer token: ", token)
		c.AbortWithStatusJSON(http.StatusUnauthorized, gin.H{"error": "Unauthorized"})
	}
	token = strings.Split(token, " ")[1]
	if token != conf.APIKey {
		log.Println("Failed token validation: Invalid token: ", token)
		c.AbortWithStatusJSON(http.StatusUnauthorized, gin.H{"error": "Unauthorized"})
	}
	c.Next()
}
