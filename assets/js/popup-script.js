document.addEventListener("DOMContentLoaded", () => {
  if (window.hasPopupLoaded) return; // ✅ Cegah script dipanggil lebih dari sekali
  window.hasPopupLoaded = true;

  fetch(wpPopupData.apiUrl)
      .then(response => response.json())
      .then(data => {
          console.log("Popup Data (Raw):", data);

          // Hapus duplikasi dalam data API (jika ada)
          const uniquePopups = [];
          const popupIds = new Set();

          data.forEach(popup => {
              if (!popupIds.has(popup.id)) {
                  popupIds.add(popup.id);
                  uniquePopups.push(popup);
              }
          });

          const matchedPopups = uniquePopups.filter(popup => {
              try {
                  // ✅ Pastikan hanya popup dengan status "active"
                  if (popup.popup_status !== "active") {
                      console.warn(`Popup ID: ${popup.id} tidak ditampilkan karena statusnya inactive.`);
                      return false;
                  }

                  let targetedPages = popup.targeted_pages;

                  if (typeof targetedPages === "string" && targetedPages.trim() !== "") {
                      targetedPages = JSON.parse(targetedPages);
                  }

                  if (!Array.isArray(targetedPages)) {
                      console.warn(`Popup ID: ${popup.id} - targeted_pages bukan array:`, targetedPages);
                      return false;
                  }

                  console.log(`Popup ID: ${popup.id} - Parsed Targeted Pages:`, targetedPages);
                  console.log("Current Page ID:", Number(wpPopupData.currentPageId));

                  return targetedPages.includes(Number(wpPopupData.currentPageId));
              } catch (error) {
                  console.error(`Error parsing targeted_pages for Popup ID: ${popup.id}`, error);
                  return false;
              }
          });

          console.log("Matched Popups:", matchedPopups);

          if (matchedPopups.length > 0) {
              setTimeout(() => {
                  matchedPopups.forEach(popup => showPopup(popup));
              }, 2000);
          }
      })
      .catch(error => console.error("Error fetching popup data:", error));
});

function showPopup(popup) {
  // ✅ Cek apakah popup dengan ID ini sudah ada di dalam DOM
  if (document.querySelector(`.custom-popup[data-popup-id="${popup.id}"]`)) {
      console.warn(`Popup ID: ${popup.id} sudah ada, tidak ditampilkan lagi.`);
      return;
  }

  const popupContainer = document.createElement("div");
  popupContainer.classList.add("custom-popup");
  popupContainer.setAttribute("data-popup-id", popup.id);

  // Menentukan kelas tambahan berdasarkan tipe popup
  if (popup.popup_type === "slide-in") {
      popupContainer.classList.add("slide-in-popup");
  } else {
      popupContainer.classList.add("modal-popup"); // Default modal
  }

  popupContainer.innerHTML = `
      <div class="popup-content">
          <h2>${popup.name}</h2>
          <div class="popup-body">${popup.content}</div>
          <button class="close-popup">Tutup</button>
      </div>
  `;

  document.body.appendChild(popupContainer);

  // Tambahkan animasi dengan opacity dan transform
  setTimeout(() => {
      popupContainer.style.opacity = "1";
      popupContainer.style.transform = "translateY(0)";
  }, 100); // Delay untuk efek animasi

  // Event listener untuk tombol tutup
  popupContainer.querySelector(".close-popup").addEventListener("click", () => {
      closePopup(popupContainer);
  });

  // **Tipe Exit-Intent**: Muncul saat pengguna mencoba keluar dari halaman
  if (popup.popup_type === "exit-intent") {
      let hasShownExitIntent = false; // Cegah muncul lebih dari sekali
      document.addEventListener("mouseleave", (event) => {
          if (event.clientY <= 0 && !hasShownExitIntent) {
              popupContainer.style.display = "block";
              hasShownExitIntent = true;
          }
      });
  }
}

function closePopup(popupContainer) {
  popupContainer.style.opacity = "0";
  popupContainer.style.transform = "translateY(-20px)"; // Efek slide-out
  setTimeout(() => popupContainer.remove(), 300); // Hapus setelah animasi selesai
}
