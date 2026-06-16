/**
 * Admin Dashboard & CRUD
 */

const Admin = {
    async loadStats() {
        const response = await API.get('admin/index.php', { resource: 'stats' });
        if (response.success) {
            this.renderStats(response.stats);
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

    async loadRoomsTable() {
        const response = await API.get('admin/index.php', { resource: 'chambres', action: 'list' });
        if (response.success) {
            const tableBody = document.querySelector('#rooms-table tbody');
            if (!tableBody) return;

            tableBody.innerHTML = response.data.chambres.map(room => `
                <tr>
                    <td>${room.numero}</td>
                    <td><span class="badge badge-primary">${room.type}</span></td>
                    <td>${room.prix_nuit} €</td>
                    <td>${room.capacite} pers.</td>
                    <td>
                        <span class="badge ${room.disponibilite ? 'badge-success' : 'badge-error'}">
                            ${room.disponibilite ? 'Libre' : 'Occuper'}
                        </span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <button class="btn btn-sm btn-ghost" onclick="Admin.editRoom(${room.id_chambre})">✏️</button>
                            <button class="btn btn-sm btn-ghost text-danger" onclick="Admin.deleteRoom(${room.id_chambre})">🗑️</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }
    },

    deleteRoom(id) {
        modal.confirm('Supprimer la chambre', 'Êtes-vous sûr de vouloir supprimer cette chambre ? Cette action est irréversible.', async () => {
            const response = await API.post(`admin/index.php?resource=chambres&action=delete&id=${id}`);
            if (response.success) {
                notify.success(msg);
                this.loadRoomsTable();
            }
        });
    }
};

window.Admin = Admin;
