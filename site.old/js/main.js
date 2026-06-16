/**
 * Main application logic
 */

const App = {
    user: null,

    async init() {
        console.log('Hotel App Initializing...');
        await this.checkAuth();
        this.setupEventListeners();
        this.initDarkMode();
        this.handleResponsiveMenu();
    },

    async checkAuth() {
        const response = await API.get('auth.php', { action: 'check' });
        if (response.logged_in) {
            this.user = response.user;
            this.updateNavbarAuth(true);
        } else {
            this.updateNavbarAuth(false);
        }
    },

    updateNavbarAuth(isLoggedIn) {
        const authLinks = document.getElementById('auth-links');
        if (!authLinks) return;

        if (isLoggedIn) {
            authLinks.innerHTML = `
                <a href="profil.html" class="nav-link">Mon Profil</a>
                <button id="logout-btn" class="btn btn-outline btn-sm">Déconnexion</button>
            `;
            if (this.user.role === 'admin') {
                const nav = document.querySelector('.navbar-nav');
                if (nav && !nav.querySelector('[href="admin/index.html"]')) {
                    const adminLink = document.createElement('a');
                    adminLink.href = 'admin/index.html';
                    adminLink.className = 'nav-link';
                    adminLink.innerHTML = '✨ Admin';
                    nav.appendChild(adminLink);
                }
            }

            document.getElementById('logout-btn')?.addEventListener('click', () => this.logout());
        } else {
            authLinks.innerHTML = `
                <a href="login.html" class="btn btn-ghost btn-sm">Connexion</a>
                <a href="register.html" class="btn btn-primary btn-sm">S'inscrire</a>
            `;
        }
    },

    async logout() {
        const response = await API.post('auth.php?action=logout');
        if (response.success) {
            notify.success(response.message);
            window.location.href = 'index.html';
        }
    },

    setupEventListeners() {
        // Scroll behavior
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar?.classList.add('scrolled');
            } else {
                navbar?.classList.remove('scrolled');
            }
        });
    },

    togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('.material-symbols-outlined');
        if (input.type === 'password') {
            input.type = 'text';
            icon.textContent = 'visibility_off';
        } else {
            input.type = 'password';
            icon.textContent = 'visibility';
        }
    },

    initDarkMode() {
        const toggle = document.getElementById('dark-mode-toggle');
        const currentTheme = localStorage.getItem('theme') || 'light';

        document.documentElement.setAttribute('data-theme', currentTheme);

        if (toggle) {
            toggle.checked = currentTheme === 'dark';
            toggle.addEventListener('change', (e) => {
                const theme = e.target.checked ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('theme', theme);
            });
        }
    },

    handleResponsiveMenu() {
        const hamburger = document.querySelector('.hamburger');
        const nav = document.querySelector('.navbar-nav');

        hamburger?.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            nav?.classList.toggle('mobile-open');
        });

        // Close menu on link click
        nav?.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                hamburger?.classList.remove('active');
                nav.classList.remove('mobile-open');
            });
        });
    }
};

document.addEventListener('DOMContentLoaded', () => App.init());
window.App = App;
