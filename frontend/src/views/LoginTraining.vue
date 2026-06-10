<script setup>
import { useAuthStore } from '@/stores/auth'
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({ email: '', password: '' })
const isLoading = ref(false)
const errorMsg = ref('')
const showPassword = ref(false)

async function handleLogin() {
  isLoading.value = true
  errorMsg.value = ''
  try {
    await authStore.login(form.email, form.password)
    const role = authStore.userRole
    
    // Only allow training roles
    if (!role || !role.includes('Latihan')) {
      errorMsg.value = 'Akun ini bukan akun latihan. Silakan gunakan login utama.'
      await authStore.logout()
      return
    }
    
    const baseRole = role.replace(' Latihan', '').trim()
    const roleMap = { 'Manajer': 'manager', 'Admin': 'admin', 'Petugas': 'staff' }
    const target = roleMap[baseRole] || baseRole.toLowerCase()
    router.push(`/training/dashboard/${target}`)
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Login gagal, periksa email & password!'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="login-page">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <div class="login-card">
      <div class="training-badge">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        MODE LATIHAN
      </div>

      <div class="login-brand">
        <div class="brand-logo">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
        </div>
        <h1>USK<span class="accent">Gudang</span></h1>
        <p>Training Mode — Warehouse Management</p>
      </div>

      <div v-if="errorMsg" class="error-box">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        {{ errorMsg }}
      </div>

      <form @submit.prevent="handleLogin">
        <div class="field">
          <label>Email</label>
          <div class="input-wrap">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
            <input v-model="form.email" type="email" placeholder="email latihan..." required />
          </div>
        </div>

        <div class="field">
          <label>Password</label>
          <div class="input-wrap">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="••••••••" required />
            <button type="button" class="eye-btn" @click="showPassword = !showPassword">
              <span v-if="showPassword"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg></span>
              <span v-else><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></span>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-submit" :disabled="isLoading">
          <span v-if="isLoading" class="spinner"></span>
          {{ isLoading ? 'Processing...' : 'Masuk Mode Latihan' }}
          <svg v-if="!isLoading" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
        </button>
      </form>

      <div class="info-box">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
        <span>Data di mode latihan terpisah dari sistem asli dan aman untuk bereksperimen.</span>
      </div>

      <p class="back-link">
        <router-link to="/login" class="link">← Kembali ke Login Utama</router-link>
      </p>
    </div>
  </div>
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg-base);
  padding: 20px;
  position: relative;
  overflow: hidden;
}

.blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.35;
  animation: float 8s ease-in-out infinite;
}
.blob-1 { width: 400px; height: 400px; background: linear-gradient(135deg, #06b6d4, #0284c7); top: -100px; right: -100px; }
.blob-2 { width: 300px; height: 300px; background: linear-gradient(135deg, #22d3ee, #0ea5e9); bottom: -80px; left: -80px; animation-delay: -3s; }
.blob-3 { width: 200px; height: 200px; background: linear-gradient(135deg, #06b6d4, #7c3aed); top: 50%; left: 50%; animation-delay: -5s; }

@keyframes float {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33% { transform: translate(30px, -30px) scale(1.05); }
  66% { transform: translate(-20px, 20px) scale(0.95); }
}

.login-card {
  width: 100%; max-width: 420px;
  background: var(--glass);
  backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
  border: 1px solid var(--glass-border);
  border-radius: var(--radius-xl);
  padding: 40px 36px;
  position: relative; z-index: 1;
  box-shadow: var(--shadow-lg), inset 0 1px 0 rgba(255,255,255,0.04);
}

.training-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: linear-gradient(135deg, #06b6d4, #0284c7);
  color: #fff;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 1.5px;
  padding: 10px 20px;
  border-radius: var(--radius-md);
  margin-bottom: 24px;
  text-transform: uppercase;
  box-shadow: 0 4px 16px rgba(6,182,212,0.3);
  animation: badge-glow 2s ease-in-out infinite;
}
@keyframes badge-glow {
  0%, 100% { box-shadow: 0 4px 16px rgba(6,182,212,0.3); }
  50% { box-shadow: 0 4px 24px rgba(6,182,212,0.5); }
}

.login-brand { text-align: center; margin-bottom: 32px; }
.brand-logo {
  width: 64px; height: 64px; margin: 0 auto 16px;
  background: linear-gradient(135deg, #06b6d4, #0ea5e9);
  border-radius: 18px;
  display: flex; align-items: center; justify-content: center;
  font-size: 28px; color: white;
  box-shadow: 0 4px 20px rgba(6,182,212,0.35);
}
.login-brand h1 { font-size: 26px; color: var(--text-primary); font-weight: 800; letter-spacing: -0.5px; }
.accent { color: #06b6d4; }
.login-brand p { font-size: 13px; color: var(--text-muted); margin-top: 4px; }

.error-box {
  background: var(--danger-bg); border: 1px solid rgba(239,68,68,0.2); color: var(--danger);
  padding: 12px 16px; border-radius: var(--radius-md); font-size: 13px; margin-bottom: 20px;
  display: flex; align-items: center; gap: 8px;
}

.info-box {
  display: flex; align-items: flex-start; gap: 8px;
  background: rgba(6,182,212,0.08); border: 1px solid rgba(6,182,212,0.15);
  color: #06b6d4; padding: 12px 16px; border-radius: var(--radius-md);
  font-size: 12px; margin-top: 20px; line-height: 1.5;
}
.info-box svg { flex-shrink: 0; margin-top: 1px; }

.field { margin-bottom: 18px; }
.field label { display: block; font-size: 13px; color: var(--text-secondary); margin-bottom: 8px; font-weight: 500; }
.input-wrap { position: relative; display: flex; align-items: center; }
.input-icon { position: absolute; left: 14px; color: var(--text-muted); pointer-events: none; }
.input-wrap input {
  width: 100%; padding: 12px 14px 12px 44px;
  background: var(--bg-base); border: 1px solid var(--border-default);
  border-radius: var(--radius-md); color: var(--text-primary); font-size: 14px; font-family: inherit; outline: none;
}
.input-wrap input:focus { border-color: #06b6d4; box-shadow: 0 0 0 3px rgba(6,182,212,0.15); }
.input-wrap input::placeholder { color: var(--text-muted); }
.eye-btn { position: absolute; right: 12px; background: none; border: none; cursor: pointer; font-size: 16px; padding: 4px; color: var(--text-muted); }

.btn-submit {
  width: 100%; padding: 14px; margin-top: 8px;
  background: linear-gradient(135deg, #06b6d4, #0ea5e9);
  color: white; border: none; border-radius: var(--radius-md);
  font-size: 15px; font-weight: 600; font-family: inherit; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  box-shadow: 0 4px 16px rgba(6,182,212,0.3);
}
.btn-submit:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 24px rgba(6,182,212,0.4); }
.btn-submit:active:not(:disabled) { transform: translateY(0); }
.btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }

.spinner { width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.back-link { text-align: center; font-size: 13px; color: var(--text-muted); margin-top: 16px; }
.link { color: #06b6d4; text-decoration: none; font-weight: 600; }
.link:hover { text-decoration: underline; }
</style>
