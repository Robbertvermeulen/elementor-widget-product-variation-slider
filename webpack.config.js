const path = require("path");
const defaults = require("@wordpress/scripts/config/webpack.config");

module.exports = {
  ...defaults,
  mode: "development",
  entry: {
    main: path.resolve(__dirname, "src/index.js"),
  },
  output: {
    path: path.resolve(__dirname, "assets/js"),
    filename: "[name].js",
  },
  externals: {
    react: "React",
    "react-dom": "ReactDOM",
  },
};
