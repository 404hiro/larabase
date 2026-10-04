import './styles.css';

import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
import Login from './pages/Login.vue';
import Message from './pages/Message.vue';
import Profile from './pages/Profile.vue';
import Welcome from './pages/Welcome.vue';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', component: Welcome },
    { path: '/login', component: Login },
    { path: '/@:slug/message', component: Message, props: true },
    { path: '/@:slug', component: Profile, props: true },
  ],
});

createApp(App).use(router).mount('#app');
