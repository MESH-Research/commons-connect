// CLI Interface for the Commons Connect Search Service
//
// Usage:
//   ccs <command> [options]
//
// Run 'ccs [<command>] --help' for more information.

package main

import (
	"context"
	"encoding/json"
	"fmt"
	"log"
	"os"
	"strconv"
	"strings"
	"text/tabwriter"
	"time"

	"github.com/MESH-Research/commons-connect/cc-search/config"
	"github.com/MESH-Research/commons-connect/cc-search/search"
	"github.com/MESH-Research/commons-connect/cc-search/types"
)

type Command struct {
	Description            string
	Usage                  string
	RequiredPositionalArgs []RequiredPositionalArg
	NamedArgs              map[string]string
	Runner                 func(args ParsedArgs)
}

type RequiredPositionalArg struct {
	Name        string
	Description string
}

type ParsedArgs struct {
	PositionalArgs map[string]string
	NamedArgs      map[string]string
}

var commands = map[string]Command{
	"status": {
		Description:            "Get the status of the search service",
		Usage:                  "ccs status",
		RequiredPositionalArgs: []RequiredPositionalArg{},
		NamedArgs:              map[string]string{},
		Runner:                 cmdStatus,
	},
	"get": {
		Description:            "Get a document by id",
		Usage:                  "ccs get <id>",
		RequiredPositionalArgs: []RequiredPositionalArg{{Name: "id", Description: "The id of the document to get"}},
		NamedArgs:              map[string]string{},
		Runner:                 cmdGet,
	},
	"delete": {
		Description:            "Delete a document by id",
		Usage:                  "ccs delete <id>",
		RequiredPositionalArgs: []RequiredPositionalArg{{Name: "id", Description: "The id of the document to delete"}},
		NamedArgs:              map[string]string{},
		Runner:                 cmdDelete,
	},
	"delete-node": {
		Description:            "Delete all documents from a network node",
		Usage:                  "ccs delete-node <network-node>",
		RequiredPositionalArgs: []RequiredPositionalArg{{Name: "network-node", Description: "The network node to delete documents from"}},
		NamedArgs:              map[string]string{},
		Runner:                 cmdDeleteNode,
	},
	"reset": {
		Description:            "Reset the search index",
		Usage:                  "ccs reset",
		RequiredPositionalArgs: []RequiredPositionalArg{},
		NamedArgs:              map[string]string{},
		Runner:                 cmdReset,
	},
	"search": {
		Description:            "Search for documents",
		Usage:                  "ccs search [options]",
		RequiredPositionalArgs: []RequiredPositionalArg{},
		NamedArgs: map[string]string{
			"query":        "The search query",
			"limit":        "The maximum number of results to return",
			"username":     "Search only documents with this username as a contributor",
			"start-date":   "Search only documents published after this date (YYYY-MM-DD)",
			"end-date":     "Search only documents published before this date (YYYY-MM-DD)",
			"title":        "Search only documents with this title",
			"content-type": "Search only documents with this content type",
			"network":      "Search only documents with this network node",
		},
		Runner: cmdSearch,
	},
}

func main() {
	if len(os.Args) < 2 || contains(os.Args, "--help") || contains(os.Args, "-h") {
		if len(os.Args) > 1 {
			showHelp(os.Args[1])
		} else {
			showHelp("")
		}
		return
	}

	commandName, parsedArgs := parseArgs(os.Args[1:])
	commands[commandName].Runner(parsedArgs)
}

// Parse the arguments for a command
// Returns the positional and named arguments
//
// The first argument is the command name. Further arguments are parsed as follows:
//   - Named arguments are formatted as --key=value (no spaces between the = sign)
//   - Positional arguments are formatted as value. They must come before named arguments and are required.
//
// If a required positional argument is not provided, the program will panic.
// If a named argument is not provided, the value will be an empty string.
// If a named argument is provided without a value, the value will be set to "true".
// If a named argument is provided that is not in the command, the program will panic.
func parseArgs(args []string) (commandName string, parsedArgs ParsedArgs) {
	positionalArgs := map[string]string{}
	namedArgs := map[string]string{}

	commandName = args[0]
	command, ok := commands[commandName]
	if !ok {
		panic(fmt.Sprintf("Invalid command: %s", commandName))
	}

	for position, arg := range args[1:] {
		if strings.HasPrefix(arg, "--") {
			argName, argValue, found := strings.Cut(arg, "=")
			argName = strings.TrimPrefix(argName, "--")
			if !found {
				argValue = "true"
			}
			if _, ok := command.NamedArgs[argName]; !ok {
				panic(fmt.Sprintf("Invalid named argument: %s", argName))
			}
			namedArgs[argName] = argValue
		} else {
			argName := command.RequiredPositionalArgs[position].Name
			positionalArgs[argName] = arg
		}
	}

	if len(command.RequiredPositionalArgs) != len(positionalArgs) {
		panic(fmt.Sprintf("Invalid number of positional arguments. Expected %d, got %d", len(command.RequiredPositionalArgs), len(positionalArgs)))
	}

	return commandName, ParsedArgs{PositionalArgs: positionalArgs, NamedArgs: namedArgs}
}

func showHelp(commandName string) {
	fmt.Println("Commons Connect Search CLI")
	fmt.Println()

	command, ok := commands[commandName]
	if !ok {
		fmt.Println("Usage: ccs <command> [options]")
		fmt.Println()
		fmt.Println("Commands:")
		for commandName := range commands {
			fmt.Println("  " + commandName + ": " + commands[commandName].Description)
		}
		fmt.Println()
		fmt.Println("Run 'ccs <command> --help' for more information about a command.")
		return
	}

	fmt.Println(commandName + ": " + command.Description)
	fmt.Println()
	fmt.Println("Usage: " + command.Usage)
	fmt.Println()
	if len(command.RequiredPositionalArgs) > 0 {
		fmt.Println("Positional arguments:")
		for _, arg := range command.RequiredPositionalArgs {
			fmt.Printf("  %s: %s\n", arg.Name, arg.Description)
		}
	}
	if len(command.NamedArgs) > 0 {
		fmt.Println("Named arguments:")
		for name, description := range command.NamedArgs {
			fmt.Printf("  --%s: %s\n", name, description)
		}
	}
}

type SearchServiceStatus struct {
	IndexName           string
	SearchEndpoint      string
	SearchUsername      string
	SearchPasswordIsSet bool
	APIKey              string
	NumDocs             int
	CreationTime        time.Time
	NumReplicas         int
	NumShards           int
}

func cmdStatus(_ ParsedArgs) {
	conf := config.GetConfig()
	searcher := search.GetSearcher(conf)
	indexSettings, err := search.GetIndexInfo(&searcher)
	if err != nil {
		fmt.Println("Error getting index info:", err)
		return
	}

	indexData, ok := indexSettings[conf.IndexName].(map[string]interface{})
	if !ok {
		fmt.Println("Unexpected index data structure")
		return
	}

	settings, ok := indexData["settings"].(map[string]interface{})
	if !ok {
		fmt.Println("Unexpected settings structure")
		return
	}

	indexSettings, ok = settings["index"].(map[string]interface{})
	if !ok {
		fmt.Println("Unexpected index settings structure")
		return
	}

	creationDate, _ := strconv.ParseInt(indexSettings["creation_date"].(string), 10, 64)
	numReplicas, _ := strconv.Atoi(indexSettings["number_of_replicas"].(string))
	numShards, _ := strconv.Atoi(indexSettings["number_of_shards"].(string))

	response, err := searcher.Client.Count(
		searcher.Client.Count.WithIndex(conf.IndexName),
		searcher.Client.Count.WithContext(context.Background()),
	)
	if err != nil {
		fmt.Println("Error getting document count:", err)
		return
	}

	if response.StatusCode != 200 {
		fmt.Println("Error getting document count:", response.StatusCode)
		return
	}

	defer response.Body.Close()

	var r map[string]interface{}
	if err := json.NewDecoder(response.Body).Decode(&r); err != nil {
		log.Fatalf("Error parsing the response body: %s", err)
	}

	count := r["count"].(float64)
	countInt, err := strconv.Atoi(fmt.Sprintf("%d", int(count)))
	if err != nil {
		fmt.Println("Error converting document count to int:", err)
		return
	}

	status := SearchServiceStatus{
		IndexName:           conf.IndexName,
		SearchEndpoint:      conf.SearchEndpoint,
		SearchUsername:      conf.User,
		SearchPasswordIsSet: conf.Password != "",
		APIKey:              conf.APIKey,
		NumDocs:             countInt,
		CreationTime:        time.Unix(creationDate/1000, 0),
		NumReplicas:         numReplicas,
		NumShards:           numShards,
	}

	fmt.Printf("Index Status:\n")
	fmt.Printf("  Index Name: %s\n", status.IndexName)
	fmt.Printf("  Search Endpoint: %s\n", status.SearchEndpoint)
	fmt.Printf("  Search Username: %s\n", status.SearchUsername)
	fmt.Printf("  Search Password is set: %v\n", status.SearchPasswordIsSet)
	fmt.Printf("  API Key: %s\n", status.APIKey)
	fmt.Printf("  Number of Documents: %d\n", status.NumDocs)
	fmt.Printf("  Creation Time: %s\n", status.CreationTime)
	fmt.Printf("  Number of Replicas: %d\n", status.NumReplicas)
	fmt.Printf("  Number of Shards: %d\n", status.NumShards)
}

func cmdGet(args ParsedArgs) {
	conf := config.GetConfig()
	searcher := search.GetSearcher(conf)
	document, err := search.GetDocument(searcher, args.PositionalArgs["id"])
	if err != nil {
		fmt.Println("Error getting document:", err)
		return
	}

	fmt.Println("Internal ID: " + document.InternalID)
	fmt.Println("Search ID: " + document.ID)
	fmt.Println("Title: " + document.Title)
	fmt.Println("Description: " + document.Description)
	fmt.Println("Owner: " + document.Owner.Username)
	fmt.Println("Contributors:")
	printUsers(document.Contributors)
	fmt.Println("Primary URL: " + document.PrimaryURL)
	fmt.Println("Other URLs: " + strings.Join(document.OtherURLs, ", "))
	fmt.Println("Thumbnail URL: " + document.ThumbnailURL)
	content := document.Content
	if len(content) > 1000 {
		content = content[:1000] + "..."
	}
	fmt.Println("Content: " + content)
	fmt.Println("Publication Date: " + document.PublicationDate)
	fmt.Println("Modified Date: " + document.ModifiedDate)
	fmt.Println("Language: " + document.Language)
	fmt.Println("Content Type: " + document.ContentType)
	fmt.Println("Network Node: " + document.NetworkNode)
}

func printUsers(users []types.User) {
	for _, user := range users {
		fmt.Println("  " + user.Name + " (" + user.Username + ")")
	}
}

func cmdDelete(args ParsedArgs) {
	conf := config.GetConfig()
	searcher := search.GetSearcher(conf)

	document, err := search.GetDocument(searcher, args.PositionalArgs["id"])
	if err != nil {
		fmt.Println("Error getting document:", err)
		return
	}

	fmt.Println("Deleting document: " + document.ID)
	fmt.Println("Internal ID: " + document.InternalID)
	fmt.Println("Title: " + document.Title)
	fmt.Println("Owner: " + document.Owner.Username)
	fmt.Println("Publication Date: " + document.PublicationDate)
	fmt.Println("Network Node: " + document.NetworkNode)

	fmt.Print("Are you sure you want to delete this document? (y/N): ")
	var response string
	fmt.Scanln(&response)
	if strings.ToLower(response) != "y" {
		fmt.Println("Document deletion aborted.")
		return
	}

	err = search.DeleteDocument(searcher, args.PositionalArgs["id"])
	if err != nil {
		fmt.Println("Error deleting document:", err)
		return
	}
	fmt.Println("Document deleted")
}

func cmdDeleteNode(args ParsedArgs) {
	conf := config.GetConfig()
	searcher := search.GetSearcher(conf)

	fmt.Println("Deleting documents from network node: " + args.PositionalArgs["network-node"])
	fmt.Print("Are you sure you want to delete all documents from this network node? (y/N): ")
	var response string
	fmt.Scanln(&response)
	if strings.ToLower(response) != "y" {
		fmt.Println("Document deletion aborted.")
		return
	}

	err := search.DeleteNode(searcher, args.PositionalArgs["network-node"])
	if err != nil {
		fmt.Println("Error deleting documents:", err)
		return
	}
	fmt.Println("Documents deleted")
}

func cmdReset(_ ParsedArgs) {
	conf := config.GetConfig()
	searcher := search.GetSearcher(conf)

	fmt.Println("Resetting index")
	fmt.Print("Are you sure you want to reset the index? This will delete all documents and reset the index. (y/N): ")
	var response string
	fmt.Scanln(&response)
	if strings.ToLower(response) != "y" {
		fmt.Println("Index reset aborted.")
		return
	}

	err := search.ResetIndex(&searcher)
	if err != nil {
		fmt.Println("Error resetting index:", err)
		return
	}
	fmt.Println("Index reset")
}

func cmdSearch(args ParsedArgs) {
	conf := config.GetConfig()
	searcher := search.GetSearcher(conf)

	params := types.SearchParams{
		ExactMatch: make(map[string]string),
	}

	if args.NamedArgs["query"] != "" {
		params.Query = args.NamedArgs["query"]
	}
	if args.NamedArgs["username"] != "" {
		params.ExactMatch["contributors.username"] = args.NamedArgs["username"]
	}
	if args.NamedArgs["title"] != "" {
		params.ExactMatch["title"] = args.NamedArgs["title"]
	}
	if args.NamedArgs["content-type"] != "" {
		params.ExactMatch["content_type"] = args.NamedArgs["content-type"]
	}
	if args.NamedArgs["network"] != "" {
		params.ExactMatch["network_node"] = args.NamedArgs["network"]
	}
	if args.NamedArgs["start-date"] != "" {
		params.StartDate = args.NamedArgs["start-date"]
	}
	if args.NamedArgs["end-date"] != "" {
		params.EndDate = args.NamedArgs["end-date"]
	}
	if args.NamedArgs["limit"] != "" {
		limit, err := strconv.Atoi(args.NamedArgs["limit"])
		if err != nil {
			fmt.Println("Invalid limit value:", err)
			return
		}
		params.PerPage = limit
	}

	searchResult, err := search.Search(searcher, params)
	if err != nil {
		fmt.Println("Error searching for documents:", err)
		return
	}

	fmt.Println("Found " + fmt.Sprintf("%d", searchResult.Total) + " documents")
	fmt.Println()

	w := tabwriter.NewWriter(os.Stdout, 0, 0, 1, ' ', tabwriter.Debug)
	fmt.Fprintln(w, "ID\tInternal ID\tTitle\tFirst Author\tPublication Date\tLast Updated\tNetwork Node")
	fmt.Fprintln(w, "----\t-----------\t----\t------------\t----------------\t------------\t----------")
	for _, hit := range searchResult.Hits {
		firstAuthor := ""
		if len(hit.Contributors) > 0 {
			firstAuthor = hit.Contributors[0].Username
		}
		fmt.Fprintf(w, "%.40s\t%.40s\t%.60s\t%.40s\t%.40s\t%.40s\t%.40s\n", hit.ID, hit.InternalID, hit.Title, firstAuthor, hit.PublicationDate, hit.ModifiedDate, hit.NetworkNode)
	}

	w.Flush()
}

func contains(slice []string, item string) bool {
	for _, s := range slice {
		if s == item {
			return true
		}
	}
	return false
}
