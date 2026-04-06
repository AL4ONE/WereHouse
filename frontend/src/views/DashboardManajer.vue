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

async function fetchBarangs() {
  isLoading.value = true
  try {
    const res = await api.get('/barangs')
    barangs.value = res.data.data
  } catch (e) { console.error(e) }
  finally { isLoading.value = false }
}

const hasFilter = computed(() => !!(startDate.value || endDate.value))

const isDateInRange = (dateStr) => {
  if (!startDate.value && !endDate.value) return true
  if (!dateStr) return false
  const itemDate = new Date(dateStr).getTime()
  const start = startDate.value ? new Date(startDate.value).getTime() : 0
  let end = Number.MAX_SAFE_INTEGER
  if (endDate.value) { const e = new Date(endDate.value); e.setHours(23,59,59,999); end = e.getTime() }
  return itemDate >= start && itemDate <= end
}

const reportData = computed(() => {
  let filtered = barangs.value
  if (hasFilter.value) filtered = filtered.filter(p => isDateInRange(p.created_at))
  return filtered.map(p => {
    const filteredMasuk = (p.barang_masuks || []).filter(item => isDateInRange(item.created_at))
    const filteredKeluar = (p.barang_keluars || []).filter(item => isDateInRange(item.created_at))
    const opnames = p.status_op_names || []
    const totalMasuk = filteredMasuk.reduce((sum, item) => sum + item.stock, 0)
    const totalKeluar = filteredKeluar.reduce((sum, item) => sum + item.stock, 0)
    return { ...p, totalMasuk, totalKeluar, opnames }
  })
})

function resetFilter() { startDate.value = ''; endDate.value = '' }

function exportLaporanUtama() {
  const doc = new jsPDF('landscape')
  doc.text('Laporan Produk: Stok, Transaksi & Opname', 14, 15)
  doc.setFontSize(10)
  doc.text(`Tanggal Cetak: ${new Date().toLocaleString()}`, 14, 22)
  doc.text(`Periode: ${startDate.value || 'Awalan'} s.d ${endDate.value || 'Sekarang'}`, 14, 28)
  const tableData = reportData.value.map((p, i) => {
    const opS = p.opnames.map(o => `${o.tipe === 'penambahan' ? '+' : '-'}${o.stock} (${o.keterangan})`).join('\n') || '-'
    return [i + 1, p.name, p.satuan, p.stock_awal, p.totalMasuk, p.totalKeluar, opS, p.stock_saat_ini]
  })
  autoTable(doc, {
    startY: 34,
    head: [['No', 'Nama Barang', 'Satuan', 'Stok Awal', 'Total Masuk', 'Total Keluar', 'Opname', 'Sisa Stok']],
    body: tableData, theme: 'grid',
    headStyles: { fillColor: [99, 102, 241] }, columnStyles: { 6: { cellWidth: 50 } }, styles: { fontSize: 9 },
  })
  doc.save(`Laporan_Produk_${startDate.value || 'Semua'}_${endDate.value || 'Sekarang'}.pdf`)
}

function exportLowStock() {
  const lowStock = barangs.value.filter(p => p.stock_saat_ini <= 5)
  if (!lowStock.length) { alert('Tidak ada barang dengan stok menipis.'); return }
  const doc = new jsPDF()
  doc.setTextColor(220, 38, 38)
  doc.text('Laporan Stok Menipis (Stok <= 5)', 14, 15)
  doc.setTextColor(0, 0, 0)
  doc.setFontSize(10)
  doc.text(`Tanggal Cetak: ${new Date().toLocaleString()}`, 14, 22)
  autoTable(doc, {
    startY: 30, head: [['No', 'Nama Barang', 'Sisa Stok', 'Satuan']],
    body: lowStock.map((p, i) => [i + 1, p.name, p.stock_saat_ini, p.satuan]),
    theme: 'striped', headStyles: { fillColor: [220, 38, 38] }
  })
  doc.save(`Laporan_Stok_Menipis_${new Date().toISOString().split('T')[0]}.pdf`)
}

onMounted(() => { fetchBarangs() })
</script>

<template>
  <DashboardLayout :navLinks="navLinks">
    <div class="hero">
      <h1>Dashboard <span class="gradient-text">Manager</span> 📊</h1>
      <p>Laporan lengkap produk: stok, transaksi, dan opname.</p>
    </div>

    <!-- Filter Card -->
    <div class="glass-card filter-card">
      <div class="filter-top">
        <div class="filter-title">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
          <strong>Filter Range Tanggal</strong>
        </div>
        <button class="btn-ghost" @click="resetFilter" v-if="hasFilter">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
          Reset
        </button>
      </div>
      <div class="filter-row">
        <div class="filter-group">
          <label>Dari</label>
          <input type="date" v-model="startDate" />
        </div>
        <div class="filter-group">
          <label>Sampai</label>
          <input type="date" v-model="endDate" />
        </div>
      </div>
      <p v-if="hasFilter" class="filter-info">📋 Menampilkan {{ reportData.length }} barang dalam rentang waktu terpilih.</p>
    </div>

    <!-- Export -->
    <div class="export-row">
      <button class="btn-primary" @click="exportLaporanUtama">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Export Laporan (PDF)
      </button>
      <button class="btn-danger" @click="exportLowStock">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        Export Stok Menipis
      </button>
    </div>

    <!-- Table -->
    <div v-if="isLoading" class="loading-state">
      <div class="spinner-lg"></div>
      <span>Memuat data laporan...</span>
    </div>
    <div v-else class="glass-card table-card">
      <table>
        <thead>
          <tr>
            <th>No</th>
            <th>Nama Barang</th>
            <th>Satuan</th>
            <th>Stok Awal</th>
            <th>Total Masuk</th>
            <th>Total Keluar</th>
            <th>Opname</th>
            <th>Sisa Stok</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(p, i) in reportData" :key="p.id">
            <td class="num-cell">{{ i + 1 }}</td>
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
          <tr v-if="!reportData.length">
            <td colspan="8" class="empty-cell">
              {{ hasFilter ? '❌ Tidak ada barang dalam rentang waktu ini.' : '📭 Belum ada data barang.' }}
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
  background: linear-gradient(135deg, #f59e0b, #f97316);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.hero p { font-size: 15px; color: var(--text-muted); }

/* Glass Card */
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
.filter-group input {
  padding: 9px 14px; border-radius: var(--radius-sm);
  background: var(--bg-base); border: 1px solid var(--border-default);
  color: var(--text-primary); font-size: 13px; font-family: inherit; outline: none;
}
.filter-group input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }
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
