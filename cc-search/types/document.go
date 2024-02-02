package types

type Document struct {
	ID              string   `json:"_id"`
	Title           string   `json:"title"`
	Description     string   `json:"description"`
	OwnerName       string   `json:"owner_name"`
	OtherNames      []string `json:"other_names"`
	OwnerUsername   string   `json:"owner_username"`
	OtherUsernames  []string `json:"other_usernames"`
	PrimaryURL      string   `json:"primary_url"`
	OtherURLs       []string `json:"other_urls"`
	Content         string   `json:"content"`
	PublicationDate string   `json:"publication_date"`
	ModifiedDate    string   `json:"modified_date"`
	Language        string   `json:"language"`
	ContentType     string   `json:"content_type"`
	NetworkNode     string   `json:"network_node"`
}
