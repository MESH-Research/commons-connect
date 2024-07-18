import apiFetch from "@wordpress/api-fetch";

import { useState, useEffect } from "@wordpress/element";

import { Button } from "@wordpress/components";

type SiteOptions = {
    cc_server_url: string;
    cc_search_endpoint: string;
    cc_search_key: string;
    search_page_id: number;
};

export const SettingsPanel = () => {
    const [siteOptions, updateSiteOptions] = useState({
        cc_server_url: "",
        cc_search_endpoint: "",
        cc_search_key: "",
        search_page_id: 0,
    });

    const { cc_server_url, cc_search_endpoint, cc_search_key, search_page_id } =
        siteOptions as SiteOptions;

    useEffect(() => {
        refreshSiteOptions();
    }, []);

    const refreshSiteOptions = () => {
        apiFetch({
            path: "/cc-client/v1/options",
        }).then((options) => {
            updateSiteOptions(options as SiteOptions);
        });
    };

    const doSave = () => {
        apiFetch({
            path: "/cc-client/v1/options",
            method: "POST",
            data: siteOptions,
        }).then((response) => {
            console.log(response);
        });
    };

    return (
        <div>
            <h1>CommonsConnect Settings</h1>
            <div id="settings-form">
                <label>CommonsConnect server URL:</label>
                <input
                    type="text"
                    value={cc_server_url}
                    name="cc_server_url"
                    onChange={(event) => {
                        updateSiteOptions({
                            ...siteOptions,
                            cc_server_url: event.target.value,
                        });
                    }}
                />

                <label>CommonsConnect search endpoint:</label>
                <input
                    type="text"
                    value={cc_search_endpoint}
                    name="cc_search_endpoint"
                    onChange={(event) => {
                        updateSiteOptions({
                            ...siteOptions,
                            cc_search_endpoint: event.target.value,
                        });
                    }}
                />

                <label>CommonsConnect server API key:</label>
                <input
                    type="text"
                    value={cc_search_key}
                    name="cc_search_key"
                    onChange={(event) => {
                        updateSiteOptions({
                            ...siteOptions,
                            cc_search_key: event.target.value,
                        });
                    }}
                />

                <label>Search Page ID:</label>
                <input
                    type="number"
                    value={search_page_id}
                    name="search_page_id"
                    onChange={(event) => {
                        updateSiteOptions({
                            ...siteOptions,
                            search_page_id: parseInt(event.target.value),
                        });
                    }}
                />
            </div>
            <Button variant="primary" onClick={doSave}>
                Save
            </Button>
        </div>
    );
};
