package opensearch

import (
	"context"
	"crypto/tls"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"log"
	"net"
	"net/http"
	"os"
	"strings"
	"time"

	opensearch "github.com/opensearch-project/opensearch-go/v2"
	opensearchapi "github.com/opensearch-project/opensearch-go/v2/opensearchapi"

	"github.com/MESH-Research/commons-connect/cc-search/types"
)

type osIndexSettings struct {
	Settings *struct {
		Index *struct {
			NumberOfShards   int `json:"number_of_shards,omitempty"`
			NumberOfReplicas int `json:"number_of_replicas,omitempty"`
		} `json:"index,omitempty"`
	} `json:"settings,omitempty"`
	Mappings *struct {
		Properties map[string]struct {
			Type   string `json:"type,omitempty"`
			Store  bool   `json:"store,omitempty"`
			Index  bool   `json:"index,omitempty"`
			Fields *struct {
				Prefix *struct {
					Type string `json:"type,omitempty"`
				} `json:"prefix,omitempty"`
			} `json:"fields,omitempty"`
		} `json:"properties"`
	} `json:"mappings,omitempty"`
}

type indexDocumentResponse struct {
	Index   string `json:"_index"`
	ID      string `json:"_id"`
	Version int64  `json:"_version"`
	Result  string `json:"result"`
	Shards  struct {
		Total      int64 `json:"total"`
		Successful int64 `json:"successful"`
		Failed     int64 `json:"failed"`
	} `json:"_shards"`
	SeqNo       int64 `json:"_seq_no"`
	PrimaryTerm int64 `json:"_primary_term"`
}

func getIndexSettings() (osIndexSettings, error) {
	data, err := os.ReadFile("index_settings.json")
	if err != nil {
		return osIndexSettings{}, err
	}
	settings, err := parseIndexSettings(data)
	return settings, err
}

func parseIndexSettings(data []byte) (osIndexSettings, error) {
	var settings osIndexSettings
	err := json.Unmarshal(data, &settings)
	if err != nil {
		return osIndexSettings{}, err
	}
	return settings, nil
}

func getClientNoAuth(clientURL string) (*opensearch.Client, error) {
	client, err := opensearch.NewClient(opensearch.Config{
		Transport: &http.Transport{
			TLSClientConfig: &tls.Config{InsecureSkipVerify: true},
		},
		Addresses: []string{clientURL},
	})
	return client, err
}

func getClientUserPass(clientURL string, user string, pass string) (*opensearch.Client, error) {
	client, err := opensearch.NewClient(opensearch.Config{
		Transport: &http.Transport{
			MaxIdleConnsPerHost:   10,
			ResponseHeaderTimeout: time.Second,
			DialContext:           (&net.Dialer{Timeout: time.Second}).DialContext,
			TLSClientConfig: &tls.Config{
				MinVersion: tls.VersionTLS11,
			},
		},
		Addresses: []string{clientURL},
		Username:  user,
		Password:  pass,
	})
	return client, err
}

// CreateIndex creates an index with the given name and settings
// and returns the name of the index created.
//
// If the index already exists, an error is returned.
func CreateIndex(client *opensearch.Client, indexName string, settings osIndexSettings) (string, error) {
	requestBody, err := json.Marshal(settings)
	if err != nil {
		return ``, err
	}
	req := opensearchapi.IndicesCreateRequest{
		Index: indexName,
		Body:  strings.NewReader(string(requestBody)),
	}
	response, err := req.Do(context.Background(), client)
	if err != nil {
		return ``, err
	}
	responseText, err := io.ReadAll(response.Body)
	if err != nil {
		return ``, err
	}
	var result map[string]interface{}
	err = json.Unmarshal(responseText, &result)
	if err != nil {
		return ``, err
	}
	index, ok := result["index"]
	if !ok {
		log.Println(`No index in response: `, result)
		return ``, errors.New(`no index in response: `)
	}
	return index.(string), nil
}

func DeleteIndex(client *opensearch.Client, indexName string) error {
	req := opensearchapi.IndicesDeleteRequest{
		Index: []string{indexName},
	}
	_, err := req.Do(context.Background(), client)
	return err
}

// Indexes a new document and returns its ID. IDs have the form 'yQQEYY0B1VMrrWgmZN1j'.
// This is not for updating existing documents.
func IndexDocument(client *opensearch.Client, indexName string, document types.Document) (string, error) {
	body, err := json.Marshal(document)
	if err != nil {
		return ``, errors.New(`error marshalling document: ` + err.Error())
	}
	req := opensearchapi.IndexRequest{
		Index: indexName,
		Body:  strings.NewReader(string(body)),
	}
	response, err := req.Do(context.Background(), client)
	if err != nil {
		return ``, errors.New(`error indexing document: ` + err.Error())
	}
	body, err = io.ReadAll(response.Body)
	if err != nil {
		return ``, errors.New(`error reading response body: ` + err.Error())
	}
	var res indexDocumentResponse
	err = json.Unmarshal(body, &res)
	if err != nil {
		return ``, errors.New(`error unmarshalling response: ` + err.Error())
	}
	return res.ID, nil
}

func UpdateDocument(client *opensearch.Client, indexName string, document types.Document, id string) error {
	body, err := json.Marshal(document)
	if err != nil {
		return errors.New(`error marshalling document: ` + err.Error())
	}
	req := opensearchapi.IndexRequest{
		Index:      indexName,
		Body:       strings.NewReader(string(body)),
		DocumentID: id,
	}
	_, err = req.Do(context.Background(), client)
	return err
}

func DeleteDocument(client *opensearch.Client, indexName string, id string) error {
	req := opensearchapi.DeleteRequest{
		Index:      indexName,
		DocumentID: id,
	}
	_, err := req.Do(context.Background(), client)
	return err
}

func RawSearch(client *opensearch.Client, indexName string, query string) (string, error) {
	req := opensearchapi.SearchRequest{
		Index: []string{indexName},
		Body:  strings.NewReader(query),
	}
	response, err := req.Do(context.Background(), client)
	if err != nil {
		return ``, err
	}
	body, err := io.ReadAll(response.Body)
	if err != nil {
		return ``, err
	}
	return string(body), nil
}

func BasicSearch(client *opensearch.Client, indexName string, query string) (*types.SearchResult, error) {
	QueryJSON := fmt.Sprintf(`{
		"query": {
			"multi_match": {
				"query": "%s"
			}
		}
	}`, query)

	req := opensearchapi.SearchRequest{
		Index: []string{indexName},
		Body:  strings.NewReader(QueryJSON),
	}
	response, err := req.Do(context.Background(), client)
	if err != nil {
		return nil, err
	}
	body, err := io.ReadAll(response.Body)
	if err != nil {
		return nil, err
	}
	var searchResult types.SearchResult
	err = json.Unmarshal(body, &searchResult)
	if err != nil {
		return nil, err
	}
	return &searchResult, nil
}
