<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import api from '@/api'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { useAuthStore } from '@/stores/auth'
import { adminNavLinks, petugasNavLinks } from '@/config/navLinks'

const authStore = useAuthStore()
const navLinks = authStore.userRole === 'Admin' ? adminNavLinks : petugasNavLinks

const barangs = ref([])
const inventoryIns = ref([])
const msg = ref({ text: '', type: '' })
const isSubmitting = ref(false)
const editId = ref(null)

const form = ref({ barang_id: '', supplier_id: '', stock: 1 })
const barcodeInput = ref('')
const stockInput = ref(null)

function handleBarcodeScan() {
  if (!barcodeInput.value) return
  const match = barangs.value.find(b => b.kode_barang === barcodeInput.value)
  if (match) {
    form.value.barang_id = match.id
    barcodeInput.value = ''
    msg.value = { text: `Barcode terdeteksi: ${match.name}`, type: 'ok' }
    // Focus quantity field after selecting product
    setTimeout(() => {
      stockInput.value?.focus()
    }, 100)
  } else {
    msg.value = { text: 'Barcode tidak terdaftar!', type: 'error' }
  }
}

const filteredSuppliers = computed(() => {
  if (!form.value.barang_id) return []
  const barang = barangs.value.find(b => b.id === form.value.barang_id)
  return barang?.suppliers || []
})

const selectedBarang = computed(() => {
  return barangs.value.find(b => b.id === form.value.barang_id) || null
})

const totalHarga = computed(() => {
  if (!selectedBarang.value) return 0
  return selectedBarang.value.harga * form.value.stock
})

function formatRupiah(n) {
  return 'Rp ' + Number(n).toLocaleString('id-ID')
}

watch(() => form.value.barang_id, () => { 
  if(!editId.value) form.value.supplier_id = '' 
})

async function fetchBarangs() {
  try { 
    const res = await api.get('/products'); 
    barangs.value = res.data.data 
  } 
  catch (e) { console.log(e) }
}

async function fetchInventoryIn() {
  try { 
    const res = await api.get('/inventory-in'); 
    inventoryIns.value = res.data.data 
  } 
  catch (e) { console.log(e) }
}

async function handleSubmit() {
  msg.value = { text: '', type: '' }
  isSubmitting.value = true
  try {
    if (editId.value) {
      await api.post(`/inventory-in/${editId.value}`, form.value)
      msg.value = { text: 'Data barang masuk berhasil diupdate!', type: 'ok' }
      editId.value = null
    } else {
      await api.post('/inventory-in', form.value)
      msg.value = { text: 'Catatan barang masuk berhasil ditambahkan!', type: 'ok' }
    }
    form.value = { barang_id: '', supplier_id: '', stock: 1 }
    fetchBarangs()
    fetchInventoryIn()
  } catch (e) { 
    console.log(e)
    msg.value = { text: e.response?.data?.error ? 'Validasi error, periksa kembali inputan' : 'Terjadi kesalahan sistem', type: 'error' }
  } finally { isSubmitting.value = false }
}

function handleEdit(item) {
  editId.value = item.id
  form.value = {
    barang_id: item.barang_id,
    supplier_id: item.supplier_id,
    stock: item.stock
  }
}

async function handleDelete(id) {
  if(!confirm('Yakin ingin menghapus data ini? Stok barang akan disesuaikan kembali.')) return;
  msg.value = { text: '', type: '' }
  try {
    await api.delete(`/inventory-in/${id}`)
    msg.value = { text: 'Data barang masuk berhasil dihapus!', type: 'ok' }
    fetchBarangs()
    fetchInventoryIn()
  } catch (e) {
    console.log(e)
    msg.value = { text: 'Gagal menghapus data', type: 'error' }
  }
}

function cancelEdit() {
  editId.value = null
  form.value = { barang_id: '', supplier_id: '', stock: 1 }
}

onMounted(() => { 
  fetchBarangs() 
  fetchInventoryIn()
})
</script>

<template>
  <DashboardLayout :navLinks="navLinks">
    <div class="hero">
      <h1>Barang <span class="gradient-text">Masuk</span></h1>
    </div>

    <div v-if="msg.text" class="alert" :class="msg.type">
      <svg v-if="msg.type==='ok'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      {{ msg.text }}
    </div>

    <div class="content-grid">
      <div class="glass-card form-card">
        <h3 class="card-title">{{ editId ? 'Edit Barang Masuk' : 'Tambah Barang Masuk' }}</h3>
        <form @submit.prevent="handleSubmit">
          <div class="field barcode-scan-field">
            <label>Scan Barcode (Opsional)</label>
            <div class="input-with-icon">
              <svg class="scan-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V5a2 2 0 0 1 2-2h2"></path><path d="M17 3h2a2 2 0 0 1 2 2v2"></path><path d="M21 17v2a2 2 0 0 1-2 2h-2"></path><path d="M7 21H5a2 2 0 0 1-2-2v-2"></path><line x1="8" y1="12" x2="16" y2="12"></line></svg>
              <input v-model="barcodeInput" @keydown.enter.prevent="handleBarcodeScan" placeholder="Klik & scan di sini..." />
            </div>
            <small class="hint">Arahkan scanner ke barcode lalu tekan enter</small>
          </div>
          <div class="field">
            <label>Produk</label>
            <select v-model="form.barang_id" required>
              <option value="" disabled>-- Pilih Produk --</option>
              <option v-for="p in barangs" :key="p.id" :value="p.id">{{ p.name }} (stok: {{ p.stock_saat_ini }})</option>
            </select>
          </div>
          <div class="field">
            <label>Supplier</label>
            <select v-model="form.supplier_id" required :disabled="!form.barang_id">
              <option value="" disabled>{{ form.barang_id ? '-- Pilih Supplier --' : '-- Pilih Produk dulu --' }}</option>
              <option v-for="sup in filteredSuppliers" :key="sup.id" :value="sup.id">{{ sup.name }}</option>
            </select>
          </div>
          <div class="field">
            <label>Jumlah Barang Masuk</label>
            <input ref="stockInput" v-model.number="form.stock" type="number" min="1" required />
          </div>
          <div v-if="selectedBarang" class="pricing-info">
            <p>Harga per barang: <strong>{{ formatRupiah(selectedBarang.harga) }}</strong></p>
            <p>Total Harga: <strong>{{ formatRupiah(totalHarga) }}</strong></p>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn-submit orange" :disabled="isSubmitting">
              <span v-if="isSubmitting" class="spinner"></span>
              {{ isSubmitting ? 'Memproses...' : (editId ? 'Update' : 'Simpan Barang Masuk') }}
            </button>
            <button v-if="editId" type="button" class="btn-cancel" @click="cancelEdit" :disabled="isSubmitting">
              Batal
            </button>
          </div>
        </form>
      </div>

      <div class="glass-card table-section">
        <h3 class="card-title">Riwayat Barang Masuk</h3>
        <div class="table-container">
          <table class="inventory-table">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Produk</th>
                <th>Supplier</th>
                <th>Harga Satuan</th>
                <th>Jumlah Masuk</th>
                <th>Total Harga</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in inventoryIns" :key="item.id">
                <td>{{ new Date(item.created_at).toLocaleDateString() }}</td>
                <td>{{ item.barang?.name || 'Unknown' }}</td>
                <td>{{ item.supplier?.name || 'Unknown' }}</td>
                <td>{{ formatRupiah(item.harga_satuan) }}</td>
                <td><span class="badge increment">+{{ item.stock }}</span></td>
                <td style="font-weight: bold;">{{ formatRupiah(item.total_harga) }}</td>
                <td>
                  <div class="actions">
                    <button class="btn-icon edit" @click="handleEdit(item)" title="Edit">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </button>
                    <button class="btn-icon delete" @click="handleDelete(item.id)" title="Hapus">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="inventoryIns.length === 0">
                <td colspan="7" class="empty-state">Belum ada data barang masuk.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
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

.content-grid {
  display: grid;
  grid-template-columns: 350px 1fr;
  gap: 20px;
  align-items: start;
}
@media (max-width: 900px) {
  .content-grid { grid-template-columns: 1fr; }
}

.glass-card {
  background: var(--bg-surface); border: 1px solid var(--border-default);
  border-radius: var(--radius-lg); overflow: hidden; padding: 24px;
}

.card-title {
  font-size: 16px; font-weight: 700; color: var(--text-primary); margin-bottom: 20px;
}

.field { margin-bottom: 18px; }
.field label { display: block; font-size: 13px; color: var(--text-secondary); margin-bottom: 8px; font-weight: 500; }
.field input, .field select {
  width: 100%; padding: 11px 14px; background: var(--bg-base); border: 1px solid var(--border-default);
  border-radius: var(--radius-sm); color: var(--text-primary); font-size: 14px; font-family: inherit; outline: none;
}
.field input:focus, .field select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }
.field select option { background: var(--bg-surface); color: var(--text-primary); }

.form-actions { display: flex; gap: 10px; margin-top: 20px; }

.btn-submit {
  flex: 1; border: none; padding: 12px 20px; border-radius: var(--radius-sm);
  font-size: 14px; font-weight: 600; cursor: pointer; font-family: inherit; color: #fff;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-submit.orange {
  background: linear-gradient(135deg, var(--accent), #f59e0b);
  box-shadow: 0 2px 12px var(--accent-glow);
}
.btn-submit.orange:hover { transform: translateY(-1px); box-shadow: 0 4px 20px var(--accent-glow); }
.btn-submit:disabled, .btn-cancel:disabled { opacity: 0.6; cursor: not-allowed; transform: none !important; }

.btn-cancel {
  padding: 12px 20px; border: 1px solid var(--border-default); background: var(--bg-surface);
  border-radius: var(--radius-sm); color: var(--text-primary); font-size: 14px; font-weight: 600;
  cursor: pointer; transition: all 0.2s ease;
}
.btn-cancel:hover { background: var(--bg-base); }

.spinner { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Table Styles */
.table-container { overflow-x: auto; margin-top: 10px; }
.inventory-table {
  width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;
}
.inventory-table th { padding: 12px 16px; color: var(--text-secondary); font-weight: 600; border-bottom: 2px solid var(--border-default); white-space: nowrap; }
.inventory-table td { padding: 16px; color: var(--text-primary); border-bottom: 1px solid var(--border-default); vertical-align: middle; }
.inventory-table tbody tr:hover { background: rgba(255,255,255,0.02); }

.badge.increment { background: rgba(52,211,153,0.1); color: var(--success); padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 600; border: 1px solid rgba(52,211,153,0.2); }

.empty-state { text-align: center; color: var(--text-muted); padding: 32px !important; }

.actions { display: flex; gap: 8px; }
.btn-icon { width: 32px; height: 32px; border-radius: var(--radius-sm); border: 1px solid var(--border-default); background: var(--bg-base); color: var(--text-secondary); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; }
.btn-icon:hover { color: var(--text-primary); border-color: var(--text-primary); }
.btn-icon.edit:hover { color: var(--accent); border-color: var(--accent); background: rgba(249,115,22,0.1); }
.btn-icon.delete:hover { border-color: var(--danger); background: var(--danger-bg); color: var(--danger); }

.pricing-info {
  margin: 16px 0;
  padding: 12px;
  background: rgba(249,115,22,0.05);
  border: 1px solid rgba(249,115,22,0.15);
  border-radius: var(--radius-sm);
}
.pricing-info p {
  margin: 0 0 4px;
  font-size: 13px;
  color: var(--text-secondary);
}
.pricing-info strong {
  color: var(--accent);
}

.barcode-scan-field {
  margin-bottom: 24px;
  padding: 12px;
  background: var(--bg-elevated);
  border: 1px dashed var(--border-strong);
  border-radius: var(--radius-md);
}
.input-with-icon { position: relative; display: flex; align-items: center; }
.scan-icon { position: absolute; left: 12px; color: var(--accent); opacity: 0.7; }
.input-with-icon input { padding-left: 40px !important; border-style: solid !important; background: var(--bg-surface) !important; }

.hint { font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block; }
</style>
