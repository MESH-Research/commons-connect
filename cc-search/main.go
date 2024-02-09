package main

import (
	"github.com/MESH-Research/commons-connect/cc-search/api"
	"github.com/MESH-Research/commons-connect/cc-search/config"
	"github.com/MESH-Research/commons-connect/cc-search/search"
)

func main() {
	conf := config.GetConfig()
	searcher := search.GetSearcher(conf)
	search.MaybeCreateIndex(&searcher)
	router := api.SetupRouter(searcher, conf)
	router.Run(":80")
}
