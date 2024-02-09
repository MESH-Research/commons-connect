package types

type OSIndexSettings struct {
	Settings *struct {
		Index *struct {
			NumberOfShards   int `json:"number_of_shards,omitempty,string"`
			NumberOfReplicas int `json:"number_of_replicas,omitempty,string"`
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
