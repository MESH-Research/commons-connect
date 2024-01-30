package main

import (
	"fmt"

	"github.com/MESH-Research/commons-connect/cc-search/api"
)

func main() {
	fmt.Println("Hello, world!")
	router := api.SetupRouter()
	router.Run(":80")
}
