import { request } from './apiClient.js';

export async function listUsers(params = {}) {
    return await request('/users', { method: 'GET', params });
}

export async function getUser(id) {
    return await request(`/users/${id}`, { method: 'GET' });
}

export default { listUsers, getUser };
