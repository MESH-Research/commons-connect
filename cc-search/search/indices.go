package search

import (
	"context"
	_ "embed"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"log"
	"strings"

	"github.com/MESH-Research/commons-connect/cc-search/types"
	opensearchapi "github.com/opensearch-project/opensearch-go/v2/opensearchapi"
)

func MaybeCreateIndex(searcher *types.Searcher) error {
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
	settings, err := getIndexSettings()
	if err != nil {
		return err
	}
	return CreateIndex(searcher, settings)
}

func CreateIndex(searcher *types.Searcher, settings types.OSIndexSettings) error {
	requestBody, err := json.Marshal(settings)
	if err != nil {
		return err
	}
	req := opensearchapi.IndicesCreateRequest{
		Index: searcher.IndexName,
		Body:  strings.NewReader(string(requestBody)),
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
	err := DeleteIndex(searcher)
	if err != nil {
		return err
	}
	settings, err := getIndexSettings()
	if err != nil {
		return err
	}
	return CreateIndex(searcher, settings)
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
	if response.StatusCode != 200 {
		return fmt.Errorf(`non-200 status code: %v`, response.StatusCode)
	}
	return nil
}

func GetIndexInfo(searcher *types.Searcher) (types.OSIndexSettings, error) {
	req := opensearchapi.IndicesGetRequest{
		Index: []string{searcher.IndexName},
	}
	response, err := req.Do(context.Background(), searcher.Client)
	if err != nil {
		log.Println(`Error getting index settings: `, err)
		return types.OSIndexSettings{}, err
	}
	defer response.Body.Close()
	var indexSettings map[string]types.OSIndexSettings
	err = json.NewDecoder(response.Body).Decode(&indexSettings)
	if err != nil {
		log.Println(`Error decoding index settings: `, err.Error())
		return types.OSIndexSettings{}, err
	}

	return indexSettings[searcher.IndexName], nil
}

//go:embed index_settings.json
var indexSettings []byte

func getIndexSettings() (types.OSIndexSettings, error) {
	var settings types.OSIndexSettings
	err := json.Unmarshal(indexSettings, &settings)
	if err != nil {
		return types.OSIndexSettings{}, err
	}
	return settings, nil
}
