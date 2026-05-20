/* ============================================
   KeyChain Studio — Main JS
   ============================================ */

// ---- Modal ----
function openModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.remove('open');
  document.body.style.overflow = '';
}

// ---- Design Card Selection ----
function initDesignCards() {
  const cards = document.querySelectorAll('.design-card[data-design-id]');
  const hiddenInput = document.getElementById('selectedDesignId');
  const priceInput  = document.getElementById('selectedDesignPrice');
  cards.forEach(card => {
    card.addEventListener('click', () => {
      cards.forEach(c => c.classList.remove('selected'));
      card.classList.add('selected');
      if (hiddenInput) hiddenInput.value = card.dataset.designId;
      if (priceInput) {
        priceInput.value = card.dataset.price;
        updatePriceCalc();
      }
    });
  });
}

// ---- Quantity Stepper ----
function initQtyStepper() {
  document.querySelectorAll('.qty-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = document.getElementById(btn.dataset.target);
      if (!target) return;
      let val = parseInt(target.value) || 1;
      if (btn.dataset.dir === 'up') val++;
      if (btn.dataset.dir === 'down') val = Math.max(1, val - 1);
      target.value = val;
      updatePriceCalc();
    });
  });
  const qtyInput = document.getElementById('quantity');
  if (qtyInput) qtyInput.addEventListener('input', updatePriceCalc);
}

// ---- Price Calculator ----
function updatePriceCalc() {
  const qtyEl   = document.getElementById('quantity');
  const priceEl = document.getElementById('selectedDesignPrice');
  const totalEl = document.getElementById('calcTotal');
  const unitEl  = document.getElementById('calcUnit');
  const qtyDisp = document.getElementById('calcQty');
  if (!qtyEl || !priceEl || !totalEl) return;
  const qty   = parseInt(qtyEl.value) || 1;
  const price = parseFloat(priceEl.value) || 0;
  const total = qty * price;
  if (unitEl)  unitEl.textContent  = '₱' + price.toFixed(2);
  if (qtyDisp) qtyDisp.textContent = qty;
  totalEl.textContent = '₱' + total.toFixed(2);
  const hiddenTotal = document.getElementById('totalPrice');
  if (hiddenTotal) hiddenTotal.value = total.toFixed(2);
}

// ---- Upload Preview ----
function initUploadPreview() {
  const input   = document.getElementById('photoUpload');
  const preview = document.getElementById('uploadPreview');
  const previewImg = document.getElementById('uploadPreviewImg');
  if (!input || !preview || !previewImg) return;
  input.addEventListener('change', () => {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
      previewImg.src = e.target.result;
      preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
  });
  // Drag-drop
  const zone = document.querySelector('.upload-zone');
  if (!zone) return;
  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
  zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('dragover');
    const dt = e.dataTransfer;
    if (dt.files.length) {
      input.files = dt.files;
      input.dispatchEvent(new Event('change'));
    }
  });
}

// ---- Flash Auto-Dismiss ----
function initFlashMessages() {
  document.querySelectorAll('.flash').forEach(flash => {
    setTimeout(() => {
      flash.style.transition = 'opacity 0.5s, transform 0.5s';
      flash.style.opacity = '0';
      flash.style.transform = 'translateY(-8px)';
      setTimeout(() => flash.remove(), 500);
    }, 4000);
  });
}

// ---- Table Search Filter ----
function initTableSearch() {
  const search = document.getElementById('tableSearch');
  const table  = document.getElementById('dataTable');
  if (!search || !table) return;
  search.addEventListener('input', () => {
    const q = search.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

// ---- Sidebar Toggle (mobile) ----
function initSidebar() {
  const sidebarToggle  = document.getElementById('sidebarToggle');
  const sidebar        = document.getElementById('sidebar');
  const sidebarOverlay = document.getElementById('sidebarOverlay');
  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      if (sidebarOverlay) sidebarOverlay.classList.toggle('open');
    });
  }
  if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', () => {
      sidebar.classList.remove('open');
      sidebarOverlay.classList.remove('open');
    });
  }
}

// ---- Mobile Nav ----
function initMobileNav() {
  const mobileToggle = document.getElementById('mobileNavToggle');
  const navLinks     = document.getElementById('navLinks');
  if (mobileToggle && navLinks) {
    mobileToggle.addEventListener('click', () => navLinks.classList.toggle('open'));
  }
}

// ---- Modal Close Buttons ----
function initModalClose() {
  document.querySelectorAll('[data-modal-close]').forEach(btn => {
    btn.addEventListener('click', () => closeModal(btn.dataset.modalClose));
  });
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
      if (e.target === overlay) {
        overlay.classList.remove('open');
        document.body.style.overflow = '';
      }
    });
  });
}

// ---- Confirm Delete ----
function initConfirm() {
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.dataset.confirm || 'Are you sure?')) e.preventDefault();
    });
  });
}

// ---- Animate Stats on Load ----
function animateStats() {
  document.querySelectorAll('.stat-value[data-target]').forEach(el => {
    const target  = parseFloat(el.dataset.target) || 0;
    const isFloat = el.dataset.float === 'true';
    let start = 0;
    const step = target / 40;
    const timer = setInterval(() => {
      start = Math.min(start + step, target);
      el.textContent = isFloat ? '₱' + start.toFixed(2) : Math.round(start).toString();
      if (start >= target) clearInterval(timer);
    }, 20);
  });
}

// ---- Init ----
document.addEventListener('DOMContentLoaded', () => {
  initSidebar();
  initMobileNav();
  initModalClose();
  initConfirm();
  initDesignCards();
  initQtyStepper();
  updatePriceCalc();
  initUploadPreview();
  initFlashMessages();
  initTableSearch();
  animateStats();
});
