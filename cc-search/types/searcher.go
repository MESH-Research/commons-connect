package types

import (
	osg "github.com/opensearch-project/opensearch-go/v2"
)

type Searcher struct {
	IndexName string
	Client    *osg.Client
}
