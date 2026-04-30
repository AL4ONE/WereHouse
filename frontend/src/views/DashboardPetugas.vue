<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { petugasNavLinks as navLinks } from '@/config/navLinks'
</script>

<template>
  <DashboardLayout :navLinks="navLinks">
    <div class="hero">
      <div class="hero-text">
        <h1>Selamat Datang, <span class="gradient-text">Petugas</span></h1>
      </div>
    </div>

    <div class="cards">
      <router-link to="/staff/inventory-in" class="card">
        <div class="card-glow green"></div>
        <span class="card-label">Barang Masuk</span>
        <span class="card-desc">Catat stok masuk dari supplier</span>
        <span class="card-arrow">→</span>
      </router-link>
      <router-link to="/staff/inventory-out" class="card">
        <div class="card-glow orange"></div>
        <span class="card-label">Barang Keluar</span>
        <span class="card-desc">Catat stok keluar ke tujuan</span>
        <span class="card-arrow">→</span>
      </router-link>
    </div>
  </DashboardLayout>
</template>

<style scoped>
.hero { margin-bottom: 36px; }
.hero h1 {
  font-size: 28px; font-weight: 800; color: var(--text-primary);
  letter-spacing: -0.5px; margin-bottom: 6px;
}
.gradient-text {
  background: linear-gradient(135deg, var(--accent), #f59e0b);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.hero p { font-size: 15px; color: var(--text-muted); }

.cards {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px;
}
.card {
  position: relative; background: var(--bg-surface); border: 1px solid var(--border-default);
  border-radius: var(--radius-lg); padding: 28px 24px; text-decoration: none;
  overflow: hidden; display: flex; flex-direction: column; gap: 4px;
}
.card:hover {
  border-color: var(--border-strong); transform: translateY(-4px); box-shadow: var(--shadow-md);
}
.card:hover .card-glow { opacity: 1; }
.card:hover .card-arrow { opacity: 1; transform: translateX(0); }

.card-glow {
  position: absolute; top: -40px; right: -40px;
  width: 120px; height: 120px; border-radius: 50%;
  filter: blur(40px); opacity: 0; transition: opacity 0.4s;
}
.card-glow.green { background: #f97316; }
.card-glow.orange { background: #f97316; }

.card-icon-wrap {
  width: 48px; height: 48px; border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  font-size: 22px; margin-bottom: 12px;
}
.card-icon-wrap.green { background: rgba(249,115,22,0.12); }
.card-icon-wrap.orange { background: rgba(249,115,22,0.12); }

.card-label { font-size: 16px; font-weight: 700; color: var(--text-primary); }
.card-desc { font-size: 13px; color: var(--text-muted); }
.card-arrow {
  position: absolute; top: 24px; right: 24px; font-size: 18px;
  color: var(--accent); opacity: 0; transform: translateX(-8px); transition: all 0.3s;
}
</style>
