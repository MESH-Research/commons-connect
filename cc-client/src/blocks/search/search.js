import { useRef, useEffect, useState } from "@wordpress/element";

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
        totalPages: totalPages.current,
        perPage: perPage.current,
    });
    console.log(pageData)
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
const sampleResults = [
    {
        title: "Result 1",
        description:
            "Cheesecake lemon drops tart macaroon jujubes pie. Bear claw tart lollipop oat cake marshmallow jujubes chocolate bar carrot cake. Candy  canes gummies dragée jelly beans chocolate cake...",
        owner: {
            name: "Administrator",
            username: "reginald",
            url: "http://profiles.kcommons.org/reginald",
        },
        thumbnail_url: "https://placehold.co/200x75/000000/FFF",
        publication_date: "2023-11-21",
        language: "en",
        content_type: "site",
    },
    {
        title: "Result 2",
        description:
            "Cheesecake lemon drops tart macaroon jujubes pie. Bear claw tart lollipop oat cake marshmallow jujubes chocolate bar carrot cake. Candy  canes gummies dragée jelly beans chocolate cake...",
        language: "en",
        content_type: "group",
    },
    {
        title: "Result 3",
        description:
            "Cheesecake lemon drops tart macaroon jujubes pie. Bear claw tart lollipop oat cake marshmallow jujubes chocolate bar carrot cake. Candy  canes gummies dragée jelly beans chocolate cake...",
        language: "en",
        content_type: "profile",
    },
    {
        title: "Deposit 1",
        description:
            "Cheesecake lemon drops tart macaroon jujubes pie. Bear claw tart lollipop oat cake marshmallow jujubes chocolate bar carrot cake. Candy  canes gummies dragée jelly beans chocolate cake...",
        owner: {
            name: "Author",
            url: "",
        },
        publication_date: "2023-11-21",
        language: "en",
        content_type: "deposit",
    },
    {
        title: "On Open Scholarship",
        description:
            "An essay on the nature of open scholarship and the role of the library in supporting it.",
        owner: {
            name: "Reginald Gibbons",
            username: "reginald",
            url: "http://profiles.kcommons.org/reginald",
        },
        contributors: [
            {
                name: "Reginald Gibbons",
                username: "reginald",
                url: "http://profiles.kcommons.org/reginald",
                role: "first author",
                network_node: "mla",
            },
            {
                name: "Edwina Gibbons",
                username: "edwina",
                url: "http://profiles.kcommons.org/edwina",
                role: "author",
                network_node: "hc",
            },
            {
                name: "Obadiah Gibbons",
                username: "obadiah",
            },
            {
                name: "Lila Gibbons",
                username: "lila",
            },
        ],
        primary_url: "http://works.kcommons.org/records/1234",
        other_urls: [
            "http://works.hcommons.org/records/1234",
            "http://works.mla.kcommons.org/records/1234",
            "http://works.hastac.kcommons.org/records/1234",
        ],
        thumbnail_url: "https://placehold.co/75x200/000000/FFF",
        content:
            "This is the content of the essay. It is a long essay, and it is very interesting. It is also very well-written and well-argued and well-researched and well-documented and well-cited",
        publication_date: "2018-01-01",
        modified_date: "2018-01-02",
        language: "en",
        content_type: "deposit",
        network_node: "works",
    },
];
console.log(sampleResults);
const resultsData = [];
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
console.log(generateSampleJson)
function pushResults(data) {
    data.forEach((result) => {
        resultsData.push(generateSampleJson(result));
    });
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
        <section>
            <p>No results found.</p>
        </section>
    );
}
function SearchResultSection(searchTerm) {
    if (resultsData.length === 0 && searchTerm.value !== "") {
        return <NoData />;
    } else if (resultsData.length > 0 && searchTerm.value !== "") {
        return resultsData.map(function (result, i) {
            return <SearchResult key={i} data={result} />;
        });
    } else {
        return "";
    }
}

function getSearchTermFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get("search") ?? "";
}

export default function CCSearch() {

    const defaultEndDate = new Date().toISOString().split("T")[0];
    const searchTerm = useFormInput(getSearchTermFromUrl());
    const searchType = useFormInput("all");
    const sortBy = useFormInput("relevance");
    const dateRange = useFormInput("anytime");
    const endDate = useFormInput(defaultEndDate);
    const startDate = useFormInput("");
    const currentPage = useRef(1);
    const totalPages = useRef(1);
    const perPage = useRef(20);

    function performSearch(event) {
        if (event !== null) {
            event.preventDefault();
        }
        const params = {
            sort_by: sortBy.value,
            content_type: searchType.value,
            page: currentPage.current,
            per_page: perPage.current,
            q: searchTerm.value,
        };
        if (dateRange.value === "custom") {
            params.start_date = startDate.value;
            params.end_date = endDate.value;
        }

        const url = new URL(
            "https://commons-connect-client.lndo.site/wp-json/cc-client/v1/search",
        );
        Object.keys(params).forEach((key) =>
            url.searchParams.append(key, params[key]),
        );

        fetch(url)
            .then((response) => response.json())
            .then((data) => {
                const parsed = JSON.parse(data);
                console.log(parsed)
                pushResults(JSON.parse(data).hits);
                totalPages.current = JSON.parse(data).total_pages;
            });
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
            <article>
                <SearchResultSection searchTerm={searchTerm} />
                {resultsData.length > 0 && (
                    <Paginator
                        totalPages={totalPages}
                        currentPage={currentPage}
                        perPage={perPage}
                    />
                )}
            </article>
        </main>
    );
}
