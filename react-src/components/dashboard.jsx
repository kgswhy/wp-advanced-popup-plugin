import { useState, useEffect } from "react";
import "../../assets/scss/main.scss"; // Import SCSS

const API_URL = "http://localhost:10004/wp-json/artistudio/v1/popup";
const PAGES_API_URL = "http://localhost:10004/wp-json/wp/v2/pages"; // API untuk halaman

const Dashboard = () => {
    const [popups, setPopups] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [pageNames, setPageNames] = useState({}); // Untuk mapping ID halaman ke Nama Halaman

    useEffect(() => {
        fetchPopups();
    }, []);

    const fetchPopups = async () => {
        try {
            console.log("Fetching data from API...");

            const response = await fetch(API_URL, {
                method: "GET",
                credentials: "include",
                headers: {
                    "Content-Type": "application/json",
                },
            });

            if (!response.ok) {
                if (response.status === 401) {
                    throw new Error("Unauthorized. Silakan login kembali.");
                } else if (response.status === 404) {
                    setPopups([]);
                    setLoading(false);
                    return;
                } else {
                    throw new Error("Gagal mengambil data.");
                }
            }

            const data = await response.json();
            console.log("Data popup:", data);

            setPopups(data);
            setLoading(false);

            // Ambil semua ID halaman dari popup yang ditargetkan
            const allPageIds = [...new Set(data.flatMap(popup => popup.targeted_pages || []))];
            if (allPageIds.length > 0) {
                fetchPageNames(allPageIds);
            }
        } catch (error) {
            console.error("Error fetching popups:", error);
            setError(error.message);
            setLoading(false);
        }
    };

    const fetchPageNames = async (pageIds) => {
        try {
            const responses = await Promise.all(
                pageIds.map(id => fetch(`${PAGES_API_URL}/${id}`).then(res => res.ok ? res.json() : null))
            );

            const pageNameMap = {};
            responses.forEach(page => {
                if (page) {
                    pageNameMap[page.id] = page.title.rendered; // Simpan ID ke Nama Halaman
                }
            });

            setPageNames(pageNameMap);
        } catch (error) {
            console.error("Error fetching page names:", error);
        }
    };

    const handleDelete = async (popupId) => {
        const confirmDelete = window.confirm("Apakah Anda yakin ingin menghapus popup ini?");
        if (!confirmDelete) return;
    
        try {
            const response = await fetch(`${API_URL}/${popupId}`, {
                method: "DELETE",
                credentials: "include",
                headers: {
                    "Content-Type": "application/json",
                },
            });
    
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || "Gagal menghapus popup.");
            }
    
            // ✅ Hapus dari state setelah berhasil dihapus di server
            setPopups((prevPopups) => prevPopups.filter((popup) => popup.id !== popupId));
    
            alert("Popup berhasil dihapus!");
        } catch (error) {
            console.error("Error deleting popup:", error);
            alert(error.message);
        }
    };

    return (
        <div className="dashboard-container">
            <h1>WP Advanced Popups - Dashboard</h1>
            <h2>Daftar Popups</h2>

            {loading ? (
                <p>Loading...</p>
            ) : error ? (
                <p style={{ color: "red" }}>{error}</p>
            ) : (
                <table className="popup-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Popup</th>
                            <th>Tipe</th>
                            <th>Status</th>
                            <th>Target Page</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {popups.length > 0 ? (
                            popups.map((popup) => (
                                <tr key={popup.id}>
                                    <td>{popup.id}</td>
                                    <td>{popup.name}</td>
                                    <td>{popup.popup_type}</td>
                                    <td className={popup.popup_status === "active" ? "status-active" : "status-inactive"}>
                                        {popup.popup_status}
                                    </td>
                                    <td>
                                        {
                                            (popup.targeted_pages || [])
                                                .map(id => pageNames[id] || `ID: ${id}`)
                                                .join(", ")
                                        }
                                    </td>
                                    <td>
                                        <button className="delete-button" onClick={() => handleDelete(popup.id)}>
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td colSpan="6">Belum ada popup.</td>
                            </tr>
                        )}
                    </tbody>
                </table>
            )}
        </div>
    );
};

export default Dashboard;
