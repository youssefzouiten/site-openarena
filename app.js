<<<<<<< HEAD
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
    showToast._timer = setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

function showAlert(containerId, message, type = 'info') {
    const el = $(containerId);
    if (!el) return;
    el.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
}

function clearAlert(containerId) {
    const el = $(containerId);
    if (!el) return;
    el.innerHTML = '';
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
=======
let currentUser = null; // ← AJOUT IMPORTANT

async function handleRegister(e) {
    e.preventDefault();
    const a = document.getElementById('registerAlert');
    const pseudo = document.getElementById('regPseudo').value.trim();
    const email = document.getElementById('regEmail').value.trim();
    const password = document.getElementById('regPassword').value;
    const password2 = document.getElementById('regPasswordConfirm').value;
    const ville = document.getElementById('regVille').value;
    if(password !== password2) {
        a.innerHTML = '<div class="alert alert-error">❌ MDP ne correspondent pas.</div>';
        return false;
    }
    const res = await API.register({pseudo, email, password, ville});
    if(res.success) {
>>>>>>> origin/master
        currentUser = res.user;
        closeModal('modalRegister');
        updateNav();
        updateHeroButtons();
<<<<<<< HEAD
        await refreshAllData();
        loadProfil();
        showPage('profil');
        showToast('Compte créé avec succès');
        e.target.reset();
    } else {
        showAlert('registerAlert', res.message || 'Erreur lors de l’inscription', 'error');
    }

=======
        showToast('✅ Bienvenue ' + pseudo + ' !');
        navigateTo('profil');
    } else {
        a.innerHTML = '<div class="alert alert-error">❌ ' + res.message + '</div>';
    }
>>>>>>> origin/master
    return false;
}

async function handleLogin(e) {
    e.preventDefault();
<<<<<<< HEAD
    clearAlert('loginAlert');

    const pseudo = $('loginPseudo').value.trim();
    const password = $('loginPassword').value;

    const res = await API.login({ pseudo, password });

    if (res.success) {
=======
    const a = document.getElementById('loginAlert');
    const pseudo = document.getElementById('loginPseudo').value.trim();
    const password = document.getElementById('loginPassword').value;
    const res = await API.login({pseudo, password});
    if(res.success) {
>>>>>>> origin/master
        currentUser = res.user;
        closeModal('modalLogin');
        updateNav();
        updateHeroButtons();
<<<<<<< HEAD
        await refreshAllData();
        loadProfil();
        showPage('profil');
        showToast('Connexion réussie');
        e.target.reset();
    } else {
        showAlert('loginAlert', res.message || 'Erreur de connexion', 'error');
    }

=======
        showToast('✅ Bon retour ' + pseudo + ' !');
        navigateTo('profil');
    } else {
        a.innerHTML = '<div class="alert alert-error">❌ ' + res.message + '</div>';
    }
>>>>>>> origin/master
    return false;
}

async function handleLogout() {
<<<<<<< HEAD
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
    await Promise.all([
        loadJoueurs(),
        loadRDV(),
        loadParties()
    ]);

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

function updateStats() {
    if ($('statJoueurs')) $('statJoueurs').textContent = joueurs.length;
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

    let list = [...joueurs];

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

    if (classementBody) {
        classementBody.innerHTML = joueurs.map((j, i) => {
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

    if (classementVillesBody) {
        const villes = {};
        joueurs.forEach(j => {
            const v = j.ville || 'Inconnue';
            if (!villes[v]) villes[v] = { ville: v, joueurs: 0, score: 0 };
            villes[v].joueurs += 1;
            villes[v].score += Number(j.score || 0);
        });

        const rows = Object.values(villes).sort((a, b) => b.score - a.score);

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
        if ((tab === 'publier' && i === 0) || (tab === 'liste' && i === 1)) {
            btn.classList.add('active');
        }
    });

    $('rdvTab-publier').classList.toggle('active', tab === 'publier');
    $('rdvTab-liste').classList.toggle('active', tab === 'liste');
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
}

async function publierRDV() {
    if (!currentUser) {
        showToast('Connecte-toi pour publier un rendez-vous', true);
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

    if (counter) {
        counter.textContent = `${filtered.length} rendez-vous`;
    }

    if (!filtered.length) {
        list.innerHTML = `
            <div class="empty-state">
                <div class="icon">📭</div>
                <p>Aucun rendez-vous trouvé.</p>
            </div>
        `;
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
                ${canDelete ? `
                    <div class="rdv-actions">
                        <button class="btn-danger" onclick="deleteRDV(${Number(r.id)})">Supprimer</button>
                    </div>
                ` : ''}
            </div>
        `;
    }).join('');
}

async function deleteRDV(id) {
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
=======
    await API.logout();
    currentUser = null;
    updateNav();
    updateHeroButtons();
    showToast('👋 Déconnecté !');
    navigateTo('accueil');
}

async function loadClassement() {
    const res = await API.getJoueurs();
    if(!res.success) return;
    const users = res.joueurs;
    const body = document.getElementById('classementBody');
    if(users.length === 0) {
        body.innerHTML = '<tr><td colspan="6" style="color:#666;">Aucun joueur.</td></tr>';
    } else {
        body.innerHTML = users.map((u, i) => {
            const pos = i + 1;
            const medal = pos===1?'🥇':pos===2?'🥈':pos===3?'🥉':pos;
            const isMe = currentUser && u.pseudo === currentUser.pseudo;
            const kd = u.deaths > 0 ? (u.kills/u.deaths).toFixed(2) : (u.kills||0).toFixed(2);
            return '<tr '+(isMe?'style="background:#1f1f35;"':'')+'>'+
                '<td>'+medal+'</td>'+
                '<td style="color:#ff6b35;font-weight:700;">'+esc(u.pseudo)+(isMe?' ⭐':'')+'</td>'+
                '<td>'+u.ville+'</td>'+
                '<td style="color:#4ecdc4;">'+(u.kills||0)+'</td>'+
                '<td style="color:#ffd700;">'+kd+'</td>'+
                '<td><strong>'+(u.score||0)+'</strong></td></tr>';
        }).join('');
    }
    const villes = ['Paris','Dieppe','Rouen','Lille','Marseille'];
    const icons = {Paris:'🗼',Dieppe:'⚓',Rouen:'🏰',Lille:'🏭',Marseille:'🌊'};
    const vs = villes.map(v => ({
        nom: v,
        joueurs: users.filter(u => u.ville===v).length,
        score: users.filter(u => u.ville===v).reduce((s,u) => s+(u.score||0), 0)
    })).sort((a,b) => b.score-a.score);
    document.getElementById('classementVillesBody').innerHTML = vs.map((v,i) =>
        '<tr><td>'+(i+1)+'</td><td style="color:#ff6b35;font-weight:700;">'+(icons[v.nom]||'')+' '+v.nom+'</td><td>'+v.joueurs+'</td><td><strong>'+v.score+'</strong></td></tr>'
    ).join('');
}

async function afficherJoueurs() {
    const res = await API.getJoueurs();
    if(!res.success) return;
    const search = (document.getElementById('joueursSearch')?.value||'').toLowerCase();
    const vf = document.getElementById('joueursFilterVille')?.value||'all';
    const filtered = res.joueurs.filter(u =>
        u.pseudo.toLowerCase().includes(search) && (vf==='all'||u.ville===vf)
    );
    const body = document.getElementById('joueursBody');
    if(filtered.length === 0) {
        body.innerHTML = '<tr><td colspan="5" style="color:#666;">Aucun joueur.</td></tr>';
        return;
    }
    body.innerHTML = filtered.map(u => {
        const isMe = currentUser && u.pseudo === currentUser.pseudo;
        return '<tr '+(isMe?'style="background:#1f1f35;"':'')+'>'+
            '<td style="color:#ff6b35;font-weight:700;">'+esc(u.pseudo)+(isMe?' ⭐':'')+'</td>'+
            '<td>'+u.ville+'</td>'+
            '<td>'+(u.score||0)+'</td>'+
            '<td>'+(u.matchs||0)+'</td>'+
            '<td style="color:#666;">'+u.createAt+'</td></tr>';
    }).join('');
}

async function afficherRDV() {
    const res = await API.getRDV();
    if(!res.success) return;
    const container = document.getElementById('rdvList');
    const search = (document.getElementById('rdvSearch')?.value||'').toLowerCase();
    const mf = document.getElementById('rdvFilterMode')?.value||'all';
    const filtered = res.rdvs.filter(r =>
        (r.pseudo.toLowerCase().includes(search)||r.message.toLowerCase().includes(search)) &&
        (mf==='all'||r.mode===mf)
    );
    document.getElementById('rdvCounter').textContent = filtered.length + ' rendez-vous';
    if(filtered.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="icon">📭</div><p>Aucun rendez-vous trouvé.</p></div>';
        return;
    }
    container.innerHTML = filtered.map(rdv => {
        const dateObj = new Date(rdv.date+'T'+rdv.time);
        const df = dateObj.toLocaleDateString('fr-FR',{weekday:'long',year:'numeric',month:'long',day:'numeric'});
        const isPast = dateObj < new Date();
        const isOwner = currentUser && rdv.pseudo === currentUser.pseudo;
        const isAdmin = currentUser && currentUser.role === 'admin';
        return '<div class="rdv-item" style="'+(isPast?'border-left-color:#666;':'')+'">'+
            '<div class="rdv-header">'+
            '<span class="rdv-pseudo">🎮 '+esc(rdv.pseudo)+'</span>'+
            '<span class="rdv-date-time">📅 '+df+' à '+rdv.time+(isPast?' <span style="color:#ff4444;font-size:0.8rem;">(passé)</span>':'')+'</span>'+
            '</div>'+
            '<span class="rdv-mode">🎯 '+rdv.mode+'</span>'+
            '<span class="rdv-mode">🏙️ '+(rdv.ville||'?')+'</span>'+
            '<div class="rdv-message">'+esc(rdv.message)+'</div>'+
            ((isOwner||isAdmin)?'<div class="rdv-actions"><button class="btn-danger" onclick="deleteRDV('+rdv.id+')">🗑️ Supprimer</button></div>':'')+
            '</div>';
    }).join('');
}

async function publierRDV() {
    if(!currentUser) { showToast('🔒 Connecte-toi !', true); openModal('modalLogin'); return; }
    const mode = document.getElementById('rdvMode').value;
    const date = document.getElementById('rdvDate').value;
    const time = document.getElementById('rdvTime').value;
    const message = document.getElementById('rdvMessage').value.trim();
    if(!date) { showToast('⚠️ Choisis une date !', true); return; }
    if(!time) { showToast('⚠️ Choisis une heure !', true); return; }
    if(!message) { showToast('⚠️ Écris un message !', true); return; }
    const res = await API.addRDV({mode, date, time, message});
    if(res.success) {
        showToast('✅ RDV publié !');
        resetRdvForm();
        afficherRDV();
    } else {
        showToast('❌ Erreur : ' + res.message, true);
    }
}

async function deleteRDV(id) {
    if(!currentUser) return;
    if(!confirm('Supprimer ce RDV ?')) return;
    const res = await API.deleteRDV(id);
    if(res.success) { showToast('🗑️ Supprimé'); afficherRDV(); }
    else showToast('❌ ' + res.message, true);
}

// ← AJOUT
async function clearAllRDV() {
    if(!confirm('Supprimer TOUS les RDV ?')) return;
    const res = await API.clearAllRDV();
    if(res.success) { showToast('🗑️ Tous supprimés'); afficherRDV(); }
    else showToast('❌ ' + res.message, true);
}

// ← AJOUT
async function lancerPartie(e) {
    e.preventDefault();
    const a = document.getElementById('adminAlert');
    const map = document.getElementById('adminMap').value;
    const mode = document.getElementById('adminMode').value;
    const nbJoueurs = document.getElementById('adminNbJoueurs').value;
    const temps = document.getElementById('adminTemps').value;
    const kills = document.getElementById('adminKills').value;
    const res = await API.lancerPartie({map, mode, nbJoueurs, temps, kills});
    if(res.success) {
        showToast('🚀 Partie lancée !');
        a.innerHTML = '<div class="alert alert-success">✅ Partie configurée !</div>';
        loadParties();
    } else {
        a.innerHTML = '<div class="alert alert-error">❌ ' + res.message + '</div>';
    }
    return false;
}

// ← AJOUT
async function loadParties() {
    const res = await API.getParties();
    if(!res.success) return;
    const body = document.getElementById('partiesBody');
    if(!res.parties || res.parties.length === 0) {
        body.innerHTML = '<tr><td colspan="6" style="color:#666;">Aucune partie.</td></tr>';
        return;
    }
    body.innerHTML = res.parties.map(p =>
        '<tr><td>'+p.map+'</td><td>'+p.mode+'</td><td>'+p.nbJoueurs+'</td>'+
        '<td>'+p.temps+' min</td><td>'+p.kills+'</td>'+
        '<td><span class="badge badge-joueur">'+p.statut+'</span></td></tr>'
    ).join('');
}

async function updateStats() {
    const res = await API.getJoueurs();
    if(res.success) document.getElementById('statJoueurs').textContent = res.joueurs.length;
    const rdvRes = await API.getRDV();
    if(rdvRes.success) document.getElementById('statRDV').textContent = rdvRes.rdvs.length;
}
    async function loadAdmin() {
    const warn = document.getElementById('adminAuthWarn');
    const c = document.getElementById('adminContent');
    if(!warn || !c) return;
    if(!currentUser || currentUser.role !== 'admin') {
        warn.style.display = 'block';
        c.style.display = 'none';
        return;
    }
    warn.style.display = 'none';
    c.style.display = 'block';
    loadParties();
    loadAdminJoueurs();
>>>>>>> origin/master
}

async function lancerPartie(e) {
    e.preventDefault();
<<<<<<< HEAD

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
            <td>${escapeHtml(p.mode || p.mode_jeu || '-')}</td>
            <td>${Number(p.nbJoueurs || p.nb_joueurs || 0)}</td>
            <td>${Number(p.temps || 0)} min</td>
            <td>${Number(p.kills || p.kills_max || 0)}</td>
            <td>${escapeHtml(p.statut || '-')}</td>
            <td>
                ${currentUser && currentUser.role === 'admin'
                    ? `<button class="btn-danger" onclick="deletePartie(${Number(p.id)})">Supprimer</button>`
                    : '-'}
            </td>
        </tr>
    `).join('');
}

async function deletePartie(id) {
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
=======
    const a = document.getElementById('adminAlert');
    const map = document.getElementById('adminMap').value;
    const mode = document.getElementById('adminMode').value;
    const nbJoueurs = document.getElementById('adminNbJoueurs').value;
    const temps = document.getElementById('adminTemps').value;
    const kills = document.getElementById('adminKills').value;
    const res = await API.lancerPartie({map, mode, nbJoueurs, temps, kills});
    if(res.success) {
        showToast('🚀 Partie lancée !');
        a.innerHTML = '<div class="alert alert-success">✅ Partie configurée !</div>';
        loadParties();
    } else {
        a.innerHTML = '<div class="alert alert-error">❌ ' + res.message + '</div>';
    }
    return false;
}

async function loadParties() {
    const res = await API.getParties();
    if(!res.success) return;
    const body = document.getElementById('partiesBody');
    if(!res.parties || res.parties.length === 0) {
        body.innerHTML = '<tr><td colspan="6" style="color:#666;">Aucune partie.</td></tr>';
        return;
    }
    body.innerHTML = res.parties.map(p =>
        '<tr><td>'+p.map+'</td><td>'+p.mode+'</td><td>'+p.nbJoueurs+'</td>'+
        '<td>'+p.temps+' min</td><td>'+p.kills+'</td>'+
        '<td><span class="badge badge-joueur">'+p.statut+'</span></td>'+
        '<td><button class="btn-danger" onclick="deletePartie('+p.id+')">🗑️</button></td></tr>'
    ).join('');
}

async function deletePartie(id) {
    if(!confirm('Supprimer cette partie ?')) return;
    const res = await API.deletePartie(id);
    if(res.success) { showToast('🗑️ Partie supprimée'); loadParties(); }
    else showToast('❌ ' + res.message, true);
}

async function loadAdminJoueurs() {
    const res = await API.getJoueurs();
    if(!res.success) return;
    const body = document.getElementById('adminJoueursBody');
    if(!res.joueurs || res.joueurs.length === 0) {
        body.innerHTML = '<tr><td colspan="5" style="color:#666;">Aucun joueur.</td></tr>';
        return;
    }
    body.innerHTML = res.joueurs.map(u =>
        '<tr>'+
        '<td style="color:#ff6b35;font-weight:700;">'+esc(u.pseudo)+'</td>'+
        '<td>'+u.email+'</td>'+
        '<td>'+u.ville+'</td>'+
        '<td><span class="badge '+(u.role==='admin'?'badge-admin':'badge-joueur')+'">'+u.role+'</span></td>'+
        '<td>'+
            '<button class="btn-edit" onclick="editJoueur(\''+u.pseudo+'\',\''+u.ville+'\',\''+u.role+'\')">✏️ Modifier</button>'+
            '<button class="btn-danger" onclick="deleteJoueur(\''+u.pseudo+'\')">🗑️ Supprimer</button>'+
        '</td></tr>'
    ).join('');
}

async function deleteJoueur(pseudo) {
    if(!confirm('Supprimer ' + pseudo + ' ?')) return;
    const res = await API.deleteJoueur(pseudo);
    if(res.success) { showToast('🗑️ Joueur supprimé'); loadAdminJoueurs(); updateStats(); }
    else showToast('❌ ' + res.message, true);
}

function editJoueur(pseudo, ville, role) {
    document.getElementById('editPseudo').value = pseudo;
    document.getElementById('editVille').value = ville;
    document.getElementById('editRole').value = role;
    document.getElementById('modalEditJoueur').classList.add('active');
>>>>>>> origin/master
}

async function handleEditJoueur(e) {
    e.preventDefault();
<<<<<<< HEAD

    const data = {
        pseudo: editingPseudo,
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
        content.innerHTML = `
            <div class="card">
                <p>Connecte-toi pour voir ton profil.</p>
            </div>
        `;
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
    const keys = [
        'A','B','C','D','E','F','G','H','I','J','K','L','M',
        'N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
        'SPACE','SHIFT','CTRL','ALT','TAB','ENTER',
        'UP','DOWN','LEFT','RIGHT',
        'MOUSE1','MOUSE2','MOUSE3'
    ];

const ids = [
    'bindAvancer',
    'bindReculer',
    'bindGauche',
    'bindDroite'
];
    ids.forEach(id => {
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

    if (res.success) {
        showToast('Touches sauvegardées');
    } else {
        showToast(res.message || 'Erreur de sauvegarde', true);
    }
}

function bindUIEvents() {
    const msg = $('rdvMessage');
    const count = $('rdvCharCount');

    if (msg && count) {
        msg.addEventListener('input', () => {
            count.textContent = `${msg.value.length} / 300`;
        });
    }

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
=======
    const pseudo = document.getElementById('editPseudo').value;
    const ville = document.getElementById('editVille').value;
    const role = document.getElementById('editRole').value;
    const password = document.getElementById('editPassword').value;
    const res = await API.editJoueur({pseudo, ville, role, password});
    if(res.success) {
        showToast('✅ Joueur modifié !');
        closeModal('modalEditJoueur');
        loadAdminJoueurs();
    } else {
        showToast('❌ ' + res.message, true);
    }
    return false;
}

async function handleAddJoueur(e) {
    e.preventDefault();
    const pseudo = document.getElementById('addPseudo').value.trim();
    const email = document.getElementById('addEmail').value.trim();
    const password = document.getElementById('addPassword').value;
    const ville = document.getElementById('addVille').value;
    const role = document.getElementById('addRole').value;
    const res = await API.register({pseudo, email, password, ville, role});
    if(res.success) {
        showToast('✅ Joueur ajouté !');
        closeModal('modalAddJoueur');
        loadAdminJoueurs();
        updateStats();
        document.getElementById('addJoueurForm').reset();
    } else {
        showToast('❌ ' + res.message, true);
    }
    return false;
}
async function init() {
    const res = await API.getSession();
    currentUser = res.user || null;
    updateNav();
    updateHeroButtons();
    updateStats();
    navigateTo('accueil');
    const di = document.getElementById('rdvDate');
    if(di) di.min = new Date().toISOString().split('T')[0];
    document.getElementById('rdvMessage').addEventListener('input', function(){
        document.getElementById('rdvCharCount').textContent = this.value.length + ' / 300';
    });
}

function navigateTo(page){
    document.querySelectorAll('.page').forEach(p=>p.classList.remove('active'));
    const t=document.getElementById('page-'+page);
    if(t){t.classList.add('active');window.scrollTo({top:0,behavior:'smooth'});}
    if(page==='classement')loadClassement();
    if(page==='rdv')loadRDVPage();
    if(page==='profil')loadProfil();
    if(page==='joueurs')afficherJoueurs();
    if(page==='admin')loadAdmin(); // ← AJOUT
}

function updateNav(){
    const n=document.getElementById('navMenu');
    let h='<li><a onclick="navigateTo(\'accueil\')">🏠 Accueil</a></li>'+
          '<li><a onclick="navigateTo(\'classement\')">🏆 Classement</a></li>'+
          '<li><a onclick="navigateTo(\'rdv\')">📅 RDV</a></li>'+
          '<li><a onclick="navigateTo(\'joueurs\')">👥 Joueurs</a></li>';
    if(currentUser){
        // ← AJOUT lien Admin si admin
        if(currentUser.role === 'admin'){
            h+='<li><a onclick="navigateTo(\'admin\')" style="color:#ffd700;">⚙️ Admin</a></li>';
        }
        h+='<li><a onclick="navigateTo(\'profil\')" class="nav-user">👤 '+currentUser.pseudo+'</a></li>'+
           '<li><a onclick="handleLogout()" class="nav-btn-logout">🚪 Déconnexion</a></li>';
    }else{
        h+='<li><a onclick="openModal(\'modalLogin\')" class="nav-btn-login">🔑 Connexion</a></li>'+
           '<li><a onclick="openModal(\'modalRegister\')" class="nav-btn-register">📝 Inscription</a></li>';
    }
    n.innerHTML=h;
}

function updateHeroButtons(){
    const c=document.getElementById('heroBtns');
    if(currentUser){
        c.innerHTML='<button class="btn-primary" onclick="navigateTo(\'rdv\')">📅 Planifier</button>'+
                    '<button class="btn-secondary" onclick="navigateTo(\'profil\')">👤 Mon profil</button>';
    }else{
        c.innerHTML='<button class="btn-primary" onclick="openModal(\'modalRegister\')">📝 S\'inscrire</button>'+
                    '<button class="btn-outline" onclick="openModal(\'modalLogin\')">🔑 Se connecter</button>';
    }
}

function openModal(id){document.getElementById(id).classList.add('active');}
function closeModal(id){document.getElementById(id).classList.remove('active');}
function esc(str){const d=document.createElement('div');d.textContent=str;return d.innerHTML;}
function showToast(msg,isError=false){
    const t=document.getElementById('toast');t.textContent=msg;
    t.className='toast show'+(isError?' error':'');
    setTimeout(()=>t.classList.remove('show'),3500);
}
function resetRdvForm(){
    document.getElementById('rdvMode').value='Free For All';
    document.getElementById('rdvDate').value='';
    document.getElementById('rdvTime').value='';
    document.getElementById('rdvMessage').value='';
    document.getElementById('rdvCharCount').textContent='0 / 300';
}
function loadRDVPage(){
    const warn=document.getElementById('rdvAuthWarn');
    const form=document.getElementById('rdvFormContainer');
    if(!currentUser){
        warn.style.display='block';
        form.style.opacity='0.5';
        form.style.pointerEvents='none';
    }else{
        warn.style.display='none';
        form.style.opacity='1';
        form.style.pointerEvents='auto';
        document.getElementById('rdvPseudo').value=currentUser.pseudo;
    }
    afficherRDV();
}
function loadProfil(){
    const c=document.getElementById('profilContent');
    if(!currentUser){
        c.innerHTML='<div class="alert alert-info">🔒 Connecte-toi</div>';
        return;
    }
    c.innerHTML='<div class="profile-header">'+
        '<div class="profile-avatar">'+currentUser.pseudo.charAt(0).toUpperCase()+'</div>'+
        '<div class="profile-info">'+
        '<h3>'+currentUser.pseudo+'</h3>'+
        '<p>'+currentUser.email+'</p>'+
        '<p>'+currentUser.ville+'</p>'+
        '</div></div>';
}
function showClassTab(tab){
    document.querySelectorAll('#classTabs .tab-btn').forEach(b=>b.classList.remove('active'));
    document.querySelectorAll('[id^="classTab-"]').forEach(c=>c.classList.remove('active'));
    event.target.classList.add('active');
    document.getElementById('classTab-'+tab).classList.add('active');
}
function switchRdvTab(tab){
    document.querySelectorAll('#page-rdv .tab-btn').forEach(b=>b.classList.remove('active'));
    document.querySelectorAll('[id^="rdvTab-"]').forEach(c=>c.classList.remove('active'));
    event.target.classList.add('active');
    document.getElementById('rdvTab-'+tab).classList.add('active');
    if(tab==='liste')afficherRDV();
>>>>>>> origin/master
}
