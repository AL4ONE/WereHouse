<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api'

const router = useRouter()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const isLoading = ref(false)
const errorMsg = ref('')
const successMsg = ref('')
const showPassword = ref(false)

async function handleRegister() {
  isLoading.value = true
  errorMsg.value = ''
  successMsg.value = ''

  try {
    const response = await api.post('/register', {
      name: form.name,
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation
    })

    localStorage.setItem('token', response.data.token)
    localStorage.setItem('user', JSON.stringify(response.data.data))

    successMsg.value = 'Registrasi berhasil!'

    setTimeout(() => {
      router.push('/belajar/barang')
    }, 1000)
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Registrasi gagal!'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="register-page">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <div class="register-card">
      <div class="register-brand">
        <div class="brand-logo">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <line x1="19" y1="8" x2="19" y2="14"></line>
            <line x1="22" y1="11" x2="16" y2="11"></line>
          </svg>
        </div>
        <h1>Buat <span class="accent">Akun</span></h1>
        <p>Daftar untuk mengakses sistem</p>
      </div>

      <div v-if="errorMsg" class="error-box">{{ errorMsg }}</div>
      <div v-if="successMsg" class="success-box">{{ successMsg }}</div>

      <form @submit.prevent="handleRegister">
        <div class="field">
          <label>Nama Lengkap</label>
          <div class="input-wrap">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <input v-model="form.name" type="text" placeholder="Masukkan nama" required />
          </div>
        </div>

        <div class="field">
          <label>Email</label>
          <div class="input-wrap">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="2" y="4" width="20" height="16" rx="2"/>
              <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
            </svg>
            <input v-model="form.email" type="email" placeholder="email@contoh.com" required />
          </div>
        </div>

        <div class="field">
          <label>Password</label>
          <div class="input-wrap">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="Minimal 6 karakter" required />
            <button type="button" class="eye-btn" @click="showPassword = !showPassword">👁</button>
          </div>
        </div>

        <div class="field">
          <label>Konfirmasi Password</label>
          <div class="input-wrap">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            <input v-model="form.password_confirmation" :type="showPassword ? 'text' : 'password'" placeholder="Ulangi password" required />
          </div>
        </div>

        <button type="submit" class="btn-submit" :disabled="isLoading">
          <span v-if="isLoading" class="spinner"></span>
          {{ isLoading ? 'Memproses...' : 'Daftar Sekarang' }}
        </button>
      </form>

      <p class="switch-text">
        Sudah punya akun?
        <router-link to="/learning/login" class="link">Masuk di sini</router-link>
      </p>
    </div>
  </div>
</template>

<style scoped>
.register-page {
  min-height: 100vh;
  display: flex; align-items: center; justify-content: center;
  background: var(--bg-base); padding: 20px;
  position: relative; overflow: hidden;
}
.blob { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.35; animation: float 8s ease-in-out infinite; }
.blob-1 { width: 400px; height: 400px; background: linear-gradient(135deg, #f97316, #ea580c); top: -100px; right: -100px; }
.blob-2 { width: 300px; height: 300px; background: linear-gradient(135deg, #fb923c, #f59e0b); bottom: -80px; left: -80px; animation-delay: -3s; }
.blob-3 { width: 200px; height: 200px; background: linear-gradient(135deg, #f97316, #dc2626); top: 50%; left: 50%; animation-delay: -5s; }
@keyframes float {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33% { transform: translate(30px, -30px) scale(1.05); }
  66% { transform: translate(-20px, 20px) scale(0.95); }
}
.register-card {
  width: 100%; max-width: 440px;
  background: var(--glass); backdrop-filter: blur(24px);
  border: 1px solid var(--glass-border); border-radius: var(--radius-xl);
  padding: 36px; position: relative; z-index: 1;
  box-shadow: var(--shadow-lg);
}
.register-brand { text-align: center; margin-bottom: 28px; }
.brand-logo {
  width: 64px; height: 64px; margin: 0 auto 16px;
  background: linear-gradient(135deg, var(--accent), #f59e0b);
  border-radius: 18px; display: flex; align-items: center; justify-content: center;
  box-shadow: var(--shadow-glow); color: white;
}
.register-brand h1 { font-size: 26px; color: var(--text-primary); font-weight: 800; }
.accent { color: var(--accent); }
.register-brand p { font-size: 13px; color: var(--text-muted); margin-top: 4px; }
.error-box { background: var(--danger-bg); border: 1px solid rgba(239,68,68,0.2); color: var(--danger); padding: 12px 16px; border-radius: var(--radius-md); font-size: 13px; margin-bottom: 20px; }
.success-box { background: var(--success-bg); border: 1px solid rgba(34,197,94,0.2); color: var(--success); padding: 12px 16px; border-radius: var(--radius-md); font-size: 13px; margin-bottom: 20px; }
.field { margin-bottom: 16px; }
.field label { display: block; font-size: 13px; color: var(--text-secondary); margin-bottom: 8px; font-weight: 500; }
.input-wrap { position: relative; display: flex; align-items: center; }
.input-icon { position: absolute; left: 14px; color: var(--text-muted); pointer-events: none; }
.input-wrap input {
  width: 100%; padding: 12px 14px 12px 44px;
  background: var(--bg-base); border: 1px solid var(--border-default);
  border-radius: var(--radius-md); color: var(--text-primary); font-size: 14px; font-family: inherit; outline: none;
}
.input-wrap input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }
.input-wrap input::placeholder { color: var(--text-muted); }
.eye-btn { position: absolute; right: 12px; background: none; border: none; cursor: pointer; padding: 4px; color: var(--text-muted); font-size: 16px; }
.btn-submit {
  width: 100%; padding: 14px; margin-top: 8px;
  background: linear-gradient(135deg, var(--accent), #f59e0b);
  color: white; border: none; border-radius: var(--radius-md);
  font-size: 15px; font-weight: 600; font-family: inherit; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  box-shadow: 0 4px 16px var(--accent-glow);
}
.btn-submit:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 24px rgba(249,115,22,0.4); }
.btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }
.spinner { width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.switch-text { text-align: center; font-size: 13px; color: var(--text-muted); margin-top: 20px; }
.link { color: var(--accent); text-decoration: none; font-weight: 600; }
.link:hover { text-decoration: underline; }
</style>
