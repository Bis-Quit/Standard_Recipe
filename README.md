# 🍲 Standard Recipe Management System

> **Aplikasi manajemen resep dan costing hotel berbasis web, dirancang untuk menggantikan input manual Excel dengan antarmuka yang modern dan mudah digunakan.**

![Project Banner](www/assets/app-logo.png)

---

## 📖 Tentang Project
Project ini dibuat untuk menyelesaikan masalah efisiensi di departemen **Finance & Kitchen**. Sebelumnya, penghitungan *Recipe Costing* dilakukan manual menggunakan Excel yang rentan human-error dan sulit dikelola datanya.

Sistem ini hadir sebagai solusi otomasi yang mempermudah proses input, perhitungan HPP (Harga Pokok Penjualan), hingga reporting, dengan *User Interface* (UI) yang dirancang khusus agar mudah dipahami oleh pengguna non-teknis (User Friendly).

## ✨ Fitur Utama
* **dashboard Interaktif:** Tampilan data resep yang rapi mirip format tabel standar industri.
* **Import Data Mastery:** Support input data bahan baku via file **CSV** dan **Excel**.
* **Smart Calculation:** Menghitung total cost otomatis berdasarkan quantity bahan.
* **Export Fleksibel:** Laporan resep bisa di-export kembali ke format **CSV** atau **Excel**.
* **Database Ringan:** Menggunakan **SQLite** sehingga mudah dipindahkan (portable) tanpa setup server database yang rumit.

## 🛠️ Tech Stack
Project ini dibangun dengan teknologi yang efisien dan mudah di-deploy:
* **Bahasa:** PHP (Native)
* **Database:** SQLite (`hotel_system.db`)
* **Frontend:** HTML5, CSS3 (Custom Styling)
* **Library:** PHPSpreadsheet (untuk fitur Excel)

## 📸 Screenshots
*(Tempatkan screenshot aplikasi di sini nanti biar makin kece)*

| Dashboard Utama | Detail Resep |
| :---: | :---: |
<<<<<<< HEAD
| ![Dashboard]<img width="3384" height="1864" alt="image" src="https://github.com/user-attachments/assets/ff14bc16-981a-4d20-a1e1-851b635b7fcc" /> | ![Detail]<img width="3384" height="1864" alt="image" src="https://github.com/user-attachments/assets/15b78d74-c3f5-4547-97ef-d4d5bb054cea" /> |
=======
| <img width="3384" height="1864" alt="image" src="https://github.com/user-attachments/assets/ff14bc16-981a-4d20-a1e1-851b635b7fcc" /> | <img width="3384" height="1864" alt="image" src="https://github.com/user-attachments/assets/15b78d74-c3f5-4547-97ef-d4d5bb054cea" /> |
>>>>>>> 4af7256f3920dc805c70c8016440fcd8fa127cdc

---

## 🚀 Cara Instalasi (Localhost)
1.  Clone repository ini atau download ZIP.
2.  Pastikan PC sudah terinstall **XAMPP** atau **PHP**.
3.  Pindahkan folder project ke `htdocs` (jika pakai XAMPP).
4.  Jalanan command di terminal folder project:
    ```bash
    php -S localhost:8000 -t www
    ```
5.  Buka browser dan akses `http://localhost:8000`.

---
