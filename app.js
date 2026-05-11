let currentUser = null;
let joueurs = [];
let rdvs = [];
let parties = [];

function $(id){ return document.getElementById(id); }

function showToast(message, isError = false){
    const toast = $('toast');
    if(!toast) return;
    toast.textContent = message;
    toast.className = 'toast' + (isError ? ' error' : '');
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

function openModal(id){ if($(id)) $(id).classList.add('active'); }
function closeModal(id){ if($(id)) $(id).classList.remove('active'); }

function joueursPublics(){
    return joueurs.filter(j => j.role !== 'admin');
}

function navigateTo(page){
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    const target = $('page-' + page);
    if(target) target.classList.add('active');

    if(page === 'joueurs') afficherJoueurs();
    if(page === 'classement') afficherClassement();
    if(page === 'touches') loadKeybinds();
    if(page === 'profil') afficherProfil();
    if(page === 'rdv') loadRDVPage();
    if(page === 'admin') {
        renderAdminJoueurs();
        renderParties();
    }
}

function updateNav(){
    const nav = $('navMenu');
    if(!nav) return;

    let html = `
        <li><a onclick="navigateTo('accueil')">Accueil</a></li>
        <li><a onclick="navigateTo('classement')">Classement</a></li>
        <li><a onclick="navigateTo('rdv')">Rendez-vous</a></li>
        <li><a onclick="navigateTo('joueurs')">Joueurs</a></li>
        <li><a onclick="navigateTo('touches')">Touches</a></li>
    `;

    if(currentUser){
        html += `
            <li class="nav-separator"></li>
            <li><a class="nav-user" onclick="navigateTo('profil')">👤 ${currentUser.pseudo}</a></li>
        `;

        if(currentUser.role === 'admin'){
            html += `<li><a onclick="navigateTo('admin')">Admin</a></li>`;
        }

        html += `<li><a class="nav-btn-logout" onclick="logout()">Déconnexion</a></li>`;
    }else{
        html += `
            <li class="nav-separator"></li>
            <li><a class="nav-btn-login" onclick="openModal('modalLogin')">Connexion</a></li>
            <li><a class="nav-btn-register" onclick="openModal('modalRegister')">Inscription</a></li>
        `;
    }

    nav.innerHTML = html;
}

function updateHeroButtons(){
    const hero = $('heroBtns');
    if(!hero) return;

    if(currentUser){
        hero.innerHTML = `<button class="btn-primary" onclick="navigateTo('rdv')">🎮 Jouer maintenant</button>`;
    }else{
        hero.innerHTML = `
            <button class="btn-primary" onclick="openModal('modalRegister')">🚀 Commencer</button>
            <button class="btn-outline" onclick="openModal('modalLogin')">🔑 Connexion</button>
        `;
    }
}

async function handleRegister(e){
    e.preventDefault();

    const pseudo = $('regPseudo').value.trim();
    const email = $('regEmail').value.trim();
    const password = $('regPassword').value;
    const password2 = $('regPasswordConfirm').value;
    const ville = $('regVille').value;

    if(password !== password2){
        $('registerAlert').innerHTML = `<div class="alert alert-error">❌ Mots de passe différents</div>`;
        return false;
    }

    const res = await API.register({ pseudo, email, password, ville });

    if(res.success){
        currentUser = res.user;
        closeModal('modalRegister');
        updateNav();
        updateHeroButtons();
        await refreshAllData();
        showToast('✅ Compte créé');
        navigateTo('profil');
    }else{
        $('registerAlert').innerHTML = `<div class="alert alert-error">❌ ${res.message}</div>`;
    }

    return false;
}

async function handleLogin(e){
    e.preventDefault();

    const pseudo = $('loginPseudo').value.trim();
    const password = $('loginPassword').value;

    const res = await API.login({ pseudo, password });

    if(res.success){
        currentUser = res.user;
        closeModal('modalLogin');
        updateNav();
        updateHeroButtons();
        await refreshAllData();
        showToast('✅ Connexion réussie');
        navigateTo('profil');
    }else{
        $('loginAlert').innerHTML = `<div class="alert alert-error">❌ ${res.message}</div>`;
    }

    return false;
}

async function logout(){
    await API.logout();
    currentUser = null;
    updateNav();
    updateHeroButtons();
    navigateTo('accueil');
    showToast('👋 Déconnecté');
}

async function refreshAllData(){
    const joueursRes = await API.getJoueurs();
    if(joueursRes.success) joueurs = joueursRes.joueurs || [];

    const rdvRes = await API.getRDV();
    if(rdvRes.success) rdvs = rdvRes.rdvs || [];

    const partiesRes = await API.getParties();
    if(partiesRes.success) parties = partiesRes.parties || [];

    afficherJoueurs();
    afficherClassement();
    afficherRDV();
    renderAdminJoueurs();
    renderParties();

    if($('statJoueurs')) $('statJoueurs').textContent = joueursPublics().length;
    if($('statRDV')) $('statRDV').textContent = rdvs.length;
}

function afficherJoueurs(){
    const body = $('joueursBody');
    if(!body) return;

    const list = joueursPublics();

    if(list.length === 0){
        body.innerHTML = `<tr><td colspan="5">Aucun joueur.</td></tr>`;
        return;
    }

    body.innerHTML = list.map(j => `
        <tr>
            <td>${j.pseudo}</td>
            <td>${j.ville}</td>
            <td>${j.score ?? 0}</td>
            <td>${j.matchs ?? 0}</td>
            <td>${j.createAt ?? '-'}</td>
        </tr>
    `).join('');

    if($('statJoueurs')) $('statJoueurs').textContent = list.length;
}

function afficherClassement(){
    const body = $('classementBody');
    if(!body) return;

    const list = joueursPublics();

    if(list.length === 0){
        body.innerHTML = `<tr><td colspan="6">Aucun joueur.</td></tr>`;
        return;
    }

    body.innerHTML = list.map((j, i) => {
        const kills = Number(j.kills ?? 0);
        const deaths = Number(j.deaths ?? 0);
        const kd = deaths > 0 ? (kills / deaths).toFixed(2) : kills;

        return `
            <tr>
                <td>#${i + 1}</td>
                <td>${j.pseudo}</td>
                <td>${j.ville}</td>
                <td>${kills}</td>
                <td>${kd}</td>
                <td>${j.score ?? 0}</td>
            </tr>
        `;
    }).join('');
}

function loadRDVPage(){
    const pseudoInput = $('rdvPseudo');
    const clearBtn = document.querySelector('#page-rdv .btn-danger');

    if(pseudoInput && currentUser){
        pseudoInput.value = currentUser.pseudo;
    }

    if(clearBtn){
        clearBtn.style.display = currentUser && currentUser.role === 'admin'
            ? 'inline-block'
            : 'none';
    }

    afficherRDV();
}

async function publierRDV(){
    if(!currentUser){
        showToast('Connecte-toi pour publier un rendez-vous', true);
        return;
    }

    const mode = $('rdvMode').value;
    const date = $('rdvDate').value;
    const time = $('rdvTime').value;
    const message = $('rdvMessage').value.trim();

    if(!date || !time || !message){
        showToast('Complète tous les champs', true);
        return;
    }

    const res = await API.addRDV({ mode, date, time, message });

    if(res.success){
        showToast('Rendez-vous publié');
        $('rdvMessage').value = '';
        await refreshAllData();
        switchRdvTab('liste');
    }else{
        showToast(res.message || 'Erreur RDV', true);
    }
}

function switchRdvTab(tab){
    document.querySelectorAll('#page-rdv .tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('#page-rdv .tab-content').forEach(content => content.classList.remove('active'));

    if(tab === 'publier'){
        document.querySelector('#page-rdv .tab-btn:nth-child(1)').classList.add('active');
        $('rdvTab-publier').classList.add('active');
    }

    if(tab === 'liste'){
        document.querySelector('#page-rdv .tab-btn:nth-child(2)').classList.add('active');
        $('rdvTab-liste').classList.add('active');
        afficherRDV();
    }
}

function afficherRDV(){
    const list = $('rdvList');
    const counter = $('rdvCounter');
    if(!list) return;

    if(counter) counter.textContent = rdvs.length + ' rendez-vous';

    if(rdvs.length === 0){
        list.innerHTML = `
            <div class="empty-state">
                <div class="icon">📭</div>
                <p>Aucun rendez-vous.</p>
            </div>
        `;
        return;
    }

    list.innerHTML = rdvs.map(r => {
        const canDelete = currentUser && (
            currentUser.role === 'admin' || currentUser.pseudo === r.pseudo
        );

        return `
            <div class="rdv-item">
                <div class="rdv-header">
                    <span class="rdv-pseudo">🎮 ${r.pseudo}</span>
                    <span class="rdv-date-time">📅 ${r.date} à ${r.time}</span>
                </div>

                <span class="rdv-mode">🎯 ${r.mode}</span>
                <span class="rdv-mode">🏙️ ${r.ville || '-'}</span>

                <div class="rdv-message">${r.message}</div>

                ${canDelete ? `
                    <div class="rdv-actions">
                        <button class="btn-danger" onclick="deleteRDV(${r.id})">🗑️ Supprimer</button>
                    </div>
                ` : ''}
            </div>
        `;
    }).join('');
}
async function deleteRDV(id){
    if(!currentUser || currentUser.role !== 'admin'){
        showToast('Action réservée à l’admin', true);
        return;
    }

    if(!confirm('Supprimer ce rendez-vous ?')) return;

    const res = await API.deleteRDV(id);

    if(res.success){
        showToast('Rendez-vous supprimé');
        await refreshAllData();
    }else{
        showToast(res.message || 'Erreur suppression', true);
    }
}

function afficherProfil(){
    const profil = $('profilContent');
    if(!profil || !currentUser) return;

    profil.innerHTML = `
        <div class="card">
            <div class="profile-header">
                <div class="profile-avatar">${currentUser.pseudo.charAt(0).toUpperCase()}</div>
                <div class="profile-info">
                    <h3>${currentUser.pseudo}</h3>
                    <p>📧 ${currentUser.email}</p>
                    <p>🏙️ ${currentUser.ville}</p>
                    <p>🎖️ ${currentUser.role}</p>
                </div>
            </div>
        </div>
    `;
}

function renderAdminJoueurs(){
    const body = $('adminJoueursBody');
    if(!body) return;

    if(joueurs.length === 0){
        body.innerHTML = `<tr><td colspan="5">Aucun joueur.</td></tr>`;
        return;
    }

    body.innerHTML = joueurs.map(j => `
        <tr>
            <td>${j.pseudo}</td>
            <td>${j.email || '-'}</td>
            <td>${j.ville || '-'}</td>
            <td>${j.role || 'joueur'}</td>
            <td>
                <button class="btn-edit" onclick="openEditJoueurModal('${j.pseudo}')">Modifier</button>
                <button class="btn-danger" onclick="deleteJoueur('${j.pseudo}')">Supprimer</button>
            </td>
        </tr>
    `).join('');
}

function openEditJoueurModal(pseudo){
    const joueur = joueurs.find(j => j.pseudo === pseudo);
    if(!joueur) return;

    $('editPseudo').value = joueur.pseudo;
    $('editVille').value = joueur.ville || 'Marseille';
    $('editRole').value = joueur.role || 'joueur';
    $('editPassword').value = '';

    openModal('modalEditJoueur');
}

async function handleEditJoueur(e){
    e.preventDefault();

    const res = await API.editJoueur({
        pseudo: $('editPseudo').value,
        ville: $('editVille').value,
        role: $('editRole').value,
        password: $('editPassword').value
    });

    if(res.success){
        closeModal('modalEditJoueur');
        showToast('Joueur modifié');
        await refreshAllData();
    }else{
        showToast(res.message || 'Erreur modification', true);
    }

    return false;
}

async function deleteJoueur(pseudo){
    if(!confirm('Supprimer ' + pseudo + ' ?')) return;

    const res = await API.deleteJoueur(pseudo);

    if(res.success){
        showToast('Joueur supprimé');
        await refreshAllData();
    }else{
        showToast(res.message || 'Erreur suppression', true);
    }
}

async function handleAddJoueur(e){
    e.preventDefault();

    const res = await API.register({
        pseudo: $('addPseudo').value.trim(),
        email: $('addEmail').value.trim(),
        password: $('addPassword').value,
        ville: $('addVille').value,
        role: $('addRole').value
    });

    if(res.success){
        closeModal('modalAddJoueur');
        $('addJoueurForm').reset();
        showToast('Joueur ajouté');
        await refreshAllData();
    }else{
        showToast(res.message || 'Erreur ajout joueur', true);
    }

    return false;
}

async function lancerPartie(e){
    e.preventDefault();

    const res = await API.lancerPartie({
        map: $('adminMap').value,
        mode: $('adminMode').value,
        nbJoueurs: $('adminNbJoueurs').value,
        temps: $('adminTemps').value,
        kills: $('adminKills').value
    });

    if(res.success){
        showToast('Partie lancée');
        await refreshAllData();
    }else{
        showToast(res.message || 'Erreur lancement partie', true);
    }

    return false;
}

function renderParties(){
    const body = $('partiesBody');
    if(!body) return;

    if(parties.length === 0){
        body.innerHTML = `<tr><td colspan="7">Aucune partie.</td></tr>`;
        return;
    }

    body.innerHTML = parties.map(p => `
        <tr>
            <td>${p.map}</td>
            <td>${p.mode}</td>
            <td>${p.nbJoueurs}</td>
            <td>${p.temps} min</td>
            <td>${p.kills}</td>
            <td>${p.statut}</td>
            <td>
                <form action = "http://192.168.1.5:8000/stop" method = "POST">
                <button class="btn-danger" onclick="deletePartie(${p.id})">Supprimer</button>
                </form>
            </td>
        </tr>
    `).join('');
}

async function deletePartie(id){
    if(!confirm('Supprimer cette partie ?')) return;

    const res = await API.deletePartie(id);

    if(res.success){
        showToast('Partie supprimée');
        await refreshAllData();
    }else{
        showToast(res.message || 'Erreur suppression partie', true);
    }
}

function initKeySelectsSafe(){
    const keys = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];

    ['bindAvancer','bindReculer','bindGauche','bindDroite'].forEach(id => {
        const select = $(id);
        if(!select) return;

        select.innerHTML = '';
        keys.forEach(k => {
            const option = document.createElement('option');
            option.value = k;
            option.textContent = k;
            select.appendChild(option);
        });
    });
}

async function loadKeybinds(){
    if(!currentUser) return;

    const res = await API.getKeybinds();
    if(!res.success) return;

    const k = res.keybinds;
    $('bindAvancer').value = k.avancer;
    $('bindReculer').value = k.reculer;
    $('bindGauche').value = k.gauche;
    $('bindDroite').value = k.droite;
}

async function saveKeybinds(){
    if(!currentUser){
        showToast('Connecte-toi pour sauvegarder tes touches', true);
        return;
    }

    const avancer = $('bindAvancer').value;
    const reculer = $('bindReculer').value;
    const gauche = $('bindGauche').value;
    const droite = $('bindDroite').value;

    const touches = [avancer, reculer, gauche, droite];

    if([...new Set(touches)].length !== touches.length){
        showToast('❌ Chaque touche doit être unique', true);
        return;
    }

    const res = await API.saveKeybinds({ avancer, reculer, gauche, droite });

    if(res.success) showToast('💾 Touches sauvegardées');
    else showToast(res.message || 'Erreur sauvegarde', true);
}

async function init(){
    initKeySelectsSafe();

    const session = await API.getSession();
    if(session.success) currentUser = session.user;

    updateNav();
    updateHeroButtons();
    await refreshAllData();
}
async function clearAllRDV(){
    if(!currentUser || currentUser.role !== 'admin'){
        showToast('Action réservée à l’admin', true);
        return;
    }

    if(!confirm('Supprimer tous les rendez-vous ?')) return;

    const res = await API.clearAllRDV();

    if(res.success){
        showToast('Tous les rendez-vous supprimés');
        await refreshAllData();
    }else{
        showToast(res.message || 'Erreur suppression totale', true);
    }
}