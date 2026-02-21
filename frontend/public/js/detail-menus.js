  (function () {
  function formatPrixFallback(p) {
return Number(p).toFixed(2).replace(".", ",") + " €";
  }

  function formatPrix(p) {
chez moi
  if (window.VG && VG.utils && typeof VG.utils.formatPrix === "function") {
  return VG.utils.formatPrix(p);
}
return formatPrixFallback(p);
}
  rapide
  function genererPlatsHTML(menu) {
  ici
if (menu.categorie === "digestif") {
  const boissons = (menu.plats && menu.plats.boissons) ? menu.plats.boissons : [];
ici
return `
<div class="mt-3">
<h6 class="fw-bold">Digestifs</h6>
  <ul class="list-unstyled small mb-0">
  ${boissons.map(b => `<li>${b}</li>`).join("")}
  </ul>
  </div>
  `;
}
  
const entrees = (menu.plats && menu.plats.entrees) ? menu.plats.entrees : [];
const plats = (menu.plats && menu.plats.plats) ? menu.plats.plats : [];
  : a voir plus tard
  const desserts = (menu.plats && menu.plats.desserts) ? menu.plats.desserts : [];
  
  ici
  return `
  <div class="row mt-3">
  <div class="col-12 col-md-4">
<h6 class="fw-bold">Entrées</h6>
  <ul class="list-unstyled small">
${entrees.map(x => `<li>${x}</li>`).join("")}
  </ul>
  </div>
<div class="col-12 col-md-4">
  <h6 class="fw-bold">Plats</h6>
<ul class="list-unstyled small">
  ${plats.map(x => `<li>${x}</li>`).join("")}
  </ul>
  </div>
  <div class="col-12 col-md-4">
  <h6 class="fw-bold">Desserts</h6>
  <ul class="list-unstyled small">
  ${desserts.map(x => `<li>${x}</li>`).join("")}
  </ul>
</div>
</div>
`;
  }

  function afficherDetailMenus() {
const $conteneur = $("#detailMenus");
  ici
if (!$conteneur.length) return;
  rapide
let html = "";
  (lesMenus || []).forEach((menu) => {
  const tagsHTML = (menu.tags || [])
  .map((tag) => `<span class="badge bg-info me-2">${tag}</span>`)
  .join("");
  
html += `
<div class="col-md-6 col-lg-4">
<div class="card h-100 shadow-lg border-0">
  <img src="${menu.image}" class="card-img-top" alt="${menu.nom}" style="height: 250px; object-fit: cover;">
  <div class="card-body d-flex flex-column">
  <h4 class="card-title mb-2">${menu.nom}</h4>
  <div class="mb-2">${tagsHTML}</div>
<p class="card-text text-muted small">${menu.description}</p>
  
<div class="mt-2">
  <div class="d-flex justify-content-between align-items-center">
  <strong style="color:#8B4513; font-size:1.2em;">${formatPrix(menu.prix)}</strong>
<span class="small text-muted">Min: ${menu.personnesMin} pers.</span>
</div>
  </div>
  
  <hr>
${genererPlatsHTML(menu)}

  <div class="mt-auto pt-3">
  <a href="commander.html?menu=${menu.id}" class="btn btn-primary w-100">Commander</a>
</div>
  </div>
  </div>
  </div>
  `;
});

  $conteneur.html(html);
}

  $(document).ready(function () {
afficherDetailMenus();
});
  })();