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
    router.push(`/dashboard/${role}`)
    console.log(role);
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Login gagal, coba lagi!'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="login-page">
    <div class="login-box">
      <div class="login-brand">
        <span class="brand-emoji">📦</span>
        <h1>USKGudang</h1>
        <p>Warehouse Management System</p>
      </div>

      <div v-if="errorMsg" class="error-box">{{ errorMsg }}</div>

      <form @submit.prevent="handleLogin">
        <div class="field">
          <label>Email</label>
          <input v-model="form.email" type="email" placeholder="email@contoh.com" required />
        </div>

        <div class="field">
          <label>Password</label>
          <div class="password-wrap">
            <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="••••••••" required />
            <button type="button" class="eye-btn" @click="showPassword = !showPassword">
              {{ showPassword ? '🙈' : '👁️' }}
            </button>
          </div>
        </div>

        <button type="submit" class="btn-submit" :disabled="isLoading">
          {{ isLoading ? 'Memproses...' : 'Masuk' }}
        </button>
      </form>
    </div>
  </div>
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #13131f;
  padding: 20px;
}

.login-box {
  width: 100%;
  max-width: 380px;
  background: #1e1e2e;
  border: 1px solid #2a2a3d;
  border-radius: 16px;
  padding: 36px 32px;
}

.login-brand {
  text-align: center;
  margin-bottom: 28px;
}

.brand-emoji { font-size: 40px; }
.login-brand h1 { font-size: 22px; color: #f1f5f9; margin: 8px 0 4px; font-weight: 700; }
.login-brand p { font-size: 13px; color: #64748b; }

.error-box {
  background: rgba(248,113,113,0.1);
  border: 1px solid rgba(248,113,113,0.2);
  color: #f87171;
  padding: 10px 14px;
  border-radius: 10px;
  font-size: 13px;
  margin-bottom: 16px;
}

.field { margin-bottom: 16px; }
.field label { display: block; font-size: 13px; color: #94a3b8; margin-bottom: 6px; font-weight: 500; }

.field input, .password-wrap input {
  width: 100%;
  padding: 10px 14px;
  background: #13131f;
  border: 1px solid #363650;
  border-radius: 10px;
  color: #e2e8f0;
  font-size: 14px;
  outline: none;
  transition: border-color 0.15s;
  box-sizing: border-box;
}

.field input:focus, .password-wrap input:focus { border-color: #6366f1; }
.field input::placeholder, .password-wrap input::placeholder { color: #4a4a6a; }

.password-wrap {
  position: relative;
}

.eye-btn {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  font-size: 16px;
}

.btn-submit {
  width: 100%;
  padding: 12px;
  margin-top: 4px;
  background: #6366f1;
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s;
}

.btn-submit:hover:not(:disabled) { background: #4f46e5; }
.btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }
</style>
