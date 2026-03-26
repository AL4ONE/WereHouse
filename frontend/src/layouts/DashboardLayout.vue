<script setup>
import { useAuthStore } from '@/stores/auth'
import { useRouter, useRoute } from 'vue-router'
import { computed } from 'vue'

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
</script>

<template>
  <div class="app-shell">
    <!-- Top Nav -->
    <nav class="topnav">
      <div class="nav-left">
        <span class="brand">📦 <strong>USKGudang</strong></span>
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
        <span class="role-badge">{{ userRole }}</span>
        <span class="user-name">{{ userName }}</span>
        <button @click="handleLogout" class="btn-logout">Logout</button>
      </div>
    </nav>

    <!-- Page Content -->
    <main class="page-content">
      <slot />
    </main>
  </div>
</template>

<style scoped>
.app-shell {
  min-height: 100vh;
  background: #13131f;
}

.topnav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 32px;
  height: 60px;
  background: #1e1e2e;
  border-bottom: 1px solid #2a2a3d;
  position: sticky;
  top: 0;
  z-index: 50;
}

.nav-left {
  display: flex;
  align-items: center;
  gap: 32px;
}

.brand {
  font-size: 16px;
  color: #e2e8f0;
  letter-spacing: -0.3px;
}

.nav-links {
  display: flex;
  gap: 4px;
}

.nav-link {
  padding: 6px 14px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  color: #94a3b8;
  text-decoration: none;
  transition: all 0.15s;
}

.nav-link:hover {
  color: #e2e8f0;
  background: #2a2a3d;
}

.nav-link.active {
  color: #fff;
  background: #6366f1;
}

.nav-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.role-badge {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: #f59e0b;
  background: rgba(245, 158, 11, 0.12);
  padding: 4px 10px;
  border-radius: 6px;
}

.user-name {
  font-size: 13px;
  color: #cbd5e1;
  text-transform: capitalize;
}

.btn-logout {
  font-size: 12px;
  padding: 6px 14px;
  background: transparent;
  color: #f87171;
  border: 1px solid rgba(248,113,113,0.25);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.15s;
}

.btn-logout:hover {
  background: rgba(248,113,113,0.1);
}

.page-content {
  max-width: 1100px;
  margin: 0 auto;
  padding: 32px 24px;
}

@media (max-width: 768px) {
  .topnav { padding: 0 16px; flex-wrap: wrap; height: auto; padding: 12px 16px; gap: 8px; }
  .nav-links { flex-wrap: wrap; }
  .page-content { padding: 20px 16px; }
}
</style>
