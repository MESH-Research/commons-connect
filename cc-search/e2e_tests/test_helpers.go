package e2e_tests

import (
	"encoding/json"
	"io"
	"os"
	"path"
	"runtime"

	"github.com/MESH-Research/commons-connect/cc-search/api"
	"github.com/MESH-Research/commons-connect/cc-search/config"
	"github.com/MESH-Research/commons-connect/cc-search/opensearch"
	"github.com/MESH-Research/commons-connect/cc-search/types"
	"github.com/gin-gonic/gin"
)

func setupTestRouter() *gin.Engine {
	conf := config.GetConfig()
	searcher := opensearch.GetSearcher(conf)
	opensearch.MaybeCreateIndex(&searcher)
	router := api.SetupRouter(searcher, conf)
	return router
}

func getSingleTestDocument(filename string) types.Document {
	data := getTestFileReader(filename)
	var doc types.Document
	err := json.NewDecoder(data).Decode(&doc)
	if err != nil {
		panic(err)
	}
	return doc
}

func getTestFileReader(filename string) io.Reader {
	_, thisFile, _, _ := runtime.Caller(0)
	dir := path.Dir(thisFile)
	filePath := dir + "/data/" + filename
	reader, err := os.Open(filePath)
	if err != nil {
		panic(err)
	}
	return reader
}
