import { listReservations, createReservation, cancelReservation } from '../js/services/reservationsService.js';

const els = {
  list: document.getElementById('reservationsList'),
  meta: document.getElementById('listMeta'),
  pageInfo: document.getElementById('pageInfo'),
  prev: document.getElementById('prevPage'),
  next: document.getElementById('nextPage'),
  btnApply: document.getElementById('btnApply'),
  filterUser: document.getElementById('filterUser'),
  filterRoom: document.getElementById('filterRoom'),
  filterStatus: document.getElementById('filterStatus'),
  filterFrom: document.getElementById('filterFrom'),
  filterTo: document.getElementById('filterTo'),
  createForm: document.getElementById('createForm'),
  createResult: document.getElementById('createResult')
};

let page = 1;
const perPage = 10;

async function load() {
  const criteria = {};
  const user = parseInt(els.filterUser.value || 0, 10);
  const room = parseInt(els.filterRoom.value || 0, 10);
  if (user > 0) criteria.user_id = user;
  if (room > 0) criteria.room_id = room;
  if (els.filterStatus.value) criteria.status = els.filterStatus.value;
  if (els.filterFrom.value) criteria.from = new Date(els.filterFrom.value).toISOString();
  if (els.filterTo.value) criteria.to = new Date(els.filterTo.value).toISOString();

  const resp = await listReservations({ page, per_page: perPage, ...criteria });
  renderList(resp.data || [], resp.meta || {});
}

function renderList(items, meta) {
  els.list.innerHTML = '';
  if (!items.length) {
    els.list.innerHTML = '<li>(sin resultados)</li>';
  }
  items.forEach(r => {
    const li = document.createElement('li');
    li.innerHTML = `<strong>#${r.id}</strong> — ${r.user?.full_name || 'Usuario '+r.user_id} — Hab:${r.room?.number||r.room_id} — ${r.check_in} → ${r.check_out} — $${r.total_price} — <em>${r.status}</em>`;
    const btnDetails = document.createElement('button');
    btnDetails.textContent = 'Detalles';
    btnDetails.addEventListener('click', ()=>alert(JSON.stringify(r, null, 2)));
    li.appendChild(btnDetails);

    if (r.status !== 'cancelled'){
      const btnCancel = document.createElement('button');
      btnCancel.textContent = 'Cancelar';
      btnCancel.style.marginLeft = '8px';
      btnCancel.addEventListener('click', async ()=>{
        if (!confirm('Cancelar reserva #'+r.id+'?')) return;
        try{
          const res = await cancelReservation(r.id);
          alert('Cancelada: ' + JSON.stringify(res));
          load();
        }catch(err){
          alert('Error: ' + (err.message||JSON.stringify(err)));
        }
      });
      li.appendChild(btnCancel);
    }

    els.list.appendChild(li);
  });

  els.meta.textContent = `Mostrando ${items.length} resultados`;
  els.pageInfo.textContent = String(meta.current_page || page);
}

els.prev.addEventListener('click', ()=>{ if (page>1){ page--; load(); }});
els.next.addEventListener('click', ()=>{ page++; load(); });
els.btnApply.addEventListener('click', ()=>{ page=1; load(); });

els.createForm.addEventListener('submit', async (ev)=>{
  ev.preventDefault();
  const form = new FormData(els.createForm);
  const body = {
    user_id: Number(form.get('user_id')),
    room_id: Number(form.get('room_id')),
    check_in: new Date(form.get('check_in')).toISOString(),
    check_out: new Date(form.get('check_out')).toISOString()
  };
  els.createResult.textContent = 'Creando...';
  try{
    const res = await createReservation(body);
    els.createResult.textContent = 'Creada: #' + res.id;
    els.createForm.reset();
    load();
  }catch(err){
    els.createResult.textContent = 'Error: ' + (err.message || JSON.stringify(err));
  }
});

// initial load
load().catch(e=>console.error(e));
