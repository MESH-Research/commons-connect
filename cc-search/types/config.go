package types

type Config struct {
	User           string `mapstructure:"os_user"`
	Password       string `mapstructure:"os_password"`
	SearchEndpoint string `mapstructure:"os_endpoint"`
	IndexName      string `mapstructure:"os_index"`
	APIKey         string `mapstructure:"api_key"`
	AdminAPIKey    string `mapstructure:"admin_api_key"`
	ClientMode     string `mapstructure:"os_client_mode"`
}
