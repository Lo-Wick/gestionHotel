/**
 * Reservation Logic & Pricing
 */

const Booking = {
    currentRoom: null,

    getPrestations(roomType) {
        const prestations = {
            Standard: [
                { icon: 'king_bed', name: 'Lit double' },
                { icon: 'wifi', name: 'Wi-Fi gratuit' },
                { icon: 'shower', name: 'Salle de douche' },
                { icon: 'ac_unit', name: 'Climatisation' },
                { icon: 'tv', name: 'Télévision LED' },
                { icon: 'lock', name: 'Coffre-fort' }
            ],
            Confort: [
                { icon: 'king_bed', name: 'Literie Premium' },
                { icon: 'wifi', name: 'Wi-Fi fibre gratuit' },
                { icon: 'bathtub', name: 'Salle de bain avec baignoire' },
                { icon: 'ac_unit', name: 'Climatisation' },
                { icon: 'tv', name: 'Smart TV HD' },
                { icon: 'lock', name: 'Coffre-fort' },
                { icon: 'local_bar', name: 'Minibar' },
                { icon: 'coffee_maker', name: 'Machine Nespresso' }
            ],
            Suite: [
                { icon: 'bed', name: 'Grand lit King Size' },
                { icon: 'wifi', name: 'Wi-Fi fibre ultra-rapide' },
                { icon: 'hot_tub', name: 'Salle de bain avec baignoire spa' },
                { icon: 'deck', name: 'Salon indépendant' },
                { icon: 'ac_unit', name: 'Climatisation régulée' },
                { icon: 'tv', name: 'Smart TV 4K' },
                { icon: 'local_bar', name: 'Minibar gratuit réapprovisionné' },
                { icon: 'lock', name: 'Coffre-fort' },
                { icon: 'coffee_maker', name: 'Nespresso & Théière premium' },
                { icon: 'room_service', name: 'Room service 24h/24' },
                { icon: 'dry_cleaning', name: 'Peignoirs & chaussons soyeux' }
            ],
            Présidentielle: [
                { icon: 'bed', name: 'Lit Royal King Size' },
                { icon: 'wifi', name: 'Wi-Fi fibre ultra-rapide dédié' },
                { icon: 'hot_tub', name: 'Marbre, balnéo & douche italienne' },
                { icon: 'deck', name: 'Salon & Salle à manger privés' },
                { icon: 'ac_unit', name: 'Climatisation régulée' },
                { icon: 'tv', name: '2x Smart TV 4K grand écran' },
                { icon: 'local_bar', name: 'Bar privé avec spiritueux fins' },
                { icon: 'lock', name: 'Coffre-fort biométrique' },
                { icon: 'coffee_maker', name: 'Machine Nespresso (boissons gratuites)' },
                { icon: 'support_agent', name: 'Majordome dédié 24h/24' },
                { icon: 'room_service', name: 'Room service gastronomique' },
                { icon: 'spa', name: 'Accès libre au Spa VIP' },
                { icon: 'airport_shuttle', name: 'Transfert aéroport en limousine' },
                { icon: 'dry_cleaning', name: 'Peignoirs de soie & articles de marque' }
            ]
        };
        return prestations[roomType] || prestations['Standard'];
    },

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    },

    showLoading(container) {
        if (!container) return;
        container.setAttribute('aria-busy', 'true');
        container.innerHTML = `
            <div class="loading-state" role="status">
                <div class="spinner"></div>
                <p>Chargement des détails de la chambre…</p>
                <span class="text-xs text-secondary">Veuillez patienter quelques instants.</span>
            </div>`;
    },

    showError(container, message, showRetry = true) {
        if (!container) return;
        container.setAttribute('aria-busy', 'false');
        container.innerHTML = `
            <div class="error-state" role="alert">
                <span class="material-symbols-outlined error-icon">error_outline</span>
                <h3>Impossible d'afficher cette chambre</h3>
                <p>${this.escapeHtml(message)}</p>
                <div class="error-actions">
                    ${showRetry ? '<button type="button" class="btn btn-primary" id="retry-detail">Réessayer</button>' : ''}
                    <a href="chambres.html" class="btn btn-outline">Retour aux chambres</a>
                </div>
            </div>`;
        document.getElementById('retry-detail')?.addEventListener('click', () => this.initDetail());
    },

    async initDetail() {
        const urlParams = new URLSearchParams(window.location.search);
        const roomId = urlParams.get('id');
        const detailContainer = document.getElementById('room-detail');

        if (!roomId) {
            this.showError(detailContainer, 'Identifiant de chambre manquant. Sélectionnez une chambre depuis la liste.', false);
            return;
        }

        this.showLoading(detailContainer);

        const timeoutId = setTimeout(() => {
            if (!this.currentRoom) {
                this.showError(detailContainer, 'Le serveur met trop de temps à répondre. Vérifiez votre connexion ou réessayez.');
            }
        }, 8000);

        try {
            const response = await API.get('chambres.php', { action: 'detail', id: roomId });
            clearTimeout(timeoutId);

            if (response.success && response.chambre) {
                this.currentRoom = response.chambre;
                this.renderDetail();
            } else {
                this.showError(detailContainer, response.error || 'Cette chambre est introuvable ou n\'est plus disponible.');
            }
        } catch (err) {
            clearTimeout(timeoutId);
            console.error('Exception while fetching room details:', err);
            this.showError(detailContainer, 'Erreur réseau ou serveur. Assurez-vous que WAMP est démarré.');
        }
    },

    getTypeLabel(type) {
        const labels = {
            Standard: 'Chambre Standard',
            Confort: 'Chambre Confort',
            Suite: 'Suite Premium',
            Présidentielle: 'Suite Présidentielle'
        };
        return labels[type] || type;
    },

    renderDetail() {
        const el = document.getElementById('room-detail');
        if (!el || !this.currentRoom) return;

        const room = this.currentRoom;
        const esc = (v) => this.escapeHtml(v);
        const description = room.description || "Profitez d'un séjour inoubliable au sein de notre établissement. Cette chambre décorée avec soin allie confort moderne et authenticité malgache.";
        const isAvailable = room.disponibilite == 1 || room.disponibilite === true;
        const imgSrc = room.image_url || 'assets/img/room_standard.png';

        document.title = `${this.getTypeLabel(room.type)} n°${room.numero} | Cocotel`;

        const breadcrumbCurrent = document.querySelector('.breadcrumb .current');
        if (breadcrumbCurrent) {
            breadcrumbCurrent.textContent = `Chambre ${room.numero}`;
        }

        el.setAttribute('aria-busy', 'false');

        // Get images based on room type, fallback to default if not found
        let images = [];
        const typeStr = room.type ? room.type.toLowerCase() : 'standard';
        
        if (typeStr.includes('suite') || typeStr.includes('présidentielle')) {
            images = [
                'assets/img/room_suite_bed.png',
                'assets/img/room_suite_salon.png',
                'assets/img/room_suite_bathroom.png'
            ];
        } else if (typeStr.includes('confort')) {
            images = [
                'assets/img/room_confort_bed.png',
                'assets/img/room_confort_vue.png',
                'assets/img/room_confort_bathroom.png'
            ];
        } else {
            images = [
                'assets/img/room_standard_bed.png',
                'assets/img/room_standard_vue.png',
                'assets/img/room_standard_bathroom.png'
            ];
        }

        // Generate carousel HTML
        let slidesHTML = '';
        let dotsHTML = '';
        images.forEach((img, idx) => {
            slidesHTML += `<div class="carousel-slide ${idx === 0 ? 'active' : ''}">
                              <img src="${esc(img)}" alt="${esc(this.getTypeLabel(room.type))} vue ${idx+1}">
                           </div>`;
            dotsHTML += `<div class="carousel-dot ${idx === 0 ? 'active' : ''}" data-idx="${idx}"></div>`;
        });

        el.innerHTML = `
            <div class="chambre-detail-layout slide-up">
                <div class="detail-main">
                    <div class="detail-image-wrap" id="room-carousel">
                        <div class="carousel-container">
                            ${slidesHTML}
                        </div>
                        <button class="carousel-btn prev" aria-label="Image précédente"><span class="material-symbols-outlined">chevron_left</span></button>
                        <button class="carousel-btn next" aria-label="Image suivante"><span class="material-symbols-outlined">chevron_right</span></button>
                        <div class="carousel-nav">
                            ${dotsHTML}
                        </div>
                        <div class="detail-badges">
                            <span class="badge badge-type">${esc(room.type)}</span>
                            <span class="badge ${isAvailable ? 'badge-success' : 'badge-warning'}">
                                ${isAvailable ? 'Disponible' : 'Occupée'}
                            </span>
                        </div>
                    </div>
                    <div class="detail-content">
                        <div class="detail-meta-row">
                            <span class="meta-item"><span class="material-symbols-outlined">door_front</span> Chambre n°${esc(room.numero)}</span>
                            <span class="meta-item"><span class="material-symbols-outlined">group</span> Jusqu'à ${esc(room.capacite)} personne${room.capacite > 1 ? 's' : ''}</span>
                            <span class="meta-item"><span class="material-symbols-outlined">schedule</span> Arrivée 15h · Départ 11h</span>
                        </div>

                        <h1>${esc(this.getTypeLabel(room.type))}</h1>
                        <p class="description">${esc(description)}</p>

                        <div class="detail-info-cards">
                            <div class="info-card">
                                <span class="material-symbols-outlined">verified</span>
                                <div>
                                    <strong>Réservation flexible</strong>
                                    <p>Annulation gratuite jusqu'à 48 h avant l'arrivée.</p>
                                </div>
                            </div>
                            <div class="info-card">
                                <span class="material-symbols-outlined">restaurant</span>
                                <div>
                                    <strong>Petit-déjeuner inclus</strong>
                                    <p>Buffet gastronomique servi de 7h à 10h30.</p>
                                </div>
                            </div>
                            <div class="info-card">
                                <span class="material-symbols-outlined">support_agent</span>
                                <div>
                                    <strong>Conciergerie 24h/24</strong>
                                    <p>Notre équipe vous accompagne à chaque étape.</p>
                                </div>
                            </div>
                        </div>

                        <h3 class="mb-4">Prestations incluses</h3>
                        <div class="features-grid mb-8">
                            ${Booking.getPrestations(room.type).map(p => `
                                <div class="feature-item">
                                    <span class="material-symbols-outlined">${esc(p.icon)}</span>
                                    <span>${esc(p.name)}</span>
                                </div>
                            `).join('')}
                        </div>

                        <div class="detail-story">
                            <h3>L'expérience Cocotel</h3>
                            <p>Depuis 2016, le Cocotel accueille les voyageurs au cœur de Fianarantsoa, Madagascar. Chaque chambre est pensée pour allier élégance et confort — literie premium, artisanat malgache sélectionné et vue sur les collines verdoyantes de la région Matsiatra Ambony.</p>
                        </div>
                    </div>
                </div>

                <div class="detail-sidebar">
                    <div class="summary-card">
                        <div class="summary-card-header">
                            <h3>Tarif & Réservation</h3>
                        </div>
                        <div class="summary-card-body">
                            <div class="price-large">
                                <span class="price-value">${esc(parseFloat(room.prix_nuit).toFixed(0))} Ar</span>
                                <span class="price-unit">/ nuit</span>
                            </div>
                            <p class="text-xs text-secondary text-center mb-4">Taxes et TVA incluses · Paiement sécurisé</p>

                            <ul class="policy-list">
                                <li><span class="material-symbols-outlined">check_circle</span> Confirmation immédiate</li>
                                <li><span class="material-symbols-outlined">check_circle</span> Meilleur tarif garanti</li>
                                <li><span class="material-symbols-outlined">check_circle</span> Sans frais cachés</li>
                            </ul>

                            <a href="reservation.html?id=${esc(room.id_chambre)}" class="btn btn-primary btn-block btn-lg">
                                Réserver cette chambre
                            </a>
                            <a href="chambres.html" class="btn btn-ghost btn-block mt-3">Voir toutes les chambres</a>
                        </div>
                    </div>

                    <div class="trust-card">
                        <div class="trust-score">
                            <span class="score">4.8</span>
                            <div>
                                <strong>Excellent</strong>
                                <p>Basé sur 1 240 avis clients</p>
                            </div>
                        </div>
                        <blockquote class="guest-quote">
                            « Un séjour parfait — chambre impeccable, personnel aux petits soins. »
                            <cite>— Marie L., séjour en ${esc(room.type)}</cite>
                        </blockquote>
                    </div>
                </div>

            </div>`;

        this.initCarousel();

        // Check if there are parameters in URL (from search)
        const urlParams = new URLSearchParams(window.location.search);
        const checkin = urlParams.get('date_debut');
        const checkout = urlParams.get('date_fin');

        if (checkin) document.getElementById('date_debut').value = checkin;
        if (checkout) document.getElementById('date_fin').value = checkout;

        this.setupBookingForm();
        this.updatePricing();
    },

    initCarousel() {
        const carousel = document.getElementById('room-carousel');
        if (!carousel) return;

        const slides = carousel.querySelectorAll('.carousel-slide');
        const dots = carousel.querySelectorAll('.carousel-dot');
        const btnPrev = carousel.querySelector('.carousel-btn.prev');
        const btnNext = carousel.querySelector('.carousel-btn.next');
        let currentIdx = 0;

        const showSlide = (idx) => {
            slides.forEach(s => s.classList.remove('active'));
            dots.forEach(d => d.classList.remove('active'));
            
            slides[idx].classList.add('active');
            if (dots[idx]) dots[idx].classList.add('active');
            currentIdx = idx;
        };

        if (btnPrev) {
            btnPrev.addEventListener('click', () => {
                let idx = currentIdx - 1;
                if (idx < 0) idx = slides.length - 1;
                showSlide(idx);
            });
        }

        if (btnNext) {
            btnNext.addEventListener('click', () => {
                let idx = currentIdx + 1;
                if (idx >= slides.length) idx = 0;
                showSlide(idx);
            });
        }

        dots.forEach(dot => {
            dot.addEventListener('click', (e) => {
                const idx = parseInt(e.target.getAttribute('data-idx'));
                showSlide(idx);
            });
        });
        
        // Auto advance every 5s
        setInterval(() => {
            if (document.visibilityState === 'visible') {
                let idx = currentIdx + 1;
                if (idx >= slides.length) idx = 0;
                showSlide(idx);
            }
        }, 5000);
    },

    setupBookingForm() {
        const form = document.getElementById('booking-form');
        if (!form) return;

        const dates = form.querySelectorAll('input[type="date"]');
        dates.forEach(input => {
            input.addEventListener('change', () => this.updatePricing());
        });

        if (typeof validation !== 'undefined') {
            validation.setupForm('booking-form', (data) => this.submitBooking(data));
        }
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
                priceEl.textContent = `${total} Ar (${nights} nuits)`;
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
        } else if (response.status === 401) {
            notify.warning('Connectez-vous pour finaliser votre réservation.');
            setTimeout(() => {
                window.location.href = 'login.html?redirect=' + encodeURIComponent(window.location.href);
            }, 1500);
        } else {
            notify.error(response.error);
        }
    }
};

window.Booking = Booking;
