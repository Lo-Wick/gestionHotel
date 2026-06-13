/**
 * Toast Notification System
 */

const notify = {
    containerId: 'toast-container',

    init() {
        if (!document.getElementById(this.containerId)) {
            const container = document.createElement('div');
            container.id = this.containerId;
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
    },

    show(message, type = 'info', duration = 5000) {
        this.init();
        const container = document.getElementById(this.containerId);

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        };

        toast.innerHTML = `
            <div class="toast-icon">${icons[type] || icons.info}</div>
            <div class="toast-content">
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-dismiss">&times;</button>
        `;

        container.appendChild(toast);

        // Auto remove
        const timeout = setTimeout(() => this.dismiss(toast), duration);

        // Manual dismiss
        toast.querySelector('.toast-dismiss').addEventListener('click', () => {
            clearTimeout(timeout);
            this.dismiss(toast);
        });
    },

    dismiss(toast) {
        toast.classList.add('toast-exit');
        toast.addEventListener('animationend', () => {
            toast.remove();
        });
    },

    success(msg) { this.show(msg, 'success'); },
    error(msg) { this.show(msg, 'error'); },
    warning(msg) { this.show(msg, 'warning'); },
    info(msg) { this.show(msg, 'info'); }
};

window.notify = notify;
