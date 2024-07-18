const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const path = require("path");

const defaultEntry = defaultConfig.entry();

module.exports = {
    ...defaultConfig,
    entry: {
        ...defaultEntry,
        "admin/admin": "./src/admin/admin.tsx",
    },
};
