document.addEventListener("DOMContentLoaded", function () {
    function getCookie(name) {
        let cookies = document.cookie.split("; ");
        for (let i = 0; i < cookies.length; i++) {
            let parts = cookies[i].split("=");
            if (parts[0] === name) {
                return decodeURIComponent(parts[1]);
            }
        }
        return null;
    }

    let jwtToken = getCookie("jwt_token"); // Ambil token dari cookies

    if (jwtToken) {
        console.log("JWT Token Found in Cookies:", jwtToken);
        localStorage.setItem("jwt_token", jwtToken); // Simpan ke localStorage
    } else {
        console.warn("JWT Token not found in cookies.");
    }
});
