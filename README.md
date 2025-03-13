# 🚀 WP Advanced Popup Plugin

## 📌 Deskripsi
🖥️ **WP Advanced Popup Plugin** adalah plugin WordPress yang memungkinkan Anda menampilkan popup dengan mudah dan fleksibel. Plugin ini dibuat menggunakan **React** untuk tampilan frontend dan **Webpack** sebagai module bundler.

## ✨ Fitur
- ⚛️ **React** sebagai frontend interaktif.
- 📦 **Webpack** untuk bundling modern.
- 🎯 **react-hook-form** untuk manajemen form yang efisien.
- 🔔 **react-toastify** untuk notifikasi user-friendly.
- 🔗 **axios** untuk komunikasi API dengan WordPress.
- ⏳ **Popup muncul selama 2 detik** sesuai dengan targeted pages.

## ⚙️ Instalasi

### 1️⃣ Clone Repository
```sh
git clone https://github.com/username/wp-advanced-popup-plugin.git
cd wp-advanced-popup-plugin
```

### 2️⃣ Instalasi Dependencies
Jalankan perintah berikut untuk menginstal semua dependencies yang diperlukan:
```sh
npm install
```

### 3️⃣ Build Frontend
Untuk membangun React dan menggabungkannya ke dalam WordPress, jalankan perintah berikut:
```sh
npm run build
```
Jika ingin menjalankan mode pengembangan dengan live-reload:
```sh
npm run start
```

### 4️⃣ Aktifkan Plugin di WordPress
1. 📂 Pindahkan folder `wp-advanced-popup-plugin` ke dalam folder `wp-content/plugins/` di instalasi WordPress Anda.
2. 🔑 Login ke dashboard WordPress.
3. ⚙️ Pergi ke **Plugins** → **Installed Plugins**.
4. ✅ Aktifkan **WP Advanced Popup Plugin**.

## 📁 Struktur Direktori
```
wp-advanced-popup-plugin/
│── 📂 assets/
│   ├── 🎨 css/
│   ├── 🛠️ js/
│   ├── 🎭 scss/
│── 📂 includes/
│   ├── 📝 class-admin.php
│   ├── 🛡️ class-api.php
│── 📂 react-src/
│   ├── 📦 components/
│   │   ├── ➕ addPopup.jsx
│   │   ├── 📊 dashboard.jsx
│   ├── 🔌 index.js
│── 📂 vendor/
│── ⚙️ .babelrc
│── 📜 composer.json
│── 📦 package.json
│── 🔧 webpack.config.js
│── 🏗️ wp-advanced-popup-plugin.php
```

## 🎮 Penggunaan
Setelah plugin diaktifkan, Anda dapat menggunakannya di dalam WordPress. **Popup akan muncul secara otomatis sesuai dengan halaman yang ditargetkan dan akan menghilang setelah 2 detik.**

## 🎥 Demo Popup
<video width="600" controls>
  <source src="/demo/demo.mov" type="video/mp4">
  Your browser does not support the video tag.
</video>


## 🤝 Kontribusi
Jika ingin berkontribusi, silakan fork repository ini dan buat pull request dengan perubahan yang Anda buat.

## 📜 Lisensi
Plugin ini dilisensikan di bawah [MIT License](LICENSE).
