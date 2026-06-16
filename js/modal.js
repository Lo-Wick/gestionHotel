/**
 * Modal Management Module
 */

const modal = {
    init() {
        if (!document.getElementById('modal-container')) {
            const backdrop = document.createElement('div');
            backdrop.id = 'modal-backdrop';
            backdrop.className = 'modal-backdrop';

            const container = document.createElement('div');
            container.id = 'modal-container';
            container.className = 'modal';
            container.setAttribute('role', 'dialog');
            container.setAttribute('aria-modal', 'true');
            container.setAttribute('aria-labelledby', 'modal-title');

            document.body.appendChild(backdrop);
            document.body.appendChild(container);

            backdrop.addEventListener('click', () => this.close());
            
            // Close on ESC key press
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && container.classList.contains('active')) {
                    this.close();
                }
            });
        }
    },

    async open({ title, content, footer, onClose }) {
        this.init();
        const backdrop = document.getElementById('modal-backdrop');
        const container = document.getElementById('modal-container');

        container.innerHTML = `
            <div class="modal-header">
                <h3 id="modal-title">${title}</h3>
                <button class="modal-close" aria-label="Fermer la boîte de dialogue">&times;</button>
            </div>
            <div class="modal-body">
                ${content}
            </div>
            <div class="modal-footer">
                ${footer || ''}
            </div>
        `;

        backdrop.classList.add('active');
        container.classList.add('active');

        // Focus first button/input inside modal for accessibility
        setTimeout(() => {
            const interactive = container.querySelector('button, input, select, textarea, a');
            if (interactive) interactive.focus();
        }, 50);

        container.querySelector('.modal-close').onclick = () => {
            this.close();
            if (onClose) onClose();
        };

        return container;
    },

    close() {
        const backdrop = document.getElementById('modal-backdrop');
        const container = document.getElementById('modal-container');
        if (backdrop) backdrop.classList.remove('active');
        if (container) container.classList.remove('active');
    },

    confirm(title, message, onConfirm) {
        const footer = `
            <button class="btn btn-ghost modal-cancel">Annuler</button>
            <button class="btn btn-danger modal-confirm">Confirmer</button>
        `;

        this.open({
            title,
            content: `<p>${message}</p>`,
            footer
        }).then(container => {
            container.querySelector('.modal-cancel').onclick = () => this.close();
            container.querySelector('.modal-confirm').onclick = () => {
                onConfirm();
                this.close();
            };
        });
    }
};

window.modal = modal;
