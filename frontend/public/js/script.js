  /**
* VITE GOURMAND - SCRIPT CENTRAL v5.0 FINAL
  * Régénération complète - Version stable et fonctionnelle
  */

  class ViteGourmand {
  constructor() {
  this.utils = {
formatPrix: (p) => Number(p).toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' }),
getPage: () => window.location.pathname.split('/').pop().replace('.html', '') || 'index'
};
this.init();
  }
  
init() {
  $(document).ready(() => {
this.page = this.utils.getPage();
  console.log("✅ Page chargée:", this.page);
  this.router();
});
}

  router() {
  $('.nav-link').removeClass('active');
  $(`.nav-link[href="${this.page}.html"]`).addClass('active');
  
switch(this.page) {
case 'index': this.initIndex(); break;
case 'menus': this.initMenus(); break;
case 'commander': this.initCommander(); break;
  case 'recap-commande': this.initRecap(); break;
  case 'compte': this.initCompte(); break;
  case 'login': this.initLogin(); break;
  }
}
  
  // ==========================================
// MODULE: INDEX
// ==========================================
  initIndex() {
if (typeof lesAvis !== 'undefined') {
  lesAvis.forEach(avis => {
$('#avisClients').append(`
  <div class="col-md-4">
<div class="card p-4 shadow-sm h-100 border-0">
  <div class="text-warning mb-3">★★★★★</div>
<p class="fst-italic mb-3">"${avis.commentaire}"</p>
  <small class="text-muted fw-bold">- ${avis.auteur}</small>
</div>
  </div>
`);
});
}
  }

  // ==========================================
// MODULE: MENUS
// ==========================================
  initMenus() {
this.afficherMenus('all');
this.afficherPlatsCarte();

$('.filtre').on('click', (e) => {
  $('.filtre').removeClass('active');
  $(e.currentTarget).addClass('active');
  this.afficherMenus($(e.currentTarget).data('categorie'));
  });
}

  afficherMenus(cat) {
  const grid = $('#grilleMenus').empty();
  const items = cat === 'all' ? lesMenus : lesMenus.filter(m => m.categorie === cat);
  
  items.forEach(m => {
  let details = '';
if (m.categorie === 'digestif') {
  details = `<p class="small mb-0"><strong>${m.plats.boissons.join(', ')}</strong></p>`;
} else {
details = `
  <p class="small mb-1 text-muted"><strong>Entrées:</strong> ${m.plats.entrees.slice(0,2).join(', ')}...</p>
  <p class="small mb-1 text-muted"><strong>Plats:</strong> ${m.plats.plats.slice(0,2).join(', ')}...</p>
<p class="small mb-0 text-muted"><strong>Desserts:</strong> ${m.plats.desserts.slice(0,2).join(', ')}...</p>
`;
  }

  grid.append(`
<div class="col-md-6 col-lg-4">
<div class="card h-100 shadow-sm border-0">
  <img src="${m.image}" class="card-img-top" style="height: 220px; object-fit: cover;">
  <div class="card-body d-flex flex-column">
  <div class="d-flex justify-content-between mb-2">
<h5 class="fw-bold text-primary mb-1">${m.nom}</h5>
<span class="badge bg-primary">${this.utils.formatPrix(m.prix)}</span>
  </div>
  <p class="text-muted small flex-grow-1 mb-3">${m.description}</p>
<div class="bg-light p-2 rounded mb-3 border">${details}</div>
  <a href="commander.html" class="btn btn-primary w-100 mt-auto">Commander</a>
</div>
</div>
</div>
`);
  });
}

  afficherPlatsCarte() {
  const grid = $('#grilleCarte').empty();
const cats = { 'classiques': 'Les Classiques', 'vegan': 'Cuisine Végétale', 'sansPorc': 'Sans Porc' };
  
Object.entries(platsALaCarte).forEach(([key, plats]) => {
rapide
  const list = plats.map(p => `<li class="mb-2"><i class="fas fa-utensils text-primary me-2 small"></i>${p}</li>`).join('');
  grid.append(`
<div class="col-md-4">
  <div class="card h-100 border-0 shadow-sm">
<div class="card-header bg-primary text-white text-center fw-bold text-uppercase py-3">
${cats[key]}
</div>
  <div class="card-body">
<ul class="list-unstyled mb-0 small text-muted">${list}</ul>
</div>
<div class="card-footer bg-white border-0 text-center pb-3">
  <a href="commander.html" class="btn btn-sm btn-outline-secondary rounded-pill px-4">Choisir</a>
</div>
</div>
  </div>
`);
});
  }
  // je laisse ca la tkt
  // ==========================================
  // MODULE: COMMANDER
  // ==========================================
  initCommander() {
console.log("🚀 Commander chargé");

  $('.service-selector').off('click').on('click', (e) => {
  this.typeService = $(e.currentTarget).data('type');
$('#selectMenu').empty();
$('#listEntrees, #listPlats, #listDesserts, #listBoissons').empty();
  $('#detailMenuSelectionne, #sectionPersonnalisation').hide();
  
  $('#etape0').fadeOut(200, () => {
  $('#etape1').fadeIn(200);
this.remplirSelectMenu();
  });
});
  chez moi
  $('#selectMenu').off('change').on('change', () => this.selectionnerMenu());
  $('#btnVersRecap').off('click').on('click', () => this.allerRecap());
$('input[name="typeBoisson"]').off('change').on('change', () => this.remplirBoissons());
  }
  
remplirSelectMenu() {
ici
  const sel = $('#selectMenu').empty().append('<option value="">-- Sélectionnez --</option>');
  $('#titre-dynamique').text(this.typeService === 'menu' ? 'Votre Menu' : 'À la carte');
  
  if (this.typeService === 'menu') {
lesMenus.filter(m => m.categorie !== 'digestif').forEach(m => {
sel.append(`<option value="${m.id}">${m.nom}</option>`);
});
  } else {
  Object.entries(platsALaCarte).forEach(([c, p]) => {
  p.forEach((plat, i) => sel.append(`<option value="${c}-${i}">${plat}</option>`));
  });
}
  }

  selectionnerMenu() {
  const val = $('#selectMenu').val();
if (!val) return;
  chez moi
  if (this.typeService === 'menu') {
  this.choix = lesMenus.find(m => m.id == val);
  $('#imageMenu').attr('src', this.choix.image).show();
  $('#descriptionMenu').text(this.choix.description);
  this.remplirListes(this.choix.plats);
  } else {
const nomPlat = $('#selectMenu option:selected').text();
  this.choix = { nom: nomPlat, prix: PRIX_PLAT_ALA_CARTE };
$('#imageMenu').hide();
  $('#descriptionMenu').text("Plat individuel");
  $('#listEntrees, #listPlats').empty();
  $('#listPlats').html(`<div class="alert alert-success p-2 mb-0">${nomPlat}</div>`);

const desserts = [...new Set(lesMenus.flatMap(m => m.plats.desserts || []))];
this.remplirRadio('#listDesserts', desserts, 'dessert');
}

$('#nomMenuAffiche').text(this.choix.nom);
$('#prixMenuAffiche').text(this.utils.formatPrix(this.choix.prix));
  this.remplirBoissons();
  this.remplirDigestifs();
  $('#detailMenuSelectionne, #sectionPersonnalisation').fadeIn();
}

  remplirListes(plats) {
  this.remplirRadio('#listEntrees', plats.entrees || [], 'entree');
  this.remplirRadio('#listPlats', plats.plats || [], 'plat');
this.remplirRadio('#listDesserts', plats.desserts || [], 'dessert');
  }
  
remplirRadio(container, data, name) {
  const div = $(container).empty();
  data.forEach((item, index) => {
  div.append(`
  <div class="form-check mb-2">
  <input class="form-check-input" type="radio" name="${name}" value="${item}" id="${name}-${index}">
  <label class="form-check-label small" for="${name}-${index}">${item}</label>
</div>
`);
  });
}
  
remplirBoissons() {
  const div = $('#listBoissons').empty();
  chez moi
  const type = $('input[name="typeBoisson"]:checked').val() || 'sans';
  
if (type === 'sans') {
  const bSans = [...lesBoissons.nonAlcoolisees.sodas, ...lesBoissons.nonAlcoolisees.jusAndSirops];
bSans.forEach(b => div.append(`<div class="form-check mb-1"><input class="form-check-input" type="radio" name="boisson" value="${b}"> <label class="form-check-label small">${b}</label></div>`));
  } else {
: a voir plus tard
const bAlcool = ["Vin rouge", "Vin blanc", "Champagne", "Bière"];
bAlcool.forEach(b => div.append(`<div class="form-check mb-1"><input class="form-check-input" type="radio" name="boisson" value="${b}"> <label class="form-check-label small">${b}</label></div>`));
}
  }
  
  remplirDigestifs() {
const digestifMenu = lesMenus.find(m => m.categorie === 'digestif');
  const sel = $('#selectDigestif').empty().append('<option value="">Aucun</option>');
if (digestifMenu?.plats?.boissons) {
digestifMenu.plats.boissons.forEach(d => sel.append(`<option value="${d}">${d}</option>`));
  }
}

allerRecap() {
// // Validation des données
const commande = {
typeService: this.typeService,
menu: this.choix.nom,
  prix: this.choix.prix,
  entree: $('input[name="entree"]:checked').val(),
  plat: $('input[name="plat"]:checked').val() || this.choix.nom,
dessert: $('input[name="dessert"]:checked').val(),
  boisson: $('input[name="boisson"]:checked').val(),
digestif: $('#selectDigestif').val()
};
sessionStorage.setItem('tempCommande', JSON.stringify(commande));
  window.location.href = 'recap-commande.html';
  }
  : a voir plus tard
// ==========================================
// MODULE: RECAP
// ==========================================
initRecap() {
  this.data = JSON.parse(sessionStorage.getItem('tempCommande'));
  if (!this.data) return window.location.href = 'commander.html';
  
$('input[name="modeService"]').on('change', () => this.majRecap());
  $('#convives').on('input', () => this.majRecap());
  
$('#formFinal').on('submit', (e) => {
e.preventDefault();
alert("✅ Commande validée avec succès !");
  sessionStorage.removeItem('tempCommande');
window.location.href = 'index.html';
  });
  
  this.majRecap();
  }
  
  majRecap() {
const mode = $('input[name="modeService"]:checked').val();
  const n = parseInt($('#convives').val()) || 1;
  
  let dig = this.data.digestif || "Aucun";
if (mode === 'aEmporter' && this.data.digestif) dig = "Non inclus";
  
$('#affichageRecap').html(`
  <div class="mb-3">
  <h5 class="text-primary">${this.data.menu}</h5>
  <small class="text-muted d-block">Entrée: ${this.data.entree || '—'}</small>
<small class="text-muted d-block">Plat: ${this.data.plat}</small>
  <small class="text-muted d-block">Dessert: ${this.data.dessert || '—'}</small>
<small class="text-muted d-block">Boisson: ${this.data.boisson || '—'}</small>
  <strong class="d-block mt-2">Digestif: ${dig}</strong>
</div>
`);

: a voir plus tard
let prixBase = this.data.prix;
if (this.data.digestif && mode === 'surPlace') prixBase += PRIX_DIGESTIF;

let total = prixBase * n;
let rabais = [];
  
if (total > 100) { total *= 0.90; rabais.push("10% (Total > 100€)"); }
  if (n > 6) { total *= 0.75; rabais.push("25% (> 6 personnes)"); }
  
  $('#affichageRabais').html(rabais.length ?
  `<div class="alert alert-success p-2 small mb-0"><i class="fas fa-tags me-1"></i>${rabais.join(' + ')}</div>` :
  '<small class="text-muted">Aucune réduction</small>'
  );
$('#totalFinal').text(this.utils.formatPrix(total));
  }

// ==========================================
  // AUTRES MODULES
  // ==========================================
initLogin() {
  $('#formLogin').submit(e => {
e.preventDefault();
window.location.href = 'compte.html';
  });
}
ici
  initCompte() {
si c utile
if(typeof mesCommandes !== 'undefined') {
  mesCommandes.forEach(c => $('#tableCommandes').append(`
  <tr>
  <td>${c.id}</td>
  <td>${c.titreMenu}</td>
<td>${c.date}</td>
<td>${this.utils.formatPrix(c.prixTotal)}</td>
<td><span class="badge bg-success">${c.statut}</span></td>
  </tr>
  `));
}
}
  }

  window.VG = new ViteGourmand();
console.log("✅ ViteGourmand initialisé avec succès");