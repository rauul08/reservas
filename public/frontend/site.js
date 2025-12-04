// Frontend site bridge: expose legacy functions globally and optionally use services
import * as roomsSvc from '../js/services/roomsService.js';
import * as usersSvc from '../js/services/usersService.js';
import * as reservationsSvc from '../js/services/reservationsService.js';
import * as authSvc from '../js/services/authService.js';

// Derive an API base path when not provided by the page.
// Example: if the page is served at /reservademo/public/frontend/..., base becomes /reservademo/public
if (!window.API_BASE) {
  try {
    const m = location.pathname.match(/^(.*)\/frontend(\/|$)/);
    window.API_BASE = m ? m[1] : '';
  } catch (e) {
    window.API_BASE = '';
  }
}
// Debug: print detected API base for troubleshooting
try { console.debug('Detected API_BASE =', window.API_BASE); } catch(e) {}

function renderAuthUI() {
  const authButtonsEl = document.getElementById('authButtons');
  if (!authButtonsEl) return;

  if (authSvc.isAuthenticated()) {
    const user = authSvc.getUser();
    console.log('Rendering auth UI for user:', user); // Debug
    
    // Ensure we have user data
    if (!user) {
      console.error('User is authenticated but no user data found');
      authSvc.clearAuth();
      location.reload();
      return;
    }
    
    const displayName = user.name || user.email || 'Usuario';
    const userPicture = user.picture || user.picture_url || '';
    
    authButtonsEl.innerHTML = `
      <div style="display: flex; align-items: center; gap: 1rem;">
        ${userPicture ? `<img src="${userPicture}" alt="${displayName}" style="width: 32px; height: 32px; border-radius: 50%;" onerror="this.style.display='none'" />` : ''}
        <span style="color: black; font-weight: 500;">${displayName}</span>
        <button class="btn-login" onclick="logout()">Cerrar Sesión</button>
      </div>
    `;
  } else {
    authButtonsEl.innerHTML = `
      <button class="btn-login" onclick="loginWithGoogle()">
        <svg style="width: 18px; height: 18px; margin-right: 8px; display: inline-block; vertical-align: middle;" viewBox="0 0 24 24">
          <path fill="currentColor" d="M21.35,11.1H12.18V13.83H18.69C18.36,17.64 15.19,19.27 12.19,19.27C8.36,19.27 5,16.25 5,12C5,7.9 8.2,4.73 12.2,4.73C15.29,4.73 17.1,6.7 17.1,6.7L19,4.72C19,4.72 16.56,2 12.1,2C6.42,2 2.03,6.8 2.03,12C2.03,17.05 6.16,22 12.25,22C17.6,22 21.5,18.33 21.5,12.91C21.5,11.76 21.35,11.1 21.35,11.1V11.1Z" />
        </svg>
        Iniciar con Google
      </button>
    `;
  }
}

// Make functions globally available
window.loginWithGoogle = function() {
  authSvc.loginWithGoogle();
};

window.logout = function() {
  if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
    authSvc.logout();
  }
};

function setDefaultDates() {
  try {
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    const dayAfter = new Date(tomorrow);
    dayAfter.setDate(dayAfter.getDate() + 1);
    const checkinEl = document.getElementById('checkin');
    const checkoutEl = document.getElementById('checkout');
    if (checkinEl && checkoutEl) {
      checkinEl.valueAsDate = tomorrow;
      checkoutEl.valueAsDate = dayAfter;
    }
  } catch (e) {
    // non blocking
    console.debug('setDefaultDates error', e);
  }
}

async function populateDestinations() {
  try {
    // fetch rooms and derive distinct destinations
    const resp = await roomsSvc.listRooms({ per_page: 200 });
    const rooms = (resp && resp.data) ? resp.data : [];
    const set = new Set();
    rooms.forEach(r => {
      if (r.destination) set.add(r.destination);
    });
    const dests = Array.from(set);
    const sel = document.getElementById('destination');
    if (!sel) return;
    sel.innerHTML = '';
    const allOpt = document.createElement('option');
    allOpt.value = '';
    allOpt.textContent = 'Seleccione destino';
    sel.appendChild(allOpt);
    dests.forEach(d => {
      const o = document.createElement('option');
      o.value = d;
      o.textContent = d;
      sel.appendChild(o);
    });
  } catch (e) {
    console.debug('populateDestinations error', e);
  }
}

async function isRoomAvailable(roomId, fromIso, toIso) {
  try {
    const resp = await reservationsSvc.listReservations({ room_id: roomId, from: fromIso, to: toIso, per_page: 1 });
    // resp.meta.total indicates overlapping reservations
    const total = resp && resp.meta ? resp.meta.total : (resp && resp.data ? resp.data.length : 0);
    return (Number(total) === 0);
  } catch (e) {
    console.debug('isRoomAvailable error', e);
    // be conservative: treat as unavailable on error
    return false;
  }
}

let cachedCategories = [];
let originalRoomsHTML = null;

async function loadRooms(category = null) {
  try {
    // request available rooms from backend; when category is provided, pass it
    const params = { status: 'available', per_page: 100 };
    if (category && category !== 'all') params.category = category;
    const resp = await roomsSvc.listRooms(params);
    const rooms = (resp && resp.data) ? resp.data : [];

    // On the initial full load (no category filter) derive categories from returned rooms
    if (!category) {
      const set = new Set();
      rooms.forEach(r => {
        if (r.category) set.add(r.category);
        else if (r.subtype) set.add(r.subtype);
      });
      cachedCategories = Array.from(set).map(String);
      renderFilterTabs(cachedCategories, 'all');
    } else {
      // ensure filter UI marks the active tab
      renderFilterTabs(cachedCategories, category);
    }

    if (!rooms.length) {
      console.debug('loadRooms: no rooms returned from API');
      // hide all static cards if none returned
      const staticCards = Array.from(document.querySelectorAll('.rooms-list .room-card'));
      staticCards.forEach(c => c.classList.remove('show'));
      return;
    }

    // If we're loading the full list, restore original static markup first
    if (!category) {
      const container = document.querySelector('.rooms-list');
      if (container && originalRoomsHTML) {
        container.innerHTML = originalRoomsHTML;
      }
    }
    renderRooms(rooms);
  } catch (e) {
    console.debug('loadRooms error', e);
    // leave static markup in place as fallback
  }
}

function renderRooms(rooms) {
  const container = document.querySelector('.rooms-list');
  if (!container) return;

  // Find existing static cards so we preserve title, rating and amenities as requested
  const staticCards = Array.from(container.querySelectorAll('.room-card'));

  function formatRating(rating) {
    if (rating === null || rating === undefined || rating === '') return 'N/A';
    const num = Number(rating);
    if (Number.isNaN(num)) return String(rating);
    // create simple star visual (rounded to nearest integer)
    const rounded = Math.round(num);
    const full = Math.max(0, Math.min(5, rounded));
    const empty = 5 - full;
    const stars = '★'.repeat(full) + '☆'.repeat(empty);
    return `${stars} ${num}`;
  }

  // Update existing static cards with DB data where possible
  for (let i = 0; i < rooms.length; i++) {
    const r = rooms[i];
    const price = Number(r.price) || 0;
    const desc = r.description || '';
    const type = (r.subtype || r.category || 'all').toString().toLowerCase();
    const id = r.id || '';

    if (staticCards[i]) {
      const card = staticCards[i];
      card.setAttribute('data-type', type);
      card.setAttribute('data-id', id);

      // update description while preserving inner structure elsewhere
      const descEl = card.querySelector('.room-description');
      if (descEl) {
        descEl.textContent = desc;
      }

      // update price display (element with class 'price')
      const priceEl = card.querySelector('.price');
      if (priceEl) {
        priceEl.innerHTML = `$${price.toLocaleString()} <span>MXN</span>`;
      }

      // update title if API provides a more specific value (prefer explicit title)
      const titleEl = card.querySelector('.room-title h3');
      const apiTitle = r.title || r.name || r.subtype || r.category || null;
      if (titleEl && apiTitle) {
        // capitalize first letter when falling back to subtype/category
        const out = (r.title ? String(apiTitle) : String(apiTitle)).toString();
        titleEl.textContent = out.charAt(0).toUpperCase() + out.slice(1);
      }

      // update rating if provided by API (keep static if not)
      const ratingEl = card.querySelector('.room-rating');
      if (ratingEl) {
        if (r.rating || r.rating === 0) {
          ratingEl.innerHTML = formatRating(r.rating);
        }
      }

      // update amenities if API returns them (comma-separated or array)
      const amenitiesEl = card.querySelector('.room-amenities');
      if (amenitiesEl) {
        let items = [];
        if (r.amenities) {
          if (Array.isArray(r.amenities)) items = r.amenities;
          else if (typeof r.amenities === 'string') items = r.amenities.split(',').map(s => s.trim()).filter(Boolean);
        }
        if (items.length) {
          amenitiesEl.innerHTML = items.map(it => `<span class="amenity">${it}</span>`).join(' ');
        }
      }

      // update image if API provides an image_url, otherwise try a local fallback by type
      const imgEl = card.querySelector('.room-image');
      if (imgEl) {
        const makeResolved = (url) => {
          if (!url) return null;
          // if url is absolute path starting with '/', prefix API_BASE
          if (String(url).startsWith('/')) {
            const base = (window.API_BASE || '').replace(/\/$/, '');
            return base + url;
          }
          return url;
        };
        const resolved = makeResolved(r.image_url) || (window.API_BASE.replace(/\/$/, '') + '/images/rooms/' + encodeURIComponent(type) + '.jpg');
        imgEl.src = resolved;
      }

      // update reserve button to pass room_id, subtype/category and current price
      const btn = card.querySelector('.btn-reserve');
      if (btn) {
        // prefer subtype for the visible type; fallback to category
        const subtypeSafe = String(type).replace(/'/g, "\\'");
        btn.setAttribute('onclick', `reserveRoom(${id}, '${subtypeSafe}', ${price})`);
      }

      // keep title, room-rating and room-amenities as they are in the static HTML
    } else {
      // create a new card when API returns more rooms than static markup
      const div = document.createElement('div');
      div.className = 'room-card show';
      div.setAttribute('data-type', type);
      div.setAttribute('data-id', id);
        // prefer explicit room title; fallback to subtype/category with capitalization
        const titleText = (r.title || r.subtype || r.category || 'Habitación').toString();
        const safeTitle = titleText.replace(/</g, '&lt;').replace(/>/g, '&gt;');
        const safeDesc = desc.replace(/</g, '&lt;').replace(/>/g, '&gt;');
        const dynamicImg = r.image_url ? (String(r.image_url).startsWith('/') ? (window.API_BASE.replace(/\/$/, '') + r.image_url) : r.image_url) : ('https://source.unsplash.com/featured/?hotel,room,' + encodeURIComponent(type));
        // build amenities HTML
        let amenHTML = '';
        if (r.amenities) {
          let items = [];
          if (Array.isArray(r.amenities)) items = r.amenities;
          else if (typeof r.amenities === 'string') items = r.amenities.split(',').map(s => s.trim()).filter(Boolean);
          amenHTML = items.map(it => `<span class="amenity">${it}</span>`).join(' ');
        } else {
          amenHTML = `<span class="amenity">📶 WiFi Gratis</span> <span class="amenity">🚿 Baño Privado</span> <span class="amenity">📺 TV</span>`;
        }

        const ratingHTML = (r.rating || r.rating === 0) ? `<div class="room-rating">${formatRating(r.rating)}</div>` : `<div class="room-rating">N/A</div>`;

        div.innerHTML = `
          <div class="room-image-container">
            <img src="${dynamicImg}" alt="${safeTitle}" class="room-image">
            
          </div>
          <div class="room-details">
            <div>
              <div class="room-header">
                <div class="room-title">
                  <h3>${safeTitle.charAt(0).toUpperCase() + safeTitle.slice(1)}</h3>
                  ${ratingHTML}
                </div>
              </div>
              <p class="room-description">${safeDesc}</p>
              <div class="room-amenities">
                ${amenHTML}
              </div>
            </div>
            <div class="room-footer">
              <div class="price-info">
                <span class="price-label">Precio por noche</span>
                <div class="price">$${price.toLocaleString()} <span>MXN</span></div>
              </div>
              <button class="btn-reserve" onclick="reserveRoom(${id}, '${type}', ${price})">Ver Detalles</button>
            </div>
          </div>
        `;
      container.appendChild(div);
    }
  }

  // Hide extra static cards if API returned fewer rooms than static markup
  if (staticCards.length > rooms.length) {
    for (let j = rooms.length; j < staticCards.length; j++) {
      staticCards[j].classList.remove('show');
    }
  }
}

function renderFilterTabs(categories, activeCategory) {
  const tabsContainer = document.querySelector('.filter-tabs');
  if (!tabsContainer) return;
  // Always show 'Todas' first
  tabsContainer.innerHTML = '';
  const allBtn = document.createElement('button');
  allBtn.className = (activeCategory === 'all' || !activeCategory) ? 'active' : '';
  allBtn.setAttribute('data-filter', 'all');
  allBtn.textContent = 'Todas';
  allBtn.addEventListener('click', () => {
    // load all rooms
    clearActiveFilter(tabsContainer);
    allBtn.classList.add('active');
    loadRooms(null);
  });
  tabsContainer.appendChild(allBtn);

  categories.forEach(cat => {
    const btn = document.createElement('button');
    const isActive = (String(cat) === String(activeCategory));
    btn.className = isActive ? 'active' : '';
    btn.setAttribute('data-filter', cat);
    btn.textContent = cat.charAt(0).toUpperCase() + String(cat).slice(1);
    btn.addEventListener('click', () => {
      clearActiveFilter(tabsContainer);
      btn.classList.add('active');
      loadRooms(cat);
    });
    tabsContainer.appendChild(btn);
  });
}

function clearActiveFilter(container) {
  Array.from(container.querySelectorAll('button')).forEach(b => b.classList.remove('active'));
}

function searchRooms() {
  (async () => {
    const checkin = document.getElementById('checkin')?.value;
    const checkout = document.getElementById('checkout')?.value;
    const destination = document.getElementById('destination')?.value;
    const guests = Number(document.getElementById('guests')?.value || 1);

    if (!destination) {
      alert('Por favor selecciona un destino');
      return;
    }
    if (!checkin || !checkout) {
      alert('Por favor selecciona las fechas de entrada y salida');
      return;
    }
    if (new Date(checkin) >= new Date(checkout)) {
      alert('La fecha de salida debe ser posterior a la fecha de entrada');
      return;
    }

    try {
      // call server-side availability endpoint (handles capacity and overlapping reservations)
      const fromIso = checkin; // server expects YYYY-MM-DD
      const toIso = checkout;
      const resp = await roomsSvc.listAvailable({ destination: destination, from: fromIso, to: toIso, guests: guests, per_page: 200 });
      const available = (resp && resp.data) ? resp.data : [];

      if (!available.length) {
        alert('Lo siento, no hay habitaciones disponibles para las fechas seleccionadas en este destino');
        const container = document.querySelector('.rooms-list');
        if (container) container.innerHTML = '<p>No hay habitaciones disponibles para esas fechas.</p>';
        return;
      }

      // show available rooms
      const container = document.querySelector('.rooms-list');
      if (container) container.innerHTML = '';
      renderRooms(available);

    } catch (e) {
      console.error('searchRooms error', e);
      alert('Error al buscar habitaciones. Revisa la consola.');
    }
  })();
}

function reserveRoom(roomId, tipo, precio) {
  // Check if user is authenticated
  if (!authSvc.isAuthenticated()) {
    const shouldLogin = confirm('Debes iniciar sesión para hacer una reserva.\n\n¿Deseas iniciar sesión con Google ahora?');
    if (shouldLogin) {
      authSvc.loginWithGoogle();
    }
    return;
  }

  // Get authenticated user
  const user = authSvc.getUser();
  if (!user || !user.id) {
    alert('Error: No se pudo obtener la información del usuario. Por favor, vuelve a iniciar sesión.');
    authSvc.clearAuth();
    return;
  }

  // Validate dates
  const checkin = document.getElementById('checkin')?.value;
  const checkout = document.getElementById('checkout')?.value;
  if (!checkin || !checkout) {
    alert('Por favor selecciona tus fechas de viaje antes de continuar');
    return;
  }

  const days = Math.ceil((new Date(checkout) - new Date(checkin)) / (1000 * 60 * 60 * 24));
  const total = precio * days;

  // Confirm reservation details
  const proceed = confirm(
    `Habitación: ${tipo.toUpperCase()}\n` +
    `Precio por noche: $${precio.toLocaleString()} MXN\n` +
    `Noches: ${days}\n` +
    `Total: $${total.toLocaleString()} MXN\n\n` +
    `Usuario: ${user.name || user.email}\n\n` +
    `¿Confirmar reserva?`
  );
  
  if (!proceed) return;

  // Create reservation with authenticated user and selected room
  if (reservationsSvc && typeof reservationsSvc.createReservation === 'function') {
    reservationsSvc.createReservation({
      user_id: user.id,
      room_id: Number(roomId),
      check_in: new Date(checkin).toISOString(),
      check_out: new Date(checkout).toISOString()
    }).then(res => {
      alert(
        `¡Reserva creada exitosamente! 🎉\n\n` +
        `Número de reserva: #${res.id}\n` +
        `Habitación: ${tipo}\n` +
        `Check-in: ${checkin}\n` +
        `Check-out: ${checkout}\n` +
        `Total: $${total.toLocaleString()} MXN`
      );
      console.info('Reservation created:', res);
    }).catch(err => {
      console.error('createReservation error:', err);
      alert('Error al crear la reserva: ' + (err.message || 'Error desconocido. Ver consola para más detalles.'));
    });
  } else {
    alert('Error: El servicio de reservas no está disponible.');
  }
}

// Initialize UI on DOM ready: set defaults, populate destinations, capture original static HTML and load rooms
document.addEventListener('DOMContentLoaded', async () => {
  try {
    // Render auth UI first
    renderAuthUI();
    
    setDefaultDates();

    // capture original static markup so we can restore it when needed
    const container = document.querySelector('.rooms-list');
    if (container) {
      originalRoomsHTML = container.innerHTML;
    }

    // populate destinations select and initial rooms
    await populateDestinations();
    await loadRooms(null);
  } catch (e) {
    console.debug('init error', e);
  }
});

function goToLogin() { alert('Redirigiendo al inicio de sesión...'); }
function goToRegister() { alert('Redirigiendo al registro...'); }

function setupFilters() {
  // filter tabs are rendered dynamically via renderFilterTabs()
  // this function kept for compatibility; actual listeners are attached when tabs are created
}

function toggleFAQ(button) {
  const answer = button.nextElementSibling;
  const isActive = button.classList.contains('active');
  document.querySelectorAll('.faq-question').forEach(q => q.classList.remove('active'));
  document.querySelectorAll('.faq-answer').forEach(a => a.classList.remove('active'));
  if (!isActive) {
    button.classList.add('active');
    answer.classList.add('active');
  }
}

function openChat() { alert('Abriendo chat en vivo...\n\n¡Un agente se conectará contigo en breve!'); }

function setupFAQ() {
  document.querySelectorAll('.faq-question').forEach(btn => {
    btn.addEventListener('click', () => toggleFAQ(btn));
  });
}

function setupSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });
}

// expose legacy globals so inline attributes keep working
window.searchRooms = searchRooms;
window.reserveRoom = reserveRoom;
window.goToLogin = goToLogin;
window.goToRegister = goToRegister;
window.toggleFAQ = function(btn){ toggleFAQ(btn); };
window.openChat = openChat;

// initialize behaviours
document.addEventListener('DOMContentLoaded', () => {
  setDefaultDates();
  // attempt to load live rooms from API; falls back to static HTML
  // capture original static HTML so we can restore it when switching back to 'all'
  const container = document.querySelector('.rooms-list');
  if (container) originalRoomsHTML = container.innerHTML;
  loadRooms();
  setupFilters();
  setupFAQ();
  setupSmoothScroll();
});

export default {};
