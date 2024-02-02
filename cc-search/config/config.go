package config

import (
	"github.com/spf13/viper"
)

var config *viper.Viper

func Init() error {
	config = viper.New()
	config.SetConfigName("config")
	config.SetConfigType("json")
	config.AddConfigPath("..")

	config.SetEnvPrefix("cc")
	config.AutomaticEnv()

	err := config.ReadInConfig()
	return err
}

func GetConfig() *viper.Viper {
	return config
}
