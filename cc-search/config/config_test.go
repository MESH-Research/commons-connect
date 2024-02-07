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
	if config.User == "" {
		t.Error("Expected non-empty string for User, got empty string")
	}
}

func TestEnvConfig(t *testing.T) {
	err := Init()
	if err != nil {
		t.Errorf("Error initializing config: %v", err)
	}
	config := GetConfig()
	if config.User == "test12345" {
		t.Errorf("Expected something else, got %s", config.User)

	}
	os.Setenv("CC_OS_USER", "test12345")
	config = GetConfig()
	if config.User != "test12345" {
		t.Errorf("Expected test12345, got %s", config.User)
	}
}
