// Simulasi user (hardcoded)
const users = {
  'admin': '12345',
  'user': 'pass'
};

// Simpan cookie
function setCookie(name, value, days) {
  const expires = new Date(Date.now() + days * 864e5).toUTCString();
  document.cookie = `${name}=${value}; path=/; expires=${expires}`;
}

// Ambil cookie
function getCookie(name) {
  const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
  return match ? match[2] : null;
}

// Hapus cookie
function deleteCookie(name) {
  document.cookie = name + '=; Max-Age=0; path=/;';
}

// Proses login
if (document.getElementById('loginForm')) {
  document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const uname = document.getElementById('username').value;
    const pass = document.getElementById('password').value;

    if (users[uname] && users[uname] === pass) {
      const sessionData = { username: uname, loginTime: Date.now() };
      sessionStorage.setItem('session', JSON.stringify(sessionData));
      setCookie('session_user', uname, 1); // expire in 1 day

      window.location.href = 'home.html';
    } else {
      alert('Username atau password salah!');
    }
  });
}

// Cek session di home
function checkSession() {
  const session = JSON.parse(sessionStorage.getItem('session'));
  const cookieUser = getCookie('session_user');

  if (session && cookieUser && session.username === cookieUser) {
    document.getElementById('welcome').innerText = `Halo, ${cookieUser}!`;
  } else {
    alert('Session tidak ditemukan. Silakan login kembali.');
    window.location.href = 'login.html';
  }
}

// Logout
function logout() {
  sessionStorage.removeItem('session');
  deleteCookie('session_user');
  window.location.href = 'login.html';
}
