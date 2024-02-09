package search

import (
	"context"
	"crypto/tls"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"net"
	"net/http"
	"strings"
	"time"

	osg "github.com/opensearch-project/opensearch-go/v2"
	opensearchapi "github.com/opensearch-project/opensearch-go/v2/opensearchapi"

	"github.com/MESH-Research/commons-connect/cc-search/types"
)

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

func GetSearcher(conf types.Config) types.Searcher {
	client, err := GetClient(
		conf.SearchEndpoint,
		conf.User,
		conf.Password,
		conf.ClientMode,
	)
	if err != nil {
		panic(err)
	}
	return types.Searcher{
		IndexName: conf.IndexName,
		Client:    client,
	}
}

func GetClient(clientURL string, user string, pass string, clientMode string) (*osg.Client, error) {
	if clientURL == `` {
		return nil, errors.New(`clientURL is required`)
	}
	if clientMode == `noauth` {
		return GetClientNoAuth(clientURL)
	}
	if (user == ``) || (pass == ``) {
		return nil, errors.New(`user and pass are required for basic auth mode`)
	}
	return GetClientUserPass(clientURL, user, pass)
}

func GetClientNoAuth(clientURL string) (*osg.Client, error) {
	client, err := osg.NewClient(osg.Config{
		Transport: &http.Transport{
			TLSClientConfig: &tls.Config{InsecureSkipVerify: true},
		},
		Addresses: []string{clientURL},
	})
	return client, err
}

func GetClientUserPass(clientURL string, user string, pass string) (*osg.Client, error) {
	client, err := osg.NewClient(osg.Config{
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

// Indexes a new document and returns its ID. IDs have the form 'yQQEYY0B1VMrrWgmZN1j'.
// This is not for updating existing documents.
func IndexDocument(searcher types.Searcher, document types.Document) (*types.Document, error) {
	if document.ID != `` {
		return nil, errors.New(`ID should not be provided for new documents`)
	}
	body, err := json.Marshal(document)
	if err != nil {
		return nil, errors.New(`error marshalling document: ` + err.Error())
	}
	req := opensearchapi.IndexRequest{
		Index: searcher.IndexName,
		Body:  strings.NewReader(string(body)),
	}
	response, err := req.Do(context.Background(), searcher.Client)
	if err != nil {
		return nil, errors.New(`error indexing document: ` + err.Error())
	}
	defer response.Body.Close()
	if response.StatusCode != 201 {
		bodyBytes, _ := io.ReadAll(response.Body)
		return nil, errors.New(string(bodyBytes))
	}
	var res indexDocumentResponse
	err = json.NewDecoder(response.Body).Decode(&res)
	if err != nil {
		return nil, errors.New(`error decoding response: ` + err.Error())
	}
	document.ID = res.ID
	return &document, nil
}

func BulkIndexDocuments(searcher types.Searcher, documents []types.Document) ([]types.Document, error) {
	bodyLines := []string{}
	createLine := fmt.Sprintf(`{"create":{"_index":"%s"}}`, searcher.IndexName)
	for _, document := range documents {
		body, err := json.Marshal(document)
		if err != nil {
			return nil, errors.New(`error marshalling document: ` + err.Error())
		}
		bodyLines = append(bodyLines, createLine)
		bodyLines = append(bodyLines, string(body))
	}
	body := strings.NewReader(strings.Join(bodyLines, "\n") + "\n")
	req := opensearchapi.BulkRequest{
		Body:    body,
		Timeout: time.Second * 100,
		//SourceIncludes: []string{`title`, `primary_url`},
	}
	response, err := req.Do(context.Background(), searcher.Client)
	if err != nil {
		return nil, errors.New(`error indexing documents: ` + err.Error())
	}
	defer response.Body.Close()
	if response.StatusCode != 200 {
		bodyBytes, _ := io.ReadAll(response.Body)
		return nil, errors.New(string(bodyBytes))
	}

	var result struct {
		Items []struct {
			Create struct {
				ID string `json:"_id"`
			} `json:"create"`
		} `json:"items"`
	}
	err = json.NewDecoder(response.Body).Decode(&result)
	if err != nil {
		return nil, errors.New(`error decoding response: ` + err.Error())
	}
	mgetLines := []string{}
	for _, item := range result.Items {
		mgetLines = append(mgetLines, `{"_id":"`+item.Create.ID+`"}`)
	}
	mgetBody := strings.NewReader(`{"docs":[` + strings.Join(mgetLines, ",") + `]}`)
	mreq := opensearchapi.MgetRequest{
		Index: searcher.IndexName,
		Body:  mgetBody,
	}
	mresponse, err := mreq.Do(context.Background(), searcher.Client)
	if err != nil {
		return nil, errors.New(`error getting indexed documents: ` + err.Error())
	}
	defer mresponse.Body.Close()
	if mresponse.StatusCode != 200 {
		bodyBytes, _ := io.ReadAll(mresponse.Body)
		return nil, errors.New(string(bodyBytes))
	}
	var mgetResult struct {
		Docs []struct {
			ID     string         `json:"_id"`
			Source types.Document `json:"_source"`
		} `json:"docs"`
	}
	err = json.NewDecoder(mresponse.Body).Decode(&mgetResult)
	if err != nil {
		return nil, errors.New(`error decoding response: ` + err.Error())
	}
	indexedDocuments := []types.Document{}
	for _, doc := range mgetResult.Docs {
		doc.Source.ID = doc.ID
		indexedDocuments = append(indexedDocuments, doc.Source)
	}
	return indexedDocuments, nil
}

func GetDocument(searcher types.Searcher, id string) (*types.Document, error) {
	req := opensearchapi.GetRequest{
		Index:      searcher.IndexName,
		DocumentID: id,
	}
	response, err := req.Do(context.Background(), searcher.Client)
	if err != nil {
		return nil, errors.New(`error getting document: ` + err.Error())
	}
	defer response.Body.Close()
	if response.StatusCode != 200 {
		bodyBytes, _ := io.ReadAll(response.Body)
		return nil, errors.New(string(bodyBytes))
	}
	var responseJSON struct {
		ID     string         `json:"_id"`
		Source types.Document `json:"_source"`
	}
	err = json.NewDecoder(response.Body).Decode(&responseJSON)
	if err != nil {
		return nil, errors.New(`error decoding response: ` + err.Error())
	}
	responseJSON.Source.ID = responseJSON.ID
	return &responseJSON.Source, nil
}

func UpdateDocument(searcher types.Searcher, document types.Document) error {
	id := document.ID
	document.ID = ``
	body := struct {
		Doc types.Document `json:"doc"`
	}{Doc: document}
	reqBody, err := json.Marshal(body)
	if err != nil {
		return errors.New(`error marshalling document: ` + err.Error())
	}
	req := opensearchapi.UpdateRequest{
		Index:      searcher.IndexName,
		Body:       strings.NewReader(string(reqBody)),
		DocumentID: id,
	}
	response, err := req.Do(context.Background(), searcher.Client)
	if err != nil {
		return err
	}
	defer response.Body.Close()
	if response.StatusCode != 200 {
		bodyBytes, _ := io.ReadAll(response.Body)
		return errors.New(string(bodyBytes))
	}
	return err
}

func DeleteDocument(searcher types.Searcher, id string) error {
	req := opensearchapi.DeleteRequest{
		Index:      searcher.IndexName,
		DocumentID: id,
	}
	_, err := req.Do(context.Background(), searcher.Client)
	return err
}

func RawSearch(searcher types.Searcher, query string) (string, error) {
	req := opensearchapi.SearchRequest{
		Index: []string{searcher.IndexName},
		Body:  strings.NewReader(query),
	}
	response, err := req.Do(context.Background(), searcher.Client)
	if err != nil {
		return ``, err
	}
	defer response.Body.Close()
	body, err := io.ReadAll(response.Body)
	if err != nil {
		return ``, err
	}
	return string(body), nil
}

func BasicSearch(searcher types.Searcher, query string) (*types.SearchResult, error) {
	QueryJSON := fmt.Sprintf(`{
		"query": {
			"multi_match": {
				"query": "%s"
			}
		}
	}`, query)

	req := opensearchapi.SearchRequest{
		Index: []string{searcher.IndexName},
		Body:  strings.NewReader(QueryJSON),
	}
	response, err := req.Do(context.Background(), searcher.Client)
	if err != nil {
		return nil, err
	}
	defer response.Body.Close()
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
