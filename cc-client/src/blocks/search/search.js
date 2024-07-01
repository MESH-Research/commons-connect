import { useEffect, useState } from "@wordpress/element";
import useWindowDimensions from "./mediaqueries.js";
import moment from "moment";

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
function CustomDateRange({ dateRangeValue, startDate, endDate, isBusy }) {
    return (
        dateRangeValue == "custom" && (
            <div className="ccs-row ccs-date-ranges">
                <label>
                    <span>Start Date</span>
                    <br />
                    <input
                        type="date"
                        name="customStartDate"
                        {...startDate}
                        disabled={isBusy}
                    />
                </label>
                <label>
                    <span>End Date</span>
                    <br />
                    <input
                        type="date"
                        name="customEndDate"
                        {...endDate}
                        disabled={isBusy}
                    />
                </label>
            </div>
        )
    );
}
function Paginator(data) {
    let exceedsMaxDisplay = data.totalPages > 7;
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
        if (data.currentPage <= 4) {
            setSlots([
                { label: 1, value: 1 },
                { label: 2, value: 2 },
                { label: 3, value: 3 },
                { label: 4, value: 4 },
                { label: 5, value: 5 },
                { label: "…", value: null, clickable: false },
                { label: data.totalPages, value: data.totalPages },
            ]);
        } else if (
            data.currentPage > 4 &&
            data.currentPage < data.totalPages - 3
        ) {
            setSlots([
                { label: 1, value: 1 },
                { label: "…", value: null, clickable: false },
                {
                    label: data.currentPage - 1,
                    value: data.currentPage - 1,
                },
                { label: data.currentPage, value: data.currentPage },
                {
                    label: data.currentPage + 1,
                    value: data.currentPage + 1,
                },
                { label: "…", value: null, clickable: false },
                { label: data.totalPages, value: data.totalPages },
            ]);
        } else if (
            data.currentPage > 4 &&
            data.currentPage >= data.totalPages - 3
        ) {
            setSlots([
                { label: 1, value: 1 },
                { label: "…", value: null, clickable: false },
                {
                    label: data.totalPages - 4,
                    value: data.totalPages - 4,
                },
                {
                    label: data.totalPages - 3,
                    value: data.totalPages - 3,
                },
                {
                    label: data.totalPages - 2,
                    value: data.totalPages - 2,
                },
                {
                    label: data.totalPages - 1,
                    value: data.totalPages - 1,
                },
                { label: data.totalPages, value: data.totalPages },
            ]);
        }
    } else {
        slots = [];
        for (let i = 1; i <= data.totalPages; i++) {
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
            return <span key={index}>{slot.label}</span>;
        }
        return (
            <button
                key={index}
                onClick={(e) => setPage(e, slot.value)}
                style={
                    data.currentPage == slot.value ? { fontWeight: "bold" } : {}
                }
                className="ccs-page-button"
                aria-current={data.currentPage == slot.value ? true : null}
                aria-label={"Page " + slot.value + " of " + data.totalPages}
            >
                {slot.label}
            </button>
        );
    });
    function setPage(e, page) {
        e.preventDefault();
        data.setCurrentPage(page);
    }
    function decrementPage() {
        if (data.currentPage > 1) {
            data.setCurrentPage(data.currentPage - 1);
        }
    }
    function incrementPage() {
        if (data.currentPage != data.totalPages) {
            data.setCurrentPage(data.currentPage + 1);
        }
    }
    const { width } = useWindowDimensions();
    return (
        <footer>
            <nav
                aria-label={
                    "Select a page of " +
                    data.totalPages +
                    " pages of search results"
                }
                className="ccs-footer-nav"
            >
                <button
                    className="ccs-page-prev"
                    onClick={decrementPage}
                    disabled={data.currentPage === 1 || data.isBusy}
                    aria-label={
                        data.currentPage !== 1
                            ? "Previous Page " + (data.currentPage - 1)
                            : null
                    }
                >
                    {width > 500 ? "Previous" : "◀"}
                </button>
                {slotMarkup}
                <button
                    className="ccs-page-next"
                    onClick={incrementPage}
                    disabled={
                        data.currentPage === data.totalPages || data.isBusy
                    }
                    aria-label={
                        data.currentPage !== data.totalPages
                            ? "Next Page " + (data.currentPage + 1)
                            : null
                    }
                >
                    {width > 500 ? "Next" : "▶"}
                </button>
            </nav>
        </footer>
    );
}
const person = {
    name: "",
    username: "",
    url: "",
    network_node: "",
    role: "",
};
function setResult(options) {
    const record = {
        _internal_id: "",
        _id: "",
        title: "",
        description: "",
        owner: person,
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
        let b = {};
        b = setResult(result);
        // b.contributors = result.contributors.map((contributor) => {
        //     return { ...contributor, ...person };
        // });
        a.push(b);
    });
    return a;
}
function getContentTypeLabel(type) {
    const labels = {
        deposit: "Work/Deposit",
        work: "Work/Deposit",
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
    if (Object.hasOwn(data, "content_type")) {
        if (data.content_type === "user" || data.content_type === "profile") {
            return null;
        }
    }
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
    let thumbnail = null;
    if (
        Object.hasOwn(data, "thumbnail_url") &&
        (data.content_type === "user" || data.content_type === "profile")
    ) {
        thumbnail = (
            <img
                src={data.thumbnail_url}
                alt=""
                className="ccs-profile-thumbnail"
            />
        );
    }

    return (
        <section className="ccs-result">
            <header className="ccs-row ccs-result-header">
                {data.content_type && (
                    <span className="ccs-tag">
                        {getContentTypeLabel(data.content_type)}
                    </span>
                )}
                {thumbnail}
                <a href={data.primary_url} className="ccs-result-title">
                    {decodeHTMLElement(data.title)}
                </a>
                {renderContributor(data)}
                {dateLabel && <span className="ccs-date">{dateLabel}</span>}
            </header>
            <div className="ccs-result-description">
                {data.thumbnail_url !== "" &&
                    data.content_type !== "user" &&
                    data.content_type !== "profile" && (
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
        data.searchResults.length === 0 &&
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
                    setCurrentPage={data.setCurrentPage}
                    perPage={data.perPage}
                    isBusy={data.isBusy}
                />
            </div>
        );
    } else {
        return "";
    }
}
function getSearchTermFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get("search") ?? urlParams.get("q") ?? "";
}
function getDefaultEndDate() {
    return moment().format("YYYY-MM-DD");
}
function calculateTotalPages(total, per_page) {
    return Math.floor(total / per_page);
}
function setUrl(params) {
    const url = new URL(window.location.href);
    url.searchParams.delete("search");
    Object.keys(params).forEach((key) => {
        if (params[key] === "") {
            return url.searchParams.delete(key);
        } else {
            return url.searchParams.set(key, params[key]);
        }
    });
    window.history.pushState({}, "", url);
}
/**
 * Compare two objects for equality
 * @param {Object} o1
 * @param {Object} o2
 * @returns boolean
 */
function isEqual(o1, o2) {
    let result = true;
    let o1keys = Object.keys(Object(o1));
    let o2keys = Object.keys(Object(o2));
    let index = o1keys.length;
    if (o1keys.length !== o2keys.length) {
        return false;
    }
    while (index--) {
        if (!o1.hasOwnProperty(o2keys[index])) {
            return false;
        }
        if (o1[o2keys[index]] !== o2[o2keys[index]]) {
            return false;
        }
    }
    return result;
}

export default function CCSearch() {
    const [isBusy, setIsBusy] = useState(false);
    const [lastSearchParams, setLastSearchParams] = useState(null);
    const searchTerm = useFormInput(getSearchTermFromUrl());
    const searchType = useFormInput("");
    const sortBy = useFormInput("");
    const dateRange = useFormInput("anytime");
    const endDate = useFormInput(getDefaultEndDate());
    const startDate = useFormInput("");
    const [currentPage, setCurrentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [perPage] = useState(20);
    const [searchPerformed, setSearchPerformed] = useState(false);
    const [searchResults, setSearchResults] = useState([]);
    const [thisCommonsOnly, setThisCommonsOnly] = useState(false);

    async function performSearch(event) {
        if (event !== null) {
            event.preventDefault();
        }
        if (searchTerm.value === "") {
            return;
        }
        setIsBusy(true);
        const params = {
            q: searchTerm.value,
            page: currentPage,
            per_page: perPage,
            sort_by: sortBy.value,
            this_commons: thisCommonsOnly ? 1 : 0,
        };
        if (searchType.value !== "") {
            params.content_type = searchType.value;
        }
        if (dateRange.value === "custom") {
            if (startDate.value !== "") {
                params.start_date = startDate.value;
                params.end_date = endDate.value;
            }
        } else if (dateRange.value !== "anytime") {
            params.start_date = moment()
                .subtract(1, dateRange.value)
                .format("YYYY-MM-DD");
            params.end_date = moment().format("YYYY-MM-DD");
        }

        // setSearchPerformed(true);
        // setSearchResults(processResults(sampleJson.hits));
        // setTotalPages(
        //     calculateTotalPages(sampleJson.total, sampleJson.per_page) || 1,
        // );

        const url = new URL(
            "/wp-json/cc-client/v1/search",
            window.location.origin,
        );
        Object.keys(params).forEach((key) =>
            url.searchParams.append(key, params[key]),
        );
        setUrl(params);
        try {
            await fetch(url)
                .then((response) => response.json())
                .then((data) => {
                    setSearchPerformed(true);
                    setSearchResults(processResults(data.hits));
                    setTotalPages(calculateTotalPages(data.total, data.per_page) | 1);
                });
        } catch (error) {
            console.log(error)
        }

        if (lastSearchParams !== null) {
            delete lastSearchParams.page;
        }
        if (params !== null) {
            delete params.page;
        }
        if (!isEqual(lastSearchParams, params)) {
            setCurrentPage(1);
        }
        setLastSearchParams(params);
        setIsBusy(false);
    }

    useEffect(() => {
        performSearch(null);
    }, [currentPage]);

    return (
        <main className={isBusy ? "ccs-loading" : ""}>
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
                                    disabled={isBusy}
                                />
                                <button
                                    aria-label="Search"
                                    disabled={isBusy}
                                >
                                    🔍
                                </button>
                            </label>
                        </div>
                        <div className="ccs-row ccs-search-options">
                            <div className="search-option">
                                <label>
                                    <span className="ccs-label">Type</span>
                                    <br />
                                    <select
                                        {...searchType}
                                        disabled={isBusy}
                                    >
                                        <option value="">All Types</option>
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
                                    <select {...sortBy} disabled={isBusy}>
                                        <option value="">Relevance</option>
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
                                    <select {...dateRange} disabled={isBusy}>
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
                                    isBusy={isBusy}
                                />
                            </div>
                        </div>
                        <div>
                            <label>
                                <input
                                    type="checkbox"
                                    name="searchCommonsOnly"
                                    checked={thisCommonsOnly}
                                    disabled={isBusy}
                                    onChange={() =>
                                        setThisCommonsOnly(!thisCommonsOnly)
                                    }
                                />
                                <span>&nbsp;</span>
                                <span>Search only this Commons</span>
                            </label>
                        </div>
                        <div className="ccs-search-button">
                            <button type="submit" disabled={isBusy}>
                                Search
                            </button>
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
                    setCurrentPage={setCurrentPage}
                    perPage={perPage}
                    isBusy={isBusy}
                />
            </article>
        </main>
    );
}
