<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { useAuthStore } from '@/stores/auth'
import { adminNavLinks, petugasNavLinks } from '@/config/navLinks'

const authStore = useAuthStore()
const isAdmin = authStore.userRole === 'Admin'
const navLinks = isAdmin ? adminNavLinks : petugasNavLinks

const barangs = ref([])
const allSuppliers = ref([])
const isLoading = ref(false)
const showModal = ref(false)
const isEditing = ref(false)
const isOpNaming = ref(false)
const isAssigning = ref(false)
const editId = ref(null)
const msg = ref({ text: '', type: '' })
const form = ref({ name: '', stock: 0, satuan: '', supplier_ids: [] })
const formOpName = ref({ stock: 0, keterangan: '', tipe: '' })
const formAssign = ref({ supplier_ids: [] })

async function fetchBarangs() {
  isLoading.value = true
  try { const res = await api.get('/barangs'); barangs.value = res.data.data }
  catch (e) { 
    console.log(e) 
  } 
  finally { 
    isLoading.value = false 
  }
}
async function fetchSuppliers() {
  if (!isAdmin) return
  try { 
    const res = await api.get('/suppliers'); 
    allSuppliers.value = res.data.data || [] 
  }
  catch (e) { 
    console.log(e) 
  }
}

function openCreate() { 
  isEditing.value = false; 
  isOpNaming.value = false; 
  isAssigning.value = false; 
  editId.value = null; 
  form.value = { name: '', stock: 0, satuan: '', supplier_ids: [] }; 
  showModal.value = true 
}
function openOpName(p) { 
  isEditing.value = false; 
  isOpNaming.value = true; 
  isAssigning.value = false; 
  editId.value = p.id; 
  formOpName.value = { stock: 0, keterangan: '', tipe: 'pengurangan' }; 
  showModal.value = true 
}
function openAssignSupplier(p) { 
  isEditing.value = false; 
  isOpNaming.value = false; 
  isAssigning.value = true; 
  editId.value = p.id; 
  formAssign.value = { supplier_ids: p.suppliers ? p.suppliers.map(s => s.id) : [] }; 
  showModal.value = true 
}

async function handleSubmit() {
  try {
    if (isOpNaming.value) { 
      await api.post(`/barang/${editId.value}/opName`, formOpName.value); 
      msg.value = { text: 'Opname berhasil ditambahkan!', type: 'ok' } 
    }
    else if (isAssigning.value) { 
      await api.post(`/barang/${editId.value}/suppliers`, formAssign.value); 
      msg.value = { text: 'Supplier berhasil di-assign!', type: 'ok' } 
    }
    else if (isEditing.value) { 
      await api.post(`/barang/${editId.value}`, form.value); 
      msg.value = { text: 'Barang diupdate!', type: 'ok' } 
    }
    else { 
      const payload = { name: form.value.name, stock_awal: form.value.stock, stock_saat_ini: form.value.stock, satuan: form.value.satuan, supplier_ids: form.value.supplier_ids }
      await api.post('/barang', payload); 
      msg.value = { text: 'Barang ditambahkan!', type: 'ok' } 
    }
    showModal.value = false; fetchBarangs()
  } catch (e) { console.log(e) }
}

async function handleDelete(id) {
  if (!confirm('Hapus barang ini?')) return
  try { 
    await api.delete(`/barang/${id}`); 
    msg.value = { text: 'Barang dihapus!', type: 'ok' }; 
    fetchBarangs() 
  }
  catch (e) { 
    console.log(e) 
  }
}

onMounted(() => { 
  fetchBarangs(); 
  fetchSuppliers() 
})
</script>

<template>
  <DashboardLayout :navLinks="navLinks">
    <div class="top-row">
      <div>
        <h1 class="page-title">Data <span class="gradient-text">Barang</span></h1>
      </div>
      <div v-if="isAdmin">
        <button class="btn-primary" @click="openCreate">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Tambah Barang
        </button>
      </div>
    </div>

    <div v-if="msg.text" class="alert" :class="msg.type">{{ msg.text }}</div>

    <div class="glass-card table-card">
      <div v-if="isLoading" class="loading">
        <div class="spinner-lg"></div>
        <span>Memuat data...</span>
      </div>
      <table v-else>
        <thead>
          <tr>
            <th>No</th><th>Nama Barang</th><th>Stok Awal</th><th>Stok Saat Ini</th><th>Satuan</th>
            <th v-if="isAdmin">Supplier</th><th>Riwayat Opname</th><th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(p, i) in barangs" :key="p.id">
            <td class="num-cell">{{ i + 1 }}</td>
            <td class="name-cell">{{ p.name }}</td>
            <td class="num-cell">{{ p.stock_awal }}</td>
            <td class="num-cell" :class="p.stock_saat_ini <= 5 ? 'err' : 'ok'">
              <span class="stock-pill" :class="p.stock_saat_ini <= 5 ? 'low' : ''">{{ p.stock_saat_ini }}</span>
            </td>
            <td>{{ p.satuan }}</td>
            <td v-if="isAdmin">
              <span v-if="p.suppliers?.length" class="badge-row">
                <span v-for="s in p.suppliers" :key="s.id" class="badge">{{ s.name }}</span>
              </span>
              <span v-else class="muted">Belum ada</span>
            </td>
            <td>
              <div v-for="op in p.status_op_names" :key="op.id" class="op-item">
                <span :class="op.tipe === 'penambahan' ? 'ok' : 'err'">{{ op.tipe === 'penambahan' ? '+' : '-' }}{{ op.stock }}</span>
                <small class="op-ket">({{ op.keterangan }})</small>
              </div>
              <span v-if="!p.status_op_names?.length" class="muted">Belum ada</span>
            </td>
            <td class="actions">
              <button v-if="isAdmin" class="chip-btn amber" @click="openAssignSupplier(p)">Supplier</button>
              <button class="chip-btn teal" @click="openOpName(p)">Opname</button>
              <button v-if="isAdmin" class="chip-btn red" @click="handleDelete(p.id)">Hapus</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <transition name="modal">
      <div v-if="showModal" class="overlay" @click.self="showModal = false">
        <div class="modal">
          <div class="modal-header">
            <h2>{{ isAssigning ? 'Assign Supplier' : (isOpNaming ? 'Tambah Opname' : (isEditing ? 'Edit Produk' : 'Tambah Produk')) }}</h2>
            <button class="modal-close" @click="showModal = false">✕</button>
          </div>

          <form v-if="isOpNaming" @submit.prevent="handleSubmit">
            <div class="field"><label>Jumlah Stok</label><input v-model.number="formOpName.stock" type="number" min="1" required /></div>
            <div class="field"><label>Tipe</label>
              <select v-model="formOpName.tipe" required><option value="penambahan">Penambahan (+)</option><option value="pengurangan">Pengurangan (-)</option></select>
            </div>
            <div class="field"><label>Keterangan</label><input v-model="formOpName.keterangan" placeholder="Alasan opname" required /></div>
            <div class="modal-btns"><button type="button" class="btn-ghost" @click="showModal = false">Batal</button><button type="submit" class="btn-primary">Simpan Opname</button></div>
          </form>

          <form v-else-if="isAssigning" @submit.prevent="handleSubmit">
            <div class="field"><label>Pilih Supplier</label>
              <div class="checkbox-group"><label v-for="sup in allSuppliers" :key="sup.id" class="checkbox-item"><input type="checkbox" :value="sup.id" v-model="formAssign.supplier_ids" /><span>{{ sup.name }}</span></label></div>
              <span v-if="!allSuppliers.length" class="hint">Belum ada supplier. Tambah supplier dulu.</span>
            </div>
            <div class="modal-btns"><button type="button" class="btn-ghost" @click="showModal = false">Batal</button><button type="submit" class="btn-primary">Simpan</button></div>
          </form>

          <form v-else @submit.prevent="handleSubmit">
            <div class="field"><label>Nama Barang</label><input v-model="form.name" placeholder="Nama produk" required /></div>
            <div class="field"><label>Stock</label><input v-model.number="form.stock" type="number" min="0" required /></div>
            <div class="field"><label>Satuan</label><input v-model="form.satuan" placeholder="pcs, kg, liter, dll" required /></div>
            <div v-if="isAdmin" class="field"><label>Supplier (opsional)</label>
              <div class="checkbox-group"><label v-for="sup in allSuppliers" :key="sup.id" class="checkbox-item"><input type="checkbox" :value="sup.id" v-model="form.supplier_ids" /><span>{{ sup.name }}</span></label></div>
              <span v-if="!allSuppliers.length" class="hint">Belum ada supplier.</span>
            </div>
            <div class="modal-btns"><button type="button" class="btn-ghost" @click="showModal = false">Batal</button><button type="submit" class="btn-primary">Simpan</button></div>
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

.alert { padding: 12px 16px; border-radius: var(--radius-sm); font-size: 13px; margin-bottom: 18px; }
.alert.ok { background: var(--success-bg); color: var(--success); border: 1px solid rgba(52,211,153,0.15); }

.glass-card { background: var(--bg-surface); border: 1px solid var(--border-default); border-radius: var(--radius-lg); overflow: hidden; }
.table-card { overflow-x: auto; }

.loading { text-align: center; padding: 48px; color: var(--text-muted); display: flex; flex-direction: column; align-items: center; gap: 12px; }
.spinner-lg { width: 28px; height: 28px; border: 3px solid var(--border-default); border-top-color: var(--accent); border-radius: 50%; animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

table { width: 100%; border-collapse: collapse; }
thead { background: var(--bg-elevated); }
th { text-align: left; padding: 14px 16px; font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; font-weight: 600; }
td { padding: 14px 16px; font-size: 13px; color: var(--text-secondary); border-top: 1px solid var(--border-subtle); vertical-align: top; }
tbody tr { transition: background 0.15s; }
tbody tr:hover { background: var(--bg-elevated); }

.num-cell { font-weight: 700; font-variant-numeric: tabular-nums; }
.name-cell { color: var(--text-primary); font-weight: 500; }
.ok { color: var(--success); }
.err { color: var(--danger); }

.stock-pill {
  display: inline-flex; padding: 3px 10px; border-radius: 99px; font-size: 12px; font-weight: 700;
  background: var(--success-bg); color: var(--success);
}
.stock-pill.low { background: var(--danger-bg); color: var(--danger); }

.badge-row { display: flex; flex-wrap: wrap; gap: 4px; }
.badge { font-size: 11px; background: rgba(249,115,22,0.1); color: var(--accent); padding: 3px 10px; border-radius: 99px; font-weight: 500; }
.muted { color: var(--text-muted); font-style: italic; font-size: 12px; }

.actions { display: flex; gap: 6px; flex-wrap: wrap; }
.chip-btn {
  border: none; padding: 5px 12px; border-radius: 99px; font-size: 11px; font-weight: 600; cursor: pointer; font-family: inherit;
  transition: all 0.2s;
}
.chip-btn.teal { background: rgba(20,184,166,0.1); color: #14b8a6; }
.chip-btn.teal:hover { background: rgba(20,184,166,0.2); }
.chip-btn.amber { background: var(--warning-bg); color: var(--warning); }
.chip-btn.amber:hover { background: rgba(251,191,36,0.2); }
.chip-btn.red { background: var(--danger-bg); color: var(--danger); }
.chip-btn.red:hover { background: rgba(251,113,133,0.2); }

.op-item { font-size: 11px; display: flex; gap: 4px; margin-bottom: 3px; align-items: center; }
.op-ket { color: var(--text-muted); }

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
.field input:not([type="checkbox"]), .field select {
  width: 100%; padding: 10px 14px; background: var(--bg-base); border: 1px solid var(--border-default);
  border-radius: var(--radius-sm); color: var(--text-primary); font-size: 14px; font-family: inherit; outline: none;
}
.field input:not([type="checkbox"]):focus, .field select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }
.field select option { background: var(--bg-surface); color: var(--text-primary); }
.field input::placeholder { color: var(--text-muted); }

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
</style>
