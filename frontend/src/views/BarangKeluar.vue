<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { useAuthStore } from '@/stores/auth'
import { adminNavLinks, petugasNavLinks } from '@/config/navLinks'

const authStore = useAuthStore()
const navLinks = authStore.userRole === 'Admin' ? adminNavLinks : petugasNavLinks

const barangs = ref([])
const msg = ref({ text: '', type: '' })
const isLoading = ref(false)
const form = ref({ barang_id: '', destination: '', stock: 1 })

async function fetchBarangs() {
  try { 
    const res = await api.get('/barangs'); 
    barangs.value = res.data.data 
  } 
  catch (e) {
    console.log(e)
  }
}

async function handleSubmit() {
  msg.value = { text: '', type: '' }
  isLoading.value = true
  try {
    await api.post('/inventoryOut', form.value)
    msg.value = { text: 'Catatan barang keluar berhasil!', type: 'ok' }
    form.value = { barang_id: '', destination: '', stock: 1 }
    fetchBarangs()
  } 
  catch (e) { 
    console.log(e) 
  }
  finally { 
    isLoading.value = false 
  }
}

onMounted(() => { 
  fetchBarangs() 
})
</script>

<template>
  <DashboardLayout :navLinks="navLinks">
    <div class="hero">
      <h1>📤 Barang <span class="gradient-text">Keluar</span></h1>
      <p>Catat barang keluar dari gudang ke tujuan</p>
    </div>

    <div v-if="msg.text" class="alert" :class="msg.type">
      <svg v-if="msg.type==='ok'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      {{ msg.text }}
    </div>

    <div class="glass-card form-card">
      <form @submit.prevent="handleSubmit">
        <div class="field">
          <label>Barang</label>
          <select v-model="form.barang_id" required>
            <option value="" disabled>-- Pilih Barang --</option>
            <option v-for="p in barangs" :key="p.id" :value="p.id">{{ p.name }} (stok: {{ p.stock_saat_ini }})</option>
          </select>
        </div>
        <div class="field">
          <label>Tujuan</label>
          <input v-model="form.destination" placeholder="contoh: Ruang Server LT 2" required />
        </div>
        <div class="field">
          <label>Jumlah Keluar</label>
          <input v-model.number="form.stock" type="number" min="1" required />
        </div>
        <button type="submit" class="btn-submit orange" :disabled="isLoading">
          <span v-if="isLoading" class="spinner"></span>
          {{ isLoading ? 'Memproses...' : 'Simpan Barang Keluar' }}
        </button>
      </form>
    </div>
  </DashboardLayout>
</template>

<style scoped>
.hero { margin-bottom: 24px; }
.hero h1 { font-size: 26px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; }
.gradient-text { background: linear-gradient(135deg, var(--accent), #f59e0b); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.hero p { font-size: 14px; color: var(--text-muted); }

.alert {
  padding: 12px 16px; border-radius: var(--radius-sm); font-size: 13px; margin-bottom: 18px;
  display: flex; align-items: center; gap: 8px;
}
.alert.ok { background: var(--success-bg); color: var(--success); border: 1px solid rgba(52,211,153,0.15); }
.alert.error { background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(251,113,133,0.15); }

.glass-card { background: var(--bg-surface); border: 1px solid var(--border-default); border-radius: var(--radius-lg); }
.form-card { padding: 28px; max-width: 500px; }

.field { margin-bottom: 18px; }
.field label { display: block; font-size: 13px; color: var(--text-secondary); margin-bottom: 8px; font-weight: 500; }
.field input, .field select {
  width: 100%; padding: 11px 14px; background: var(--bg-base); border: 1px solid var(--border-default);
  border-radius: var(--radius-sm); color: var(--text-primary); font-size: 14px; font-family: inherit; outline: none;
}
.field input:focus, .field select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }
.field select option { background: var(--bg-surface); color: var(--text-primary); }
.field input::placeholder { color: var(--text-muted); }

.btn-submit {
  width: 100%; border: none; padding: 12px 20px; border-radius: var(--radius-sm);
  font-size: 14px; font-weight: 600; cursor: pointer; font-family: inherit; color: #fff;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-submit.orange {
  background: linear-gradient(135deg, var(--accent), #f59e0b);
  box-shadow: 0 2px 12px var(--accent-glow);
}
.btn-submit.orange:hover { transform: translateY(-1px); box-shadow: 0 4px 20px var(--accent-glow); }
.btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none !important; }

.spinner { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
