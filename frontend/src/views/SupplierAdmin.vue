<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { adminNavLinks as navLinks } from '@/config/navLinks'

const barangs = ref([])
const form = ref({ name: '', email: '', phone: 0, barang_ids: [] })
const msg = ref({ text: '', type: '' })
const isLoading = ref(false)

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
    await api.post('/supplier', form.value)
    msg.value = { text: 'Supplier berhasil ditambahkan!', type: 'ok' }
    form.value = { name: '', email: '', phone: 0, barang_ids: [] }
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
      <h1>🚚 Tambah <span class="gradient-text">Supplier</span></h1>
      <p>Daftarkan supplier baru ke sistem</p>
    </div>

    <div v-if="msg.text" class="alert" :class="msg.type">
      <svg v-if="msg.type==='ok'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      {{ msg.text }}
    </div>

    <div class="glass-card form-card">
      <form @submit.prevent="handleSubmit">
        <div class="field">
          <label>Nama Supplier</label>
          <input v-model="form.name" placeholder="PT Teknologi Jaya" required />
        </div>
        <div class="field">
          <label>Email</label>
          <input v-model="form.email" type="email" placeholder="example@gmail.com" required />
        </div>
        <div class="field">
          <label>Nomor Telepon</label>
          <input v-model="form.phone" placeholder="081234567890" required />
        </div>
        <div class="field">
          <label>Produk yang disuplai</label>
          <div class="checkbox-group">
            <label v-for="b in barangs" :key="b.id" class="checkbox-item">
              <input type="checkbox" :value="b.id" v-model="form.barang_ids" />
              <span>{{ b.name }}</span>
            </label>
          </div>
          <span class="hint">Pilih produk yang disuplai oleh supplier ini</span>
        </div>
        <button type="submit" class="btn-submit" :disabled="isLoading">
          <span v-if="isLoading" class="spinner"></span>
          {{ isLoading ? 'Menyimpan...' : 'Simpan Supplier' }}
        </button>
      </form>
    </div>
  </DashboardLayout>
</template>

<style scoped>
.hero { margin-bottom: 24px; }
.hero h1 { font-size: 26px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; }
.gradient-text { background: linear-gradient(135deg, #f59e0b, #f97316); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.hero p { font-size: 14px; color: var(--text-muted); }

.alert {
  padding: 12px 16px; border-radius: var(--radius-sm); font-size: 13px; margin-bottom: 18px;
  display: flex; align-items: center; gap: 8px;
}
.alert.ok { background: var(--success-bg); color: var(--success); border: 1px solid rgba(52,211,153,0.15); }

.glass-card { background: var(--bg-surface); border: 1px solid var(--border-default); border-radius: var(--radius-lg); }
.form-card { padding: 28px; max-width: 500px; }

.field { margin-bottom: 18px; }
.field label { display: block; font-size: 13px; color: var(--text-secondary); margin-bottom: 8px; font-weight: 500; }
.field input:not([type="checkbox"]) {
  width: 100%; padding: 11px 14px; background: var(--bg-base); border: 1px solid var(--border-default);
  border-radius: var(--radius-sm); color: var(--text-primary); font-size: 14px; font-family: inherit; outline: none;
}
.field input:not([type="checkbox"]):focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }
.field input::placeholder { color: var(--text-muted); }

.checkbox-group { display: flex; flex-wrap: wrap; gap: 6px; padding: 10px; background: var(--bg-base); border: 1px solid var(--border-default); border-radius: var(--radius-sm); max-height: 160px; overflow-y: auto; }
.checkbox-item { display: flex; align-items: center; gap: 6px; padding: 5px 12px; background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: 99px; font-size: 12px; color: var(--text-primary); cursor: pointer; }
.checkbox-item:hover { border-color: var(--border-strong); }
.checkbox-item input[type="checkbox"] { accent-color: var(--accent-bold); }
.hint { font-size: 11px; color: var(--text-muted); margin-top: 6px; display: block; }

.btn-submit {
  width: 100%; border: none; padding: 12px 20px; border-radius: var(--radius-sm);
  font-size: 14px; font-weight: 600; cursor: pointer; font-family: inherit; color: #fff;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  background: linear-gradient(135deg, var(--accent), #f59e0b);
  box-shadow: 0 2px 12px rgba(249,115,22,0.25);
}
.btn-submit:hover { transform: translateY(-1px); box-shadow: 0 4px 20px rgba(249,115,22,0.35); }
.btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none !important; }

.spinner { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
