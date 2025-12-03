import { request } from './apiClient.js';

export async function listReservations(options = {}) {
    // options: { page, per_page, user_id, room_id, status, from, to }
    return await request('/reservations', { method: 'GET', params: options });
}

export async function getReservation(id) {
    return await request(`/reservations/${id}`, { method: 'GET' });
}

export async function createReservation(payload) {
    return await request('/reservations', { method: 'POST', body: payload });
}

export async function updateReservation(id, payload) {
    return await request(`/reservations/${id}`, { method: 'PUT', body: payload });
}

export async function cancelReservation(id) {
    return await request(`/reservations/${id}`, { method: 'DELETE' });
}

export default {
    listReservations,
    getReservation,
    createReservation,
    updateReservation,
    cancelReservation,
};
