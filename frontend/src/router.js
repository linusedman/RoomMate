import { createRouter, createWebHistory } from 'vue-router';
import BaseLayout from './layouts/BaseLayout.vue';
import LoginView from './views/LoginView.vue';
import RegisterView from './views/RegisterView.vue';
import ResetPasswordView from './views/ResetPasswordView.vue';
import ScheduleView from './views/ScheduleView.vue';
import LogoutView from './views/LogoutView.vue';
import MainView from './views/MainView.vue';
import axios from "axios";

const routes = [
  {
    path: '/',
    component: BaseLayout,
    children: [
      { path: '', redirect: '/login' },
      { path: 'login', component: LoginView },
      { path: 'register', component: RegisterView },
      { path: 'resetpassword', component: ResetPasswordView },
      { path: 'schedule', component: ScheduleView, meta: { requiresAuth: true, role: 'user' } },
      { path: 'logout', component: LogoutView },
      { path: 'main', component: MainView, meta: { requiresAuth: true, role: 'user' }},
    ],
  },
  { path: '/:pathMatch(.*)*', redirect: '/login' },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;

router.beforeEach(async (to, from, next) => {
  // routes without auth are always allowed
  if (!to.meta.requiresAuth) {
    return next()
  }

  try {
    const res = await axios.get("http://localhost/RoomMate/backend/pages/check_login.php", { withCredentials: true })
    const { loggedIn, admin } = res.data

    if (!loggedIn) {
      return next('/login')
    }

    // If route requires admin but user is not admin
    if (to.meta.role === 'admin' && admin !== 'True') {
      return next('/main')
    }

    // ✅ allowed
    next()
  } catch (err) {
    console.error('Session check failed', err)
    next('/login')
  }
})
