<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api'

const router = useRouter()
const form = reactive({ email: '', password: '' })
const isLoading = ref(false)
const errorMsg = ref('')
const showPassword = ref(false)

async function handleLogin() {
  isLoading.value = true
  errorMsg.value = ''
  try {
    const response = await api.post('/login', {
      email: form.email,
      password: form.password
    })
    localStorage.setItem('token', response.data.token)
    localStorage.setItem('user', JSON.stringify(response.data.data))
    router.push('/belajar/barang')
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Login gagal!'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="login-page">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="login-card">
      <div class="login-brand">
        <div class="brand-logo">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
            <polyline points="10 17 15 12 10 7"/>
            <line x1="15" y1="12" x2="3" y2="12"/>
          </svg>
        </div>
        <h1>Belajar <span class="accent">Login</span></h1>
        <p>Masuk ke sistem</p>
      </div>

      <div v-if="errorMsg" class="error-box">{{ errorMsg }}</div>

      <form @submit.prevent="handleLogin">
        <div class="field">
          <label>Email</label>
          <div class="input-wrap">
            <input v-model="form.email" type="email" placeholder="email@contoh.com" required />
          </div>
        </div>
        <div class="field">
          <label>Password</label>
          <div class="input-wrap">
            <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="password" required />
            <button type="button" class="eye-btn" @click="showPassword = !showPassword">👁</button>
          </div>
        </div>
        <button type="submit" class="btn-submit" :disabled="isLoading">
          <span v-if="isLoading" class="spinner"></span>
          {{ isLoading ? 'Memproses...' : 'Masuk' }}
        </button>
      </form>

      <p class="switch-text">Belum punya akun? <router-link to="/learning/register" class="link">Daftar</router-link></p>
    </div>
  </div>
</template>

<style scoped>
.login-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: var(--bg-base); padding: 20px; position: relative; overflow: hidden; }
.blob { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.35; animation: float 8s ease-in-out infinite; }
.blob-1 { width: 400px; height: 400px; background: linear-gradient(135deg, #f97316, #ea580c); top: -100px; right: -100px; }
.blob-2 { width: 300px; height: 300px; background: linear-gradient(135deg, #fb923c, #f59e0b); bottom: -80px; left: -80px; animation-delay: -3s; }
@keyframes float { 0%, 100% { transform: translate(0,0) scale(1); } 50% { transform: translate(30px,-30px) scale(1.05); } }
.login-card { width: 100%; max-width: 420px; background: var(--glass); backdrop-filter: blur(24px); border: 1px solid var(--glass-border); border-radius: var(--radius-xl); padding: 40px 36px; position: relative; z-index: 1; box-shadow: var(--shadow-lg); }
.login-brand { text-align: center; margin-bottom: 32px; }
.brand-logo { width: 64px; height: 64px; margin: 0 auto 16px; background: linear-gradient(135deg, var(--accent), #f59e0b); border-radius: 18px; display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-glow); color: white; }
.login-brand h1 { font-size: 26px; color: var(--text-primary); font-weight: 800; }
.accent { color: var(--accent); }
.login-brand p { font-size: 13px; color: var(--text-muted); margin-top: 4px; }
.error-box { background: var(--danger-bg); border: 1px solid rgba(239,68,68,0.2); color: var(--danger); padding: 12px 16px; border-radius: var(--radius-md); font-size: 13px; margin-bottom: 20px; }
.field { margin-bottom: 18px; }
.field label { display: block; font-size: 13px; color: var(--text-secondary); margin-bottom: 8px; font-weight: 500; }
.input-wrap { position: relative; display: flex; align-items: center; }
.input-wrap input { width: 100%; padding: 12px 14px; background: var(--bg-base); border: 1px solid var(--border-default); border-radius: var(--radius-md); color: var(--text-primary); font-size: 14px; font-family: inherit; outline: none; }
.input-wrap input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }
.input-wrap input::placeholder { color: var(--text-muted); }
.eye-btn { position: absolute; right: 12px; background: none; border: none; cursor: pointer; padding: 4px; font-size: 16px; }
.btn-submit { width: 100%; padding: 14px; margin-top: 8px; background: linear-gradient(135deg, var(--accent), #f59e0b); color: white; border: none; border-radius: var(--radius-md); font-size: 15px; font-weight: 600; font-family: inherit; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 16px var(--accent-glow); }
.btn-submit:hover:not(:disabled) { transform: translateY(-1px); }
.btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }
.spinner { width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.switch-text { text-align: center; font-size: 13px; color: var(--text-muted); margin-top: 20px; }
.link { color: var(--accent); text-decoration: none; font-weight: 600; }
</style>
