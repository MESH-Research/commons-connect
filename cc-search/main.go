package main

import (
	"github.com/MESH-Research/commons-connect/cc-search/api"
	"github.com/MESH-Research/commons-connect/cc-search/config"
	"github.com/MESH-Research/commons-connect/cc-search/opensearch"
)

func main() {
	conf := config.GetConfig()
	searcher := opensearch.GetSearcher(conf)
	opensearch.MaybeCreateIndex(&searcher)
	router := api.SetupRouter(searcher, conf)
	router.Run(":80")
}
