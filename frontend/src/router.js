import { createRouter, createWebHistory } from 'vue-router';
import BaseLayout from './layouts/BaseLayout.vue';
import LoginView from './views/LoginView.vue';
import RegisterView from './views/RegisterView.vue';
import ResetPasswordView from './views/ResetPasswordView.vue';
import ScheduleView from './views/ScheduleView.vue';
import LogoutView from './views/LogoutView.vue';

const routes = [
  {
    path: '/',
    component: BaseLayout,
    children: [
      { path: '', redirect: '/login' },
      { path: 'login', component: LoginView },
      { path: 'register', component: RegisterView },
      { path: 'resetpassword', component: ResetPasswordView },
      { path: 'schedule', component: ScheduleView },
      { path: 'logout', component: LogoutView },
    ],
  },
  { path: '/:pathMatch(.*)*', redirect: '/login' },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
