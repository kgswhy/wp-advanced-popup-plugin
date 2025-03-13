import React from "react";
import ReactDOM from "react-dom";
import Dashboard from "./components/dashboard";
import PopupForm from "./components/addPopup";

document.addEventListener("DOMContentLoaded", function () {
    const rootElement = document.getElementById("Root");
    if (rootElement) {
        ReactDOM.render(<Dashboard />, rootElement);
    }
});
document.addEventListener("DOMContentLoaded", function () {
    const rootElement = document.getElementById("addPopup");
    if (rootElement) {
        ReactDOM.render(<PopupForm />, rootElement);
    }
});
