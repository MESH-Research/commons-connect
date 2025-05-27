package config

import (
	"reflect"

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
	config.AddConfigPath("../../")

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

	confType := reflect.TypeOf(conf)
	confValue := reflect.ValueOf(&conf).Elem()

	// I'm going through these contortions because it seems otherwise AutomaticEnv()
	// doesn't work in the absense of a config file.
	// See this issue and the linked discussion for possible reason why:
	// https://github.com/spf13/viper/issues/1721
	for i := 0; i < confType.NumField(); i++ {
		field := confType.Field(i)
		fieldValue := confValue.Field(i)

		var value interface{}
		if tag, ok := field.Tag.Lookup("mapstructure"); ok {
			value = config.Get(tag)
		}

		// Only set the value if it's not nil and the types are compatible
		if value != nil {
			valueReflect := reflect.ValueOf(value)
			if valueReflect.Type().ConvertibleTo(fieldValue.Type()) {
				fieldValue.Set(valueReflect.Convert(fieldValue.Type()))
			}
		}
	}

	return conf
}
