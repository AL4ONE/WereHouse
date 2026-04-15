<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { adminNavLinks as navLinks } from '@/config/navLinks'
</script>

<template>
  <DashboardLayout :navLinks="navLinks">
    <div class="hero">
      <div class="hero-text">
        <h1>Selamat Datang, <span class="gradient-text">Admin</span></h1>
      </div>
    </div>

    <div class="cards">
      <router-link to="/Admin/barangs" class="card">
        <div class="card-glow c1"></div><span class="card-label">Produk</span>
        <span class="card-desc">Kelola data barang</span>
        <span class="card-arrow">→</span>
      </router-link>
      <router-link to="/Admin/suppliers" class="card">
        <div class="card-glow c2"></div>
        <span class="card-label">Supplier</span>
        <span class="card-desc">Atur data supplier</span>
        <span class="card-arrow">→</span>
      </router-link>
      <router-link to="/Admin/barang-masuk" class="card">
        <div class="card-glow c3"></div>
        <span class="card-label">Barang Masuk</span>
        <span class="card-desc">Catat stok masuk</span>
        <span class="card-arrow">→</span>
      </router-link>
      <router-link to="/Admin/barang-keluar" class="card">
        <div class="card-glow c4"></div>
        <span class="card-label">Barang Keluar</span>
        <span class="card-desc">Catat stok keluar</span>
        <span class="card-arrow">→</span>
      </router-link>
    </div>
  </DashboardLayout>
</template>

<style scoped>
.hero {
  margin-bottom: 36px;
}
.hero h1 {
  font-size: 28px;
  font-weight: 800;
  color: var(--text-primary);
  letter-spacing: -0.5px;
  margin-bottom: 6px;
}
.gradient-text {
  background: linear-gradient(135deg, var(--accent), #f97316);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.hero p {
  font-size: 15px;
  color: var(--text-muted);
}

.cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 18px;
}

.card {
  position: relative;
  background: var(--bg-surface);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-lg);
  padding: 28px 24px;
  text-decoration: none;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.card:hover {
  border-color: var(--border-strong);
  transform: translateY(-4px);
  box-shadow: var(--shadow-md);
}
.card:hover .card-glow { opacity: 1; }
.card:hover .card-arrow { opacity: 1; transform: translateX(0); }

.card-glow {
  position: absolute;
  top: -40px; right: -40px;
  width: 120px; height: 120px;
  border-radius: 50%;
  filter: blur(40px);
  opacity: 0;
  transition: opacity 0.4s;
}
.card-glow.c1 { background: #f97316; }
.card-glow.c2 { background: #f97316; }
.card-glow.c3 { background: #f97316; }
.card-glow.c4 { background: #f97316; }

.card-icon-wrap {
  width: 48px; height: 48px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  margin-bottom: 12px;
}
.card-icon-wrap.c1 { background: rgba(249,115,22,0.12); }
.card-icon-wrap.c2 { background: rgba(249,115,22,0.12); }
.card-icon-wrap.c3 { background: rgba(249,115,22,0.12); }
.card-icon-wrap.c4 { background: rgba(249,115,22,0.12); }

.card-label {
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
}
.card-desc {
  font-size: 13px;
  color: var(--text-muted);
}
.card-arrow {
  position: absolute;
  top: 24px; right: 24px;
  font-size: 18px;
  color: var(--accent);
  opacity: 0;
  transform: translateX(-8px);
  transition: all 0.3s;
}
</style>
