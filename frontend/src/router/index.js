import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import DashboardManajer from '@/views/DashboardManajer.vue'
import DashboardAdmin from '@/views/DashboardAdmin.vue'
import DashboardPetugas from '@/views/DashboardPetugas.vue'
import BarangMasuk from '@/views/BarangMasuk.vue'
import BarangKeluar from '@/views/BarangKeluar.vue'
import AdminBarang from '@/views/AdminBarang.vue'
import SupplierAdmin from '@/views/SupplierAdmin.vue'
import AdminBarangMasuk from '@/views/AdminBarangMasuk.vue'
import AdminBarangKeluar from '@/views/AdminBarangKeluar.vue'
import BarangPetugas from '@/views/barangPetugas.vue'

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
      path: '/dashboard/Manajer',
      name: 'dashboard-Manajer',
      component: () => DashboardManajer,
      meta: { requiresAuth: true, role: 'Manajer' }
    },
    {
      path: '/dashboard/Admin',
      name: 'dashboard-Admin',
      component: () => DashboardAdmin,
      meta: { requiresAuth: true, role: 'Admin' }
    },
    {
      path: '/dashboard/Petugas',
      name: 'dashboard-Petugas',
      component: () => DashboardPetugas,
      meta: { requiresAuth: true, role: 'Petugas' }
    },
    {
      path: '/Petugas/barang-masuk',
      name: 'Petugas-barang-masuk',
      component: () => BarangMasuk,
      meta: { requiresAuth: true, role: 'Petugas' }
    },
    {
      path: '/Petugas/barang-keluar',
      name: 'Petugas-barang-keluar',
      component: () => BarangKeluar,
      meta: { requiresAuth: true, role: 'Petugas' }
    },
    {
      path: '/Petugas/barang',
      name: 'Petugas-barang',
      component: () => BarangPetugas,
      meta: { requiresAuth: true, role: 'Petugas' }
    },
    {
      path: '/Admin/barangs',
      name: 'Admin-barang',
      component: () => AdminBarang,
      meta: { requiresAuth: true, role: 'Admin' }
    },
    {
      path: '/Admin/suppliers',
      name: 'Admin-supplier',
      component: () => SupplierAdmin,
      meta: { requiresAuth: true, role: 'Admin' }
    },
    {
      path: '/Admin/barang-masuk',
      name: 'Admin-barang-masuk',
      component: () => AdminBarangMasuk,
      meta: { requiresAuth: true, role: 'Admin' }
    },
    {
      path: '/Admin/barang-keluar',
      name: 'Admin-barang-keluar',
      component: () => AdminBarangKeluar,
      meta: { requiresAuth: true, role: 'Admin' }
    },
  ],
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isLoggedIn) {
    next('/login')
  } else if (to.meta.guest && authStore.isLoggedIn) {
    const role = authStore.userRole
    next(`/dashboard/${role}`)
  } else {
    next()
  }
})

export default router
