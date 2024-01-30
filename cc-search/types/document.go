package types

type Document struct {
	ID              string   `json:"id"`
	Title           string   `json:"title"`
	ThumbnailURL    string   `json:"thumbnail_url"`
	Description     string   `json:"description"`
	OwnerName       string   `json:"owner_name"`
	OwnerUsername   string   `json:"owner_username"`
	OtherNames      []string `json:"other_names"`
	OtherUsernames  []string `json:"other_usernames"`
	PrimaryURL      string   `json:"primary_url"`
	OtherURLs       []string `json:"other_urls"`
	Content         string   `json:"content"`
	PublicationDate string   `json:"publication_date"`
	UpdatedDate     string   `json:"updated_date"`
	Type            string   `json:"type"`
	Network         string   `json:"network"`
}
