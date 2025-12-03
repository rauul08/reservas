import { request } from './apiClient.js';

export async function listRooms(params = {}) {
    return await request('/rooms', { method: 'GET', params });
}

export async function getRoom(id) {
    return await request(`/rooms/${id}`, { method: 'GET' });
}

export default { listRooms, getRoom };
