package search

import (
	"crypto/tls"
	"errors"
	"net"
	"net/http"
	"time"

	"github.com/MESH-Research/commons-connect/cc-search/types"
	osg "github.com/opensearch-project/opensearch-go/v2"
)

func GetSearcher(conf types.Config) types.Searcher {
	client, err := GetClient(
		conf.SearchEndpoint,
		conf.User,
		conf.Password,
		conf.ClientMode,
	)
	if err != nil {
		panic(err)
	}
	return types.Searcher{
		IndexName: conf.IndexName,
		Client:    client,
	}
}

func GetClient(clientURL string, user string, pass string, clientMode string) (*osg.Client, error) {
	if clientURL == `` {
		return nil, errors.New(`clientURL is required`)
	}
	if clientMode == `noauth` {
		return GetClientNoAuth(clientURL)
	}
	if (user == ``) || (pass == ``) {
		return nil, errors.New(`user and pass are required for basic auth mode`)
	}
	return GetClientUserPass(clientURL, user, pass)
}

func GetClientNoAuth(clientURL string) (*osg.Client, error) {
	client, err := osg.NewClient(osg.Config{
		Transport: &http.Transport{
			TLSClientConfig: &tls.Config{InsecureSkipVerify: true},
		},
		Addresses: []string{clientURL},
	})
	return client, err
}

func GetClientUserPass(clientURL string, user string, pass string) (*osg.Client, error) {
	client, err := osg.NewClient(osg.Config{
		Transport: &http.Transport{
			MaxIdleConnsPerHost:   10,
			ResponseHeaderTimeout: time.Second,
			DialContext:           (&net.Dialer{Timeout: time.Second}).DialContext,
			TLSClientConfig: &tls.Config{
				MinVersion: tls.VersionTLS11,
			},
		},
		Addresses: []string{clientURL},
		Username:  user,
		Password:  pass,
	})
	return client, err
}
