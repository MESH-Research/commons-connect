package search

import (
	"bytes"
	"context"
	_ "embed"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"log"

	"github.com/MESH-Research/commons-connect/cc-search/types"
	opensearchapi "github.com/opensearch-project/opensearch-go/v2/opensearchapi"
)

//go:embed index_settings.json
var indexSettings []byte

func MaybeCreateIndex(searcher *types.Searcher) error {
	return MaybeCreateCustomIndex(searcher, indexSettings)
}

func MaybeCreateCustomIndex(searcher *types.Searcher, settingsJSON []byte) error {
	if searcher.IndexName == `` {
		return errors.New(`index name is required`)
	}
	req := opensearchapi.IndicesExistsRequest{
		Index: []string{searcher.IndexName},
	}
	response, err := req.Do(context.Background(), searcher.Client)
	if err != nil {
		return err
	}
	defer response.Body.Close()
	if response.StatusCode == 200 {
		return nil
	}
	return CreateCustomIndex(searcher, settingsJSON)
}

func CreateIndex(searcher *types.Searcher) error {
	return CreateCustomIndex(searcher, indexSettings)
}

func CreateCustomIndex(searcher *types.Searcher, settingsJSON []byte) error {
	req := opensearchapi.IndicesCreateRequest{
		Index: searcher.IndexName,
		Body:  bytes.NewReader(settingsJSON),
	}
	response, err := req.Do(context.Background(), searcher.Client)
	if err != nil {
		return err
	}
	defer response.Body.Close()
	responseText, err := io.ReadAll(response.Body)
	if err != nil {
		return err
	}
	var result map[string]interface{}
	err = json.Unmarshal(responseText, &result)
	if err != nil {
		return err
	}
	index, ok := result["index"]
	if !ok {
		log.Println(`No index in response: `, result)
		return errors.New(`no index in response: `)
	}
	searcher.IndexName = index.(string)
	return nil
}

func ResetIndex(searcher *types.Searcher) error {
	return ResetCustomIndex(searcher, indexSettings)
}

func ResetCustomIndex(searcher *types.Searcher, settingsJSON []byte) error {
	err := DeleteIndex(searcher)
	if err != nil {
		return err
	}
	return CreateCustomIndex(searcher, settingsJSON)
}

func DeleteIndex(searcher *types.Searcher) error {
	req := opensearchapi.IndicesDeleteRequest{
		Index: []string{searcher.IndexName},
	}
	response, err := req.Do(context.Background(), searcher.Client)
	if err != nil {
		return err
	}
	defer response.Body.Close()
	if response.StatusCode != 200 && response.StatusCode != 404 {
		return fmt.Errorf(`non-200, non-404 status code: %v`, response.StatusCode)
	}
	return nil
}

func GetIndexInfo(searcher *types.Searcher) (map[string]interface{}, error) {
	req := opensearchapi.IndicesGetRequest{
		Index: []string{searcher.IndexName},
	}
	response, err := req.Do(context.Background(), searcher.Client)
	if err != nil {
		log.Println(`Error getting index settings: `, err)
		return nil, err
	}
	defer response.Body.Close()
	var indexSettings map[string]interface{}
	err = json.NewDecoder(response.Body).Decode(&indexSettings)
	if err != nil {
		log.Println(`Error decoding index settings: `, err.Error())
		return nil, err
	}
	return indexSettings, nil
}
