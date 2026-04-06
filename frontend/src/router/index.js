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

    // ===== Dashboard per-role =====
    {
      path: '/dashboard/Manajer',
      name: 'dashboard-Manajer',
      component: () => import('@/views/DashboardManajer.vue'),
      meta: { requiresAuth: true, role: 'Manajer' }
    },
    {
      path: '/dashboard/Admin',
      name: 'dashboard-Admin',
      component: () => import('@/views/DashboardAdmin.vue'),
      meta: { requiresAuth: true, role: 'Admin' }
    },
    {
      path: '/dashboard/Petugas',
      name: 'dashboard-Petugas',
      component: () => import('@/views/DashboardPetugas.vue'),
      meta: { requiresAuth: true, role: 'Petugas' }
    },

    // ===== Shared views (Admin + Petugas pakai component yang sama) =====
    {
      path: '/Petugas/barang-masuk',
      name: 'Petugas-barang-masuk',
      component: () => import('@/views/BarangMasuk.vue'),
      meta: { requiresAuth: true, role: 'Petugas' }
    },
    {
      path: '/Petugas/barang-keluar',
      name: 'Petugas-barang-keluar',
      component: () => import('@/views/BarangKeluar.vue'),
      meta: { requiresAuth: true, role: 'Petugas' }
    },
    {
      path: '/Petugas/barang',
      name: 'Petugas-barang',
      component: () => import('@/views/BarangPage.vue'),
      meta: { requiresAuth: true, role: 'Petugas' }
    },
    {
      path: '/Admin/barangs',
      name: 'Admin-barang',
      component: () => import('@/views/BarangPage.vue'),
      meta: { requiresAuth: true, role: 'Admin' }
    },
    {
      path: '/Admin/suppliers',
      name: 'Admin-supplier',
      component: () => import('@/views/SupplierAdmin.vue'),
      meta: { requiresAuth: true, role: 'Admin' }
    },
    {
      path: '/Admin/barang-masuk',
      name: 'Admin-barang-masuk',
      component: () => import('@/views/BarangMasuk.vue'),
      meta: { requiresAuth: true, role: 'Admin' }
    },
    {
      path: '/Admin/barang-keluar',
      name: 'Admin-barang-keluar',
      component: () => import('@/views/BarangKeluar.vue'),
      meta: { requiresAuth: true, role: 'Admin' }
    },
    // Fallback Catch-all route (404)
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
      next(`/dashboard/${role}`)
    }
  } else {
    next()
  }
})

export default router
