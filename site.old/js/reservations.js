/**
 * Reservation Logic & Pricing
 */

const Booking = {
    currentRoom: null,

    async initDetail() {
        const urlParams = new URLSearchParams(window.location.search);
        const roomId = urlParams.get('id');
        if (!roomId) return;

        const response = await API.get('chambres.php', { action: 'detail', id: roomId });
        if (response.success) {
            this.currentRoom = response.chambre;
            this.renderDetail();
        }
    },

    renderDetail() {
        const el = document.getElementById('room-detail');
        if (!el) return;

        el.innerHTML = `
            <div class="chambre-detail-layout slide-up">
                <div class="detail-main">
                    <img src="${this.currentRoom.image_url || 'https://via.placeholder.com/800x500'}" class="main-img" alt="${this.currentRoom.type}">
                    <div class="detail-content">
                        <h1>${this.currentRoom.type} - n°${this.currentRoom.numero}</h1>
                        <p class="description">${this.currentRoom.description}</p>
                        <div class="features">
                            <div class="feature-item">🛏️ Lit King Size</div>
                            <div class="feature-item">📶 WiFi Haut Débit</div>
                            <div class="feature-item">🛁 Salle de bain privée</div>
                            <div class="feature-item">❄️ Climatisation</div>
                        </div>
                    </div>
                </div>
                <div class="detail-sidebar">
                    <div class="summary-card">
                        <div class="summary-card-header">
                            <h3>Réserver cette chambre</h3>
                        </div>
                        <div class="summary-card-body">
                            <div class="summary-total">
                                <span>${this.currentRoom.prix_nuit} €</span>
                                <span class="total-price">/nuit</span>
                            </div>
                            <hr class="divider">
                            <a href="reservation.html?id=${this.currentRoom.id_chambre}" class="btn btn-primary btn-block">Vérifier les dates</a>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    setupBookingForm() {
        const form = document.getElementById('booking-form');
        if (!form) return;

        const dates = form.querySelectorAll('input[type="date"]');
        dates.forEach(input => {
            input.addEventListener('change', () => this.updatePricing());
        });

        validation.setupForm('booking-form', (data) => this.submitBooking(data));
    },

    updatePricing() {
        const dateDebut = document.getElementById('date_debut')?.value;
        const dateFin = document.getElementById('date_fin')?.value;
        const priceEl = document.getElementById('total-price-display');

        if (dateDebut && dateFin && this.currentRoom) {
            const start = new Date(dateDebut);
            const end = new Date(dateFin);
            const nights = Math.ceil((end - start) / (1000 * 60 * 60 * 24));

            if (nights > 0) {
                const total = nights * this.currentRoom.prix_nuit;
                priceEl.textContent = `${total} € (${nights} nuits)`;
            } else {
                priceEl.textContent = 'Dates invalides';
            }
        }
    },

    async submitBooking(data) {
        const response = await API.post('reservations.php?action=create', data);
        if (response.success) {
            notify.success(response.message);
            window.location.href = `panier.html?id=${response.reservation.id}`;
        } else {
            notify.error(response.error);
        }
    }
};

window.Booking = Booking;
