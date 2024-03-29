package search

import (
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"strings"
	"time"

	"github.com/MESH-Research/commons-connect/cc-search/types"
	opensearchapi "github.com/opensearch-project/opensearch-go/v2/opensearchapi"
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
