<script setup>
import { useAuthStore } from '@/stores/auth'
import { useRouter, useRoute } from 'vue-router'
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
import api from '@/api'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()

const props = defineProps({
  navLinks: { type: Array, default: () => [] }
})

const userName = computed(() => authStore.user?.name || 'User')
const userRole = computed(() => authStore.userRole || '')

function handleLogout() {
  authStore.logout()
  router.push('/login')
}

function isActive(path) {
  return route.path === path
}

// ===== Notification Bell =====
const barangs = ref([])
const showNotifDropdown = ref(false)
const notifRef = ref(null)

async function fetchBarangs() {
  try {
    const res = await api.get('/barangs')
    barangs.value = res.data.data
  } catch (e) {
    console.error(e)
  }
}

const lowStockItems = computed(() => {
  return barangs.value.filter(p => p.stock_saat_ini <= 5)
})

function toggleNotif() {
  showNotifDropdown.value = !showNotifDropdown.value
}

function handleClickOutside(e) {
  if (notifRef.value && !notifRef.value.contains(e.target)) {
    showNotifDropdown.value = false
  }
}

onMounted(() => {
  fetchBarangs()
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div class="app-shell">
    <nav class="topnav">
      <div class="nav-left">
        <router-link :to="navLinks[0]?.path || '/'" class="brand">
          <div class="brand-icon">📦</div>
          <span class="brand-text">USK<strong>Gudang</strong></span>
        </router-link>
        <div class="nav-divider"></div>
        <div class="nav-links">
          <router-link
            v-for="link in navLinks"
            :key="link.path"
            :to="link.path"
            class="nav-link"
            :class="{ active: isActive(link.path) }"
          >
            {{ link.label }}
          </router-link>
        </div>
      </div>
      <div class="nav-right">
        <!-- Notification Bell -->
        <div class="notif-wrapper" ref="notifRef">
          <button class="btn-icon" @click="toggleNotif" title="Notifikasi Stok Menipis">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <span v-if="lowStockItems.length" class="notif-badge" :class="{ 'pulse': lowStockItems.length > 0 }">{{ lowStockItems.length }}</span>
          </button>

          <transition name="dd">
            <div v-if="showNotifDropdown" class="notif-dropdown">
              <div class="dd-header">
                <span>🔔 Notifikasi Stok</span>
                <span class="dd-count" v-if="lowStockItems.length">{{ lowStockItems.length }}</span>
              </div>
              <div v-if="lowStockItems.length === 0" class="dd-empty">
                <span class="dd-empty-icon">✅</span>
                <span>Semua stok aman!</span>
              </div>
              <div v-else class="dd-list">
                <div v-for="item in lowStockItems" :key="item.id" class="dd-item">
                  <div class="dd-item-dot"></div>
                  <div class="dd-item-body">
                    <span class="dd-item-name">{{ item.name }}</span>
                    <span class="dd-item-stock">Sisa: <strong>{{ item.stock_saat_ini }}</strong> {{ item.satuan }}</span>
                  </div>
                </div>
              </div>
            </div>
          </transition>
        </div>

        <div class="nav-divider"></div>

        <div class="user-area">
          <div class="user-avatar">{{ userName.charAt(0)}}</div>
          <div class="user-info">
            <span class="user-name">{{ userName }}</span>
            <span class="user-role">{{ userRole }}</span>
          </div>
        </div>

        <button @click="handleLogout" class="btn-logout" title="Logout">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </button>
      </div>
    </nav>

    <main class="page-content">
      <slot />
    </main>
  </div>
</template>

<style scoped>
.app-shell {
  min-height: 100vh;
  background: var(--bg-base);
}

.topnav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 28px;
  height: 64px;
  background: var(--glass);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--glass-border);
  position: sticky;
  top: 0;
  z-index: 50;
}

.nav-left, .nav-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.brand {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
}

.brand-icon {
  width: 36px; height: 36px;
  background: linear-gradient(135deg, var(--accent-bold), #f59e0b);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  box-shadow: 0 2px 12px rgba(249,115,22,0.3);
}

.brand-text {
  font-size: 16px;
  color: var(--text-secondary);
  letter-spacing: -0.3px;
  font-weight: 400;
}
.brand-text strong {
  color: var(--text-primary);
  font-weight: 700;
}

.nav-divider {
  width: 1px;
  height: 28px;
  background: var(--border-default);
}

.nav-links {
  display: flex;
  gap: 2px;
}

.nav-link {
  padding: 7px 14px;
  border-radius: var(--radius-sm);
  font-size: 13px;
  font-weight: 500;
  color: var(--text-muted);
  text-decoration: none;
}

.nav-link:hover {
  color: var(--text-primary);
  background: var(--bg-hover);
}

.nav-link.active {
  color: #fff;
  background: linear-gradient(135deg, var(--accent), #f59e0b);
  box-shadow: 0 2px 8px rgba(249,115,22,0.25);
}

/* Notification */
.notif-wrapper { position: relative; }

.btn-icon {
  position: relative;
  width: 38px; height: 38px;
  background: var(--bg-elevated);
  border: 1px solid var(--border-default);
  border-radius: 10px;
  color: var(--text-muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}
.btn-icon:hover {
  color: var(--text-primary);
  border-color: var(--border-strong);
  background: var(--bg-hover);
}

.notif-badge {
  position: absolute;
  top: -5px; right: -5px;
  background: linear-gradient(135deg, #ef4444, #f97316);
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  min-width: 18px; height: 18px;
  border-radius: 99px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
  border: 2px solid var(--bg-surface);
}
.notif-badge.pulse {
  animation: badge-pulse 2s ease-in-out infinite;
}
@keyframes badge-pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.4); }
  50% { box-shadow: 0 0 0 6px rgba(239,68,68,0); }
}

/* Dropdown */
.notif-dropdown {
  position: absolute;
  top: calc(100% + 12px);
  right: 0;
  width: 340px;
  background: var(--bg-surface);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  z-index: 100;
  overflow: hidden;
}
.dd-header {
  padding: 14px 18px;
  font-size: 13px;
  font-weight: 600;
  color: var(--text-primary);
  border-bottom: 1px solid var(--border-subtle);
  background: var(--bg-elevated);
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.dd-count {
  background: var(--danger-bg);
  color: var(--danger);
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 99px;
  font-weight: 700;
}
.dd-empty {
  padding: 28px 18px;
  text-align: center;
  color: var(--success);
  font-size: 13px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
}
.dd-empty-icon { font-size: 24px; }
.dd-list { max-height: 300px; overflow-y: auto; }
.dd-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 18px;
  border-bottom: 1px solid var(--border-subtle);
}
.dd-item:last-child { border-bottom: none; }
.dd-item:hover { background: var(--bg-elevated); }
.dd-item-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  background: var(--danger);
  margin-top: 6px;
  flex-shrink: 0;
  box-shadow: 0 0 6px rgba(251,113,133,0.4);
}
.dd-item-body { display: flex; flex-direction: column; gap: 2px; }
.dd-item-name { font-size: 13px; font-weight: 600; color: var(--text-primary); }
.dd-item-stock { font-size: 12px; color: var(--danger); }

.dd-enter-active, .dd-leave-active { transition: all 0.2s ease; }
.dd-enter-from, .dd-leave-to { opacity: 0; transform: translateY(-8px) scale(0.96); }

/* User Area */
.user-area {
  display: flex;
  align-items: center;
  gap: 10px;
}
.user-avatar {
  width: 34px; height: 34px;
  border-radius: 10px;
  background: linear-gradient(135deg, #f59e0b, #f97316);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;
  color: #fff;
}
.user-info {
  display: flex;
  flex-direction: column;
}
.user-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-primary);
  text-transform: capitalize;
  line-height: 1.2;
}
.user-role {
  font-size: 11px;
  color: var(--accent);
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.6px;
}

.btn-logout {
  width: 38px; height: 38px;
  background: var(--danger-bg);
  border: 1px solid rgba(251,113,133,0.15);
  border-radius: 10px;
  color: var(--danger);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}
.btn-logout:hover {
  background: rgba(251,113,133,0.15);
  border-color: rgba(251,113,133,0.3);
}

.page-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 32px 28px;
}

@media (max-width: 768px) {
  .topnav { padding: 12px 16px; flex-wrap: wrap; height: auto; gap: 10px; }
  .nav-links { flex-wrap: wrap; gap: 4px; }
  .nav-divider { display: none; }
  .user-info { display: none; }
  .page-content { padding: 20px 16px; }
  .notif-dropdown { width: 290px; right: -40px; }
}
</style>
