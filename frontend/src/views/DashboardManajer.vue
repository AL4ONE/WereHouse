<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'

const navLinks = [
  { path: '/dashboard/Manajer', label: '🏠 Dashboard' },
]

const barangs = ref([])
const isLoading = ref(false)

async function fetchBarangs() {
  isLoading.value = true
  try {
    const res = await api.get('/barangs')
    barangs.value = res.data.data
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

// Computed: data laporan produk
const reportData = computed(() => {
  return barangs.value.map(p => {
    const totalMasuk = p.barang_masuks?.reduce((sum, item) => sum + item.stock, 0) || 0
    const totalKeluar = p.barang_keluars?.reduce((sum, item) => sum + item.stock, 0) || 0
    const opnames = p.status_op_names || []
    const totalOpnameTambah = opnames.filter(o => o.tipe === 'penambahan').reduce((sum, o) => sum + o.stock, 0)
    const totalOpnameKurang = opnames.filter(o => o.tipe === 'pengurangan').reduce((sum, o) => sum + o.stock, 0)
    return {
      ...p,
      totalMasuk,
      totalKeluar,
      opnames,
      totalOpnameTambah,
      totalOpnameKurang,
    }
  })
})

function exportLaporanUtama() {
  const doc = new jsPDF('landscape')
  doc.text('Laporan Produk: Stok, Transaksi & Opname', 14, 15)
  doc.setFontSize(10)
  doc.text(`Tanggal Cetak: ${new Date().toLocaleString()}`, 14, 22)

  const tableData = reportData.value.map((p, i) => {
    const opnameSummary = p.opnames.map(o => {
      const sign = o.tipe === 'penambahan' ? '+' : '-'
      return `${sign}${o.stock} (${o.keterangan})`
    }).join('\n') || '-'

    return [
      i + 1,
      p.name,
      p.satuan,
      p.stock_awal,
      p.totalMasuk,
      p.totalKeluar,
      opnameSummary,
      p.stock_saat_ini,
    ]
  })

  autoTable(doc, {
    startY: 30,
    head: [['No', 'Nama Barang', 'Satuan', 'Stok Awal', 'Total Masuk', 'Total Keluar', 'Opname', 'Sisa Stok']],
    body: tableData,
    theme: 'grid',
    headStyles: { fillColor: [99, 102, 241] },
    columnStyles: {
      6: { cellWidth: 50 },
    },
    styles: { fontSize: 9 },
  })

  doc.save(`Laporan_Produk_${new Date().toISOString().split('T')[0]}.pdf`)
}

function exportLowStock() {
  const lowStock = reportData.value.filter(p => p.stock_saat_ini <= 5)
  if (lowStock.length === 0) {
    alert('Tidak ada barang dengan stok menipis.')
    return
  }

  const doc = new jsPDF()
  doc.setTextColor(220, 38, 38)
  doc.text('Laporan Stok Menipis', 14, 15)
  doc.setTextColor(0, 0, 0)
  doc.setFontSize(10)
  doc.text(`Tanggal Cetak: ${new Date().toLocaleString()}`, 14, 22)

  const tableData = lowStock.map((p, i) => [
    i + 1,
    p.name,
    p.stock_saat_ini,
    p.satuan
  ])

  autoTable(doc, {
    startY: 30,
    head: [['No', 'Nama Barang', 'Sisa Stok', 'Satuan']],
    body: tableData,
    theme: 'striped',
    headStyles: { fillColor: [220, 38, 38] }
  })

  doc.save(`Laporan_Stok_Menipis_${new Date().toISOString().split('T')[0]}.pdf`)
}

onMounted(() => {
  fetchBarangs()
})
</script>

<template>
  <DashboardLayout :navLinks="navLinks">
    <h1 class="page-title">Dashboard Manager</h1>
    <p class="page-desc">Laporan lengkap produk: stok, transaksi, dan opname.</p>

    <!-- Export Buttons -->
    <div class="export-row">
      <button class="btn-export primary" @click="exportLaporanUtama">📄 Export Laporan Utama (PDF)</button>
      <button class="btn-export danger" @click="exportLowStock">⚠️ Export Stok Menipis (PDF)</button>
    </div>

    <!-- Report Table -->
    <div v-if="isLoading" class="loading">Memuat data...</div>
    <div v-else class="table-wrap">
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
            <td>{{ i + 1 }}</td>
            <td>{{ p.name }}</td>
            <td>{{ p.satuan }}</td>
            <td class="num">{{ p.stock_awal }}</td>
            <td class="num text-ok">+{{ p.totalMasuk }}</td>
            <td class="num text-err">-{{ p.totalKeluar }}</td>
            <td class="opname-cell">
              <div v-if="p.opnames.length" class="opname-list">
                <div v-for="op in p.opnames" :key="op.id" class="opname-item">
                  <span :class="op.tipe === 'penambahan' ? 'text-ok' : 'text-err'">
                    {{ op.tipe === 'penambahan' ? '+' : '-' }}{{ op.stock }}
                  </span>
                  <span class="opname-ket">{{ op.keterangan }}</span>
                </div>
              </div>
              <span v-else class="muted">-</span>
            </td>
            <td class="num sisa" :class="p.stock_saat_ini <= 5 ? 'low' : ''">{{ p.stock_saat_ini }}</td>
          </tr>
          <tr v-if="!reportData.length">
            <td colspan="8" class="empty">Belum ada data barang.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </DashboardLayout>
</template>

<style scoped>
.page-title { font-size: 22px; color: #f1f5f9; margin-bottom: 4px; }
.page-desc { font-size: 14px; color: #64748b; margin-bottom: 20px; }

.export-row { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
.btn-export {
  border: none; padding: 10px 20px; border-radius: 8px;
  font-size: 13px; font-weight: 600; cursor: pointer;
  transition: all 0.2s ease;
}
.btn-export.primary { background: #6366f1; color: #fff; }
.btn-export.primary:hover { background: #4f46e5; transform: translateY(-1px); }
.btn-export.danger { background: rgba(220,38,38,0.15); color: #f87171; border: 1px solid rgba(220,38,38,0.2); }
.btn-export.danger:hover { background: rgba(220,38,38,0.25); transform: translateY(-1px); }

.table-wrap {
  background: #1e1e2e; border: 1px solid #2a2a3d;
  border-radius: 12px; overflow-x: auto;
}
table { width: 100%; border-collapse: collapse; min-width: 700px; }
thead { background: #252538; }
th {
  text-align: left; padding: 12px 14px;
  font-size: 12px; color: #64748b;
  text-transform: uppercase; letter-spacing: 0.5px;
  white-space: nowrap;
}
td {
  padding: 12px 14px; font-size: 14px;
  color: #cbd5e1; border-top: 1px solid #2a2a3d;
  vertical-align: top;
}
tr:hover { background: #252538; }

.num { font-weight: 700; font-variant-numeric: tabular-nums; }
.text-ok { color: #4ade80; }
.text-err { color: #f87171; }
.sisa { font-size: 16px; color: #4ade80; }
.sisa.low { color: #f87171; }
.muted { color: #64748b; font-style: italic; font-size: 12px; }
.empty { text-align: center; padding: 32px; color: #64748b; }

.opname-cell { min-width: 180px; }
.opname-list { display: flex; flex-direction: column; gap: 4px; }
.opname-item {
  display: flex; align-items: center; gap: 6px;
  font-size: 12px; padding: 2px 0;
}
.opname-ket {
  color: #94a3b8; font-size: 11px;
  background: rgba(148,163,184,0.08);
  padding: 1px 6px; border-radius: 4px;
}

.loading { color: #64748b; padding: 20px; text-align: center; }
</style>
