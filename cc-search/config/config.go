package config

import (
	"github.com/spf13/viper"

	"github.com/MESH-Research/commons-connect/cc-search/types"
)

var config *viper.Viper

func Init() error {
	config = viper.New()
	config.SetConfigName("config")
	config.SetConfigType("json")
	config.AddConfigPath("/app")
	config.AddConfigPath(".")
	config.AddConfigPath("..")

	config.SetEnvPrefix("cc")
	config.AutomaticEnv()

	err := config.ReadInConfig()
	return err
}

func GetConfig() types.Config {
	if config == nil {
		Init()
	}
	var conf types.Config
	err := config.Unmarshal(&conf)
	if err != nil {
		panic(err)
	}
	return conf
}
