package config

import (
	"os"
	"testing"
)

func TestInit(t *testing.T) {
	err := Init()
	if err != nil {
		t.Errorf("Error initializing config: %v", err)
	}
}

func TestReadConfig(t *testing.T) {
	err := Init()
	if err != nil {
		t.Errorf("Error initializing config: %v", err)
	}
	config := GetConfig()
	if config.GetString("os_user") == "" {
		t.Error("Expected non-empty string for os_user, got empty string")
	}
}

func TestEnvConfig(t *testing.T) {
	err := Init()
	if err != nil {
		t.Errorf("Error initializing config: %v", err)
	}
	config := GetConfig()
	os.Setenv("CC_OS_USER", "test")
	if config.GetString("os_user") != "test" {
		t.Errorf("Expected test, got %s", config.GetString("os_user"))
	}
}
