import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { guest: true }
    },
    {
      path: '/',
      redirect: '/login'
    },

    {
      path: '/dashboard/manager',
      name: 'dashboard-manager',
      component: () => import('@/views/DashboardManajer.vue'),
      meta: { requiresAuth: true, role: 'Manajer' }
    },
    {
      path: '/dashboard/admin',
      name: 'dashboard-admin',
      component: () => import('@/views/DashboardAdmin.vue'),
      meta: { requiresAuth: true, role: 'Admin' }
    },
    {
      path: '/dashboard/staff',
      name: 'dashboard-staff',
      component: () => import('@/views/DashboardPetugas.vue'),
      meta: { requiresAuth: true, role: 'Petugas' }
    },

    {
      path: '/staff/inventory-in',
      name: 'staff-inventory-in',
      component: () => import('@/views/BarangMasuk.vue'),
      meta: { requiresAuth: true, role: 'Petugas' }
    },
    {
      path: '/staff/inventory-out',
      name: 'staff-inventory-out',
      component: () => import('@/views/BarangKeluar.vue'),
      meta: { requiresAuth: true, role: 'Petugas' }
    },
    {
      path: '/staff/products',
      name: 'staff-products',
      component: () => import('@/views/BarangPage.vue'),
      meta: { requiresAuth: true, role: 'Petugas' }
    },
    {
      path: '/admin/products',
      name: 'admin-products',
      component: () => import('@/views/BarangPage.vue'),
      meta: { requiresAuth: true, role: 'Admin' }
    },
    {
      path: '/admin/suppliers',
      name: 'Admin-supplier',
      component: () => import('@/views/SupplierAdmin.vue'),
      meta: { requiresAuth: true, role: 'Admin' }
    },
    {
      path: '/admin/inventory-in',
      name: 'admin-inventory-in',
      component: () => import('@/views/BarangMasuk.vue'),
      meta: { requiresAuth: true, role: 'Admin' }
    },
    {
      path: '/admin/inventory-out',
      name: 'admin-inventory-out',
      component: () => import('@/views/BarangKeluar.vue'),
      meta: { requiresAuth: true, role: 'Admin' }
    },
    {
      path: '/learning/login',
      name: 'learning-login',
      component: () => import('@/views/LoginBelajar.vue')
    },
    {
      path: '/learning/register',
      name: 'learning-register',
      component: () => import('@/views/RegisterView.vue')
    },
    {
      path: '/learning/products',
      name: 'learning-products',
      component: () => import('@/views/BarangCrud.vue')
    },

    {
      path: '/:pathMatch(.*)*',
      redirect: '/login'
    }
  ],
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isLoggedIn) {
    next('/login')
  } else if (to.meta.guest && authStore.isLoggedIn) {
    const role = authStore.userRole
    if (!role) {
      authStore.logout()
      next('/login')
    } else {
      const roleMap = { 'Manajer': 'manager', 'Admin': 'admin', 'Petugas': 'staff' };
      next(`/dashboard/${roleMap[role] || role.toLowerCase()}`)
    }
  } else {
    next()
  }
})

export default router
