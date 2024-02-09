package types

type Document struct {
	ID              string   `json:"_id,omitempty"`
	Title           string   `json:"title"`
	Description     string   `json:"description,omitempty"`
	OwnerName       string   `json:"owner_name,omitempty"`
	OtherNames      []string `json:"other_names,omitempty"`
	OwnerUsername   string   `json:"owner_username,omitempty"`
	OtherUsernames  []string `json:"other_usernames,omitempty"`
	PrimaryURL      string   `json:"primary_url"`
	OtherURLs       []string `json:"other_urls,omitempty"`
	Content         string   `json:"content,omitempty"`
	PublicationDate string   `json:"publication_date,omitempty"`
	ModifiedDate    string   `json:"modified_date,omitempty"`
	Language        string   `json:"language,omitempty"`
	ContentType     string   `json:"content_type,omitempty"`
	NetworkNode     string   `json:"network_node,omitempty"`
}
