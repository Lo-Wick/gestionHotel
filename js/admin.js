/**
 * Admin Dashboard & CRUD
 */

const Admin = {
    async requireAuth() {
        API.baseUrl = '../php/api/';
        const auth = await API.get('auth.php', { action: 'check' });
        if (!auth.logged_in || auth.user.role !== 'admin') {
            window.location.href = '../login.html?redirect=' + encodeURIComponent(window.location.pathname);
            return false;
        }
        return true;
    },

    async loadStats() {
        const response = await API.get('admin/index.php', { resource: 'stats' });
        if (response.success) {
            this.renderStats(response.stats);
            this.renderRoomOccupancy(response.stats.chambres);
        } else if (response.error) {
            notify.error(response.error);
        }
    },

    renderStats(stats) {
        const container = document.getElementById('stats-grid');
        if (!container) return;

        container.innerHTML = `
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--color-info-light); color: var(--color-info)"><span class="material-symbols-outlined">analytics</span></div>
                <div class="stat-value">${stats.reservations.taux_occupation}%</div>
                <div class="stat-label">Taux d'occupation</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--color-success-light); color: var(--color-success)"><span class="material-symbols-outlined">payments</span></div>
                <div class="stat-value">${stats.reservations.chiffre_affaires.toLocaleString()} €</div>
                <div class="stat-label">Chiffre d'affaires</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--color-warning-light); color: var(--color-warning)"><span class="material-symbols-outlined">hotel</span></div>
                <div class="stat-value">${stats.reservations.total}</div>
                <div class="stat-label">Réservations totales</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(201,169,110,0.1); color: var(--color-secondary-dark)"><span class="material-symbols-outlined">group</span></div>
                <div class="stat-value">${stats.clients.total}</div>
                <div class="stat-label">Clients inscrits</div>
            </div>
        `;
    },

    renderRoomOccupancy(chambreStats) {
        const container = document.getElementById('room-occupancy-list');
        if (!container || !chambreStats) return;

        const pct = chambreStats.total > 0
            ? Math.round((chambreStats.disponibles / chambreStats.total) * 100)
            : 0;

        container.innerHTML = `
            <div class="mb-4">
                <div class="flex-between mb-2">
                    <span>Chambres libres</span>
                    <b>${chambreStats.disponibles} / ${chambreStats.total}</b>
                </div>
                <div style="background: var(--color-beige); border-radius: 8px; height: 8px; overflow: hidden;">
                    <div style="background: var(--color-success); height: 100%; width: ${pct}%; transition: width 0.5s;"></div>
                </div>
            </div>
            ${(chambreStats.par_type || []).map(t => `
                <div class="flex-between text-sm mb-2">
                    <span class="badge badge-primary">${t.type}</span>
                    <span>${t.count} chambre(s) — ${Math.round(t.prix_moyen)} €/nuit</span>
                </div>
            `).join('')}
        `;
    },

    roomFilters: { type: '', numero: '' },

    async loadRoomsTable() {
        const params = { resource: 'chambres', action: 'list', limit: 50 };
        if (this.roomFilters.type) params.type = this.roomFilters.type;
        if (this.roomFilters.numero) params.numero = this.roomFilters.numero;

        const response = await API.get('admin/index.php', params);
        if (response.success) {
            const tableBody = document.querySelector('#rooms-table tbody');
            if (!tableBody) return;

            if (response.data.chambres.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center p-6">Aucune chambre trouvée.</td></tr>';
                return;
            }

            tableBody.innerHTML = response.data.chambres.map(room => `
                <tr>
                    <td>${room.numero}</td>
                    <td><span class="badge badge-primary">${room.type}</span></td>
                    <td>${room.prix_nuit} €</td>
                    <td>${room.capacite} pers.</td>
                    <td>
                        <span class="badge ${room.disponibilite ? 'badge-success' : 'badge-error'}">
                            ${room.disponibilite ? 'Libre' : 'Occupée'}
                        </span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <button class="btn btn-sm btn-ghost" title="Modifier" onclick="Admin.editRoom(${room.id_chambre})">✏️</button>
                            <button class="btn btn-sm btn-ghost text-danger" title="Supprimer" onclick="Admin.deleteRoom(${room.id_chambre})">🗑️</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        } else if (response.error) {
            notify.error(response.error);
        }
    },

    async editRoom(id) {
        const res = await API.get('chambres.php', { action: 'detail', id });
        if (res.success && typeof openRoomModal === 'function') {
            openRoomModal(res.chambre);
        } else {
            notify.error(res.error || 'Impossible de charger la chambre');
        }
    },

    deleteRoom(id) {
        modal.confirm('Supprimer la chambre', 'Êtes-vous sûr de vouloir supprimer cette chambre ? Cette action est irréversible.', async () => {
            const response = await API.post(`admin/index.php?resource=chambres&action=delete&id=${id}`);
            if (response.success) {
                notify.success(response.message);
                this.loadRoomsTable();
            } else {
                notify.error(response.error);
            }
        });
    },

    async loadClientsTable(search = '') {
        const params = { resource: 'clients', action: 'list' };
        if (search) params.search = search;

        const res = await API.get('admin/index.php', params);
        const tbody = document.querySelector('#clients-table tbody');
        if (!tbody) return;

        if (!res.success) {
            notify.error(res.error);
            return;
        }

        if (res.data.clients.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center p-6">Aucun client trouvé.</td></tr>';
            return;
        }

        tbody.innerHTML = res.data.clients.map(c => `
            <tr>
                <td><b>${c.nom} ${c.prenom}</b></td>
                <td>${c.email}</td>
                <td>${c.telephone}</td>
                <td><span class="badge ${c.role === 'admin' ? 'badge-primary' : 'badge-secondary'}">${c.role}</span></td>
                <td>
                    <button class="btn btn-sm btn-ghost" title="Voir le détail" onclick="Admin.viewClient(${c.id_client})">👁️</button>
                    ${c.role !== 'admin' ? `<button class="btn btn-sm btn-ghost text-danger" title="Supprimer" onclick='Admin.deleteClient(${c.id_client}, ${JSON.stringify(c.prenom + " " + c.nom)})'><span class="material-symbols-outlined">delete</span></button>` : ''}
                </td>
            </tr>
        `).join('');
    },

    async viewClient(id) {
        const res = await API.get('admin/index.php', { resource: 'clients', action: 'detail', id });
        if (!res.success) return notify.error(res.error);

        const c = res.client;
        const reservations = res.reservations?.reservations || [];
        modal.open({
            title: `Client : ${c.prenom} ${c.nom}`,
            content: `
                <p><b>Email :</b> ${c.email}</p>
                <p><b>Téléphone :</b> ${c.telephone}</p>
                <p><b>Inscrit le :</b> ${new Date(c.date_inscription).toLocaleDateString()}</p>
                <hr class="divider">
                <h4>Réservations (${reservations.length})</h4>
                ${reservations.length === 0
                    ? '<p class="text-secondary">Aucune réservation.</p>'
                    : reservations.map(r => `<p class="text-sm">RES-${r.id_reservation} — ${r.statut} — ${r.montant_total} €</p>`).join('')}
            `,
            footer: `<button class="btn btn-primary" onclick="modal.close()">Fermer</button>`
        });
    },

    deleteClient(id, name) {
        modal.confirm('Supprimer le client', `Supprimer le compte de ${name} ? Ses réservations seront également supprimées.`, async () => {
            const res = await API.post('admin/index.php?resource=clients&action=delete', { id });
            if (res.success) {
                notify.success(res.message);
                this.loadClientsTable();
            } else {
                notify.error(res.error);
            }
        });
    },

    async loadPaymentsTable() {
        const res = await API.get('admin/index.php', { resource: 'paiements', action: 'list' });
        const tbody = document.querySelector('#payments-table tbody');
        if (!tbody) return;

        if (!res.success) {
            notify.error(res.error);
            return;
        }

        if (res.data.paiements.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center p-6">Aucun paiement enregistré.</td></tr>';
            return;
        }

        tbody.innerHTML = res.data.paiements.map(p => `
            <tr>
                <td><b>#PAY-${p.id_paiement}</b></td>
                <td>RES-${p.id_reservation}</td>
                <td>${new Date(p.date_paiement).toLocaleDateString()}</td>
                <td>${p.mode_paiement}</td>
                <td><b>${p.montant} €</b></td>
                <td><span class="badge badge-success">${p.statut}</span></td>
            </tr>
        `).join('');
    },

    setupRoomFilters() {
        const searchInput = document.getElementById('room-search');
        const typeSelect = document.getElementById('room-type-filter');

        const apply = () => {
            this.roomFilters.numero = searchInput?.value.trim() || '';
            this.roomFilters.type = typeSelect?.value || '';
            this.loadRoomsTable();
        };

        let timer;
        searchInput?.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(apply, 350);
        });
        typeSelect?.addEventListener('change', apply);
    }
};

window.Admin = Admin;
