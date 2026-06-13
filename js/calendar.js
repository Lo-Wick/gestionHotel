/**
 * Simple Calendar / Date Utilities
 */

const Calendar = {
    init(inputId, options = {}) {
        const input = document.getElementById(inputId);
        if (!input) return;

        // Set min date to today for check-in
        if (options.minToday) {
            const today = new Date().toISOString().split('T')[0];
            input.setAttribute('min', today);
        }

        // Handle dependency between check-in and check-out
        if (options.linkedWith) {
            const depart = document.getElementById(options.linkedWith);
            input.addEventListener('change', () => {
                if (input.value) {
                    const nextDay = new Date(input.value);
                    nextDay.setDate(nextDay.getDate() + 1);
                    depart.setAttribute('min', nextDay.toISOString().split('T')[0]);
                    if (depart.value && new Date(depart.value) <= new Date(input.value)) {
                        depart.value = nextDay.toISOString().split('T')[0];
                    }
                }
            });
        }
    },

    formatDate(date) {
        return new Intl.DateTimeFormat('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        }).format(new Date(date));
    }
};

window.Calendar = Calendar;
