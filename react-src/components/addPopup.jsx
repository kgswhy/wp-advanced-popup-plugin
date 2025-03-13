import { useState, useEffect } from "react";
import { useForm } from "react-hook-form";
import Select from "react-select"; 
import "../../assets/scss/main.scss"; 

const API_URL = "http://localhost:10004/wp-json/artistudio/v1/popup";
const TOKEN_URL = "http://localhost:10004/wp-json/artistudio/v1/get-token";

const PopupForm = () => {
    const [pages, setPages] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [success, setSuccess] = useState("");
    const [token, setToken] = useState(null);
    
    const { register, handleSubmit, setValue, watch } = useForm({
        defaultValues: {
            popup_type: "modal",
            popup_status: "inactive",  // ✅ Tambahkan status default
            targeted_pages: []
        }
    });

    useEffect(() => {
        const fetchToken = async () => {
            try {
                const response = await fetch(TOKEN_URL, { credentials: "include" });
                const data = await response.json();

                if (data.token) {
                    console.log("Token berhasil didapatkan:", data.token);
                    setToken(data.token);
                } else {
                    throw new Error("Token tidak ditemukan");
                }
            } catch (error) {
                console.error("Error mengambil token:", error.message);
                setError("Gagal mengambil token. Harap coba lagi.");
            }
        };

        const fetchPages = async () => {
            try {
                const response = await fetch("/wp-json/wp/v2/pages?per_page=100");
                const data = await response.json();
                setPages(data.map(page => ({ value: page.id, label: page.title.rendered })));
            } catch (error) {
                console.error("Gagal memuat halaman:", error);
                setError("Gagal memuat halaman. Coba refresh halaman.");
            } finally {
                setLoading(false);
            }
        };

        fetchToken();
        fetchPages();
    }, []);

    const onSubmit = async (data) => {
        if (!token) {
            setError("Token tidak ditemukan. Harap refresh halaman.");
            return;
        }

        try {
            setError("");
            setSuccess("");

            const payload = {
                popup_name: data.popup_name,
                popup_content: data.popup_content,
                popup_type: data.popup_type || "modal",
                popup_status: data.popup_status || "inactive",  // ✅ Kirim status popup
                targeted_pages: Array.isArray(data.targeted_pages) ? data.targeted_pages.map(p => p.value) : [],
            };

            console.log("Payload yang dikirim ke API:", payload);

            const response = await fetch(API_URL, {
                method: "POST",
                credentials: "include",
                headers: {
                    "Content-Type": "application/json",
                    "Authorization": `Bearer ${token}`,
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || "Gagal menyimpan popup");
            }

            setSuccess("Popup berhasil disimpan!");
            
            // Reset form setelah sukses
            setTimeout(() => {
                setValue("popup_name", "");
                setValue("popup_content", "");
                setValue("popup_type", "modal");
                setValue("popup_status", "inactive");
                setValue("targeted_pages", []);
                setSuccess("");
            }, 2000);
        } catch (error) {
            setError(error.message);
        }
    };

    return (
        <div className="popup-form-container">
            <div className="form-card">
                <div className="card-header">
                    <h1 className="card-title">Tambah Popup Baru</h1>
                </div>

                {error && <div className="alert error">{error}</div>}
                {success && <div className="alert success">{success}</div>}

                <form onSubmit={handleSubmit(onSubmit)} className="form-content">
                    <div className="form-group">
                        <label htmlFor="popup_name" className="form-label">Nama Popup</label>
                        <input 
                            {...register("popup_name", { required: true })} 
                            type="text" 
                            id="popup_name" 
                            className="form-input"
                            placeholder="Masukkan nama popup"
                        />
                    </div>

                    <div className="form-group">
                        <label htmlFor="popup_type" className="form-label">Tipe Popup</label>
                        <select 
                            {...register("popup_type")} 
                            id="popup_type" 
                            className="form-select"
                            defaultValue="modal"
                        >
                            <option value="modal">Modal</option>
                            <option value="slide-in">Slide-in</option>
                        </select>
                    </div>

                    <div className="form-group">
                        <label htmlFor="popup_status" className="form-label">Status Popup</label>
                        <select 
                            {...register("popup_status")} 
                            id="popup_status" 
                            className="form-select"
                            defaultValue="inactive"
                        >
                            <option value="active">Aktif</option>
                            <option value="inactive">Nonaktif</option>
                        </select>
                    </div>

                    <div className="form-group">
                        <label htmlFor="popup_content" className="form-label">Konten Popup</label>
                        <textarea 
                            {...register("popup_content", { required: true })} 
                            id="popup_content" 
                            className="form-textarea" 
                            rows="6"
                            placeholder="Tulis konten popup di sini..."
                        />
                    </div>

                    <div className="form-group">
                        <label className="form-label">Target Halaman</label>
                        {loading ? (
                            <div className="loading-text">Memuat halaman...</div>
                        ) : (
                            <Select
                                options={pages}
                                isMulti
                                className="react-select-container"
                                classNamePrefix="react-select"
                                onChange={(selectedOptions) => setValue("targeted_pages", selectedOptions || [])}
                                placeholder="Pilih halaman target..."
                                noOptionsMessage={() => "Tidak ada halaman tersedia"}
                                value={watch("targeted_pages") || []} 
                            />
                        )}
                    </div>

                    <div className="form-actions">
                        <button 
                            type="submit" 
                            className="submit-button" 
                            disabled={loading}
                        >
                            {loading ? "Menyimpan..." : "Simpan Popup"}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default PopupForm;
