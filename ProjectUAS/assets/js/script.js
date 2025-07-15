document.addEventListener('DOMContentLoaded', function() {

    /**
     * FUNGSI 1: Menghilangkan pesan notifikasi secara otomatis setelah beberapa detik.
     */
    const autoDismissAlerts = document.querySelectorAll('.message.success, .message.info');
    if (autoDismissAlerts.length > 0) {
        autoDismissAlerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease, margin 0.5s ease, padding 0.5s ease, height 0.5s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                alert.style.marginTop = '0';
                alert.style.marginBottom = '0';
                alert.style.paddingTop = '0';
                alert.style.paddingBottom = '0';
                alert.style.height = '0';
                setTimeout(() => alert.remove(), 500);
            }, 5000); // Hilang setelah 5 detik
        });
    }

    /**
     * FUNGSI 2: Toggle untuk melihat/menyembunyikan password.
     * PENTING: Anda harus menambahkan Font Awesome & mengubah HTML input password Anda.
     * Contoh di HTML:
     * <div class="input-wrapper">
     * <input type="password" name="password" required>
     * <i class="fas fa-eye password-toggle"></i>
     * </div>
     */
    document.querySelectorAll('.password-toggle').forEach(toggle => {
        toggle.addEventListener('click', function (e) {
            const passwordInput = this.closest('.input-wrapper').querySelector('input');
            if (passwordInput && passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else if (passwordInput) {
                passwordInput.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
    });

    /**
     * FUNGSI 3: Menampilkan preview gambar profil saat dipilih.
     * PENTING: Anda harus menambahkan elemen <img> di file PHP Anda.
     * Contoh di HTML: 
     * <img id="profilePicturePreview" src="path/to/default-avatar.png" alt="Preview">
     */
    const profilePictureInput = document.querySelector('input[name="foto_profil"]');
    const profilePicturePreview = document.getElementById('profilePicturePreview');

    if (profilePictureInput && profilePicturePreview) {
        profilePictureInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    profilePicturePreview.src = e.target.result;
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
    
    /**
     * FUNGSI 4: Memberikan status loading pada tombol form saat disubmit.
     */
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton) {
                // Jangan nonaktifkan jika form memiliki atribut 'novalidate' untuk validasi sisi klien
                if (form.noValidate) {
                    if (!form.checkValidity()) {
                        return;
                    }
                }
                submitButton.disabled = true;
                submitButton.classList.add('button--loading');
                submitButton.innerHTML = `MEMPROSES...`;
            }
        });
    });

    /**
     * FUNGSI 5: Logika untuk menampilkan modal berkas di halaman admin (sudah ada, hanya diperbaiki sedikit).
     */
    const modal = document.getElementById("berkasModal");
    const closeModalButton = document.querySelector(".modal .close");

    if (modal && closeModalButton) {
        const closeModal = () => modal.style.display = "none";
        
        closeModalButton.onclick = closeModal;
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        };
        
        document.addEventListener('keydown', function(event) {
            if (event.key === "Escape") {
                closeModal();
            }
        });

        document.querySelectorAll('.lihat-berkas-btn').forEach(button => {
            button.addEventListener('click', function() {
                const berkasUrls = this.getAttribute('data-berkas');
                const urlArray = berkasUrls ? berkasUrls.split(',') : [];
                const berkasListDiv = document.getElementById('berkasList');
                berkasListDiv.innerHTML = '';

                if (urlArray.length > 0 && urlArray[0].trim() !== '') {
                    urlArray.forEach((url, index) => {
                        const a = document.createElement('a');
                        a.href = url.trim();
                        try {
                            let cleanUrl = url.split('/').pop().split('?')[0]; // Ambil nama file dari URL
                            let decodedName = decodeURIComponent(cleanUrl); // Decode nama file
                            a.textContent = `Berkas ${index + 1}: ${decodedName.substring(decodedName.indexOf('_') + 1)}`;
                        } catch (e) {
                            a.textContent = `Lihat Berkas ${index + 1}`;
                        }
                        a.target = '_blank';
                        a.rel = 'noopener noreferrer';
                        berkasListDiv.appendChild(a);
                    });
                } else {
                    berkasListDiv.innerHTML = '<p>Tidak ada berkas yang diunggah.</p>';
                }
                modal.style.display = "block";
            });
        });
    }

    /**
     * FUNGSI 6: Inisialisasi Grafik di Dashboard Admin (kode dari file asli Anda).
     */
    const kategoriCanvas = document.getElementById('kategoriChart');
    if (kategoriCanvas) {
        const kategoriLabels = JSON.parse(kategoriCanvas.dataset.labels);
        const kategoriValues = JSON.parse(kategoriCanvas.dataset.values);

        const ctxKategori = kategoriCanvas.getContext('2d');
        new Chart(ctxKategori, {
            type: 'pie',
            data: {
                labels: kategoriLabels,
                datasets: [{
                    label: 'Jumlah Pendaftar',
                    data: kategoriValues,
                    backgroundColor: ['rgba(94, 114, 228, 0.8)', 'rgba(23, 162, 184, 0.8)'],
                    borderColor: ['#5E72E4', '#17A2B8'],
                    borderWidth: 1
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'top' } } }
        });
    }

    const statusCanvas = document.getElementById('statusChart');
    if (statusCanvas) {
        const statusLabels = JSON.parse(statusCanvas.dataset.labels);
        const statusValues = JSON.parse(statusCanvas.dataset.values);

        // Definisikan warna untuk setiap bar pada grafik
        const barChartColors = [
            'rgba(23, 43, 77, 0.6)',    // 1. Warna untuk "Total Pendaftar"
            'rgba(253, 126, 20, 0.6)',  // 2. Warna untuk "Menunggu Verifikasi"
            'rgba(251, 99, 64, 0.6)',   // 3. Warna untuk "Berkas Tidak Lengkap"
            'rgba(45, 206, 137, 0.6)',  // 4. Warna untuk "Lulus"
            'rgba(245, 54, 92, 0.6)'    // 5. Warna untuk "Tidak Lulus"
        ];
        
        const barChartBorders = [
            'rgba(23, 43, 77, 1)',
            'rgba(253, 126, 20, 1)',
            'rgba(251, 99, 64, 1)',
            'rgba(45, 206, 137, 1)',
            'rgba(245, 54, 92, 1)'
        ];

        const ctxStatus = statusCanvas.getContext('2d');
        new Chart(ctxStatus, {
            type: 'bar',
            data: {
                labels: statusLabels,
                datasets: [{
                    label: 'Jumlah Pendaftar',
                    data: statusValues,
                    backgroundColor: barChartColors, // Gunakan array warna
                    borderColor: barChartBorders,   // Gunakan array warna border
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                plugins: { legend: { display: false } }
            }
        });
    }

    /**
     * FUNGSI 7: Fungsionalitas "Pilih Semua" Checkbox (kode dari file asli Anda).
     */
    const pilihSemuaCheckbox = document.getElementById('pilihSemua');
    if (pilihSemuaCheckbox) {
        pilihSemuaCheckbox.addEventListener('click', function(event) {
            document.querySelectorAll('.pilih-satu').forEach(function(checkbox) {
                checkbox.checked = event.target.checked;
            });
        });
    }
});