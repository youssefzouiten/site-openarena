const API = {
    call: async function(action, data = {}) {
        const form = new FormData();
        form.append('action', action);

        for (let key in data) {
            form.append(key, data[key]);
        }

        const res = await fetch('api.php', {
            method: 'POST',
            body: form,
            credentials: 'same-origin'
        });

        return res.json();
    },

    register: (d) => API.call('register', d),
    login: (d) => API.call('login', d),
    logout: () => API.call('logout'),
    getSession: () => API.call('getSession'),

    getJoueurs: () => API.call('getJoueurs'),

    getRDV: () => API.call('getRDV'),
    addRDV: (d) => API.call('addRDV', d),
    deleteRDV: (id) => API.call('deleteRDV', { id }),
    clearAllRDV: () => API.call('clearAllRDV'),

    lancerPartie: (d) => API.call('lancerPartie', d),
    getParties: () => API.call('getParties'),
    deletePartie: (id) => API.call('deletePartie', { id }),

    deleteJoueur: (pseudo) => API.call('deleteJoueur', { pseudo }),
    editJoueur: (d) => API.call('editJoueur', d),

    getKeybinds: () => API.call('getKeybinds'),
    saveKeybinds: (d) => API.call('saveKeybinds', d)
};
