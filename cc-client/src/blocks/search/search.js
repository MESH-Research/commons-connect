import { useEffect, useState } from "@wordpress/element";
import sampleJson from "./sample.json";

function useFormInput(initialValue) {
    const [value, setValue] = useState(initialValue);
    function handleChange(e) {
        setValue(e.target.value);
    }
    return {
        value,
        onChange: handleChange,
    };
}
function CustomDateRange({ dateRangeValue, startDate, endDate }) {
    return (
        dateRangeValue == "custom" && (
            <div className="ccs-row ccs-date-ranges">
                <label>
                    <span>Start Date</span>
                    <br />
                    <input type="date" name="customStartDate" {...startDate} />
                </label>
                <label>
                    <span>End Date</span>
                    <br />
                    <input type="date" name="customEndDate" {...endDate} />
                </label>
            </div>
        )
    );
}
function Paginator({ currentPage, totalPages, perPage }) {
    let [pageData, setPageData] = useState({
        currentPage: currentPage,
        totalPages: totalPages,
        perPage: perPage,
    });
    let exceedsMaxDisplay = pageData.totalPages > 7;
    let slots = [];
    function makeSlot(data) {
        data.clickable != undefined ? data.clickable : true;
        return data;
    }
    function setSlots(data) {
        slots = data.map((slot) => {
            return makeSlot(slot);
        });
    }
    if (exceedsMaxDisplay) {
        if (pageData.currentPage <= 4) {
            setSlots([
                { label: 1, value: 1 },
                { label: 2, value: 2 },
                { label: 3, value: 3 },
                { label: 4, value: 4 },
                { label: 5, value: 5 },
                { label: "...", value: null, clickable: false },
                { label: pageData.totalPages, value: pageData.totalPages },
            ]);
        } else if (
            pageData.currentPage > 4 &&
            pageData.currentPage < pageData.totalPages - 4
        ) {
            setSlots([
                { label: 1, value: 1 },
                { label: "...", value: null, clickable: false },
                {
                    label: pageData.currentPage - 1,
                    value: pageData.currentPage - 1,
                },
                { label: pageData.currentPage, value: pageData.currentPage },
                {
                    label: pageData.currentPage + 1,
                    value: pageData.currentPage + 1,
                },
                { label: "...", value: null, clickable: false },
                { label: pageData.totalPages, value: pageData.totalPages },
            ]);
        } else if (
            pageData.currentPage > 4 &&
            pageData.currentPage >= pageData.totalPages - 4
        ) {
            setSlots([
                { label: 1, value: 1 },
                { label: "...", value: null, clickable: false },
                {
                    label: pageData.totalPages - 4,
                    value: pageData.totalPages - 4,
                },
                {
                    label: pageData.totalPages - 3,
                    value: pageData.totalPages - 3,
                },
                {
                    label: pageData.totalPages - 2,
                    value: pageData.totalPages - 2,
                },
                {
                    label: pageData.totalPages - 1,
                    value: pageData.totalPages - 1,
                },
                { label: pageData.totalPages, value: pageData.totalPages },
            ]);
        }
    } else {
        slots = [];
        for (let i = 1; i <= pageData.totalPages; i++) {
            slots.push(
                makeSlot({
                    label: i,
                    value: i,
                }),
            );
        }
    }
    const slotMarkup = slots.map((slot, index) => {
        if (slot.clickable === false) {
            return (
                <span key={index} className="ccs-page-link">
                    {slot.label}
                </span>
            );
        }
        return (
            <a
                key={index}
                href="#"
                onClick={(e) => setPage(e, slot.value)}
                style={
                    pageData.currentPage == slot.value
                        ? { fontWeight: "bold" }
                        : {}
                }
                className="ccs-page-link"
                aria-current={pageData.currentPage == slot.value ? true : null}
                aria-label={"Page " + slot.value + " of " + pageData.totalPages}
            >
                {slot.label}
            </a>
        );
    });
    function setPage(e, page) {
        e.preventDefault();
        setPageData({ ...pageData, currentPage: page });
    }
    function decrementPage() {
        if (pageData.currentPage > 1) {
            setPageData({
                ...pageData,
                currentPage: (pageData.currentPage -= 1),
            });
        }
    }
    function incrementPage() {
        if (pageData.currentPage != pageData.totalPages) {
            setPageData({
                ...pageData,
                currentPage: (pageData.currentPage += 1),
            });
        }
    }
    return (
        <footer>
            <nav
                aria-label={
                    "Select a page of " +
                    pageData.totalPages +
                    " pages of search results"
                }
                className="ccs-footer-nav"
            >
                <button
                    onClick={decrementPage}
                    disabled={pageData.currentPage === 1}
                    aria-label={
                        pageData.currentPage !== 1
                            ? "Previous Page " + (pageData.currentPage - 1)
                            : null
                    }
                >
                    Previous
                </button>
                {slotMarkup}
                <button
                    onClick={incrementPage}
                    disabled={pageData.currentPage === pageData.totalPages}
                    aria-label={
                        pageData.currentPage !== pageData.totalPages
                            ? "Next Page " + (pageData.currentPage + 1)
                            : null
                    }
                >
                    Next
                </button>
            </nav>
        </footer>
    );
}
function generateSampleJson(options) {
    const record = {
        title: "",
        description: "",
        owner: {
            name: "",
        },
        contributors: [],
        primary_url: "#",
        other_urls: [],
        thumbnail_url: "",
        content: "",
        publication_date: "",
        modified_date: "",
        language: "",
        content_type: "",
        network_node: "",
    };
    return { ...record, ...options };
}
function processResults(data) {
    const a = [];
    data.forEach((result) => {
        a.push(generateSampleJson(result));
    });
    return a;
}
function getContentTypeLabel(type) {
    const labels = {
        deposit: "Work/Deposit",
        post: "Post",
        user: "Profile",
        profile: "Profile",
        group: "Group",
        site: "Site",
        discussion: "Discussion",
    };
    return labels[type] ?? "Unknown";
}
function getDateLabel(publication_date, modified_date) {
    let date = "";
    if (publication_date) {
        date = new Date(
            Date.parse(publication_date + "T00:00:00.000-05:00"),
        ).toDateString();
    }
    if (modified_date) {
        date =
            "Updated: " +
            new Date(
                Date.parse(modified_date + "T00:00:00.000-05:00"),
            ).toDateString();
    }
    return date;
}
function renderContributor(data) {
    if (Object.hasOwn(data, "owner")) {
        if (
            Object.hasOwn(data.owner, "url") &&
            Object.hasOwn(data.owner, "name")
        ) {
            return (
                <a href={data.owner.url} className="ccs-result-person">
                    {data.owner.name}
                </a>
            );
        }
        if (Object.hasOwn(data.owner, "name")) {
            return <span className="ccs-result-person">{data.owner.name}</span>;
        }
        return null;
    } else {
        return null;
    }
}
function decodeHTMLElement(text) {
    const textArea = document.createElement("textarea");
    textArea.innerHTML = text;
    return textArea.value;
}
function SearchResult({ data }) {
    const dateLabel = getDateLabel(data.publication_date, data.modified_date);

    return (
        <section className="ccs-result">
            <header className="ccs-row ccs-result-header">
                {data.content_type && (
                    <span className="ccs-tag">
                        {getContentTypeLabel(data.content_type)}
                    </span>
                )}
                <a href={data.primary_url} className="ccs-result-title">
                    {data.title}
                </a>
                {renderContributor(data)}
                {dateLabel && <span className="ccs-date">{dateLabel}</span>}
            </header>
            <div className="ccs-result-description">
                {data.thumbnail_url && (
                    <img
                        src={data.thumbnail_url}
                        alt=""
                        className="ccs-result-thumbnail"
                    />
                )}
                <p>{decodeHTMLElement(data.description)}</p>
            </div>
        </section>
    );
}
function NoData() {
    return (
        <section className="ccs-no-results">
            <p>No results.</p>
        </section>
    );
}
function SearchResultSection(data) {
    if (
        data.searchPerformed === true &&
        data.searchResults === 0 &&
        data.searchTerm !== ""
    ) {
        return <NoData />;
    } else if (
        data.searchPerformed === true &&
        data.searchResults.length > 0 &&
        data.searchTerm !== ""
    ) {
        return (
            <div>
                {data.searchResults.map(function (result, i) {
                    return <SearchResult key={i} data={result} />;
                })}
                <Paginator
                    totalPages={data.totalPages}
                    currentPage={data.currentPage}
                    perPage={data.perPage}
                />
            </div>
        );
    } else {
        return "";
    }
}
function getSearchTermFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get("search") ?? "";
}
function getDefaultEndDate() {
    return new Date().toISOString().split("T")[0];
}

export default function CCSearch() {
    const searchTerm = useFormInput(getSearchTermFromUrl());
    const searchType = useFormInput("all");
    const sortBy = useFormInput("relevance");
    const dateRange = useFormInput("anytime");
    const endDate = useFormInput(getDefaultEndDate());
    const startDate = useFormInput("");
    const [currentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [perPage] = useState(20);
    const [searchPerformed, setSearchPerformed] = useState(false);
    const [searchResults, setSearchResults] = useState([]);

    function performSearch(event) {
        if (event !== null) {
            event.preventDefault();
        }
        if (searchTerm.value === "") {
            return;
        }

        const params = {
            sort_by: sortBy.value,
            content_type: searchType.value,
            page: currentPage,
            per_page: perPage,
            q: searchTerm.value,
        };
        if (dateRange.value === "custom") {
            params.start_date = startDate.value;
            params.end_date = endDate.value;
        }

        setSearchPerformed(true);
        setSearchResults(processResults(sampleJson.hits));
        setTotalPages(sampleJson.total_pages);

        // const url = new URL(
        //     "https://commons-connect-client.lndo.site/v1/search",
        // );
        // Object.keys(params).forEach((key) =>
        //     url.searchParams.append(key, params[key]),
        // );

        // fetch(url)
        //     .then((response) => response.json())
        //     .then((data) => {
        //         const parsed = JSON.parse(data);
        //         setSearchResults(processResults(parsed.hits));
        //         setTotalPages(parsed.total_pages);
        //     });
    }

    useEffect(() => {
        performSearch(null);
    }, []);

    return (
        <main>
            <article className="ccs-row ccs-top">
                <search className="ccs-search">
                    <form onSubmit={performSearch}>
                        <div className="ccs-row ccs-search-input">
                            <label>
                                <span className="ccs-label">Search</span>
                                <br />
                                <input
                                    type="search"
                                    name="ccSearch"
                                    {...searchTerm}
                                />
                                <button aria-label="Search">🔍</button>
                            </label>
                        </div>
                        <div className="ccs-row ccs-search-options">
                            <div className="search-option">
                                <label>
                                    <span className="ccs-label">Type</span>
                                    <br />
                                    <select {...searchType}>
                                        <option value="all">All Types</option>
                                        <option value="work">
                                            Deposit/Work
                                        </option>
                                        <option value="post">Post</option>
                                        <option value="profile">Profile</option>
                                        <option value="group">Group</option>
                                        <option value="site">Site</option>
                                        <option value="discussion">
                                            Discussion
                                        </option>
                                    </select>
                                </label>
                            </div>
                            <div className="search-option">
                                <label>
                                    <span className="ccs-label">Sort By</span>
                                    <br />
                                    <select {...sortBy}>
                                        <option value="relevance">
                                            Relevance
                                        </option>
                                        <option value="publication_date">
                                            Publication Date
                                        </option>
                                        <option value="modified_date">
                                            Modified Date
                                        </option>
                                    </select>
                                </label>
                            </div>
                            <div className="search-option">
                                <label>
                                    <span className="ccs-label">
                                        Date Range
                                    </span>
                                    <br />
                                    <select {...dateRange}>
                                        <option value="anytime">Anytime</option>
                                        <option value="week">Past Week</option>
                                        <option value="month">
                                            Past Month
                                        </option>
                                        <option value="year">Past Year</option>
                                        <option value="custom">Custom</option>
                                    </select>
                                </label>
                                <CustomDateRange
                                    dateRangeValue={dateRange.value}
                                    startDate={startDate}
                                    endDate={endDate}
                                />
                            </div>
                        </div>
                        <div>
                            <label>
                                <input
                                    type="checkbox"
                                    name="searchCommonsOnly"
                                />
                                <span>&nbsp;</span>
                                <span>Search only this Commons</span>
                            </label>
                        </div>
                        <div className="ccs-search-button">
                            <button type="submit">Search</button>
                        </div>
                    </form>
                </search>
                <aside className="ccs-aside">
                    <p>Want a more refined search for deposits/works?</p>
                    <a href="#">KC Works</a>
                </aside>
            </article>
            <article role="region" aria-live="polite">
                <SearchResultSection
                    searchTerm={searchTerm}
                    searchPerformed={searchPerformed}
                    searchResults={searchResults}
                    totalPages={totalPages}
                    currentPage={currentPage}
                    perPage={perPage}
                />
            </article>
        </main>
    );
}