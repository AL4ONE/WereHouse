<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'

const navLinks = [
  { path: '/dashboard/Admin', label: '🏠 Dashboard' },
  { path: '/Admin/barangs', label: '📦 Produk' },
  { path: '/Admin/suppliers', label: '🚚 Supplier' },
  { path: '/Admin/barang-masuk', label: 'Barang Masuk' },
  { path: '/Admin/barang-keluar', label: 'Barang keluar' },
]
</script>

<template>
  <DashboardLayout :navLinks="navLinks">
    <h1 class="page-title">Dashboard Admin</h1>
    <p class="page-desc">Kelola kategori, produk, dan supplier dari sini.</p>

    <div class="cards">
      <router-link to="/Admin/barangs" class="card">
        <span class="card-icon">📦</span>
        <span class="card-label">Produk</span>
      </router-link>
      <router-link to="/Admin/suppliers" class="card">
        <span class="card-icon">🚚</span>
        <span class="card-label">Supplier</span>
      </router-link>
      <router-link to="/Admin/barang-masuk" class="card">
        <span class="card-icon">🚚</span>
        <span class="card-label">barang Masuk</span>
      </router-link>
      <router-link to="/Admin/barang-keluar" class="card">
        <span class="card-icon">🚚</span>
        <span class="card-label">barang Keluar</span>
      </router-link>
    </div>
  </DashboardLayout>
</template>

<style scoped>
.page-title { font-size: 22px; color: #f1f5f9; margin-bottom: 4px; }
.page-desc { font-size: 14px; color: #64748b; margin-bottom: 28px; }

.cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }

.card {
  background: #1e1e2e;
  border: 1px solid #2a2a3d;
  border-radius: 14px;
  padding: 28px 20px;
  text-align: center;
  text-decoration: none;
  transition: all 0.15s;
}

.card:hover { border-color: #6366f1; transform: translateY(-2px); }
.card-icon { font-size: 32px; display: block; margin-bottom: 10px; }
.card-label { font-size: 15px; font-weight: 600; color: #e2e8f0; }
</style>
