package types

import (
	"reflect"
	"slices"
	"strings"
)

type Document struct {
	InternalID      string   `json:"_internal_id,omitempty"`
	ID              string   `json:"_id,omitempty"`
	Title           string   `json:"title"`
	Description     string   `json:"description,omitempty"`
	Owner           User     `json:"owner,omitempty"`
	Contributors    []User   `json:"contributors,omitempty"`
	PrimaryURL      string   `json:"primary_url,omitempty"`
	OtherURLs       []string `json:"other_urls,omitempty"`
	ThumbnailURL    string   `json:"thumbnail_url,omitempty"`
	Content         string   `json:"content,omitempty"`
	PublicationDate string   `json:"publication_date,omitempty"`
	ModifiedDate    string   `json:"modified_date,omitempty"`
	Language        string   `json:"language,omitempty"`
	ContentType     string   `json:"content_type,omitempty"`
	NetworkNode     string   `json:"network_node,omitempty"`
}

// Filter out unnecessary fields from the document for the response. Fields to
// keep are specified by name.
func (originalDocument *Document) Filter(fields []string) {
	filteredDocument := Document{}
	for _, field := range fields {
		fieldValue := reflect.ValueOf(*originalDocument).FieldByName(field)
		if !fieldValue.IsValid() {
			continue
		}
		reflect.ValueOf(&filteredDocument).Elem().FieldByName(field).Set(fieldValue)
	}
	*originalDocument = filteredDocument
}

// Filter out unnecessary fields from the document for the response. Fields to
// remove are specified by name.
func (originalDocument *Document) FilterOut(fields []string) {
	filteredDocument := Document{}
	for i := 0; i < reflect.TypeOf(*originalDocument).NumField(); i++ {
		fieldName := reflect.TypeOf(*originalDocument).Field(i).Name
		if slices.Contains(fields, fieldName) {
			continue
		}
		fieldValue := reflect.ValueOf(*originalDocument).FieldByName(fieldName)
		if !fieldValue.IsValid() {
			continue
		}
		reflect.ValueOf(&filteredDocument).Elem().FieldByName(fieldName).Set(fieldValue)
	}
	*originalDocument = filteredDocument
}

// Filter out unnecessary fields from the document for the response.
// Fields to keep are specified according to json tags.
func (originalDocument *Document) FilterByJSON(fields []string) {
	fieldsByTag := map[string]string{}
	rt := reflect.TypeOf(*originalDocument)
	for i := 0; i < rt.NumField(); i++ {
		tag := rt.Field(i).Tag.Get("json")
		tagParts := strings.Split(tag, ",")
		if len(tagParts) == 0 {
			continue
		}
		fieldsByTag[tagParts[0]] = rt.Field(i).Name
	}

	filteredDocument := Document{}
	for _, field := range fields {
		fieldName, ok := fieldsByTag[field]
		if !ok {
			continue
		}
		fieldValue := reflect.ValueOf(*originalDocument).FieldByName(fieldName)
		if !fieldValue.IsValid() {
			continue
		}
		reflect.ValueOf(&filteredDocument).Elem().FieldByName(fieldName).Set(fieldValue)
	}
	*originalDocument = filteredDocument
}
