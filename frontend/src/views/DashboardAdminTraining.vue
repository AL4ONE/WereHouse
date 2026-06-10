<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { trainingAdminNavLinks as navLinks } from '@/config/navLinks'
</script>

<template>
  <DashboardLayout :navLinks="navLinks">
    <div class="hero">
      <div class="hero-text">
        <h1>Welcome, <span class="gradient-text">Admin Latihan</span></h1>
      </div>
    </div>

    <div class="cards">
      <router-link to="/training/admin/products" class="card">
        <div class="card-glow c1"></div><span class="card-label">Products</span>
        <span class="card-desc">Manage product data</span>
        <span class="card-arrow">→</span>
      </router-link>
      <router-link to="/training/admin/suppliers" class="card">
        <div class="card-glow c2"></div>
        <span class="card-label">Suppliers</span>
        <span class="card-desc">Manage supplier data</span>
        <span class="card-arrow">→</span>
      </router-link>
      <router-link to="/training/admin/purchase-orders" class="card">
        <div class="card-glow c1"></div>
        <span class="card-label">Purchase Orders</span>
        <span class="card-desc">Manage POs to suppliers</span>
        <span class="card-arrow">→</span>
      </router-link>
      <router-link to="/training/admin/inventory-in" class="card">
        <div class="card-glow c3"></div>
        <span class="card-label">Inbound</span>
        <span class="card-desc">Record incoming stock</span>
        <span class="card-arrow">→</span>
      </router-link>
      <router-link to="/training/admin/inventory-out" class="card">
        <div class="card-glow c4"></div>
        <span class="card-label">Outbound</span>
        <span class="card-desc">Record outgoing stock</span>
        <span class="card-arrow">→</span>
      </router-link>
    </div>
  </DashboardLayout>
</template>

<style scoped>
.hero { margin-bottom: 36px; }
.hero h1 { font-size: 28px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.5px; margin-bottom: 6px; }
.gradient-text { background: linear-gradient(135deg, #06b6d4, #0ea5e9); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; }
.card { position: relative; background: var(--bg-surface); border: 1px solid var(--border-default); border-radius: var(--radius-lg); padding: 28px 24px; text-decoration: none; overflow: hidden; display: flex; flex-direction: column; gap: 4px; }
.card:hover { border-color: var(--border-strong); transform: translateY(-4px); box-shadow: var(--shadow-md); }
.card:hover .card-glow { opacity: 1; }
.card:hover .card-arrow { opacity: 1; transform: translateX(0); }
.card-glow { position: absolute; top: -40px; right: -40px; width: 120px; height: 120px; border-radius: 50%; filter: blur(40px); opacity: 0; transition: opacity 0.4s; }
.card-glow.c1, .card-glow.c2, .card-glow.c3, .card-glow.c4 { background: #06b6d4; }
.card-label { font-size: 16px; font-weight: 700; color: var(--text-primary); }
.card-desc { font-size: 13px; color: var(--text-muted); }
.card-arrow { position: absolute; top: 24px; right: 24px; font-size: 18px; color: #06b6d4; opacity: 0; transform: translateX(-8px); transition: all 0.3s; }
</style>
