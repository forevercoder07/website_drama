// DramaMini JavaScript

console.log('DramaMini loaded!');

// Search function
function searchMovies() {
    const query = document.getElementById('searchInput').value;
    if (query) {
        window.location.href = `/?page=search&q=${encodeURIComponent(query)}`;
    }
}

// Logout function
function logout() {
    if (confirm('Logout qilasizmi?')) {
        fetch('/api/logout', { method: 'POST' }).then(() => {
            window.location.href = '/';
        });
    }
}

// Login function
function login() {
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    
    if (!username || !password) {
        alert('Username va password kerak');
        return;
    }
    
    // API call to /api/login
    fetch('/api/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Login successful!');
            window.location.href = '/';
        } else {
            alert('Error: ' + data.message);
        }
    });
}