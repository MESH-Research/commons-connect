const { useState, useEffect } = React;
const rootElement = document.getElementById("root");
function useFormInput(initialValue) {
  const [value, setValue] = useState(initialValue);
  function handleChange(e) {
    setValue(e.target.value);
  }
  return {
    value,
    onChange: handleChange
  };
}
function CustomDateRange({ dateRangeValue }) {
  const endDate = new Date().toISOString().split("T")[0];
  if (dateRangeValue != "custom") {
    return null;
  }
  return (
    <div className="ccs-row ccs-date-ranges">
      <label>
        <span>Start Date</span>
        <br />
        <input type="date" />
      </label>
      <label>
        <span>End Date</span>
        <br />
        <input type="date" defaultValue={endDate} />
      </label>
    </div>
  );
}
const sampleJson1 = {
	"title": "On Open Scholarship",
	"description": "An essay on the nature of open scholarship and the role of the library in supporting it.",
	"owner_name": "Reginald Gibbons",
	"other_names": [
		"Edwina Gibbons",
		"Obadiah Gibbons",
		"Lila Gibbons"
	],
	"owner_username": "reginald",
	"other_usernames": [
		"edwina",
		"obadiah",
		"lila"
	],
	"primary_url": "http://works.kcommons.org/records/1234",
	"other_urls": [
		"http://works.hcommons.org/records/1234",
		"http://works.mla.kcommons.org/records/1234",
		"http://works.hastac.kcommons.org/records/1234"
	],
	"thumbnail_url": "http://works.kcommons.org/records/1234/thumbnail.png",
	"content": "This is the content of the essay. It is a long essay, and it is very interesting. It is also very well-written and well-argued and well-researched and well-documented and well-cited",
	"publication_date": "2018-01-01",
	"modified_date": "2018-01-02",
	"language": "en",
	"content_type": "deposit",
	"network_node": "works"
}
const sampleResults = [
  sampleJson1,
  sampleJson1,
]
function getContentTypeLabel(type) {
  const labels = {
    'profile': "Profile",
    'group': "Group",
    'site': "Site",
    'deposit': "Work/Deposit"
  }
  return labels[type] ?? ""
}
function SearchResult({result}) {
  return (
    <section className="ccs-result">
      <header className="ccs-row ccs-result-header">
        <span className="ccs-tag">{getContentTypeLabel(result.content_type)}</span>
        <a href="#" className="ccs-result-title">
          Result 1
        </a>
        <a href="#" className="ccs-result-person">
          Administrator
        </a>
        <span className="ccs-date">November 21st, 2023</span>
      </header>
      <div className="ccs-result-description">
        <img
          src="https://placehold.co/200x75/000000/FFF"
          alt="Placeholder image that reads '200 x 75' in sans-serif white text centered on a black background."
          className="ccs-result-thumbnail"
          />
        <p>
          Cheesecake lemon drops tart macaroon jujubes pie. Bear claw tart
          lollipop oat cake marshmallow jujubes chocolate bar carrot cake.
          Candy canes gummies dragée jelly beans chocolate cake...
        </p>
      </div>
    </section>
  )
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
                <input type="search" />
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
                <input type="checkbox" />
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
        <section className="ccs-result">
          <header className="ccs-row ccs-result-header">
            <span className="ccs-tag">Site</span>
            <a href="#" className="ccs-result-title">
              Result 1
            </a>
            <a href="#" className="ccs-result-person">
              Administrator
            </a>
            <span className="ccs-date">November 21st, 2023</span>
          </header>
          <div className="ccs-result-description">
            <img
              src="https://placehold.co/200x75/000000/FFF"
              alt="Placeholder image that reads '200 x 75' in sans-serif white text centered on a black background."
              className="ccs-result-thumbnail"
            />
            <p>
              Cheesecake lemon drops tart macaroon jujubes pie. Bear claw tart
              lollipop oat cake marshmallow jujubes chocolate bar carrot cake.
              Candy canes gummies dragée jelly beans chocolate cake...
            </p>
          </div>
        </section>
        <section className="ccs-result">
          <header className="ccs-row ccs-result-header">
            <span className="ccs-tag">Group</span>
            <a href="#" className="ccs-result-title">
              Result 2 - Are thumbnail propotions guaranteed to be fixed?
            </a>
          </header>
          <div className="ccs-row ccs-result-description">
            <p>
              Cheesecake lemon drops tart macaroon jujubes pie. Bear claw tart
              lollipop oat cake marshmallow jujubes chocolate bar carrot cake.
              Candy canes gummies dragée jelly beans chocolate cake...
            </p>
          </div>
        </section>
        <section className="ccs-result">
          <header className="ccs-row ccs-result-header">
            <span className="ccs-tag">Profile</span>
            <a href="#" className="ccs-result-title">
              Result 3
            </a>
          </header>
          <div className="ccs-row ccs-result-description">
            <p>
              Cheesecake lemon drops tart macaroon jujubes pie. Bear claw tart
              lollipop oat cake marshmallow jujubes chocolate bar carrot cake.
              Candy canes gummies dragée jelly beans chocolate cake...
            </p>
          </div>
        </section>
        <section className="ccs-result">
          <header className="ccs-row ccs-result-header">
            <span className="ccs-tag">Site</span>
            <a href="#" className="ccs-result-title">
              Result 4
            </a>
            <a href="#" className="ccs-result-person">
              Administrator
            </a>
            <span className="ccs-date">November 21st, 2023</span>
          </header>
          <div className="ccs-result-description">
            <img
              src="https://placehold.co/50x50/000000/FFF"
              alt="Placeholder image that reads '500 x 50' in sans-serif white text centered on a black background."
              className="ccs-result-thumbnail"
            />
            <p>
              Cheesecake lemon drops tart macaroon jujubes pie. Bear claw tart
              lollipop oat cake marshmallow jujubes chocolate bar carrot cake.
              Candy canes gummies dragée jelly beans chocolate cake...
            </p>
          </div>
        </section>
        <section className="ccs-result">
          <header className="ccs-row ccs-result-header">
            <span className="ccs-tag">Work</span>
            <a href="#" className="ccs-result-title">
              Result 5
            </a>
            <a href="#" className="ccs-result-person">
              Author
            </a>
            <span className="ccs-date">November 21st, 2023</span>
          </header>
          <div className="ccs-result-description">
            <p>
              Cheesecake lemon drops tart macaroon jujubes pie. Bear claw tart
              lollipop oat cake marshmallow jujubes chocolate bar carrot cake.
              Candy canes gummies dragée jelly beans chocolate cake...
            </p>
          </div>
        </section>
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
