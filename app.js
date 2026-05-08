let currentUser = null;
let joueurs = [];
let rdvs = [];
let parties = [];
let editingPseudo = null;

function $(id) {
    return document.getElementById(id);
}

function escapeHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function showToast(message, isError = false) {
    const toast = $('toast');
    if (!toast) return;
    toast.textContent = message;
    toast.className = 'toast' + (isError ? ' error' : '');
    toast.classList.add('show');
    clearTimeout(showToast._timer);
    showToast._timer = setTimeout(() => toast.classList.remove('show'), 3000);
}

function showAlert(containerId, message, type = 'info') {
    const el = $(containerId);
    if (!el) return;
    el.innerHTML = `<div class="alert alert-${type}">${escapeHtml(message)}</div>`;
}

function clearAlert(containerId) {
    const el = $(containerId);
    if (el) el.innerHTML = '';
}

function openModal(id) {
    const modal = $(id);
    if (modal) modal.classList.add('active');
}

function closeModal(id) {
    const modal = $(id);
    if (modal) modal.classList.remove('active');
}

function showPage(pageId) {
    document.querySelectorAll('.page').forEach(page => page.classList.remove('active'));
    const target = $('page-' + pageId);
    if (target) target.classList.add('active');

    if (pageId === 'rdv') loadRDVPage();
    if (pageId === 'admin') loadAdminPage();
    if (pageId === 'profil') loadProfil();
    if (pageId === 'touches') loadTouchesPage();
    if (pageId === 'joueurs') afficherJoueurs();
    if (pageId === 'classement') renderClassements();

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function updateNav() {
    const nav = $('navMenu');
    if (!nav) return;

    let html = `
        <li><a onclick="showPage('accueil')">Accueil</a></li>
        <li><a onclick="showPage('classement')">Classement</a></li>
        <li><a onclick="showPage('rdv')">Rendez-vous</a></li>
        <li><a onclick="showPage('joueurs')">Joueurs</a></li>
        <li><a onclick="showPage('touches')">Touches</a></li>
    `;

    if (currentUser) {
        html += `
            <li class="nav-separator"></li>
            <li><a class="nav-user" onclick="showPage('profil')">👤 ${escapeHtml(currentUser.pseudo)}</a></li>
        `;
        if (currentUser.role === 'admin') {
            html += `<li><a onclick="showPage('admin')">⚙️ Admin</a></li>`;
        }
        html += `<li><a class="nav-btn-logout" onclick="handleLogout()">Déconnexion</a></li>`;
    } else {
        html += `
            <li class="nav-separator"></li>
            <li><a class="nav-btn-login" onclick="openModal('modalLogin')">Connexion</a></li>
            <li><a class="nav-btn-register" onclick="openModal('modalRegister')">Inscription</a></li>
        `;
    }

    nav.innerHTML = html;
}

function updateHeroButtons() {
    const heroBtns = $('heroBtns');
    if (!heroBtns) return;

    if (currentUser) {
        heroBtns.innerHTML = `
            <button class="btn-primary" onclick="showPage('classement')">🏆 Voir le classement</button>
            <button class="btn-secondary" onclick="showPage('rdv')">📅 Rendez-vous</button>
        `;
    } else {
        heroBtns.innerHTML = `
            <button class="btn-primary" onclick="openModal('modalRegister')">🎮 S'inscrire</button>
            <button class="btn-outline" onclick="openModal('modalLogin')">🔑 Se connecter</button>
        `;
    }
}

async function handleRegister(e) {
    e.preventDefault();
    clearAlert('registerAlert');

    const pseudo = $('regPseudo').value.trim();
    const email = $('regEmail').value.trim();
    const password = $('regPassword').value;
    const password2 = $('regPasswordConfirm').value;
    const ville = $('regVille').value;

    if (password !== password2) {
        showAlert('registerAlert', 'Les mots de passe ne correspondent pas', 'error');
        return false;
    }

    const res = await API.register({ pseudo, email, password, ville });

    if (res.success) {
        currentUser = res.user;
        closeModal('modalRegister');
        e.target.reset();
        await refreshAllData();
        updateNav();
        updateHeroButtons();
        loadProfil();
        showPage('profil');
        showToast('Compte créé avec succès');
    } else {
        showAlert('registerAlert', res.message || 'Erreur inscription', 'error');
    }

    return false;
}

async function handleLogin(e) {
    e.preventDefault();
    clearAlert('loginAlert');

    const pseudo = $('loginPseudo').value.trim();
    const password = $('loginPassword').value;
    const res = await API.login({ pseudo, password });

    if (res.success) {
        currentUser = res.user;
        closeModal('modalLogin');
        e.target.reset();
        await refreshAllData();
        updateNav();
        updateHeroButtons();
        loadProfil();
        showPage('profil');
        showToast('Connexion réussie');
    } else {
        showAlert('loginAlert', res.message || 'Erreur connexion', 'error');
    }

    return false;
}

async function handleLogout() {
    const res = await API.logout();
    if (res.success) {
        currentUser = null;
        updateNav();
        updateHeroButtons();
        loadRDVPage();
        showPage('accueil');
        showToast('Déconnexion réussie');
    }
}

async function refreshAllData() {
    await Promise.all([loadJoueurs(), loadRDV(), loadParties()]);
    updateStats();
    afficherJoueurs();
    afficherRDV();
    renderClassements();
    renderAdminJoueurs();
    renderParties();
}

async function loadJoueurs() {
    const res = await API.getJoueurs();
    joueurs = res.success ? (res.joueurs || []) : [];
}

async function loadRDV() {
    const res = await API.getRDV();
    rdvs = res.success ? (res.rdvs || []) : [];
}

async function loadParties() {
    const res = await API.getParties();
    parties = res.success ? (res.parties || []) : [];
}

function joueursOnly() {
    return joueurs.filter(j => j.role !== 'admin');
}

function updateStats() {
    if ($('statJoueurs')) $('statJoueurs').textContent = joueursOnly().length;
    if ($('statRDV')) $('statRDV').textContent = rdvs.length;
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return String(dateStr).slice(0, 10);
}

function afficherJoueurs() {
    const body = $('joueursBody');
    if (!body) return;

    const q = ($('joueursSearch')?.value || '').toLowerCase().trim();
    const ville = $('joueursFilterVille')?.value || 'all';
    let list = joueursOnly();

    if (q) {
        list = list.filter(j =>
            (j.pseudo || '').toLowerCase().includes(q) ||
            (j.ville || '').toLowerCase().includes(q)
        );
    }

    if (ville !== 'all') {
        list = list.filter(j => j.ville === ville);
    }

    if (!list.length) {
        body.innerHTML = `<tr><td colspan="5">Aucun joueur trouvé.</td></tr>`;
        return;
    }

    body.innerHTML = list.map(j => `
        <tr>
            <td><strong style="color:#ff8c5a;">${escapeHtml(j.pseudo)}</strong></td>
            <td>${escapeHtml(j.ville || '-')}</td>
            <td>${Number(j.score || 0)}</td>
            <td>${Number(j.matchs || 0)}</td>
            <td>${escapeHtml(formatDate(j.createAt))}</td>
        </tr>
    `).join('');
}

function renderClassements() {
    const classementBody = $('classementBody');
    const classementVillesBody = $('classementVillesBody');
    const list = joueursOnly();

    if (classementBody) {
        if (!list.length) {
            classementBody.innerHTML = `<tr><td colspan="6">Aucun joueur trouvé.</td></tr>`;
        } else {
            classementBody.innerHTML = list.map((j, i) => {
                const kills = Number(j.kills || 0);
                const deaths = Number(j.deaths || 0);
                const kd = deaths > 0 ? (kills / deaths).toFixed(2) : kills.toFixed(2);
                return `
                    <tr>
                        <td>${i + 1}</td>
                        <td>${escapeHtml(j.pseudo)}</td>
                        <td>${escapeHtml(j.ville || '-')}</td>
                        <td>${kills}</td>
                        <td>${kd}</td>
                        <td>${Number(j.score || 0)}</td>
                    </tr>
                `;
            }).join('');
        }
    }

    if (classementVillesBody) {
        const villes = {};
        list.forEach(j => {
            const v = j.ville || 'Inconnue';
            if (!villes[v]) villes[v] = { ville: v, joueurs: 0, score: 0 };
            villes[v].joueurs += 1;
            villes[v].score += Number(j.score || 0);
        });

        const rows = Object.values(villes).sort((a, b) => b.score - a.score);

        if (!rows.length) {
            classementVillesBody.innerHTML = `<tr><td colspan="4">Aucune ville trouvée.</td></tr>`;
        } else {
            classementVillesBody.innerHTML = rows.map((v, i) => `
                <tr>
                    <td>${i + 1}</td>
                    <td>${escapeHtml(v.ville)}</td>
                    <td>${v.joueurs}</td>
                    <td>${v.score}</td>
                </tr>
            `).join('');
        }
    }
}

function showClassTab(tab) {
    document.querySelectorAll('#page-classement .tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('#page-classement .tab-content').forEach(c => c.classList.remove('active'));
    const btns = document.querySelectorAll('#page-classement .tab-btn');
    if (tab === 'joueurs' && btns[0]) btns[0].classList.add('active');
    if (tab === 'villes' && btns[1]) btns[1].classList.add('active');
    const target = $('classTab-' + tab);
    if (target) target.classList.add('active');
}

function switchRdvTab(tab) {
    const page = $('page-rdv');
    if (!page) return;
    page.querySelectorAll('.tabs .tab-btn').forEach((btn, i) => {
        btn.classList.remove('active');
        if ((tab === 'publier' && i === 0) || (tab === 'liste' && i === 1)) btn.classList.add('active');
    });
    $('rdvTab-publier').classList.toggle('active', tab === 'publier');
    $('rdvTab-liste').classList.toggle('active', tab === 'liste');
    if (tab === 'liste') afficherRDV();
}

function loadRDVPage() {
    const warn = $('rdvAuthWarn');
    const pseudoInput = $('rdvPseudo');
    const clearBtn = document.querySelector('#page-rdv .btn-danger');

    if (currentUser) {
        if (warn) warn.style.display = 'none';
        if (pseudoInput) pseudoInput.value = currentUser.pseudo;
        if (clearBtn) clearBtn.style.display = currentUser.role === 'admin' ? 'inline-block' : 'none';
    } else {
        if (warn) warn.style.display = 'block';
        if (pseudoInput) pseudoInput.value = '';
        if (clearBtn) clearBtn.style.display = 'none';
    }
    afficherRDV();
}

async function publierRDV() {
    if (!currentUser) {
        showToast('Connecte-toi pour publier un rendez-vous', true);
        openModal('modalLogin');
        return;
    }

    const mode = $('rdvMode').value;
    const date = $('rdvDate').value;
    const time = $('rdvTime').value;
    const message = $('rdvMessage').value.trim();

    if (!date || !time || !message) {
        showToast('Complète tous les champs du rendez-vous', true);
        return;
    }

    const res = await API.addRDV({ mode, date, time, message });

    if (res.success) {
        $('rdvMessage').value = '';
        $('rdvDate').value = '';
        $('rdvTime').value = '';
        if ($('rdvCharCount')) $('rdvCharCount').textContent = '0 / 300';
        await loadRDV();
        updateStats();
        afficherRDV();
        switchRdvTab('liste');
        showToast('Rendez-vous publié');
    } else {
        showToast(res.message || 'Erreur publication RDV', true);
    }
}

function afficherRDV() {
    const list = $('rdvList');
    const counter = $('rdvCounter');
    if (!list) return;

    const q = ($('rdvSearch')?.value || '').toLowerCase().trim();
    const modeFilter = $('rdvFilterMode')?.value || 'all';
    let filtered = [...rdvs];

    if (q) {
        filtered = filtered.filter(r =>
            (r.pseudo || '').toLowerCase().includes(q) ||
            (r.message || '').toLowerCase().includes(q) ||
            (r.mode || '').toLowerCase().includes(q)
        );
    }

    if (modeFilter !== 'all') {
        filtered = filtered.filter(r => r.mode === modeFilter);
    }

    if (counter) counter.textContent = `${filtered.length} rendez-vous`;

    if (!filtered.length) {
        list.innerHTML = `<div class="empty-state"><div class="icon">📭</div><p>Aucun rendez-vous trouvé.</p></div>`;
        return;
    }

    list.innerHTML = filtered.map(r => {
        const canDelete = currentUser && (currentUser.role === 'admin' || currentUser.pseudo === r.pseudo);
        return `
            <div class="rdv-item">
                <div class="rdv-header">
                    <span class="rdv-pseudo">${escapeHtml(r.pseudo)}</span>
                    <span class="rdv-date-time">${escapeHtml(r.date)} à ${escapeHtml(r.time)}</span>
                </div>
                <span class="rdv-mode">${escapeHtml(r.mode)}</span>
                <span class="rdv-mode">${escapeHtml(r.ville || '-')}</span>
                <div class="rdv-message">${escapeHtml(r.message)}</div>
                ${canDelete ? `<div class="rdv-actions"><button class="btn-danger" onclick="deleteRDV(${Number(r.id)})">Supprimer</button></div>` : ''}
            </div>
        `;
    }).join('');
}

async function deleteRDV(id) {
    if (!confirm('Supprimer ce rendez-vous ?')) return;
    const res = await API.deleteRDV(id);
    if (res.success) {
        await loadRDV();
        updateStats();
        afficherRDV();
        showToast('Rendez-vous supprimé');
    } else {
        showToast(res.message || 'Suppression impossible', true);
    }
}

async function clearAllRDV() {
    if (!currentUser || currentUser.role !== 'admin') {
        showToast('Action réservée à l’admin', true);
        return;
    }
    if (!confirm('Supprimer tous les rendez-vous ?')) return;
    const res = await API.clearAllRDV();
    if (res.success) {
        await loadRDV();
        updateStats();
        afficherRDV();
        showToast('Tous les rendez-vous ont été supprimés');
    } else {
        showToast(res.message || 'Erreur', true);
    }
}

function loadAdminPage() {
    const warn = $('adminAuthWarn');
    const content = $('adminContent');

    if (!currentUser || currentUser.role !== 'admin') {
        if (warn) warn.style.display = 'block';
        if (content) content.style.display = 'none';
        return;
    }

    if (warn) warn.style.display = 'none';
    if (content) content.style.display = 'block';
    renderAdminJoueurs();
    renderParties();
}

async function lancerPartie(e) {
    e.preventDefault();
    const data = {
        map: $('adminMap').value,
        mode: $('adminMode').value,
        nbJoueurs: $('adminNbJoueurs').value,
        temps: $('adminTemps').value,
        kills: $('adminKills').value
    };
    const res = await API.lancerPartie(data);
    if (res.success) {
        showAlert('adminAlert', res.message || 'Partie lancée', 'success');
        await loadParties();
        renderParties();
    } else {
        showAlert('adminAlert', res.message || 'Erreur', 'error');
    }
    return false;
}

function renderParties() {
    const body = $('partiesBody');
    if (!body) return;
    if (!parties.length) {
        body.innerHTML = `<tr><td colspan="7">Aucune partie configurée.</td></tr>`;
        return;
    }
    body.innerHTML = parties.map(p => `
        <tr>
            <td>${escapeHtml(p.map)}</td>
            <td>${escapeHtml(p.mode || '-')}</td>
            <td>${Number(p.nbJoueurs || 0)}</td>
            <td>${Number(p.temps || 0)} min</td>
            <td>${Number(p.kills || 0)}</td>
            <td>${escapeHtml(p.statut || '-')}</td>
            <td><button class="btn-danger" onclick="deletePartie(${Number(p.id)})">Supprimer</button></td>
        </tr>
    `).join('');
}

async function deletePartie(id) {
    if (!confirm('Supprimer cette partie ?')) return;
    const res = await API.deletePartie(id);
    if (res.success) {
        await loadParties();
        renderParties();
        showToast('Partie supprimée');
    } else {
        showToast(res.message || 'Erreur suppression', true);
    }
}

function renderAdminJoueurs() {
    const body = $('adminJoueursBody');
    if (!body) return;
    if (!joueurs.length) {
        body.innerHTML = `<tr><td colspan="5">Aucun joueur.</td></tr>`;
        return;
    }
    body.innerHTML = joueurs.map(j => `
        <tr>
            <td>${escapeHtml(j.pseudo)}</td>
            <td>${escapeHtml(j.email || '-')}</td>
            <td>${escapeHtml(j.ville || '-')}</td>
            <td><span class="badge ${j.role === 'admin' ? 'badge-admin' : 'badge-joueur'}">${escapeHtml(j.role || 'joueur')}</span></td>
            <td>
                <button class="btn-edit" onclick="openEditJoueurModal('${escapeHtml(j.pseudo)}')">Modifier</button>
                <button class="btn-danger" onclick="deleteJoueur('${escapeHtml(j.pseudo)}')">Supprimer</button>
            </td>
        </tr>
    `).join('');
}

async function handleAddJoueur(e) {
    e.preventDefault();
    const pseudo = $('addPseudo').value.trim();
    const email = $('addEmail').value.trim();
    const password = $('addPassword').value;
    const ville = $('addVille').value;
    const role = $('addRole').value;
    const res = await API.register({ pseudo, email, password, ville, role });
    if (res.success) {
        closeModal('modalAddJoueur');
        $('addJoueurForm').reset();
        await refreshAllData();
        showToast('Joueur ajouté');
    } else {
        showToast(res.message || 'Erreur ajout joueur', true);
    }
    return false;
}

function openEditJoueurModal(pseudo) {
    const joueur = joueurs.find(j => j.pseudo === pseudo);
    if (!joueur) return;
    editingPseudo = joueur.pseudo;
    $('editPseudo').value = joueur.pseudo;
    $('editVille').value = joueur.ville || 'Marseille';
    $('editRole').value = joueur.role || 'joueur';
    $('editPassword').value = '';
    openModal('modalEditJoueur');
}

async function handleEditJoueur(e) {
    e.preventDefault();
    const data = {
        pseudo: editingPseudo || $('editPseudo').value,
        ville: $('editVille').value,
        role: $('editRole').value,
        password: $('editPassword').value.trim()
    };
    const res = await API.editJoueur(data);
    if (res.success) {
        closeModal('modalEditJoueur');
        await refreshAllData();
        updateNav();
        showToast('Joueur modifié');
    } else {
        showToast(res.message || 'Erreur modification', true);
    }
    return false;
}

async function deleteJoueur(pseudo) {
    if (!confirm(`Supprimer le joueur ${pseudo} ?`)) return;
    const res = await API.deleteJoueur(pseudo);
    if (res.success) {
        await refreshAllData();
        showToast('Joueur supprimé');
    } else {
        showToast(res.message || 'Erreur suppression', true);
    }
}

function loadProfil() {
    const content = $('profilContent');
    if (!content) return;
    if (!currentUser) {
        content.innerHTML = `<div class="card"><p>Connecte-toi pour voir ton profil.</p></div>`;
        return;
    }
    const badgeClass = currentUser.role === 'admin' ? 'badge-admin' : 'badge-joueur';
    content.innerHTML = `
        <div class="card">
            <div class="profile-header">
                <div class="profile-avatar">${escapeHtml(currentUser.pseudo.charAt(0).toUpperCase())}</div>
                <div class="profile-info">
                    <h3>${escapeHtml(currentUser.pseudo)}</h3>
                    <p>${escapeHtml(currentUser.email || '-')}</p>
                    <p>Ville : ${escapeHtml(currentUser.ville || '-')}</p>
                    <p><span class="badge ${badgeClass}">${escapeHtml(currentUser.role || 'joueur')}</span></p>
                </div>
            </div>
        </div>
    `;
}

function initKeySelects() {
    const keys = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','SPACE','SHIFT','CTRL','ALT','TAB','ENTER','UP','DOWN','LEFT','RIGHT','MOUSE1','MOUSE2','MOUSE3'];
    ['bindAvancer', 'bindReculer', 'bindGauche', 'bindDroite'].forEach(id => {
        const select = $(id);
        if (!select) return;
        select.innerHTML = keys.map(k => `<option value="${k}">${k}</option>`).join('');
    });
}

async function loadKeybinds() {
    if (!currentUser) return;
    const res = await API.getKeybinds();
    if (!res.success) {
        showToast(res.message || 'Impossible de charger les touches', true);
        return;
    }
    const k = res.keybinds || {};
    $('bindAvancer').value = k.avancer || 'Z';
    $('bindReculer').value = k.reculer || 'S';
    $('bindGauche').value = k.gauche || 'Q';
    $('bindDroite').value = k.droite || 'D';
}

function loadTouchesPage() {
    if (!currentUser) {
        showToast('Connecte-toi pour configurer tes touches', true);
        openModal('modalLogin');
        return;
    }
    loadKeybinds();
}

async function saveKeybinds() {
    if (!currentUser) {
        showToast('Connecte-toi pour sauvegarder tes touches', true);
        return;
    }
    const data = {
        avancer: $('bindAvancer').value,
        reculer: $('bindReculer').value,
        gauche: $('bindGauche').value,
        droite: $('bindDroite').value,
        sauter: 'SPACE',
        tirer: 'MOUSE1',
        viser: 'MOUSE2'
    };
    const res = await API.saveKeybinds(data);
    if (res.success) showToast('Touches sauvegardées');
    else showToast(res.message || 'Erreur de sauvegarde', true);
}

function bindUIEvents() {
    const msg = $('rdvMessage');
    const count = $('rdvCharCount');
    if (msg && count) {
        msg.addEventListener('input', () => {
            count.textContent = `${msg.value.length} / 300`;
        });
    }

    const dateInput = $('rdvDate');
    if (dateInput) dateInput.min = new Date().toISOString().split('T')[0];

    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', e => {
            if (e.target === modal) modal.classList.remove('active');
        });
    });
}

async function init() {
    bindUIEvents();
    initKeySelects();
    try {
        const session = await API.getSession();
        currentUser = session.user || null;
    } catch (e) {
        currentUser = null;
    }
    updateNav();
    updateHeroButtons();
    loadRDVPage();
    loadProfil();
    await refreshAllData();
}