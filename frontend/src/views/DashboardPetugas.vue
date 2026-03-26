<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'

const navLinks = [
  { path: '/dashboard/Petugas', label: '🏠 Dashboard' },
  { path: '/Petugas/barang-masuk', label: '📥 Barang Masuk' },
  { path: '/Petugas/barang-keluar', label: '📤 Barang Keluar' },
  { path: '/Petugas/barang', label: 'barang' },
]
</script>

<template>
  <DashboardLayout :navLinks="navLinks">
    <h1 class="page-title">Dashboard Petugas</h1>
    <p class="page-desc">Catat transaksi barang masuk dan keluar dari sini.</p>

    <div class="cards">
      <router-link to="/Petugas/barang-masuk" class="card green">
        <span class="card-icon">📥</span>
        <span class="card-label">Barang Masuk</span>
      </router-link>
      <router-link to="/Petugas/barang-keluar" class="card orange">
        <span class="card-icon">📤</span>
        <span class="card-label">Barang Keluar</span>
      </router-link>
    </div>
  </DashboardLayout>
</template>

<style scoped>
.page-title { font-size: 22px; color: #f1f5f9; margin-bottom: 4px; }
.page-desc { font-size: 14px; color: #64748b; margin-bottom: 28px; }

.cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }

.card {
  background: #1e1e2e; border: 1px solid #2a2a3d; border-radius: 14px;
  padding: 28px 20px; text-align: center; text-decoration: none; transition: all 0.15s;
}
.card.green:hover { border-color: #22c55e; }
.card.orange:hover { border-color: #f97316; }
.card-icon { font-size: 32px; display: block; margin-bottom: 10px; }
.card-label { font-size: 15px; font-weight: 600; color: #e2e8f0; }
</style>
