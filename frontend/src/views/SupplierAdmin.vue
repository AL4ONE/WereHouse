<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { adminNavLinks as navLinks } from '@/config/navLinks'

const barangs = ref([])
const suppliers = ref([])
const form = ref({ name: '', email: '', phone: '', alamat: '', barang_ids: [] })
const msg = ref({ text: '', type: '' })
const isLoading = ref(false)
const isSubmitting = ref(false)
const showModal = ref(false)
const isEditing = ref(false)
const editId = ref(null)

const searchQuery = ref('')

const filteredSuppliers = computed(() => {
  if (!searchQuery.value) return suppliers.value
  const q = searchQuery.value.toLowerCase()
  return suppliers.value.filter(s => 
    s.name.toLowerCase().includes(q) || 
    s.email.toLowerCase().includes(q) ||
    s.phone.includes(q) ||
    (s.alamat && s.alamat.toLowerCase().includes(q))
  )
})

async function fetchBarangs() {
  try { 
    const res = await api.get('/products'); 
    barangs.value = res.data.data 
  } 
  catch (e) { console.log(e) }
}

async function fetchSuppliers() {
  isLoading.value = true
  try {
    const res = await api.get('/suppliers')
    suppliers.value = res.data.data
  } catch (e) {
    console.log(e)
  } finally {
    isLoading.value = false
  }
}

function openCreate() {
  isEditing.value = false
  editId.value = null
  form.value = { name: '', email: '', phone: '', alamat: '', barang_ids: [] }
  showModal.value = true
}

function openEdit(supplier) {
  isEditing.value = true
  editId.value = supplier.id
  form.value = { 
    name: supplier.name, 
    email: supplier.email, 
    phone: supplier.phone, 
    alamat: supplier.alamat || '',
    barang_ids: supplier.barangs ? supplier.barangs.map(b => b.id) : [] 
  }
  showModal.value = true
}

async function handleSubmit() {
  msg.value = { text: '', type: '' }
  isSubmitting.value = true
  try {
    if (isEditing.value) {
      await api.post(`/suppliers/${editId.value}`, form.value)
      msg.value = { text: 'Supplier berhasil diupdate!', type: 'ok' }
    } else {
      await api.post('/suppliers', form.value)
      msg.value = { text: 'Supplier berhasil ditambahkan!', type: 'ok' }
    }
    showModal.value = false
    fetchSuppliers()
  } 
  catch (e) { 
    console.log(e) 
    msg.value = { text: 'Terjadi kesalahan sistem.', type: 'error' }
  }
  finally { 
    isSubmitting.value = false 
  }
}

async function handleDelete(id) {
  if (!confirm('Yakin ingin menghapus supplier ini?')) return
  try {
    await api.delete(`/suppliers/${id}`)
    msg.value = { text: 'Supplier berhasil dihapus!', type: 'ok' }
    fetchSuppliers()
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
      <div>
        <h1 class="page-title">Data <span class="gradient-text">Supplier</span></h1>
        <p class="page-desc">Kelola daftar supplier dan produk yang mereka suplai</p>
      </div>
      <div>
        <button class="btn-primary" @click="openCreate">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Tambah Supplier
        </button>
      </div>
    </div>

    <div v-if="msg.text" class="alert" :class="msg.type">
      <svg v-if="msg.type==='ok'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      {{ msg.text }}
    </div>

    <div class="glass-card">
      <div class="table-toolbar">
        <div class="search-box">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" v-model="searchQuery" placeholder="Cari nama, email, atau telepon..." />
        </div>
      </div>

      <div class="table-card">
        <div v-if="isLoading" class="loading">
          <div class="spinner-lg"></div>
          <span>Memuat data...</span>
        </div>
        <table v-else>
          <thead>
            <tr>
              <th>No</th>
              <th>Nama Supplier</th>
              <th>Kontak</th>
              <th>Produk (Barang)</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(sup, i) in filteredSuppliers" :key="sup.id">
              <td class="num-cell">{{ i + 1 }}</td>
              <td class="name-cell">{{ sup.name }}</td>
              <td>
                <div class="contact-info">
                  <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg> {{ sup.email }}</span>
                  <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg> {{ sup.phone }}</span>
                  <span v-if="sup.alamat"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> {{ sup.alamat }}</span>
                </div>
              </td>
              <td>
                <span v-if="sup.barangs?.length" class="badge-row">
                  <span v-for="b in sup.barangs" :key="b.id" class="badge">{{ b.name }}</span>
                </span>
                <span v-else class="muted">Belum ada</span>
              </td>
              <td class="actions">
                <button class="chip-btn teal" @click="openEdit(sup)">Edit</button>
                <button class="chip-btn red" @click="handleDelete(sup.id)">Hapus</button>
              </td>
            </tr>
            <tr v-if="filteredSuppliers.length === 0 && !isLoading">
              <td colspan="5" class="empty-state">Tidak ada supplier yang ditemukan.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <transition name="modal">
      <div v-if="showModal" class="overlay" @click.self="showModal = false">
        <div class="modal">
          <div class="modal-header">
            <h2>{{ isEditing ? 'Edit Supplier' : 'Tambah Supplier' }}</h2>
            <button class="modal-close" @click="showModal = false">✕</button>
          </div>
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
              <label>Alamat</label>
              <textarea v-model="form.alamat" placeholder="Jl. Sudirman No. 1, Jakarta" required></textarea>
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
            <div class="modal-btns">
              <button type="button" class="btn-ghost" @click="showModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="isSubmitting">
                <span v-if="isSubmitting" class="spinner"></span>
                {{ isSubmitting ? 'Menyimpan...' : 'Simpan Supplier' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>
  </DashboardLayout>
</template>

<style scoped>
.top-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
.page-title { font-size: 26px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; }
.gradient-text { background: linear-gradient(135deg, var(--accent), #f59e0b); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.page-desc { font-size: 14px; color: var(--text-muted); }

.btn-primary {
  display: flex; align-items: center; gap: 6px;
  background: linear-gradient(135deg, var(--accent), #f59e0b); color: #fff; border: none;
  padding: 10px 18px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit;
  box-shadow: 0 2px 12px rgba(249,115,22,0.25);
}
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 20px rgba(249,115,22,0.35); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

.alert { padding: 12px 16px; border-radius: var(--radius-sm); font-size: 13px; margin-bottom: 18px; }
.alert.ok { background: var(--success-bg); color: var(--success); border: 1px solid rgba(52,211,153,0.15); }
.alert.error { background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(251,113,133,0.15); }

.glass-card { background: var(--bg-surface); border: 1px solid var(--border-default); border-radius: var(--radius-lg); overflow: hidden; }

.table-toolbar {
  padding: 16px 20px;
  border-bottom: 1px solid var(--border-default);
  background: var(--bg-elevated);
}
.search-box {
  position: relative;
  max-width: 300px;
}
.search-box svg {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
}
.search-box input {
  width: 100%;
  padding: 9px 12px 9px 36px;
  border: 1px solid var(--border-default);
  border-radius: var(--radius-sm);
  background: var(--bg-base);
  color: var(--text-primary);
  font-size: 13px;
  outline: none;
}
.search-box input:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px var(--accent-glow);
}

.table-card { overflow-x: auto; }

.loading { text-align: center; padding: 48px; color: var(--text-muted); display: flex; flex-direction: column; align-items: center; gap: 12px; }
.spinner-lg { width: 28px; height: 28px; border: 3px solid var(--border-default); border-top-color: var(--accent); border-radius: 50%; animation: spin 0.7s linear infinite; }

table { width: 100%; border-collapse: collapse; }
thead { background: var(--bg-elevated); }
th { text-align: left; padding: 14px 16px; font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; font-weight: 600; }
td { padding: 14px 16px; font-size: 13px; color: var(--text-secondary); border-top: 1px solid var(--border-subtle); vertical-align: top; }
tbody tr { transition: background 0.15s; }
tbody tr:hover { background: var(--bg-elevated); }

.num-cell { font-weight: 700; font-variant-numeric: tabular-nums; }
.name-cell { color: var(--text-primary); font-weight: 600; }

.contact-info { display: flex; flex-direction: column; gap: 4px; font-size: 12px; color: var(--text-secondary); }
.contact-info span { display: flex; align-items: center; gap: 6px; }

.badge-row { display: flex; flex-wrap: wrap; gap: 4px; }
.badge { font-size: 11px; background: rgba(249,115,22,0.1); color: var(--accent); padding: 3px 10px; border-radius: 99px; font-weight: 500; }
.muted { color: var(--text-muted); font-style: italic; font-size: 12px; }
.empty-state { text-align: center; color: var(--text-muted); padding: 32px !important; }

.actions { display: flex; gap: 6px; flex-wrap: wrap; }
.chip-btn {
  border: none; padding: 5px 12px; border-radius: 99px; font-size: 11px; font-weight: 600; cursor: pointer; font-family: inherit;
  transition: all 0.2s;
}
.chip-btn.teal { background: rgba(20,184,166,0.1); color: #14b8a6; }
.chip-btn.teal:hover { background: rgba(20,184,166,0.2); }
.chip-btn.red { background: var(--danger-bg); color: var(--danger); }
.chip-btn.red:hover { background: rgba(251,113,133,0.2); }

/* Modal */
.overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 100; }
.modal {
  background: var(--bg-surface); border: 1px solid var(--border-default);
  border-radius: var(--radius-xl); padding: 28px; width: 100%; max-width: 460px;
  box-shadow: var(--shadow-lg);
}
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.modal h2 { font-size: 18px; color: var(--text-primary); font-weight: 700; }
.modal-close { background: var(--bg-elevated); border: none; color: var(--text-muted); width: 32px; height: 32px; border-radius: 8px; cursor: pointer; font-size: 14px; }
.modal-close:hover { color: var(--text-primary); background: var(--bg-hover); }

.modal-enter-active, .modal-leave-active { transition: all 0.25s; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from .modal, .modal-leave-to .modal { transform: scale(0.95) translateY(10px); }

.field { margin-bottom: 16px; }
.field label { display: block; font-size: 13px; color: var(--text-secondary); margin-bottom: 6px; font-weight: 500; }
.field input:not([type="checkbox"]), .field textarea {
  width: 100%; padding: 10px 14px; background: var(--bg-base); border: 1px solid var(--border-default);
  border-radius: var(--radius-sm); color: var(--text-primary); font-size: 14px; font-family: inherit; outline: none;
}
.field textarea { resize: vertical; min-height: 80px; }
.field input:not([type="checkbox"]):focus, .field textarea:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }
.field input::placeholder, .field textarea::placeholder { color: var(--text-muted); }

.checkbox-group { display: flex; flex-wrap: wrap; gap: 6px; padding: 10px; background: var(--bg-base); border: 1px solid var(--border-default); border-radius: var(--radius-sm); max-height: 160px; overflow-y: auto; }
.checkbox-item { display: flex; align-items: center; gap: 6px; padding: 5px 12px; background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: 99px; font-size: 12px; color: var(--text-primary); cursor: pointer; }
.checkbox-item:hover { border-color: var(--border-strong); }
.checkbox-item input[type="checkbox"] { accent-color: var(--accent-bold); }
.hint { font-size: 11px; color: var(--text-muted); margin-top: 6px; display: block; }

.modal-btns { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
.btn-ghost {
  background: var(--bg-elevated); color: var(--text-secondary); border: 1px solid var(--border-default);
  padding: 9px 18px; border-radius: var(--radius-sm); font-size: 13px; cursor: pointer; font-family: inherit;
}
.btn-ghost:hover { border-color: var(--border-strong); color: var(--text-primary); }

.spinner { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
