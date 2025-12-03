// Frontend site bridge: expose legacy functions globally and optionally use services
import * as roomsSvc from '../js/services/roomsService.js';
import * as usersSvc from '../js/services/usersService.js';
import * as reservationsSvc from '../js/services/reservationsService.js';

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

function searchRooms() {
  const checkin = document.getElementById('checkin')?.value;
  const checkout = document.getElementById('checkout')?.value;
  if (!checkin || !checkout) {
    alert('Por favor selecciona las fechas de entrada y salida');
    return;
  }
  if (new Date(checkin) >= new Date(checkout)) {
    alert('La fecha de salida debe ser posterior a la fecha de entrada');
    return;
  }

  // If backend rooms service is available, we can call it (non-blocking)
  try {
    // Example: roomsSvc.listRooms could accept date filters if backend supports them
    if (roomsSvc && typeof roomsSvc.listRooms === 'function') {
      roomsSvc.listRooms({ from: new Date(checkin).toISOString(), to: new Date(checkout).toISOString() })
        .then(resp => {
          console.info('roomsSvc.listRooms', resp);
          alert('Se obtuvo ' + (resp?.data?.length ?? '0') + ' habitaciones (ver consola).');
        }).catch(err => {
          console.debug('roomsSvc.listRooms error', err);
          // fallback UX: original behaviour
          alert('Buscando habitaciones disponibles...\nCheck-in: ' + checkin + '\nCheck-out: ' + checkout);
        });
      return;
    }
  } catch (e) {
    console.debug('searchRooms service error', e);
  }

  alert('Buscando habitaciones disponibles...\nCheck-in: ' + checkin + '\nCheck-out: ' + checkout);
}

function reserveRoom(tipo, precio) {
  const checkin = document.getElementById('checkin')?.value;
  const checkout = document.getElementById('checkout')?.value;
  if (!checkin || !checkout) {
    alert('Por favor selecciona tus fechas de viaje antes de continuar');
    return;
  }
  const days = Math.ceil((new Date(checkout) - new Date(checkin)) / (1000 * 60 * 60 * 24));
  const total = precio * days;

  // keep old UX but offer optional quick-create for developers
  const proceed = confirm(`Habitación: ${tipo.toUpperCase()}\nPrecio por noche: $${precio} MXN\nNoches: ${days}\nTotal: $${total} MXN\n\n¿Quieres intentar crear la reserva automáticamente?`);
  if (!proceed) {
    alert('Para continuar con la reserva, inicia sesión o regístrate.');
    return;
  }

  // ask for a user id (simple prompt, non-intrusive). If user cancels or input invalid, abort.
  const uid = prompt('Ingresa tu user_id (número) para demo de creación automática (o cancela):');
  const userId = uid ? Number(uid) : NaN;
  if (!userId || Number.isNaN(userId) || userId <= 0) {
    alert('ID de usuario inválido o cancelado. La reserva no fue creada.');
    return;
  }

  // try to create reservation via service
  if (reservationsSvc && typeof reservationsSvc.createReservation === 'function') {
    reservationsSvc.createReservation({
      user_id: userId,
      room_id: 1, // best-effort: UI does not assign numeric ids; developer can adjust
      check_in: new Date(checkin).toISOString(),
      check_out: new Date(checkout).toISOString()
    }).then(res => {
      alert('Reserva creada: #' + res.id + '\nVer consola para respuesta completa.');
      console.info('createReservation response', res);
    }).catch(err => {
      console.error('createReservation error', err);
      alert('Error al crear la reserva: ' + (err.message || JSON.stringify(err)));
    });
    return;
  }

  alert('Para continuar con la reserva, inicia sesión o regístrate.');
}

function goToLogin() { alert('Redirigiendo al inicio de sesión...'); }
function goToRegister() { alert('Redirigiendo al registro...'); }

function setupFilters() {
  const filterButtons = document.querySelectorAll('.filter-tabs button');
  const roomCards = document.querySelectorAll('.room-card');
  if (!filterButtons.length || !roomCards.length) return;
  filterButtons.forEach(button => {
    button.addEventListener('click', function() {
      filterButtons.forEach(btn => btn.classList.remove('active'));
      this.classList.add('active');
      const filter = this.getAttribute('data-filter');
      roomCards.forEach(card => {
        if (filter === 'all' || card.getAttribute('data-type') === filter) {
          card.classList.add('show');
        } else {
          card.classList.remove('show');
        }
      });
    });
  });
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
  setupFilters();
  setupFAQ();
  setupSmoothScroll();
});

export default {};
