<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'
import DashboardLayout from '@/layouts/DashboardLayout.vue'

const navLinks = [
  { path: '/dashboard/Admin', label: '🏠 Dashboard' },
  { path: '/Admin/barangs', label: '📦 Produk' },
  { path: '/Admin/suppliers', label: '🚚 Supplier' },
  { path: '/Admin/barang-masuk', label: 'Barang Masuk' },
  { path: '/Admin/barang-keluar', label: 'Barang keluar' },
]

const barangs = ref([])
const form = ref({ name: '', email: '', phone: 0, barang_ids: [] })
const msg = ref({ text: '', type: '' })
const isLoading = ref(false)

async function fetchBarangs() {
  try {
    const res = await api.get('/barangs')
    barangs.value = res.data.data
  } catch (e) {
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
  } catch (e) {
    console.log(e)
  } finally { isLoading.value = false }
}

onMounted(() => {
  fetchBarangs()
})
</script>

<template>
  <DashboardLayout :navLinks="navLinks">
    <h1 class="page-title">Tambah Supplier</h1>
    <p class="page-desc">Daftarkan supplier baru ke sistem</p>

    <div v-if="msg.text" class="alert" :class="msg.type">{{ msg.text }}</div>

    <div class="form-card">
      <form @submit.prevent="handleSubmit">
        <div class="field">
          <label>Nama Supplier</label>
          <input v-model="form.name" placeholder="PT Teknologi Jaya" required />
        </div>
        <div class="field">
          <label>email</label>
          <input v-model="form.email" placeholder="example@gmail.com" required />
        </div>
        <div class="field">
          <label>nomor telepon</label>
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
          {{ isLoading ? 'Menyimpan...' : 'Simpan Supplier' }}
        </button>
      </form>
    </div>
  </DashboardLayout>
</template>

<style scoped>
.page-title { font-size: 22px; color: #f1f5f9; margin-bottom: 4px; }
.page-desc { font-size: 14px; color: #64748b; margin-bottom: 20px; }

.alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
.alert.ok { background: rgba(34,197,94,0.1); color: #4ade80; border: 1px solid rgba(34,197,94,0.15); }
.alert.error { background: rgba(248,113,113,0.1); color: #f87171; border: 1px solid rgba(248,113,113,0.15); }

.form-card { background: #1e1e2e; border: 1px solid #2a2a3d; border-radius: 14px; padding: 24px; max-width: 480px; }

.field { margin-bottom: 14px; }
.field label { display: block; font-size: 13px; color: #94a3b8; margin-bottom: 6px; }
.field input:not([type="checkbox"]) { width: 100%; padding: 9px 12px; background: #13131f; border: 1px solid #363650; border-radius: 8px; color: #e2e8f0; font-size: 14px; outline: none; box-sizing: border-box; }
.field input:not([type="checkbox"]):focus { border-color: #6366f1; }

.checkbox-group { display: flex; flex-wrap: wrap; gap: 8px; padding: 8px; background: #13131f; border: 1px solid #363650; border-radius: 8px; max-height: 160px; overflow-y: auto; }
.checkbox-item { display: flex; align-items: center; gap: 6px; padding: 4px 10px; background: #1e1e2e; border: 1px solid #2a2a3d; border-radius: 6px; font-size: 13px; color: #e2e8f0; cursor: pointer; }
.checkbox-item input[type="checkbox"] { accent-color: #6366f1; }
.hint { font-size: 11px; color: #64748b; margin-top: 4px; display: block; }

.btn-submit { background: #6366f1; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; margin-top: 4px; }
.btn-submit:hover { background: #4f46e5; }
.btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }
</style>
