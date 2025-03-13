const path = require("path");

module.exports = {
  entry: "./react-src/index.js",
  output: {
    path: path.resolve(__dirname, "assets/js"),
    filename: "popup.js",
  },
  module: {
    rules: [
      {
        test: /\.(js|jsx)$/,
        exclude: /node_modules/,
        use: {
          loader: "babel-loader",
        },
      },
      {
        test: /\.scss$/,
        use: ["style-loader", "css-loader", "sass-loader"],
    },
    {
        test: /\.css$/,
        use: ["style-loader", "css-loader"],
    },
    ],
  },
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "react-src"),
  },
    extensions: [".js", ".jsx"],
  },
  mode: "development", // Tambahkan mode development
};