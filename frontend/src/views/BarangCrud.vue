<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'

const barangs = ref([])
const showModal = ref(false)
const isEditing = ref(false)
const editId = ref(null)
const msg = ref({ text: '', type: '' })
const form = ref({ title: '', description: '', harga: 0 })

function formatRupiah(n) {
  return 'Rp ' + Number(n).toLocaleString('id-ID')
}

async function fetchBarangs() {
  try {
    const res = await api.get('/products')
    barangs.value = res.data.data
  } catch (e) {
    console.log(e)
  }
}

function openCreate() {
  isEditing.value = false
  editId.value = null
  form.value = { title: '', description: '', harga: 0 }
  showModal.value = true
}

function openEdit(b) {
  isEditing.value = true
  editId.value = b.id
  form.value = { title: b.title, description: b.description, harga: b.harga }
  showModal.value = true
}

async function handleSubmit() {
  try {
    if (isEditing.value) {
      await api.put(`/products/${editId.value}`, {
        title: form.value.title,
        description: form.value.description,
        harga: form.value.harga
      })
      msg.value = { text: 'Barang diupdate!', type: 'ok' }
    } else {
      await api.post('/products', {
        title: form.value.title,
        description: form.value.description,
        harga: form.value.harga
      })
      msg.value = { text: 'Barang ditambahkan!', type: 'ok' }
    }
    showModal.value = false
    fetchBarangs()
  } catch (e) {
    msg.value = { text: 'Gagal menyimpan!', type: 'err' }
    console.log(e)
  }
}

async function handleDelete(id) {
  if (!confirm('Hapus barang ini?')) return
  try {
    await api.delete(`/products/${id}`)
    msg.value = { text: 'Barang dihapus!', type: 'ok' }
    fetchBarangs()
  } catch (e) {
    console.log(e)
  }
}

function handleLogout() {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  window.location.href = '/learning/login'
}

onMounted(() => {
  fetchBarangs()
})
</script>

<template>
  <div class="crud-page">
    <nav class="topnav">
      <div class="nav-left">
        <span class="brand-text">Belajar<strong>CRUD</strong></span>
        <div class="nav-divider"></div>
        <router-link to="/learning/login" class="nav-link">Login</router-link>
        <router-link to="/learning/register" class="nav-link">Register</router-link>
        <router-link to="/learning/products" class="nav-link active">Barang</router-link>
      </div>
      <button class="btn-logout" @click="handleLogout">Logout</button>
    </nav>

    <main class="page-content">
      <div class="top-row">
        <h1 class="page-title">Data <span class="gradient-text">Barang</span></h1>
        <button class="btn-primary" @click="openCreate">+ Tambah Barang</button>
      </div>

      <div v-if="msg.text" class="alert" :class="msg.type">{{ msg.text }}</div>

      <div class="glass-card">
        <table>
          <thead>
            <tr><th>No</th><th>Title</th><th>Deskripsi</th><th>Harga</th><th>Aksi</th></tr>
          </thead>
          <tbody>
            <tr v-for="(b, i) in barangs" :key="b.id">
              <td>{{ i + 1 }}</td>
              <td class="name-cell">{{ b.title }}</td>
              <td>{{ b.description }}</td>
              <td class="price-cell"><span class="price-pill">{{ formatRupiah(b.harga) }}</span></td>
              <td class="actions">
                <button class="chip-btn teal" @click="openEdit(b)">Edit</button>
                <button class="chip-btn red" @click="handleDelete(b.id)">Hapus</button>
              </td>
            </tr>
            <tr v-if="barangs.length === 0">
              <td colspan="5" class="empty">Belum ada barang.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </main>

    <transition name="modal">
      <div v-if="showModal" class="overlay" @click.self="showModal = false">
        <div class="modal">
          <div class="modal-header">
            <h2 v-if="isEditing">Edit Barang</h2>
            <h2 v-else>Tambah Barang</h2>
            <button class="modal-close" @click="showModal = false">✕</button>
          </div>
          <form @submit.prevent="handleSubmit">
            <div class="field"><label>Title</label><input v-model="form.title" placeholder="Nama barang" required /></div>
            <div class="field"><label>Deskripsi</label><textarea v-model="form.description" placeholder="Deskripsi" rows="3" required></textarea></div>
            <div class="field"><label>Harga</label><input v-model.number="form.harga" type="number" min="0" required /></div>
            <div class="modal-btns">
              <button type="button" class="btn-ghost" @click="showModal = false">Batal</button>
              <button v-if="isEditing" type="submit" class="btn-primary">Update</button>
              <button v-else type="submit" class="btn-primary">Simpan</button>
            </div>
          </form>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.crud-page { min-height: 100vh; background: var(--bg-base); }
.topnav { display: flex; align-items: center; justify-content: space-between; padding: 0 28px; height: 64px; background: var(--glass); backdrop-filter: blur(20px); border-bottom: 1px solid var(--glass-border); position: sticky; top: 0; z-index: 50; }
.nav-left { display: flex; align-items: center; gap: 12px; }
.brand-text { font-size: 16px; color: var(--text-secondary); }
.brand-text strong { color: var(--text-primary); font-weight: 700; }
.nav-divider { width: 1px; height: 28px; background: var(--border-default); }
.nav-link { padding: 7px 14px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 500; color: var(--text-muted); text-decoration: none; }
.nav-link:hover { color: var(--text-primary); background: var(--bg-hover); }
.nav-link.active { color: #fff; background: linear-gradient(135deg, var(--accent), #f59e0b); }
.btn-logout { background: var(--danger-bg); border: 1px solid rgba(251,113,133,0.15); border-radius: 10px; color: var(--danger); cursor: pointer; padding: 8px 16px; font-size: 13px; font-family: inherit; }
.btn-logout:hover { background: rgba(251,113,133,0.15); }
.page-content { max-width: 1100px; margin: 0 auto; padding: 32px 28px; }
.top-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
.page-title { font-size: 26px; font-weight: 800; color: var(--text-primary); }
.gradient-text { background: linear-gradient(135deg, var(--accent), #f59e0b); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.btn-primary { display: flex; align-items: center; gap: 6px; background: linear-gradient(135deg, var(--accent), #f59e0b); color: #fff; border: none; padding: 10px 18px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; box-shadow: 0 2px 12px rgba(249,115,22,0.25); }
.btn-primary:hover { transform: translateY(-1px); }
.alert { padding: 12px 16px; border-radius: var(--radius-sm); font-size: 13px; margin-bottom: 18px; }
.alert.ok { background: var(--success-bg); color: var(--success); border: 1px solid rgba(52,211,153,0.15); }
.alert.err { background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(239,68,68,0.15); }
.glass-card { background: var(--bg-surface); border: 1px solid var(--border-default); border-radius: var(--radius-lg); overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
thead { background: var(--bg-elevated); }
th { text-align: left; padding: 14px 16px; font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; font-weight: 600; }
td { padding: 14px 16px; font-size: 13px; color: var(--text-secondary); border-top: 1px solid var(--border-subtle); }
tbody tr:hover { background: var(--bg-elevated); }
.name-cell { color: var(--text-primary); font-weight: 600; }
.price-pill { display: inline-flex; padding: 4px 12px; border-radius: 99px; font-size: 12px; font-weight: 700; background: rgba(249,115,22,0.1); color: var(--accent); }
.empty { text-align: center; padding: 48px 16px !important; color: var(--text-muted); }
.actions { display: flex; gap: 6px; }
.chip-btn { border: none; padding: 6px 14px; border-radius: 99px; font-size: 11px; font-weight: 600; cursor: pointer; font-family: inherit; }
.chip-btn.teal { background: rgba(20,184,166,0.1); color: #14b8a6; }
.chip-btn.teal:hover { background: rgba(20,184,166,0.2); }
.chip-btn.red { background: var(--danger-bg); color: var(--danger); }
.chip-btn.red:hover { background: rgba(251,113,133,0.2); }
.overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 100; }
.modal { background: var(--bg-surface); border: 1px solid var(--border-default); border-radius: var(--radius-xl); padding: 28px; width: 100%; max-width: 480px; box-shadow: var(--shadow-lg); }
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.modal h2 { font-size: 18px; color: var(--text-primary); font-weight: 700; }
.modal-close { background: var(--bg-elevated); border: none; color: var(--text-muted); width: 32px; height: 32px; border-radius: 8px; cursor: pointer; font-size: 14px; }
.modal-enter-active, .modal-leave-active { transition: all 0.25s; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.field { margin-bottom: 16px; }
.field label { display: block; font-size: 13px; color: var(--text-secondary); margin-bottom: 6px; font-weight: 500; }
.field input, .field textarea { width: 100%; padding: 10px 14px; background: var(--bg-base); border: 1px solid var(--border-default); border-radius: var(--radius-sm); color: var(--text-primary); font-size: 14px; font-family: inherit; outline: none; resize: vertical; }
.field input:focus, .field textarea:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }
.field input::placeholder, .field textarea::placeholder { color: var(--text-muted); }
.modal-btns { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
.btn-ghost { background: var(--bg-elevated); color: var(--text-secondary); border: 1px solid var(--border-default); padding: 9px 18px; border-radius: var(--radius-sm); font-size: 13px; cursor: pointer; font-family: inherit; }
.btn-ghost:hover { border-color: var(--border-strong); color: var(--text-primary); }
</style>
