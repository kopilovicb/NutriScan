document.getElementById('loginForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const email = document.getElementById('username').value;
    const password = document.getElementById('password').value;

    let isValid = true;

    // Email validacija
    if (!email || !email.includes('@')) {
        showError('usernameError', 'Unesite validnu email adresu');
        document.getElementById('username').classList.add('error');
        isValid = false;
    } else {
        hideError('usernameError');
        document.getElementById('username').classList.remove('error');
    }

    // Lozinka validacija
    if (!password || password.length < 3) {
        showError('passwordError', 'Unesite lozinku');
        document.getElementById('password').classList.add('error');
        isValid = false;
    } else {
        hideError('passwordError');
        document.getElementById('password').classList.remove('error');
    }

    if (!isValid) return;

    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Prijavljivanje...';

    const formData = new FormData();
    formData.append('username', email);
    formData.append('password', password);

    fetch('login.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
        .then(async response => {
            const text = await response.text(); // ⬅️ UVEK prvo text

            console.log('RAW SERVER RESPONSE:', text);

            try {
                return JSON.parse(text); // ⬅️ ručno parsiranje
            } catch (e) {
                throw new Error('Server nije vratio validan JSON');
            }
        })
        .then(data => {
            if (data.success) {
                hideError('loginError');
                showSuccess('loginSuccess', 'Uspešna prijava!');

                setTimeout(() => {
                    window.location.href = data.redirect || 'dashboard.php';
                }, 1000);
            } else {
                showError('loginError', data.message || 'Greška pri prijavi');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Prijavite se';
            }
        })
        .catch(error => {
            console.error('LOGIN ERROR:', error);
            showError(
                'loginError',
                'Greška servera. Proveri konzolu (F12).'
            );
            submitBtn.disabled = false;
            submitBtn.textContent = 'Prijavite se';
        });
});

function showError(elementId, message) {
    const el = document.getElementById(elementId);
    el.textContent = message;
    el.classList.add('show');
}

function hideError(elementId) {
    const el = document.getElementById(elementId);
    el.classList.remove('show');
}

function showSuccess(elementId, message) {
    const el = document.getElementById(elementId);
    el.textContent = message;
    el.classList.add('show');
}
