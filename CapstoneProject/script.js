// Tambahkan event listener untuk tombol Login/Daftar
document.getElementById('loginBtn').addEventListener('click', function() {
    // Di sini Anda bisa mengarahkan ke halaman login atau menampilkan modal (pop-up)
    alert('Tombol Login/Daftar diklik! Fitur ini akan mengarahkan ke halaman autentikasi (Admin/Mitra/Customer).');
    
    // Logika pengalihan ke halaman login/register sesungguhnya akan seperti:
    // window.location.href = 'login.html'; 
});

// Fungsi untuk menggulir halus (smooth scrolling) ke section
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();

        const targetId = this.getAttribute('href');
        if (targetId !== '#') {
             document.querySelector(targetId).scrollIntoView({
                behavior: 'smooth'
            });
        }
    });
});

// Anda bisa menambahkan logika lain di sini, seperti:
// 1. Validasi form pencarian
// 2. Efek interaktif lainnya saat user menggulir
// 3. Mengambil data awal (jika sudah terhubung ke API/Database)