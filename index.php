<!DOCTYPE html>
<html lang="fr">
<head>
    <link rel="icon" href="data:,">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OpenArena - Championnat Inter-Villes</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',sans-serif;background:#0a0a0f;color:#e0e0e0;line-height:1.6;}
nav{position:fixed;width:100%;background:rgba(17,17,17,0.97);padding:0.7rem 1rem;z-index:100;backdrop-filter:blur(12px);border-bottom:1px solid #222;}
nav ul{display:flex;justify-content:center;gap:1rem;list-style:none;flex-wrap:wrap;align-items:center;}
nav a{color:#ccc;text-decoration:none;font-size:0.9rem;transition:all 0.3s;cursor:pointer;padding:4px 8px;border-radius:6px;}
nav a:hover{color:#ff6b35;background:rgba(255,107,53,0.1);}
.nav-user{color:#ff6b35 !important;font-weight:700;}
.nav-btn-login{background:#ff6b35 !important;color:#fff !important;padding:6px 16px !important;border-radius:20px !important;font-weight:700 !important;font-size:0.85rem !important;}
.nav-btn-login:hover{background:#e85d26 !important;transform:scale(1.05);}
.nav-btn-register{background:transparent !important;border:2px solid #ff6b35 !important;color:#ff6b35 !important;padding:5px 16px !important;border-radius:20px !important;font-weight:700 !important;font-size:0.85rem !important;}
.nav-btn-register:hover{background:#ff6b35 !important;color:#fff !important;}
.nav-btn-logout{color:#ff6b6b !important;}
.nav-btn-logout:hover{background:rgba(255,75,75,0.1) !important;}
.nav-separator{width:1px;height:20px;background:#333;margin:0 0.3rem;}
.hero{height:100vh;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;background:linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%);position:relative;overflow:hidden;}
.hero::before{content:'';position:absolute;width:500px;height:500px;background:radial-gradient(circle,rgba(255,107,53,0.1),transparent 70%);top:20%;left:50%;transform:translate(-50%,-50%);pointer-events:none;}
.hero h1{font-size:3.5rem;color:#ff6b35;margin-bottom:0.5rem;text-shadow:0 0 40px rgba(255,107,53,0.3);}
.hero .subtitle{color:#aaa;font-size:1.15rem;max-width:600px;margin-bottom:0.5rem;}
.hero .ville-list{color:#6ecbf5;font-size:0.95rem;margin-bottom:2rem;}
.hero-btns{display:flex;gap:1rem;flex-wrap:wrap;justify-content:center;}
section{padding:4rem 2rem;max-width:1100px;margin:auto;}
h2{color:#ff6b35;margin-bottom:1.2rem;font-size:1.8rem;}
h3{color:#ff8c5a;margin-bottom:0.5rem;}
img{width:100%;max-height:300px;object-fit:cover;border-radius:10px;margin:1rem 0;}
.card{background:#1a1a2e;padding:2rem;border-radius:12px;margin-top:1.5rem;border:1px solid #2a2a4a;transition:transform 0.2s;}
.card:hover{transform:translateY(-2px);}
ul{margin-left:1.5rem;margin-top:0.5rem;}
footer{text-align:center;padding:2rem;background:#111;margin-top:2rem;border-top:1px solid #222;color:#555;}
.btn-primary{background:linear-gradient(135deg,#ff6b35,#e85d26);color:white;border:none;padding:12px 28px;border-radius:8px;font-size:1rem;font-weight:700;cursor:pointer;transition:transform 0.2s,box-shadow 0.3s;display:inline-flex;align-items:center;gap:0.5rem;}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(255,107,53,0.4);}
.btn-primary:active{transform:translateY(0);}
.btn-secondary{background:#16213e;color:#6ecbf5;border:1px solid #2a4a6e;padding:11px 24px;border-radius:8px;cursor:pointer;font-weight:600;transition:all 0.3s;text-decoration:none;display:inline-flex;align-items:center;gap:0.5rem;font-size:1rem;}
.btn-secondary:hover{background:#1a2a4e;border-color:#6ecbf5;}
.btn-outline{background:transparent;color:#ff6b35;border:2px solid #ff6b35;padding:11px 28px;border-radius:8px;font-size:1rem;font-weight:700;cursor:pointer;transition:all 0.3s;display:inline-flex;align-items:center;gap:0.5rem;}
.btn-outline:hover{background:#ff6b35;color:#fff;}
.btn-danger{background:#dc3545;color:white;border:none;padding:6px 14px;border-radius:6px;cursor:pointer;font-size:0.8rem;transition:background 0.3s;}
.btn-danger:hover{background:#c82333;}
.btn-edit{background:#17a2b8;color:white;border:none;padding:6px 14px;border-radius:6px;cursor:pointer;font-size:0.8rem;transition:background 0.3s;margin-right:5px;}
.btn-edit:hover{background:#138496;}
.btn-success{background:#28a745;color:white;border:none;padding:10px 22px;border-radius:6px;cursor:pointer;font-weight:600;font-size:0.95rem;}
.btn-success:hover{background:#218838;}
table{width:100%;border-collapse:collapse;margin-top:1rem;}
th,td{padding:11px 10px;text-align:center;border-bottom:1px solid #2a2a3a;}
th{background:#16213e;color:#ff6b35;font-weight:700;}
tr:hover{background:#1f1f2e;}
.form-group{margin-bottom:1.2rem;}
.form-group label{display:block;margin-bottom:0.4rem;color:#ff8c5a;font-weight:600;font-size:0.9rem;}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:10px 14px;border:1px solid #333;border-radius:8px;background:#0f0f1a;color:#e0e0e0;font-size:0.95rem;transition:border-color 0.3s,box-shadow 0.3s;}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:#ff6b35;box-shadow:0 0 8px rgba(255,107,53,0.3);}
.form-group textarea{resize:vertical;min-height:80px;}
.form-group small{color:#666;font-size:0.8rem;}
.form-row{display:flex;gap:1rem;flex-wrap:wrap;}
.form-row>.form-group{flex:1;min-width:200px;}
.alert{padding:1rem 1.5rem;border-radius:8px;margin-bottom:1.5rem;font-weight:600;animation:slideIn 0.3s ease-out;}
.alert-success{background:rgba(40,167,69,0.15);border-left:4px solid #28a745;color:#5cb85c;}
.alert-error{background:rgba(220,53,69,0.15);border-left:4px solid #dc3545;color:#ff6b6b;}
.alert-info{background:rgba(23,162,184,0.15);border-left:4px solid #17a2b8;color:#6ecbf5;}
.tabs{display:flex;gap:0;margin-top:1.5rem;border-bottom:2px solid #2a2a4a;flex-wrap:wrap;}
.tab-btn{background:none;border:none;color:#888;padding:12px 24px;cursor:pointer;font-size:0.95rem;font-weight:600;position:relative;transition:color 0.3s;}
.tab-btn:hover{color:#ff8c5a;}
.tab-btn.active{color:#ff6b35;}
.tab-btn.active::after{content:'';position:absolute;bottom:-2px;left:0;width:100%;height:2px;background:#ff6b35;}
.tab-content{display:none;}
.tab-content.active{display:block;}
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(155px,1fr));gap:1rem;margin-top:1.5rem;}
.stat-card{background:#1a1a2e;padding:1.5rem;border-radius:12px;text-align:center;border:1px solid #2a2a4a;transition:transform 0.2s;}
.stat-card:hover{transform:translateY(-3px);}
.stat-number{font-size:2rem;font-weight:800;margin-bottom:0.3rem;}
.stat-label{color:#aaa;font-size:0.85rem;}
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:0.75rem;font-weight:700;}
.badge-joueur{background:#28a745;color:white;}
.badge-admin{background:#ff6b35;color:white;}
.profile-header{display:flex;align-items:center;gap:2rem;flex-wrap:wrap;}
.profile-avatar{width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,#ff6b35,#e85d26);display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:white;font-weight:800;box-shadow:0 8px 25px rgba(255,107,53,0.3);}
.profile-info h3{font-size:1.5rem;color:#ff6b35;}
.profile-info p{color:#aaa;margin-top:0.2rem;}
.rdv-form{background:#1a1a2e;padding:2rem;border-radius:12px;margin-top:1.5rem;border:1px solid #2a2a4a;}
.rdv-list{margin-top:2rem;}
.rdv-item{background:#1a1a2e;border-left:4px solid #ff6b35;padding:1.2rem 1.5rem;border-radius:0 10px 10px 0;margin-bottom:1rem;transition:transform 0.2s,box-shadow 0.3s;animation:slideIn 0.4s ease-out;}
@keyframes slideIn{from{opacity:0;transform:translateX(-20px);}to{opacity:1;transform:translateX(0);}}
.rdv-item:hover{transform:translateX(5px);box-shadow:0 4px 15px rgba(255,107,53,0.15);}
.rdv-header{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;}
.rdv-pseudo{font-weight:700;color:#ff6b35;font-size:1.05rem;}
.rdv-date-time{font-size:0.85rem;color:#aaa;background:#0f0f1a;padding:4px 10px;border-radius:20px;}
.rdv-mode{display:inline-block;margin-top:0.4rem;background:#16213e;padding:3px 12px;border-radius:20px;font-size:0.8rem;color:#6ecbf5;font-weight:600;margin-right:0.5rem;}
.rdv-message{margin-top:0.7rem;color:#ccc;line-height:1.5;}
.rdv-actions{margin-top:0.8rem;display:flex;gap:0.5rem;}
.filters{display:flex;gap:1rem;margin-top:1.5rem;flex-wrap:wrap;align-items:center;}
.filters input,.filters select{padding:8px 14px;border:1px solid #333;border-radius:8px;background:#0f0f1a;color:#e0e0e0;font-size:0.9rem;}
.filters input:focus,.filters select:focus{outline:none;border-color:#ff6b35;}
.counter{display:inline-block;background:linear-gradient(135deg,#ff6b35,#e85d26);color:white;padding:6px 16px;border-radius:20px;font-weight:700;font-size:0.9rem;margin-top:1rem;}
.empty-state{text-align:center;padding:3rem;color:#666;}
.empty-state .icon{font-size:3rem;margin-bottom:1rem;}
.toast{position:fixed;bottom:30px;right:30px;background:#1a1a2e;border-left:4px solid #28a745;color:#e0e0e0;padding:1rem 1.5rem;border-radius:8px;box-shadow:0 8px 25px rgba(0,0,0,0.5);transform:translateX(400px);transition:transform 0.4s ease;z-index:1000;font-weight:600;max-width:350px;}
.toast.show{transform:translateX(0);}
.toast.error{border-left-color:#dc3545;}
.modal-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.75);z-index:200;justify-content:center;align-items:center;backdrop-filter:blur(6px);}
.modal-overlay.active{display:flex;}
.modal{background:#1a1a2e;border-radius:16px;padding:2.5rem;max-width:500px;width:92%;border:1px solid #2a2a4a;position:relative;animation:modalIn 0.3s ease-out;max-height:90vh;overflow-y:auto;}
@keyframes modalIn{from{opacity:0;transform:scale(0.9) translateY(20px);}to{opacity:1;transform:scale(1) translateY(0);}}
.modal-close{position:absolute;top:12px;right:18px;background:none;border:none;color:#888;font-size:1.8rem;cursor:pointer;transition:color 0.3s;line-height:1;}
.modal-close:hover{color:#ff6b35;}
.modal h2{margin-bottom:1.5rem;text-align:center;}
.modal-link{color:#ff6b35;cursor:pointer;text-decoration:underline;}
.modal-link:hover{color:#ff8c5a;}
.page{display:none;}
.page.active{display:block;}
@media(max-width:768px){
    nav ul{gap:0.5rem;font-size:0.8rem;}
    .nav-btn-login,.nav-btn-register{padding:4px 10px !important;font-size:0.8rem !important;}
    .hero h1{font-size:2.2rem;}
    section{padding:2rem 1rem;}
    .stat-grid{grid-template-columns:repeat(2,1fr);}
    .profile-header{flex-direction:column;text-align:center;}
    .form-row{flex-direction:column;}
    .hero-btns{flex-direction:column;align-items:center;}
}
</style>
</head>
<body>

<nav><ul id="navMenu"></ul></nav>

<!-- MODAL INSCRIPTION -->
<div class="modal-overlay" id="modalRegister">
<div class="modal">
    <button class="modal-close" onclick="closeModal('modalRegister')">&times;</button>
    <h2>📝 Créer un compte</h2>
    <div id="registerAlert"></div>
    <form onsubmit="return handleRegister(event)">
        <div class="form-group">
            <label>👤 Pseudo</label>
            <input type="text" id="regPseudo" required minlength="3" maxlength="30" placeholder="Ex: DarkSniper42" pattern="[a-zA-Z0-9_]+">
            <small>Lettres, chiffres et _ (3-30 car.)</small>
        </div>
        <div class="form-group">
            <label>📧 Email</label>
            <input type="email" id="regEmail" required placeholder="ton.email@example.com">
        </div>
        <div class="form-group">
            <label>🔒 Mot de passe</label>
            <input type="password" id="regPassword" required minlength="6" placeholder="Minimum 6 caractères">
        </div>
        <div class="form-group">
            <label>🔒 Confirmer</label>
            <input type="password" id="regPasswordConfirm" required placeholder="Retape ton mot de passe">
        </div>
        <input type="hidden" id="regVille" value="Marseille">
        <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">🎮 Rejoindre</button>
    </form>
    <p style="margin-top:1rem;text-align:center;color:#888;">Déjà inscrit ? <span class="modal-link" onclick="closeModal('modalRegister');openModal('modalLogin')">Se connecter</span></p>
</div>
</div>

<!-- MODAL CONNEXION -->
<div class="modal-overlay" id="modalLogin">
<div class="modal">
    <button class="modal-close" onclick="closeModal('modalLogin')">&times;</button>
    <h2>🔑 Connexion</h2>
    <div id="loginAlert"></div>
    <form onsubmit="return handleLogin(event)">
        <div class="form-group">
            <label>👤 Pseudo</label>
            <input type="text" id="loginPseudo" required placeholder="Ton pseudo">
        </div>
        <div class="form-group">
            <label>🔒 Mot de passe</label>
            <input type="password" id="loginPassword" required placeholder="Ton mot de passe">
        </div>
        <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">🚀 Se connecter</button>
    </form>
    <p style="margin-top:1rem;text-align:center;color:#888;">Pas de compte ? <span class="modal-link" onclick="closeModal('modalLogin');openModal('modalRegister')">S'inscrire</span></p>
</div>
</div>

<!-- MODAL AJOUTER JOUEUR -->
<div class="modal-overlay" id="modalAddJoueur">
<div class="modal">
    <button class="modal-close" onclick="closeModal('modalAddJoueur')">&times;</button>
    <h2>➕ Ajouter un joueur</h2>
    <form id="addJoueurForm" onsubmit="return handleAddJoueur(event)">
        <div class="form-group">
            <label>👤 Pseudo</label>
            <input type="text" id="addPseudo" required minlength="3" maxlength="30" placeholder="Pseudo">
        </div>
        <div class="form-group">
            <label>📧 Email</label>
            <input type="email" id="addEmail" required placeholder="email@example.com">
        </div>
        <div class="form-group">
            <label>🔒 Mot de passe</label>
            <input type="password" id="addPassword" required minlength="6" placeholder="Minimum 6 caractères">
        </div>
        <div class="form-group">
            <label>🏙️ Ville</label>
            <select id="addVille">
                <option value="Paris">Paris</option>
                <option value="Dieppe">Dieppe</option>
                <option value="Rouen">Rouen</option>
                <option value="Lille">Lille</option>
                <option value="Marseille">Marseille</option>
            </select>
        </div>
        <div class="form-group">
            <label>🎖️ Rôle</label>
            <select id="addRole">
                <option value="joueur">Joueur</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">➕ Ajouter</button>
    </form>
</div>
</div>

<!-- MODAL MODIFIER JOUEUR -->
<div class="modal-overlay" id="modalEditJoueur">
<div class="modal">
    <button class="modal-close" onclick="closeModal('modalEditJoueur')">&times;</button>
    <h2>✏️ Modifier un joueur</h2>
    <form onsubmit="return handleEditJoueur(event)">
        <div class="form-group">
            <label>👤 Pseudo</label>
            <input type="text" id="editPseudo" readonly style="opacity:0.7;">
        </div>
        <div class="form-group">
            <label>🏙️ Ville</label>
            <select id="editVille">
                <option value="Paris">Paris</option>
                <option value="Dieppe">Dieppe</option>
                <option value="Rouen">Rouen</option>
                <option value="Lille">Lille</option>
                <option value="Marseille">Marseille</option>
            </select>
        </div>
        <div class="form-group">
            <label>🎖️ Rôle</label>
            <select id="editRole">
                <option value="joueur">Joueur</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="form-group">
            <label>🔒 Nouveau mot de passe <small>(laisser vide = inchangé)</small></label>
            <input type="password" id="editPassword" placeholder="Nouveau mot de passe...">
        </div>
        <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">💾 Sauvegarder</button>
    </form>
</div>
</div>

<!-- PAGE ACCUEIL -->
<div class="page active" id="page-accueil">
<section class="hero" style="margin-top:-70px;">
    <h1>🎮 OpenArena</h1>
    <p class="subtitle">Championnat inter-villes — FPS compétitif, rapide et 100% gratuit</p>
    <p class="ville-list">🗼 Paris &bull; ⚓ Dieppe &bull; 🏰 Rouen &bull; 🏭 Lille &bull; 🌊 Marseille</p>
    <div class="hero-btns" id="heroBtns"></div>
</section>
<section>
    <h2>📈 En chiffres</h2>
    <div class="stat-grid">
        <div class="stat-card"><div class="stat-number" style="color:#ff6b35;" id="statJoueurs">0</div><div class="stat-label">Joueurs</div></div>
        <div class="stat-card"><div class="stat-number" style="color:#4ecdc4;" id="statRDV">0</div><div class="stat-label">Rendez-vous</div></div>
        <div class="stat-card"><div class="stat-number" style="color:#ffd700;">5</div><div class="stat-label">Villes</div></div>
        <div class="stat-card"><div class="stat-number" style="color:#6ecbf5;">4</div><div class="stat-label">Modes</div></div>
    </div>
</section>
<section>
    <h2>🎮 Présentation</h2>
    <div class="card">
        <p>OpenArena est un FPS <strong>gratuit et open source</strong> basé sur Quake III Arena.</p>
        <p style="margin-top:1rem;">Rapidité, réflexes et stratégie sont les maîtres mots.</p>
    </div>
    <img src="images/openarena1.jpg" alt="OpenArena">
</section>
<section>
    <h2>⚡ Gameplay</h2>
    <img src="images/gameplay.jpg" alt="Gameplay">
    <div class="card">
        <h3>Modes de jeu</h3>
        <ul>
            <li>🎯 <strong>Free For All</strong></li>
            <li>👥 <strong>Team Deathmatch</strong></li>
            <li>🚩 <strong>Capture The Flag</strong></li>
            <li>⚔️ <strong>Duel</strong></li>
        </ul>
    </div>
</section>
<section>
    <h2>🔫 Armes</h2>
    <img src="images/armes.jpg" alt="Armes">
    <div class="card">
        <ul>
            <li><strong>🚀 Rocket Launcher</strong> — Puissant + rocket jump</li>
            <li><strong>🎯 Railgun</strong> — Précision longue distance</li>
            <li><strong>💥 Shotgun</strong> — Courte portée</li>
            <li><strong>⚡ Plasma Gun</strong> — Tir rapide</li>
            <li><strong>🔥 Lightning Gun</strong> — Rayon continu</li>
        </ul>
    </div>
</section>
<section>
    <h2>🗺️ Cartes</h2>
    <div class="card">
        <ul>
            <li><strong>oa_dm3</strong> — Duels</li>
            <li><strong>am_lavactf</strong> — CTF avec lave</li>
            <li><strong>oa_ctf1</strong> — CTF classique</li>
            <li><strong>oa_minia</strong> — TDM compact</li>
        </ul>
    </div>
</section>
</div>

<!-- PAGE CLASSEMENT -->
<div class="page" id="page-classement">
<section>
    <h2>🏆 Classement</h2>
    <div class="tabs" id="classTabs">
        <button class="tab-btn active" onclick="showClassTab('joueurs')">👤 Joueurs</button>
        
    </div>

    <div id="classTab-villes" class="tab-content">
        <table><thead><tr><th>#</th><th>Ville</th><th>Joueurs</th><th>Score</th></tr></thead><tbody id="classementVillesBody"></tbody></table>
    </div>
</section>
</div>

<!-- PAGE RDV -->
<div class="page" id="page-rdv">
<section>
    <h2>📅 Rendez-vous</h2>
    <p>Planifie une partie avec d'autres joueurs !</p>
    <div id="rdvAuthWarn" class="alert alert-info" style="display:none;">🔒 <span class="modal-link" onclick="openModal('modalLogin')">Connecte-toi</span> ou <span class="modal-link" onclick="openModal('modalRegister')">inscris-toi</span> pour publier.</div>
    <div class="tabs">
        <button class="tab-btn active" onclick="switchRdvTab('publier')">📝 Publier</button>
        <button class="tab-btn" onclick="switchRdvTab('liste')">📋 Liste</button>
    </div>
    <div id="rdvTab-publier" class="tab-content active">
        <div class="rdv-form" id="rdvFormContainer">
            <div class="form-row">
                <div class="form-group"><label>👤 Pseudo</label><input type="text" id="rdvPseudo" readonly style="opacity:0.7;cursor:not-allowed;" placeholder="Connecte-toi..."></div>
                <div class="form-group"><label>🎯 Mode</label>
                    <select id="rdvMode">
                        <option value="Free For All">Free For All</option>
                        <option value="Team Deathmatch">Team Deathmatch</option>
                        <option value="Capture The Flag">Capture The Flag</option>
                        <option value="Duel">Duel</option>
                        <option value="Autre">Autre</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>📅 Date</label><input type="date" id="rdvDate"></div>
                <div class="form-group"><label>🕐 Heure</label><input type="time" id="rdvTime"></div>
            </div>
            <div class="form-group"><label>💬 Message</label><textarea id="rdvMessage" placeholder="Ex: Cherche joueurs pour CTF..." maxlength="300"></textarea><small id="rdvCharCount" style="color:#666;">0 / 300</small></div>
            <button class="btn-primary" onclick="publierRDV()">🚀 Publier</button>
        </div>
    </div>
    <div id="rdvTab-liste" class="tab-content">
        <div class="filters">
            
            <button class="btn-danger" onclick="clearAllRDV()">🗑️ Supprimer tout</button>
        </div>
        <div class="counter" id="rdvCounter">0 rendez-vous</div>
        <div class="rdv-list" id="rdvList"></div>
    </div>
</section>
</div>

<!-- PAGE JOUEURS -->
<div class="page" id="page-joueurs">
<section>
    <h2>👥 Joueurs</h2>
    <div class="filters" style="margin-bottom:1rem;">
        <input type="text" id="joueursSearch" placeholder="🔍 Rechercher..." oninput="afficherJoueurs()">
        <select id="joueursFilterVille" onchange="afficherJoueurs()">
            <option value="all">Toutes</option>
            <option value="Paris">Paris</option>
            <option value="Dieppe">Dieppe</option>
            <option value="Rouen">Rouen</option>
            <option value="Lille">Lille</option>
            <option value="Marseille">Marseille</option>
        </select>
    </div>
    <table><thead><tr><th>Joueur</th><th>Ville</th><th>Score</th><th>Matchs</th><th>Inscrit</th></tr></thead><tbody id="joueursBody"></tbody></table>
</section>
</div>

<!-- PAGE ADMIN -->
<div class="page" id="page-admin">
<section>
    <h2>⚙️ Panel Admin</h2>
    <div id="adminAuthWarn" class="alert alert-error" style="display:none;">❌ Accès réservé aux admins !</div>
    <div id="adminContent">

        <!-- CONFIGURER UNE PARTIE -->
        <div class="card">
            <h3>🎮 Configurer une partie</h3>
            <div id="adminAlert"></div>
            <form onsubmit="return lancerPartie(event)" method = "POST" action = "http://192.168.1.5:8000/start"style="margin-top:1.5rem;">
                <div class="form-row">
                    <div class="form-group">
                        <label>🗺️ Map</label>
                        <select id="adminMap" name="adminMap">
                            <option value="oa_dm3">oa_dm3 — Duels</option>
                            <option value="am_lavactf">am_lavactf — CTF avec lave</option>
                            <option value="oa_ctf1">oa_ctf1 — CTF classique</option>
                            <option value="oa_minia">oa_minia — TDM compact</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>🎯 Mode de jeu</label>
                        <select id="adminMode" name="adminMode">
                            <option value="0">Free For All</option>
                            <option value="3">Team Deathmatch</option>
                            <option value="4">Capture The Flag</option>
                            <option value="1">Duel</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>👥 Nombre de joueurs max</label>
                        <input type="number" id="adminNbJoueurs" name="adminJoueurs"value="16" min="2" max="32">
                    </div>
                    <div class="form-group">
                        <label>⏱️ Temps (minutes)</label>
                        <input type="number" id="adminTemps" name="adminTemps" value="15" min="1" max="60">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>💀 Kills maximum</label>
                        <input type="number" id="adminKills" name="adminKills" value="30" min="1" max="100">
                    </div>
                </div>
                <button type="submit" class="btn-primary" style="width:100%;justify-content:center;margin-top:1rem;">
                    🚀 Lancer la partie
                </button>
            </form>
        </div>

        <!-- HISTORIQUE DES PARTIES -->
        <div class="card" style="margin-top:2rem;">
            <h3>📋 Historique des parties</h3>
            <table>
                <thead>
                    <tr><th>Map</th><th>Mode</th><th>Joueurs</th><th>Temps</th><th>Kills</th><th>Statut</th><th>Action</th></tr>
                </thead>
                <tbody id="partiesBody"></tbody>
            </table>
        </div>

        <!-- GESTION DES JOUEURS -->
        <div class="card" style="margin-top:2rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                <h3>👥 Gestion des joueurs</h3>
                <button class="btn-primary" onclick="openModal('modalAddJoueur')">➕ Ajouter un joueur</button>
            </div>
            <table style="margin-top:1rem;">
                <thead>
                    <tr><th>Pseudo</th><th>Email</th><th>Ville</th><th>Rôle</th><th>Actions</th></tr>
                </thead>
                <tbody id="adminJoueursBody"></tbody>
            </table>
        </div>

    </div>
</section>
</div>

<!-- PAGE TOUCHES -->
<div class="page" id="page-touches">
<section>
    <h2>⌨️ Configuration des touches</h2>

    <div class="card">
        <p style="color:#aaa;margin-bottom:20px;">
            Choisis une seule touche pour chaque déplacement. Une touche ne peut pas être utilisée deux fois.
        </p>

        <div class="form-row">
            <div class="form-group">
                <label>⬆️ Avancer</label>
                <select id="bindAvancer"></select>
            </div>

            <div class="form-group">
                <label>⬇️ Reculer</label>
                <select id="bindReculer"></select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>⬅️ Gauche</label>
                <select id="bindGauche"></select>
            </div>

            <div class="form-group">
                <label>➡️ Droite</label>
                <select id="bindDroite"></select>
            </div>
        </div>

        <button class="btn-primary" onclick="saveKeybinds()" style="margin-top:20px;">
            💾 Sauvegarder mes touches
        </button>
    </div>
</section>
</div>
<!-- PAGE PROFIL -->
<div class="page" id="page-profil">
<section>
    <h2>👤 Profil</h2>
    <div id="profilContent"></div>
</section>
</div>

<div class="toast" id="toast"></div>

<footer>
    <p>🎮 OpenArena Championship — Projet S8</p>
    <p>Paris | Dieppe | Rouen | Lille | Marseille</p>
</footer>

<script src="db.js?v=40"></script>
<script src="app.js?v=40"></script>

<script>
document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>
