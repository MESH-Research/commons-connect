package types

type User struct {
	Name        string `json:"name,omitempty"`
	Username    string `json:"username,omitempty"`
	URL         string `json:"url,omitempty"`
	Role        string `json:"role,omitempty"`
	NetworkNode string `json:"network_node,omitempty"`
}
