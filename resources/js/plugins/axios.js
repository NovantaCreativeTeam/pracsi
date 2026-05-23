import axios from 'axios'

const instance = axios.create({
    baseURL: '/api',
    withCredentials: true,
    headers: {
        'Accept': 'application/json'
    }
})

instance.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

export default instance
