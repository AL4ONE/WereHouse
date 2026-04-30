<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'
import { manajerNavLinks as navLinks } from '@/config/navLinks'

const barangs = ref([])
const isLoading = ref(false)
const startDate = ref('')
const endDate = ref('')
const reportType = ref('semua')

async function fetchBarangs() {
  isLoading.value = true
  try {
    const res = await api.get('/products')
    barangs.value = res.data.data
  } catch (e) { 
    console.error(e) 
  } 
  finally { 
    isLoading.value = false 
  }
}

const hasFilter = computed(() => !!(startDate.value || endDate.value || reportType.value !== 'semua'))

const isDateInRange = (dateStr) => {
  if (!startDate.value && !endDate.value) return true
  if (!dateStr) return false
  const itemDate = new Date(dateStr).getTime()
  const start = startDate.value ? new Date(startDate.value).getTime() : 0
  let end = Number.MAX_SAFE_INTEGER 
  if (endDate.value) { 
    const e = new Date(endDate.value); 
    e.setHours(23,59,59,999); 
    end = e.getTime() 
  }
  return itemDate >= start && itemDate <= end
}

const reportData = computed(() => {
  if (reportType.value === 'semua' || reportType.value === 'menipis') {
    let filtered = barangs.value
    if (startDate.value || endDate.value) {
      filtered = filtered.filter(p => isDateInRange(p.created_at))
    }
    const mapped = filtered.map(p => {
      const filteredMasuk = (p.barang_masuks || []).filter(item => isDateInRange(item.created_at))
      const filteredKeluar = (p.barang_keluars || []).filter(item => isDateInRange(item.created_at))
      const opnames = (p.status_op_names || []).filter(item => isDateInRange(item.created_at))
      const totalMasuk = filteredMasuk.reduce((sum, item) => sum + item.stock, 0)
      const totalKeluar = filteredKeluar.reduce((sum, item) => sum + item.stock, 0)
      const totalOpnamePlus = opnames.filter(o => o.tipe === 'penambahan').reduce((sum, o) => sum + o.stock, 0)
      const totalOpnameMinus = opnames.filter(o => o.tipe === 'pengurangan').reduce((sum, o) => sum + o.stock, 0)
      return { ...p, totalMasuk, totalKeluar, opnames, totalOpnamePlus, totalOpnameMinus }
    })

    if (reportType.value === 'menipis') {
      return mapped.filter(p => p.stock_saat_ini <= 5)
    }
    return mapped
  } else if (reportType.value === 'masuk') {
    let list = []
    barangs.value.forEach(p => {
      const opnames = (p.status_op_names || []).filter(item => isDateInRange(item.created_at))
      const totalOpPlus = opnames.filter(o => o.tipe === 'penambahan').reduce((s, o) => s + o.stock, 0)
      const totalOpMinus = opnames.filter(o => o.tipe === 'pengurangan').reduce((s, o) => s + o.stock, 0)
      const masukList = (p.barang_masuks || []).filter(item => isDateInRange(item.created_at))
      masukList.forEach(item => {
        list.push({ id: item.id, created_at: item.created_at, name: p.name, satuan: p.satuan, jumlah: item.stock, opnames, totalOpPlus, totalOpMinus, stock_saat_ini: p.stock_saat_ini })
      })
    })
    return list.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
  } else if (reportType.value === 'keluar') {
    let list = []
    barangs.value.forEach(p => {
      const opnames = (p.status_op_names || []).filter(item => isDateInRange(item.created_at))
      const totalOpPlus = opnames.filter(o => o.tipe === 'penambahan').reduce((s, o) => s + o.stock, 0)
      const totalOpMinus = opnames.filter(o => o.tipe === 'pengurangan').reduce((s, o) => s + o.stock, 0)
      const keluarList = (p.barang_keluars || []).filter(item => isDateInRange(item.created_at))
      keluarList.forEach(item => {
        list.push({ id: item.id, created_at: item.created_at, name: p.name, satuan: p.satuan, jumlah: item.stock, opnames, totalOpPlus, totalOpMinus, stock_saat_ini: p.stock_saat_ini })
      })
    })
    return list.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
  }
  return []
})

function resetFilter() {
  startDate.value = ''
  endDate.value = ''
  reportType.value = 'semua'
}

function cetakPDF() {
  const doc = new jsPDF({ orientation: 'landscape' })
  
  doc.text('Laporan Inventori Gudang', 14, 15)
  doc.setFontSize(10)
  doc.text(`Filter: ${reportType.value.toUpperCase()}`, 14, 22)
  if (startDate.value || endDate.value) {
    doc.text(`Periode: ${startDate.value || '...'} s/d ${endDate.value || '...'}`, 14, 28)
  }

  const startY = (startDate.value || endDate.value) ? 34 : 28
  let tableHead = []
  let tableBody = []

  if (reportType.value === 'semua' || reportType.value === 'menipis') {
    tableHead = [['No', 'Nama Barang', 'Satuan', 'Stok Awal', 'Masuk', 'Keluar', 'Opname Detail', 'Sisa Stok']]
    tableBody = reportData.value.map((p, i) => [
      i + 1, p.name, p.satuan, p.stock_awal, `+${p.totalMasuk}`, `-${p.totalKeluar}`, 
      p.opnames.map(o => `${o.tipe === 'penambahan' ? '+' : '-'}${o.stock} (${o.keterangan})`).join('\n') || '-',
      p.stock_saat_ini
    ])
  } else {
    const label = reportType.value === 'masuk' ? 'Masuk' : 'Keluar'
    tableHead = [['No', 'Tanggal', 'Nama Barang', 'Satuan', `Jumlah ${label}`, 'Opname Detail', 'Sisa Stok']]
    tableBody = reportData.value.map((p, i) => [
      i + 1, new Date(p.created_at).toLocaleDateString('id-ID'), p.name, p.satuan, 
      reportType.value === 'masuk' ? `+${p.jumlah}` : `-${p.jumlah}`,
      p.opnames.map(o => `${o.tipe === 'penambahan' ? '+' : '-'}${o.stock} (${o.keterangan})`).join('\n') || '-',
      p.stock_saat_ini
    ])
  }

  autoTable(doc, { startY, head: tableHead, body: tableBody })
  doc.save(`Laporan_${reportType.value}.pdf`)
}

onMounted(() => { fetchBarangs() })
</script>

<template>
  <DashboardLayout :navLinks="navLinks">
    <div class="hero">
      <h1>Dashboard <span class="gradient-text">Manager</span></h1>
    </div>

    <div class="glass-card filter-card">
      <div class="filter-top">
        <div class="filter-title">
          <strong>Filter Laporan</strong>
        </div>
        <button class="btn-ghost" @click="resetFilter" v-if="hasFilter">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
          Reset
        </button>
      </div>
      <div class="filter-row">
        <div class="filter-group">
          <label>Jenis Laporan</label>
          <select v-model="reportType">
            <option value="semua">Semua</option>
            <option value="masuk">Barang Masuk</option>
            <option value="keluar">Barang Keluar</option>
            <option value="menipis">Stok Menipis</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Dari Tanggal</label>
          <input type="date" v-model="startDate" />
        </div>
        <div class="filter-group">
          <label>Sampai Tanggal</label>
          <input type="date" v-model="endDate" />
        </div>
      </div>
      <p v-if="hasFilter" class="filter-info">Menampilkan data laporan berdasarkan filter yang terpilih.</p>
    </div>

    <div class="export-row no-print">
      <button class="btn-primary" @click="cetakPDF">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        Cetak Laporan / Simpan PDF
      </button>
    </div>

    <div v-if="isLoading" class="loading-state">
      <div class="spinner-lg"></div>
      <span>Memuat data laporan...</span>
    </div>
    <div v-else class="glass-card table-card">
      <table>
        <thead>
          <tr v-if="reportType === 'semua' || reportType === 'menipis'">
            <th>No</th>
            <th>Tanggal</th>
            <th>Nama Barang</th>
            <th>Satuan</th>
            <th>Stok Awal</th>
            <th>Total Masuk</th>
            <th>Total Keluar</th>
            <th>Opname</th>
            <th>Sisa Stok</th>
          </tr>
          <tr v-else-if="reportType === 'masuk'">
            <th>No</th>
            <th>Tanggal</th>
            <th>Nama Barang</th>
            <th>Satuan</th>
            <th>Total Masuk (+)</th>
            <th>Opname</th>
            <th>Sisa Stok</th>
          </tr>
          <tr v-else-if="reportType === 'keluar'">
            <th>No</th>
            <th>Tanggal</th>
            <th>Nama Barang</th>
            <th>Satuan</th>
            <th>Total Keluar (-)</th>
            <th>Opname</th>
            <th>Sisa Stok</th>
          </tr>
        </thead>
        <tbody>
          <template v-if="reportType === 'semua' || reportType === 'menipis'">
            <tr v-for="(p, i) in reportData" :key="p.id">
              <td class="num-cell">{{ i + 1 }}</td>
              <td class="muted">{{ new Date(p.created_at).toLocaleDateString('id-ID') }}</td>
              <td class="name-cell">{{ p.name }}</td>
              <td>{{ p.satuan }}</td>
              <td class="num-cell">{{ p.stock_awal }}</td>
              <td class="num-cell ok">+{{ p.totalMasuk }}</td>
              <td class="num-cell err">-{{ p.totalKeluar }}</td>
              <td class="opname-cell">
                <div v-if="p.opnames.length" class="opname-list">
                  <div v-for="op in p.opnames" :key="op.id" class="opname-chip">
                    <span :class="op.tipe === 'penambahan' ? 'ok' : 'err'">{{ op.tipe === 'penambahan' ? '+' : '-' }}{{ op.stock }}</span>
                    <span class="opname-ket">{{ op.keterangan }}</span>
                  </div>
                </div>
                <span v-else class="muted">-</span>
              </td>
              <td class="num-cell sisa" :class="p.stock_saat_ini <= 5 ? 'err' : 'ok-bold'">{{ p.stock_saat_ini }}</td>
            </tr>
          </template>

          <template v-if="reportType === 'masuk'">
            <tr v-for="(p, i) in reportData" :key="'masuk-'+p.id">
              <td class="num-cell">{{ i + 1 }}</td>
              <td class="muted">{{ new Date(p.created_at).toLocaleDateString('id-ID') }}</td>
              <td class="name-cell">{{ p.name }}</td>
              <td>{{ p.satuan }}</td>
              <td class="num-cell ok">+{{ p.jumlah }}</td>
              <td class="opname-cell">
                <div v-if="p.opnames.length" class="opname-list">
                  <div v-for="op in p.opnames" :key="op.id" class="opname-chip">
                    <span :class="op.tipe === 'penambahan' ? 'ok' : 'err'">{{ op.tipe === 'penambahan' ? '+' : '-' }}{{ op.stock }}</span>
                    <span class="opname-ket">{{ op.keterangan }}</span>
                  </div>
                </div>
                <span v-else class="muted">-</span>
              </td>
              <td class="num-cell sisa" :class="p.stock_saat_ini <= 5 ? 'err' : 'ok-bold'">{{ p.stock_saat_ini }}</td>
            </tr>
          </template>
          <template v-if="reportType === 'keluar'">
            <tr v-for="(p, i) in reportData" :key="'keluar-'+p.id">
              <td class="num-cell">{{ i + 1 }}</td>
              <td class="muted">{{ new Date(p.created_at).toLocaleDateString('id-ID') }}</td>
              <td class="name-cell">{{ p.name }}</td>
              <td>{{ p.satuan }}</td>
              <td class="num-cell err">-{{ p.jumlah }}</td>
              <td class="opname-cell">
                <div v-if="p.opnames.length" class="opname-list">
                  <div v-for="op in p.opnames" :key="op.id" class="opname-chip">
                    <span :class="op.tipe === 'penambahan' ? 'ok' : 'err'">{{ op.tipe === 'penambahan' ? '+' : '-' }}{{ op.stock }}</span>
                    <span class="opname-ket">{{ op.keterangan }}</span>
                  </div>
                </div>
                <span v-else class="muted">-</span>
              </td>
              <td class="num-cell sisa" :class="p.stock_saat_ini <= 5 ? 'err' : 'ok-bold'">{{ p.stock_saat_ini }}</td>
            </tr>
          </template>

          <tr v-if="!reportData.length">
            <td :colspan="reportType === 'semua' || reportType === 'menipis' ? 9 : 8" class="empty-cell">
              {{ hasFilter ? 'Tidak ada data laporan dalam rentang waktu ini.' : 'Belum ada data.' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </DashboardLayout>
</template>

<style scoped>
.hero { margin-bottom: 28px; }
.hero h1 { font-size: 28px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.5px; margin-bottom: 6px; }
.gradient-text {
  background: linear-gradient(135deg, var(--accent), #f59e0b);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.hero p { font-size: 15px; color: var(--text-muted); }

.glass-card {
  background: var(--bg-surface);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-lg);
  overflow: hidden;
}

/* Filter */
.filter-card { padding: 20px 24px; margin-bottom: 20px; }
.filter-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.filter-title { display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--text-primary); }
.filter-title svg { color: var(--accent); }
.btn-ghost {
  display: flex; align-items: center; gap: 4px;
  background: transparent; border: 1px solid var(--border-default); color: var(--text-secondary);
  padding: 6px 12px; border-radius: var(--radius-sm); font-size: 12px; font-weight: 500; cursor: pointer;
}
.btn-ghost:hover { border-color: var(--border-strong); color: var(--text-primary); }

.filter-row { display: flex; gap: 14px; flex-wrap: wrap; }
.filter-group { display: flex; flex-direction: column; gap: 6px; }
.filter-group label { font-size: 12px; color: var(--text-muted); font-weight: 500; }
.filter-group input, .filter-group select {
  padding: 9px 14px; border-radius: var(--radius-sm);
  background: var(--bg-base); border: 1px solid var(--border-default);
  color: var(--text-primary); font-size: 13px; font-family: inherit; outline: none;
}
.filter-group select { appearance: none; padding-right: 32px; background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23b0b0b0%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E"); background-repeat: no-repeat; background-position: right 10px top 50%; background-size: 10px auto; }
.filter-group input:focus, .filter-group select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }
.filter-info { margin-top: 12px; font-size: 12px; color: var(--text-secondary); }

/* Export */
.export-row { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.btn-primary, .btn-danger {
  display: flex; align-items: center; gap: 8px;
  border: none; padding: 10px 18px; border-radius: var(--radius-sm);
  font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit;
}
.btn-primary {
  background: linear-gradient(135deg, var(--accent), #f59e0b); color: #fff;
  box-shadow: 0 2px 12px rgba(249,115,22,0.25);
}
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 20px rgba(249,115,22,0.35); }
.btn-danger {
  background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(251,113,133,0.15);
}
.btn-danger:hover { background: rgba(251,113,133,0.15); }

/* Loading */
.loading-state { text-align: center; padding: 48px; color: var(--text-muted); display: flex; flex-direction: column; align-items: center; gap: 12px; }
.spinner-lg {
  width: 32px; height: 32px; border: 3px solid var(--border-default);
  border-top-color: var(--accent); border-radius: 50%; animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Table */
.table-card { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; min-width: 800px; }
thead { background: var(--bg-elevated); }
th {
  text-align: left; padding: 14px 16px;
  font-size: 11px; color: var(--text-muted);
  text-transform: uppercase; letter-spacing: 0.8px; font-weight: 600;
}
td {
  padding: 14px 16px; font-size: 13px;
  color: var(--text-secondary); border-top: 1px solid var(--border-subtle);
}
tbody tr { transition: background 0.15s; }
tbody tr:hover { background: var(--bg-elevated); }

.num-cell { font-weight: 700; font-variant-numeric: tabular-nums; }
.name-cell { color: var(--text-primary); font-weight: 500; }
.ok { color: var(--success); }
.err { color: var(--danger); }
.ok-bold { color: var(--success); font-size: 15px; }
.sisa { font-size: 15px; }
.muted { color: var(--text-muted); font-style: italic; font-size: 12px; }
.empty-cell { text-align: center; padding: 40px; color: var(--text-muted); font-size: 14px; }

.opname-cell { min-width: 180px; }
.opname-list { display: flex; flex-direction: column; gap: 4px; }
.opname-chip {
  display: inline-flex; align-items: center; gap: 6px; font-size: 12px;
}
.opname-ket {
  color: var(--text-muted); font-size: 11px;
  background: rgba(249,115,22,0.08); padding: 2px 8px; border-radius: 4px;
}
</style>
