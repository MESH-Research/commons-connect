const { useState, useEffect } = React;
const rootElement = document.getElementById("root");
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
function CustomDateRange({ dateRangeValue }) {
	const defaultEndDate = new Date().toISOString().split("T")[0];
	const endDate = useFormInput(defaultEndDate);
	const startDate = useFormInput("");
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
					<input
						type="date"
						name="customEndDate"
						{...endDate}
						defaultValue={defaultEndDate}
					/>
				</label>
			</div>
		)
	);
}
function generateSampleJson(options) {
	const record = {
		title: "",
		description: "",
		owner_name: "",
		other_names: [],
		owner_username: "",
		other_usernames: [],
		primary_url: "",
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
const sampleResults = [
	generateSampleJson({
		title: "Result 1",
		description:
			"Cheesecake lemon drops tart macaroon jujubes pie. Bear claw tart lollipop oat cake marshmallow jujubes chocolate bar carrot cake. Candy  canes gummies dragée jelly beans chocolate cake...",
		owner_name: "Administrator",
		thumbnail_url: "https://placehold.co/200x75/000000/FFF",
		publication_date: "2023-11-21",
		language: "en",
		content_type: "site",
	}),
	generateSampleJson({
		title: "Result 2",
		description:
			"Cheesecake lemon drops tart macaroon jujubes pie. Bear claw tart lollipop oat cake marshmallow jujubes chocolate bar carrot cake. Candy  canes gummies dragée jelly beans chocolate cake...",
		language: "en",
		content_type: "group",
	}),
	generateSampleJson({
		title: "Result 3",
		description:
			"Cheesecake lemon drops tart macaroon jujubes pie. Bear claw tart lollipop oat cake marshmallow jujubes chocolate bar carrot cake. Candy  canes gummies dragée jelly beans chocolate cake...",
		language: "en",
		content_type: "profile",
	}),
	generateSampleJson({
		title: "Deposit 1",
		description:
			"Cheesecake lemon drops tart macaroon jujubes pie. Bear claw tart lollipop oat cake marshmallow jujubes chocolate bar carrot cake. Candy  canes gummies dragée jelly beans chocolate cake...",
		owner_name: "Author",
		publication_date: "2023-11-21",
		language: "en",
		content_type: "deposit",
	}),
	generateSampleJson({
		title: "On Open Scholarship",
		description:
			"An essay on the nature of open scholarship and the role of the library in supporting it.",
		owner_name: "Reginald Gibbons",
		other_names: ["Edwina Gibbons", "Obadiah Gibbons", "Lila Gibbons"],
		owner_username: "reginald",
		other_usernames: ["edwina", "obadiah", "lila"],
		primary_url: "http://works.kcommons.org/records/1234",
		other_urls: [
			"http://works.hcommons.org/records/1234",
			"http://works.mla.kcommons.org/records/1234",
			"http://works.hastac.kcommons.org/records/1234",
		],
		thumbnail_url: "http://works.kcommons.org/records/1234/thumbnail.png",
		content:
			"This is the content of the essay. It is a long essay, and it is very interesting. It is also very well-written and well-argued and well-researched and well-documented and well-cited",
		publication_date: "2018-01-01",
		modified_date: "2018-01-02",
		language: "en",
		content_type: "deposit",
		network_node: "works",
	}),
];
const resultsData = sampleResults;
function getContentTypeLabel(type) {
	const labels = {
		profile: "Profile",
		group: "Group",
		site: "Site",
		deposit: "Work/Deposit",
	};
	return labels[type] ?? "";
}
function getDateLabel(publication_date, modified_date) {
	let date = "";
	if (publication_date) {
		date = new Date(
			Date.parse(publication_date + "T00:00:00.000-05:00"),
		).toDateString();
	}
	if (modified_date) {
		date = "Updated: " + new Date(
			Date.parse(modified_date + "T00:00:00.000-05:00"),
		).toDateString();
	}
	return date;
}
function SearchResult({ data, index }) {
	const dateLabel = getDateLabel(data.publication_date, data.modified_date);
	return (
		<section className="ccs-result" key={index}>
			<header className="ccs-row ccs-result-header">
				<span className="ccs-tag">
					{getContentTypeLabel(data.content_type)}
				</span>
				<a href={data.primary_url} className="ccs-result-title">
					{data.title}
				</a>
				{data.owner_name && (
					<a href="#" className="ccs-result-person">
						{data.owner_name}
					</a>
				)}
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
				<p>{data.description}</p>
			</div>
		</section>
	);
}
function CCSearch() {
	const searchType = useFormInput("all");
	const sortBy = useFormInput("relevance");
	const dateRange = useFormInput("anytime");
	return (
		<main>
			<article className="ccs-row ccs-top">
				<search className="ccs-search">
					<form>
						<div className="ccs-row ccs-search-input">
							<label>
								<span className="ccs-label">Search</span>
								<br />
								<input type="search" name="ccSearch" />
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
										<option value="profile">Profile</option>
										<option value="site">Site</option>
										<option value="group">Group</option>
										<option value="work">Deposit/Work</option>
									</select>
								</label>
							</div>
							<div className="search-option">
								<label>
									<span className="ccs-label">Sort By</span>
									<br />
									<select {...sortBy}>
										<option value="relevance">Relevance</option>
										<option value="date">Date Updated</option>
									</select>
								</label>
							</div>
							<div className="search-option">
								<label>
									<span className="ccs-label">Date Range</span>
									<br />
									<select {...dateRange}>
										<option value="anytime">Anytime</option>
										<option value="week">Past Week</option>
										<option value="month">Past Month</option>
										<option value="year">Past Year</option>
										<option value="custom">Custom</option>
									</select>
								</label>
								<CustomDateRange dateRangeValue={dateRange.value} />
							</div>
						</div>
						<div>
							<label>
								<input type="checkbox" name="searchCommonsOnly" />
								<span>&nbsp;</span>
								<span>Search only this Commons</span>
							</label>
						</div>
					</form>
				</search>
				<aside className="ccs-aside">
					<p>Want to search deposits/works more specifically?</p>
					<a href="#">Advanced Search</a>
				</aside>
			</article>
			<article>
				{resultsData.map(function (result, i) {
					return <SearchResult index={i} data={result} />;
				})}
			</article>
			<footer className="ccs-footer">
				<span>Previous</span>
				<span>1</span>
				<a href="#">2</a>
				<a href="#">3</a>
				<a href="#">Next</a>
			</footer>
		</main>
	);
}
const app = <CCSearch />;
ReactDOM.render(app, rootElement);
