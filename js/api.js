/**
 * API Wrapper for AJAX requests
 */

const API = {
    baseUrl: 'php/api/',

    async request(endpoint, options = {}) {
        const url = endpoint.startsWith('http') ? endpoint : this.baseUrl + endpoint;
        
        try {
            const response = await fetch(url, {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...options.headers
                }
            });

            const text = await response.text();
            let data;
            try {
                data = text ? JSON.parse(text) : {};
            } catch {
                return {
                    error: 'Réponse serveur invalide. Vérifiez que le serveur PHP est démarré.',
                    ok: false
                };
            }

            if (!response.ok) {
                return { 
                    error: data.error || `Erreur ${response.status}`, 
                    status: response.status,
                    ok: false 
                };
            }

            return { ...data, ok: true };
        } catch (error) {
            console.error('API Request Error:', error);
            return { 
                error: 'Impossible de contacter le serveur. Vérifiez votre connexion.', 
                ok: false 
            };
        }
    },

    get(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        return this.request(url, { method: 'GET' });
    },

    post(endpoint, body = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(body)
        });
    },

    put(endpoint, body = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(body)
        });
    },

    delete(endpoint, body = {}) {
        return this.request(endpoint, {
            method: 'DELETE',
            body: JSON.stringify(body)
        });
    }
};

window.API = API;
