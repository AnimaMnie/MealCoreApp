import { createRouter, createWebHistory } from 'vue-router'
import { auth } from './auth'

import LoginView from './views/LoginView.vue'
import RegisterView from './views/RegisterView.vue'
import DashboardView from './views/DashboardView.vue'
import MealLogView from './views/MealLogView.vue'
import ShoppingListView from './views/ShoppingListView.vue'
import GroupsView from './views/GroupsView.vue'
import ProductsView from './views/ProductsView.vue'
import UsersView from './views/UsersView.vue'
import NotFoundView from './views/NotFoundView.vue'

const routes = [
  { path: '/login', component: LoginView, meta: { guest: true } },
  { path: '/register', component: RegisterView, meta: { guest: true } },
  { path: '/', redirect: '/dashboard' },
  { path: '/dashboard', component: DashboardView, meta: { title: 'Pulpit' } },
  { path: '/meal-log', component: MealLogView, meta: { title: 'Dziennik' } },
  { path: '/shopping-list', component: ShoppingListView, meta: { title: 'Lista zakupów' } },
  { path: '/groups', component: GroupsView, meta: { title: 'Grupy' } },
  { path: '/products', component: ProductsView, meta: { title: 'Produkty' } },
  { path: '/users', component: UsersView, meta: { title: 'Użytkownicy', admin: true } },
  { path: '/:pathMatch(.*)*', component: NotFoundView, meta: { title: 'Nie znaleziono' } },
]

const router = createRouter({ history: createWebHistory(), routes })

// Ochrona tras: niezalogowany -> /login, zwykły user na stronie admina -> pulpit.
router.beforeEach((to) => {
  if (!to.meta.guest && !auth.isLoggedIn) return '/login'
  if (to.meta.guest && auth.isLoggedIn) return '/dashboard'
  if (to.meta.admin && !auth.isAdmin) return '/dashboard'
})

router.afterEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} · MealCore` : 'MealCore'
})

export default router
