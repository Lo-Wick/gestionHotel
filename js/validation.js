/**
 * Form Validation Module
 */

const validation = {
    rules: {
        required: (val) => val.trim() !== '' || 'Ce champ est requis',
        email: (val) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val) || 'Email invalide',
        phone: (val) => /^[\+]?[0-9\s\-\(\)]{8,20}$/.test(val) || 'Numéro de téléphone invalide',
        password: (val) => {
            if (val.length < 8) return 'Minimum 8 caractères';
            if (!/[A-Z]/.test(val)) return 'Doit contenir une majuscule';
            if (!/[a-z]/.test(val)) return 'Doit contenir une minuscule';
            if (!/[0-9]/.test(val)) return 'Doit contenir un chiffre';
            if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(val)) return 'Doit contenir un caractère spécial';
            return true;
        },
        password_confirm: (val, input) => {
            const form = input.closest('form');
            const pwd = form?.querySelector('[name="password"], [name="new_password"]')?.value;
            return val === pwd || 'Les mots de passe ne correspondent pas';
        }
    },

    validateField(input) {
        const val = input.value;
        const checks = input.dataset.validate?.split('|') || [];
        let error = null;

        for (const check of checks) {
            const rule = this.rules[check];
            if (rule) {
                const result = rule(val, input);
                if (result !== true) {
                    error = result;
                    break;
                }
            }
        }

        this.toggleError(input, error);
        return error === null;
    },

    toggleError(input, message) {
        const group = input.closest('.form-group');
        let errorEl = group.querySelector('.form-error');

        if (message) {
            input.classList.add('is-invalid');
            input.classList.remove('is-valid');
            if (!errorEl) {
                errorEl = document.createElement('div');
                errorEl.className = 'form-error';
                group.appendChild(errorEl);
            }
            errorEl.textContent = message;
        } else {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            errorEl?.remove();
        }
    },

    setupForm(formId, callback) {
        const form = document.getElementById(formId);
        if (!form) return;

        const inputs = form.querySelectorAll('[data-validate]');

        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => {
                if (input.classList.contains('is-invalid')) {
                    this.validateField(input);
                }
            });
        });

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            let isValid = true;
            inputs.forEach(input => {
                if (!this.validateField(input)) isValid = false;
            });

            if (isValid) callback(this.serialize(form));
        });
    },

    serialize(form) {
        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });
        return data;
    }
};

window.validation = validation;
