import { createRouter, createWebHistory } from 'vue-router';
import BaseLayout from './layouts/BaseLayout.vue';
import LoginView from './views/LoginView.vue';
import RegisterView from './views/RegisterView.vue';
import ResetPasswordView from './views/ResetPasswordView.vue';
import ScheduleView from './views/ScheduleView.vue';
import LogoutView from './views/LogoutView.vue';
import MainView from './views/MainView.vue';
import ResetPasswordFormView from './views/ResetPasswordFormView.vue';
import AdminView from "./views/AdminView.vue";
import SettingsView from './views/SettingsView.vue';
import ChangePasswordView from './views/ChangePasswordView.vue';
import axios from "axios";
import {ref} from "vue";

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
      { path: 'admin', component: AdminView, meta: { requiresAuth: true, role: 'admin' }},
      { path: '/reset-password', name: 'ResetPassword', component: ResetPasswordFormView },
      { path: 'settings', component: SettingsView, meta: { requiresAuth: true, role: 'user' }},
      { path: 'change-password', component: ChangePasswordView, meta: { requiresAuth: true, role: 'user' }}
    ],
  },
  { path: '/:pathMatch(.*)*', redirect: '/main' },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
export const loggedIn = ref(false);
export const admin = ref(false);

router.beforeEach(async (to, from, next) => {

  const res = await axios.get("http://localhost/RoomMate/backend/pages/check_login.php", { withCredentials: true })
  const { loggedIn: isLoggedIn, admin:isAdmin } = res.data
      loggedIn.value = isLoggedIn;
      admin.value=isAdmin
    // routes without auth are always allowed
  if (!to.meta.requiresAuth) {
    return next()
  }

  try {
    if (!loggedIn.value) {
      return next('/login')
    }

    // If route requires admin but user is not admin
    if (to.meta.role === 'admin' && admin.value !== true) {
      return next('/main')
    }

    // ✅ allowed
    next()
  } catch (err) {
    console.error('Session check failed', err)
    next('/login')
  }
})
