<script setup>
import { ref, computed, watch, onMounted } from 'vue'
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
const msg = ref({ text: '', type: '' })
const isLoading = ref(false)

const form = ref({
  barang_id: '',
  supplier_id: '',
  stock: 1,
})

// Computed: filter suppliers dari barang yang dipilih
const filteredSuppliers = computed(() => {
  if (!form.value.barang_id) return []
  const barang = barangs.value.find(b => b.id === form.value.barang_id)
  return barang?.suppliers || []
})

// Reset supplier_id ketika barang berubah
watch(() => form.value.barang_id, () => {
  form.value.supplier_id = ''
})

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
    const res = await api.post('/inventoryIn', form.value)
    msg.value = { text: `catatan barang masuk berhasil`, type: 'ok' }
    form.value.barang_id = ''
    form.value.supplier_id = ''
    form.value.stock = 1
    console.log(res.data)
    fetchBarangs()
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
    <h1 class="page-title">📥 Barang Masuk</h1>
    <p class="page-desc">Catat barang masuk ke gudang dari supplier</p>

    <div v-if="msg.text" class="alert" :class="msg.type">{{ msg.text }}</div>

    <div class="form-card">
      <form @submit.prevent="handleSubmit">
        <div class="field">
          <label>Produk</label>
          <select v-model="form.barang_id" required>
            <option value="" disabled>-- Pilih Produk --</option>
            <option v-for="p in barangs" :key="p.id" :value="p.id">
              {{ p.name }} (stok: {{ p.stock_saat_ini }})
            </option>
          </select>
        </div>
        <div class="field">
          <label>Supplier</label>
          <select v-model="form.supplier_id" required :disabled="!form.barang_id">
            <option value="" disabled>{{ form.barang_id ? '-- Pilih Supplier --' : '-- Pilih Produk dulu --' }}</option>
            <option v-for="sup in filteredSuppliers" :key="sup.id" :value="sup.id">
              {{ sup.name }}
            </option>
          </select>
        </div>
        <div class="field">
          <label>Jumlah barang Masuk</label>
          <input v-model.number="form.stock" type="number" min="1" required />
        </div>
        <button type="submit" class="btn-submit green" :disabled="isLoading">
          {{ isLoading ? 'Memproses...' : 'Simpan Barang Masuk' }}
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
.field input, .field select {
  width: 100%; padding: 9px 12px; background: #13131f; border: 1px solid #363650;
  border-radius: 8px; color: #e2e8f0; font-size: 14px; outline: none; box-sizing: border-box;
}
.field input:focus, .field select:focus { border-color: #6366f1; }
.field select option { background: #1e1e2e; color: #e2e8f0; }
.hint { font-size: 11px; color: #64748b; margin-top: 4px; display: block; }

.btn-submit { border: none; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; margin-top: 4px; color: #fff; }
.btn-submit.green { background: #22c55e; }
.btn-submit.green:hover { background: #16a34a; }
.btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }
</style>
