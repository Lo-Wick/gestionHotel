/**
 * Filters & Gallery Logic
 */

const Filters = {
    params: {
        page: 1,
        limit: 8,
        tri: 'numero'
    },

    async loadChambres(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = '<div class="spinner"></div>';

        const response = await API.get('chambres.php', { action: 'list', ...this.params });

        if (response.success) {
            const countEl = document.getElementById('results-count');
            if (countEl) countEl.textContent = response.data.total;

            this.renderRooms(container, response.data.chambres);
            this.renderPagination(response.data);
        } else {
            container.innerHTML = '<div class="empty-state"><h3>Chargement impossible</h3><p>' + (response.error || 'Erreur inconnue') + '</p></div>';
            notify.error(response.error || 'Erreur lors du chargement des chambres');
        }
    },

    getHighlights(type) {
        const h = {
            Standard: 'Lit double · Wi-Fi · Douche moderne',
            Confort: 'Literie Premium · Minibar · Nespresso',
            Suite: 'King Size · Jacuzzi · Room service 24h/24',
            Présidentielle: 'Majordome 24h/24 · Spa VIP · Limousine'
        };
        return h[type] || '';
    },

    renderRooms(container, rooms) {
        if (rooms.length === 0) {
            container.innerHTML = '<div class="empty-state"><h3>Aucune chambre disponible</h3><p>Essayez de modifier vos filtres.</p></div>';
            return;
        }

        container.innerHTML = rooms.map(room => `
            <div class="card fade-in">
                <div class="card-image">
                    <img src="${room.image_url || 'https://via.placeholder.com/400x300?text=Chambre'}" alt="${room.type}">
                    <div class="card-badge">
                        <span class="badge ${room.disponibilite ? 'badge-success' : 'badge-error'}">
                            ${room.disponibilite ? 'Disponible' : 'Occupé'}
                        </span>
                    </div>
                    <div class="card-price">${room.prix_nuit} €<small>/nuit</small></div>
                </div>
                <div class="card-body">
                    <div class="card-subtitle">${room.type}</div>
                    <h3 class="card-title">Chambre n°${room.numero}</h3>
                    <p class="card-text">${room.description}</p>
                    <div class="card-meta">
                        <span><span class="material-symbols-outlined">group</span> ${room.capacite} pers.</span>
                        <span><span class="material-symbols-outlined">hotel</span> ${room.type}</span>
                    </div>
                    <div class="card-highlights" style="margin-top: var(--space-3); font-size: var(--text-sm); color: var(--color-secondary-dark); font-weight: 600; display: flex; align-items: center; gap: 4px;">
                        <span>✨</span> <span>${this.getHighlights(room.type)}</span>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="chambre-details.html?id=${room.id_chambre}" class="btn btn-ghost btn-sm">Détails</a>
                    <a href="reservation.html?id=${room.id_chambre}" class="btn btn-primary btn-sm">Réserver</a>
                </div>
            </div>
        `).join('');
    },

    renderPagination(data) {
        const pagination = document.getElementById('pagination');
        if (!pagination) return;

        let html = '';
        for (let i = 1; i <= data.total_pages; i++) {
            html += `<button class="pagination-btn ${i === data.page ? 'active' : ''}" onclick="Filters.setPage(${i})">${i}</button>`;
        }
        pagination.innerHTML = html;
    },

    setPage(page) {
        this.params.page = page;
        this.loadChambres('rooms-container');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    setupFilters() {
        const form = document.getElementById('filter-form');
        if (!form) return;

        let debounceTimer;
        form.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const formData = new FormData(form);
                formData.forEach((value, key) => {
                    if (value) this.params[key] = value;
                    else delete this.params[key];
                });
                this.params.page = 1;
                this.loadChambres('rooms-container');
            }, 400);
        });
    }
};

window.Filters = Filters;
