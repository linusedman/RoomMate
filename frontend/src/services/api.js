
import axios from 'axios'
const api = axios.create({
  baseURL: '/api',    // /api/... proxas via vite.config.js
  withCredentials: true
})
export default api
