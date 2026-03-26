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
const allSuppliers = ref([])
const isLoading = ref(false)
const showModal = ref(false)
const isEditing = ref(false)
const isOpNaming = ref(false)
const isAssigning = ref(false)
const editId = ref(null)
const msg = ref({ text: '', type: '' })
const form = ref({ name: '', stock_awal: 0, stock_saat_ini: 0, satuan: '', supplier_ids: [] })
const formOpName = ref({ stock: 0, keterangan: '', tipe: '' })
const formAssign = ref({ supplier_ids: [] })

async function fetchBarangs() {
  isLoading.value = true
  try {
    const res = await api.get('/barangs')
    barangs.value = res.data.data
  } catch (e) {
    console.log(e)
  } finally { isLoading.value = false }
}

async function fetchSuppliers() {
  try {
    const res = await api.get('/suppliers')
    allSuppliers.value = res.data.data || []
  } catch (e) {
    console.log(e)
  }
}

function openCreate() {
  isEditing.value = false
  isOpNaming.value = false
  isAssigning.value = false
  editId.value = null
  form.value = { name: '', stock_awal: 0, stock_saat_ini: 0, satuan: '', supplier_ids: [] }
  showModal.value = true
}

function openOpName(p) {
  isEditing.value = false
  isOpNaming.value = true
  isAssigning.value = false
  editId.value = p.id
  formOpName.value = { stock: 0, keterangan: '', tipe: 'pengurangan' }
  showModal.value = true
}

function openAssignSupplier(p) {
  isEditing.value = false
  isOpNaming.value = false
  isAssigning.value = true
  editId.value = p.id
  formAssign.value = { supplier_ids: p.suppliers ? p.suppliers.map(s => s.id) : [] }
  showModal.value = true
}

async function handleSubmit() {
  try {
    if (isOpNaming.value) {
      await api.post(`/barang/${editId.value}/opName`, formOpName.value)
      msg.value = { text: 'Opname berhasil ditambahkan!', type: 'ok' }
    } else if (isAssigning.value) {
      await api.post(`/barang/${editId.value}/suppliers`, formAssign.value)
      msg.value = { text: 'Supplier berhasil di-assign!', type: 'ok' }
    } else if (isEditing.value) {
      await api.post(`/barang/${editId.value}`, form.value)
      msg.value = { text: 'Barang diupdate!', type: 'ok' }
    } else {
      await api.post('/barang', form.value)
      msg.value = { text: 'Barang ditambahkan!', type: 'ok' }
    }
    showModal.value = false
    fetchBarangs()
  } catch (e) {
    console.log(e)
  }
}

async function handleDelete(id) {
  if (!confirm('Hapus barang ini?')) return
  try {
    await api.delete(`/barang/${id}`)
    msg.value = { text: 'barang dihapus!', type: 'ok' }
    fetchBarangs()
  } catch (e) {
    console.log(e)
  }
}

onMounted(() => {
  fetchBarangs()
  fetchSuppliers()
})
</script>

<template>
  <DashboardLayout :navLinks="navLinks">
    <div class="top-row">
      <h1 class="page-title">barang</h1>
      <button class="btn-add" @click="openCreate">+ Tambah</button>
    </div>

    <div v-if="msg.text" class="alert" :class="msg.type">{{ msg.text }}</div>

    <div class="table-wrap">
      <div v-if="isLoading" class="empty">Memuat...</div>
      <table v-else>
        <thead><tr><th>No</th><th>Nama barang</th><th>Stok awal</th><th>Stok Saat ini</th><th>Satuan</th><th>Supplier</th><th>Riwayat Opname</th><th>Aksi</th></tr></thead>
        <tbody>
          <tr v-for="(p, i) in barangs" :key="p.id">
            <td>{{ i + 1 }}</td>
            <td>{{ p.name }}</td>
            <td><span class="stock">{{ p.stock_awal }}</span></td>
            <td><span class="stock" :class="p.stock_saat_ini <= 5 ? 'low' : ''">{{ p.stock_saat_ini }}</span></td>
            <td>{{ p.satuan }}</td>
            <td>
              <span v-if="p.suppliers?.length" class="supplier-list">
                <span v-for="s in p.suppliers" :key="s.id" class="badge">{{ s.name }}</span>
              </span>
              <span v-else class="muted">Belum ada</span>
            </td>
            <td>
              <div v-for="op in p.status_op_names" :key="op.id" class="op-item">
                <span :class="op.tipe === 'penambahan' ? 'text-ok' : 'text-err'">
                  {{ op.tipe === 'penambahan' ? '+' : '-' }}{{ op.stock }}
                </span>
                <small>({{ op.keterangan }})</small>
              </div>
              <span v-if="!p.status_op_names?.length" class="muted">Belum ada opname</span>
            </td>
            <td class="actions">
              <button class="btn-sm sup" @click="openAssignSupplier(p)">Supplier</button>
              <button class="btn-sm op" @click="openOpName(p)">Opname</button>
              <button class="btn-sm del" @click="handleDelete(p.id)">Hapus</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="overlay" @click.self="showModal = false">
      <div class="modal">
        <h2>{{ isAssigning ? 'Assign Supplier' : (isOpNaming ? 'Tambah Opname' : (isEditing ? 'Edit Produk' : 'Tambah Produk')) }}</h2>
        
        <!-- Opname Form -->
        <form v-if="isOpNaming" @submit.prevent="handleSubmit">
          <div class="field">
            <label>Jumlah Stok</label>
            <input v-model.number="formOpName.stock" type="number" min="1" required />
          </div>
          <div class="field">
            <label>Tipe</label>
            <select v-model="formOpName.tipe" required>
              <option value="penambahan">Penambahan (+)</option>
              <option value="pengurangan">Pengurangan (-)</option>
            </select>
          </div>
          <div class="field">
            <label>Keterangan</label>
            <input v-model="formOpName.keterangan" placeholder="Alasan opname" required />
          </div>
          <div class="modal-btns">
            <button type="button" class="btn-cancel" @click="showModal = false">Batal</button>
            <button type="submit" class="btn-add">Simpan Opname</button>
          </div>
        </form>

        <!-- Assign Supplier Form -->
        <form v-else-if="isAssigning" @submit.prevent="handleSubmit">
          <div class="field">
            <label>Pilih Supplier</label>
            <div class="checkbox-group">
              <label v-for="sup in allSuppliers" :key="sup.id" class="checkbox-item">
                <input type="checkbox" :value="sup.id" v-model="formAssign.supplier_ids" />
                <span>{{ sup.name }}</span>
              </label>
            </div>
            <span v-if="!allSuppliers.length" class="hint">Belum ada supplier. Tambah supplier dulu.</span>
          </div>
          <div class="modal-btns">
            <button type="button" class="btn-cancel" @click="showModal = false">Batal</button>
            <button type="submit" class="btn-add">Simpan</button>
          </div>
        </form>

        <!-- Create/Edit Product Form -->
        <form v-else @submit.prevent="handleSubmit">
          <div class="field">
            <label>Nama barang</label>
            <input v-model="form.name" placeholder="Nama produk" required />
          </div>
          <div class="field">
            <label>Stock awal</label>
            <input v-model.number="form.stock_awal" type="number" min="0" required />
          </div>
          <div class="field">
            <label>Stock saat ini</label>
            <input v-model.number="form.stock_saat_ini" type="number" min="0" required />
          </div>
          <div class="field">
            <label>satuan</label>
            <input v-model="form.satuan" placeholder="satuan product" required />
          </div>
          <div class="field">
            <label>Supplier (opsional)</label>
            <div class="checkbox-group">
              <label v-for="sup in allSuppliers" :key="sup.id" class="checkbox-item">
                <input type="checkbox" :value="sup.id" v-model="form.supplier_ids" />
                <span>{{ sup.name }}</span>
              </label>
            </div>
            <span v-if="!allSuppliers.length" class="hint">Belum ada supplier.</span>
          </div>
          <div class="modal-btns">
            <button type="button" class="btn-cancel" @click="showModal = false">Batal</button>
            <button type="submit" class="btn-add">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </DashboardLayout>
</template>

<style scoped>
.top-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-title { font-size: 22px; color: #f1f5f9; }

.btn-add { background: #6366f1; color: #fff; border: none; padding: 8px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-add:hover { background: #4f46e5; }

.alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
.alert.ok { background: rgba(34,197,94,0.1); color: #4ade80; border: 1px solid rgba(34,197,94,0.15); }
.alert.error { background: rgba(248,113,113,0.1); color: #f87171; border: 1px solid rgba(248,113,113,0.15); }

.table-wrap { background: #1e1e2e; border: 1px solid #2a2a3d; border-radius: 12px; overflow: hidden; }
table { width: 100%; border-collapse: collapse; }
thead { background: #252538; }
th { text-align: left; padding: 12px 16px; font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
td { padding: 12px 16px; font-size: 14px; color: #cbd5e1; border-top: 1px solid #2a2a3d; }
tr:hover { background: #252538; }
.empty { text-align: center; padding: 32px; color: #64748b; }
.actions { display: flex; gap: 8px; flex-wrap: wrap; }

.badge { font-size: 12px; background: rgba(99,102,241,0.12); color: #818cf8; padding: 3px 8px; border-radius: 5px; }
.supplier-list { display: flex; flex-wrap: wrap; gap: 4px; }
.stock { font-weight: 700; color: #4ade80; }
.stock.low { color: #f87171; }

.btn-sm { border: none; padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 500; cursor: pointer; }
.btn-sm.edit { background: rgba(99,102,241,0.12); color: #818cf8; }
.btn-sm.edit:hover { background: rgba(99,102,241,0.25); }
.btn-sm.del { background: rgba(248,113,113,0.12); color: #f87171; }
.btn-sm.del:hover { background: rgba(248,113,113,0.25); }
.btn-sm.op { background: rgba(16,185,129,0.12); color: #10b981; }
.btn-sm.op:hover { background: rgba(16,185,129,0.25); }
.btn-sm.sup { background: rgba(251,191,36,0.12); color: #fbbf24; }
.btn-sm.sup:hover { background: rgba(251,191,36,0.25); }

.op-item { font-size: 11px; display: flex; gap: 4px; margin-bottom: 2px; }
.text-ok { color: #4ade80; font-weight: 600; }
.text-err { color: #f87171; font-weight: 600; }
.muted { color: #64748b; font-style: italic; font-size: 12px; }

.overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 100; }
.modal { background: #1e1e2e; border: 1px solid #2a2a3d; border-radius: 14px; padding: 24px; width: 100%; max-width: 420px; }
.modal h2 { font-size: 18px; color: #f1f5f9; margin-bottom: 16px; }

.field { margin-bottom: 14px; }
.field label { display: block; font-size: 13px; color: #94a3b8; margin-bottom: 6px; }
.field input:not([type="checkbox"]), .field select { width: 100%; padding: 9px 12px; background: #13131f; border: 1px solid #363650; border-radius: 8px; color: #e2e8f0; font-size: 14px; outline: none; box-sizing: border-box; }
.field input:not([type="checkbox"]):focus, .field select:focus { border-color: #6366f1; }
.field select option { background: #1e1e2e; color: #e2e8f0; }

.checkbox-group { display: flex; flex-wrap: wrap; gap: 8px; padding: 8px; background: #13131f; border: 1px solid #363650; border-radius: 8px; max-height: 160px; overflow-y: auto; }
.checkbox-item { display: flex; align-items: center; gap: 6px; padding: 4px 10px; background: #1e1e2e; border: 1px solid #2a2a3d; border-radius: 6px; font-size: 13px; color: #e2e8f0; cursor: pointer; }
.checkbox-item input[type="checkbox"] { accent-color: #6366f1; }
.hint { font-size: 11px; color: #64748b; margin-top: 4px; display: block; }

.modal-btns { display: flex; gap: 10px; justify-content: flex-end; margin-top: 16px; }
.btn-cancel { background: #2a2a3d; color: #94a3b8; border: none; padding: 8px 16px; border-radius: 8px; font-size: 13px; cursor: pointer; }
</style>
