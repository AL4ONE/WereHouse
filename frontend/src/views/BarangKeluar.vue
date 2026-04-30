<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { useAuthStore } from '@/stores/auth'
import { adminNavLinks, petugasNavLinks } from '@/config/navLinks'

const authStore = useAuthStore()
const navLinks = authStore.userRole === 'Admin' ? adminNavLinks : petugasNavLinks

const barangs = ref([])
const inventoryOuts = ref([])
const msg = ref({ text: '', type: '' })
const isLoading = ref(false)
const form = ref({ barang_id: '', destination: '', recipient_address: '', recipient_phone: '', stock: 1, discount: 0, shipping_cost: 0, down_payment: 0, po_number: '', vehicle: '', vehicle_plate: '', pic_name: '' })
const barcodeInput = ref('')
const stockInput = ref(null)

function handleBarcodeScan() {
  if (!barcodeInput.value) return
  const match = barangs.value.find(b => b.kode_barang === barcodeInput.value)
  if (match) {
    form.value.barang_id = match.id
    barcodeInput.value = ''
    msg.value = { text: `Barcode terdeteksi: ${match.name}`, type: 'ok' }
    setTimeout(() => {
      stockInput.value?.focus()
    }, 100)
  } else {
    msg.value = { text: 'Barcode tidak terdaftar!', type: 'error' }
  }
}

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

async function fetchBarangs() {
  try { 
    const res = await api.get('/products'); 
    barangs.value = res.data.data 
  } 
  catch (e) {
    console.log(e)
  }
}

async function fetchInventoryOut() {
  try { 
    const res = await api.get('/inventory-out'); 
    inventoryOuts.value = res.data.data 
  } 
  catch (e) { console.log(e) }
}

async function handleSubmit() {
  msg.value = { text: '', type: '' }
  isLoading.value = true
  try {
    await api.post('/inventory-out', form.value)
    msg.value = { text: 'Catatan barang keluar berhasil!', type: 'ok' }
    form.value = { barang_id: '', destination: '', recipient_address: '', recipient_phone: '', stock: 1, discount: 0, shipping_cost: 0, down_payment: 0, po_number: '', vehicle: '', vehicle_plate: '', pic_name: '' }
    fetchBarangs()
    fetchInventoryOut()
  } 
  catch (e) { 
    console.log(e) 
    msg.value = { text: e.response?.data?.message || 'Gagal menyimpan barang keluar', type: 'error' }
  }
  finally { 
    isLoading.value = false 
  }
}

function printInvoice(item) {
  const finalTotal = (Number(item.total_harga) - Number(item.discount)) - Number(item.down_payment) + Number(item.shipping_cost)
  
  const win = window.open('', '', 'width=900,height=800')
  win.document.write(`
    <html>
      <head>
        <title>Invoice - ${item.invoice_number}</title>
        <style>
          body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 30px; color: #1a1a1a; line-height: 1.4; }
          .header { display: flex; align-items: center; margin-bottom: 20px; position: relative; }
          .logo-placeholder { width: 100px; color: red; font-weight: bold; font-size: 20px; }
          .company-info { flex: 1; text-align: center; border: 1px solid #999; padding: 10px; background: #f3f3f3; margin: 0 50px; }
          .company-info h1 { margin: 0; font-size: 20px; text-transform: uppercase; }
          .company-info p { margin: 2px 0; font-size: 12px; }
          .invoice-title { font-size: 48px; font-weight: 800; margin-left: auto; }

          .divider { border-top: 2px solid #000; margin: 15px 0; }

          .customer-section { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
          .customer-details { flex: 1; display: flex; }
          .label-kepada { font-weight: bold; width: 80px; }
          .customer-info { flex: 1; text-align: center; }
          .customer-info h3 { margin: 0; font-size: 18px; }
          .customer-info p { margin: 4px 0; font-size: 14px; }

          .invoice-meta { width: 250px; }
          .meta-table { width: 100%; border-collapse: collapse; }
          .meta-table td { border: 1px solid #000; padding: 6px 10px; font-size: 13px; }
          .meta-label { width: 100px; border-right: none !important; }

          table.items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
          table.items-table th { background: #444; color: #fff; border: 1px solid #000; padding: 10px; font-size: 13px; text-transform: uppercase; }
          table.items-table td { border: 1px solid #000; padding: 10px; font-size: 14px; }
          .text-right { text-align: right; }
          .text-center { text-align: center; }

          .footer-section { display: flex; justify-content: space-between; margin-top: 20px; }
          .notes { width: 45%; font-size: 12px; }
          .summary-table { width: 50%; border-collapse: collapse; }
          .summary-table td { padding: 4px 5px; font-size: 14px; white-space: nowrap; }
          .total-row { background: #333; color: #fff; font-weight: bold; font-size: 15px; }
          .rp-cell { width: 30px; text-align: left; }
          .val-cell { text-align: right; min-width: 120px; }
          
          .thanks { margin-top: 40px; font-weight: bold; font-size: 14px; }
          
          @media print { .no-print { display: none; } }
        </style>
      </head>
      <body>
        <div class="header">
          <div class="logo-placeholder">LOGO</div>
          <div class="company-info">
            <h1>PT MAJU MAKMUR</h1>
            <p>Jln. Mawar No. 10</p>
            <p>Madiun, Jawa Timur 130001</p>
          </div>
          <div class="invoice-title">INVOICE</div>
        </div>

        <div class="divider"></div>

        <div class="customer-section">
          <div class="customer-details">
            <div class="label-kepada">Kepada :</div>
            <div class="customer-info">
              <h3>${item.destination}</h3>
              <p>${item.recipient_address || '-'}</p>
              <p>No. Telp. ${item.recipient_phone || '-'}</p>
            </div>
          </div>
          <div class="invoice-meta">
            <table class="meta-table">
              <tr>
                <td class="meta-label">Tanggal</td>
                <td>: ${new Date(item.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })}</td>
              </tr>
              <tr>
                <td class="meta-label" style="border-top:none; border-bottom:none;"></td>
                <td style="border-top:none; border-bottom:none; height: 25px;"></td>
              </tr>
              <tr>
                <td class="meta-label">No Invoice</td>
                <td class="text-center">${item.invoice_number.split('-').pop()}</td>
              </tr>
            </table>
          </div>
        </div>

        <table class="items-table">
          <thead>
            <tr>
              <th width="50">NO.</th>
              <th>NAMA BARANG</th>
              <th width="150">HARGA</th>
              <th width="80">JUMLAH</th>
              <th width="180">TOTAL</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="text-center">1</td>
              <td>${item.barang.name}</td>
              <td class="text-right">
                <div style="display:flex; justify-content:space-between"><span>Rp.</span><span>${Number(item.harga_satuan).toLocaleString('id-ID')}</span></div>
              </td>
              <td class="text-center">${item.stock}</td>
              <td class="text-right">
                 <div style="display:flex; justify-content:space-between"><span>Rp.</span><span>${Number(item.total_harga).toLocaleString('id-ID')}</span></div>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="footer-section">
          <div class="notes">
            <strong>Catatan :</strong>
            <p>Untuk melakukan pembayaran, silahkan transfer ke rekening bank BCA no Rek: xxxxxx atau Bank Mandiri no Rek : xxxxxx sebesar nominal yang tertera pada lembar invoice ini. Terima Kasih.</p>
          </div>
          <table class="summary-table">
            <tr>
              <td class="text-right">JUMLAH</td>
              <td width="10" class="text-center">:</td>
              <td class="rp-cell">Rp.</td>
              <td class="val-cell"><strong>${Number(item.total_harga).toLocaleString('id-ID')}</strong></td>
            </tr>
            <tr>
              <td class="text-right">DISC</td>
              <td class="text-center">:</td>
              <td class="rp-cell">Rp.</td>
              <td class="val-cell"><strong>${Number(item.discount).toLocaleString('id-ID')}</strong></td>
            </tr>
            <tr>
              <td class="text-right">UANG MUKA</td>
              <td class="text-center">:</td>
              <td class="rp-cell">Rp.</td>
              <td class="val-cell"><strong>${Number(item.down_payment).toLocaleString('id-ID')}</strong></td>
            </tr>
            <tr>
              <td class="text-right">ONGKOS KIRIM</td>
              <td class="text-center">:</td>
              <td class="rp-cell">Rp.</td>
              <td class="val-cell"><strong>${Number(item.shipping_cost).toLocaleString('id-ID')}</strong></td>
            </tr>
            <tr class="total-row">
              <td class="text-right">TOTAL SISA PEMBAYARAN</td>
              <td class="text-center">:</td>
              <td class="rp-cell">Rp.</td>
              <td class="val-cell">${Number(finalTotal).toLocaleString('id-ID')}</td>
            </tr>
          </table>
        </div>

        <div class="thanks">
          Terima Kasih Atas Kepercayaan Anda untuk Membeli Produk Kami.
        </div>
      </body>
    </html>
  `)
  win.document.close()
  setTimeout(() => { win.print(); win.close(); }, 500)
}

function printSuratJalan(item) {
  const win = window.open('', '', 'width=900,height=800')
  win.document.write(`
    <html>
      <head>
        <title>Surat Jalan - ${item.invoice_number}</title>
        <style>
          body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 30px; color: #1a1a1a; line-height: 1.4; }
          .title { text-align: center; font-size: 24px; font-weight: bold; text-decoration: underline; margin-bottom: 30px; }
          .header { display: flex; justify-content: space-between; margin-bottom: 20px; }
          .comp-info h2 { margin: 0; font-size: 18px; }
          .comp-info p { margin: 2px 0; font-size: 12px; }
          
          .meta-table td { font-size: 13px; padding: 2px 5px; }
          
          .grid-section { display: flex; border: 1px solid #000; margin-bottom: 20px; }
          .grid-left { flex: 1.2; border-right: 1px solid #000; padding: 10px; }
          .grid-right { flex: 1; padding: 10px; }
          
          .grid-row { display: flex; margin-bottom: 4px; font-size: 13px; }
          .grid-label { width: 100px; font-weight: bold; }
          
          table.items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
          table.items-table th { background: #d9e1f2; border: 1px solid #000; padding: 8px; font-size: 13px; text-align: left; }
          table.items-table td { border: 1px solid #000; padding: 8px; font-size: 13px; }
          .text-center { text-align: center; }
          .text-right { text-align: right; }
          
          .signature-section { display: flex; justify-content: space-between; margin-top: 40px; }
          .sig-box { width: 150px; text-align: center; }
          .sig-space { height: 80px; border: 1px solid #000; margin-top: 5px; }
          .sig-label { font-weight: bold; background: #eee; border: 1px solid #000; padding: 5px; border-bottom: none; }
        </style>
      </head>
      <body>
        <div class="title">SURAT JALAN</div>
        <div class="header">
          <div class="comp-info">
            <h2>PT MAJU MAKMUR</h2>
            <p>Jln. Mawar No. 10</p>
            <p>Madiun, Jawa Timur 130001</p>
          </div>
          <table class="meta-table">
            <tr><td>NO. Surat Jalan</td><td>: ${item.invoice_number.replace('INV', 'SJ')}</td></tr>
            <tr><td>Tanggal</td><td>: ${new Date(item.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })}</td></tr>
            <tr><td>No. PO</td><td>: ${item.po_number || '-'}</td></tr>
          </table>
        </div>

        <div class="grid-section">
          <div class="grid-left">
            <div class="grid-row">
              <span class="grid-label">Kepada</span>
              <div style="flex:1">
                <strong>${item.destination}</strong><br>
                ${item.recipient_address || '-'}<br>
                No. Telp. ${item.recipient_phone || '-'}
              </div>
            </div>
          </div>
          <div class="grid-right">
            <div class="grid-row"><span class="grid-label">Kendaraan</span><span>: ${item.vehicle || '-'}</span></div>
            <div class="grid-row"><span class="grid-label">No. Polisi</span><span>: ${item.vehicle_plate || '-'}</span></div>
            <div class="grid-row"><span class="grid-label">PIC</span><span>: ${item.pic_name || '-'}</span></div>
          </div>
        </div>

        <table class="items-table">
          <thead>
            <tr>
              <th width="120">Kode Barang</th>
              <th>Nama Barang</th>
              <th width="100">Jumlah</th>
              <th width="150">Keterangan</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>${item.barang.kode_barang}</td>
              <td>${item.barang.name}</td>
              <td class="text-center">${item.stock}</td>
              <td>${item.barang.satuan}</td>
            </tr>
            <!-- Empty rows for spacing like Excel -->
            <tr style="height:25px"><td></td><td></td><td></td><td></td></tr>
            <tr style="height:25px"><td></td><td></td><td></td><td></td></tr>
          </tbody>
        </table>

        <div class="signature-section">
          <div class="sig-box">
            <div class="sig-label">Penerima</div>
            <div class="sig-space"></div>
          </div>
          <div class="sig-box">
            <div class="sig-label">Supir</div>
            <div class="sig-space"></div>
          </div>
          <div class="sig-box">
            <div class="sig-label">Bag. Packing</div>
            <div class="sig-space"></div>
          </div>
        </div>
      </body>
    </html>
  `)
  win.document.close()
  setTimeout(() => { win.print(); win.close(); }, 500)
}

onMounted(() => { 
  fetchBarangs() 
  fetchInventoryOut()
})
</script>

<template>
  <DashboardLayout :navLinks="navLinks">
    <div class="hero">
      <h1>Barang <span class="gradient-text">Keluar</span></h1>
    </div>

    <div v-if="msg.text" class="alert" :class="msg.type">
      <svg v-if="msg.type==='ok'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      {{ msg.text }}
    </div>

    <div class="content-grid">
      <div class="glass-card form-card">
        <h3 class="card-title">Input Barang Keluar</h3>
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
          <label>Barang</label>
          <select v-model="form.barang_id" required>
            <option value="" disabled>-- Pilih Barang --</option>
            <option v-for="p in barangs" :key="p.id" :value="p.id">{{ p.name }} (stok: {{ p.stock_saat_ini }})</option>
          </select>
        </div>
        <div class="field">
          <label>Nama Customer / PT</label>
          <input v-model="form.destination" placeholder="Contoh: PT Yaspi Jaya" required />
        </div>
        <div class="field">
          <label>Alamat Customer</label>
          <textarea v-model="form.recipient_address" placeholder="Alamat lengkap pengiriman" class="form-textarea"></textarea>
        </div>
        <div class="field">
          <label>No. Telepon Customer</label>
          <input v-model="form.recipient_phone" placeholder="Contoh: 0812345678" />
        </div>
        <div class="field">
          <label>Jumlah Keluar</label>
          <input ref="stockInput" v-model.number="form.stock" type="number" min="1" required />
        </div>
        
        <div class="shipping-divider">Informasi Pengiriman (Opsional)</div>
        <div class="field">
            <label>Nama PIC / Supir</label>
            <input v-model="form.pic_name" placeholder="Nama pengantar" />
        </div>
        <div class="field-row">
            <div class="field">
                <label>Kendaraan</label>
                <input v-model="form.vehicle" placeholder="Contoh: Pick Up / L300" />
            </div>
            <div class="field">
                <label>No. Polisi</label>
                <input v-model="form.vehicle_plate" placeholder="Contoh: B 1234 ABC" />
            </div>
        </div>
        <div class="field-row">
            <div class="field">
                <label>Potongan/Diskon (Rp)</label>
                <input v-model.number="form.discount" type="number" min="0" />
            </div>
            <div class="field">
                <label>Ongkos Kirim (Rp)</label>
                <input v-model.number="form.shipping_cost" type="number" min="0" />
            </div>
        </div>
        <div class="field">
            <label>Uang Muka / DP (Rp)</label>
            <input v-model.number="form.down_payment" type="number" min="0" />
        </div>
        <div v-if="selectedBarang" class="pricing-info">
          <p>Harga per barang: <strong>{{ formatRupiah(selectedBarang.harga) }}</strong></p>
          <p>Total Harga: <strong>{{ formatRupiah(totalHarga) }}</strong></p>
          <p class="final-price">Estimasi Akhir: <strong>{{ formatRupiah(totalHarga - form.discount + form.shipping_cost - form.down_payment) }}</strong></p>
        </div>
        <button type="submit" class="btn-submit orange" :disabled="isLoading">
          <span v-if="isLoading" class="spinner"></span>
          {{ isLoading ? 'Memproses...' : 'Simpan Barang Keluar' }}
        </button>
      </form>
    </div>

    <div class="glass-card table-section">
      <h3 class="card-title">Riwayat Barang Keluar</h3>
      <div class="table-container">
        <table class="inventory-table">
          <thead>
            <tr>
              <th width="100">Tanggal</th>
              <th width="150">Produk</th>
              <th width="180">Customer / PT</th>
              <th width="120">Harga Satuan</th>
              <th width="80">Jumlah</th>
              <th width="140">Total Harga</th>
              <th width="180">Dokumen</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in inventoryOuts" :key="item.id">
              <td class="small-text">{{ new Date(item.created_at).toLocaleDateString() }}</td>
              <td class="name-cell">{{ item.barang?.name || 'Unknown' }}</td>
              <td class="dest-cell">
                <strong>{{ item.destination }}</strong>
                <div class="sub-text">{{ item.recipient_address || '-' }}</div>
              </td>
              <td class="num-text">{{ formatRupiah(item.harga_satuan) }}</td>
              <td><span class="badge decrement">-{{ item.stock }}</span></td>
              <td style="font-weight: bold;" class="num-text">{{ formatRupiah(item.total_harga) }}</td>
              <td>
                <div class="doc-btns">
                    <button class="btn-invoice" @click="printInvoice(item)">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                        Invoice
                    </button>
                    <button v-if="authStore.userRole === 'Admin'" class="btn-invoice blue" @click="printSuratJalan(item)">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>
                        Surat Jalan
                    </button>
                </div>
              </td>
            </tr>
            <tr v-if="inventoryOuts.length === 0">
              <td colspan="7" class="empty-state">Belum ada data barang keluar.</td>
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

.glass-card { background: var(--bg-surface); border: 1px solid var(--border-default); border-radius: var(--radius-lg); }
.form-card { padding: 28px; max-width: 500px; }

.field { margin-bottom: 18px; }
.field label { display: block; font-size: 13px; color: var(--text-secondary); margin-bottom: 8px; font-weight: 500; }
.field input, .field select {
  width: 100%; padding: 11px 14px; background: var(--bg-base); border: 1px solid var(--border-default);
  border-radius: var(--radius-sm); color: var(--text-primary); font-size: 14px; font-family: inherit; outline: none;
}
.field input:focus, .field select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }
.field select option { background: var(--bg-surface); color: var(--text-primary); }
.field input::placeholder { color: var(--text-muted); }

.btn-submit {
  width: 100%; border: none; padding: 12px 20px; border-radius: var(--radius-sm);
  font-size: 14px; font-weight: 600; cursor: pointer; font-family: inherit; color: #fff;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-submit.orange {
  background: linear-gradient(135deg, var(--accent), #f59e0b);
  box-shadow: 0 2px 12px var(--accent-glow);
}
.btn-submit.orange:hover { transform: translateY(-1px); box-shadow: 0 4px 20px var(--accent-glow); }
.btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none !important; }

.spinner { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.content-grid {
  display: grid;
  grid-template-columns: 350px 1fr;
  gap: 20px;
  align-items: start;
}
@media (max-width: 900px) {
  .content-grid { grid-template-columns: 1fr; }
}

.card-title {
  font-size: 16px; font-weight: 700; color: var(--text-primary); margin-bottom: 20px;
}

.table-section { padding: 24px; }
.table-container { margin-top: 10px; overflow-x: auto; }

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
.final-price { border-top: 1px solid var(--border-subtle); margin-top: 8px; padding-top: 8px; }

.field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

.form-textarea {
  width: 100%; padding: 11px 14px; background: var(--bg-base); border: 1px solid var(--border-default);
  border-radius: var(--radius-sm); color: var(--text-primary); font-size: 14px; font-family: inherit; outline: none;
  min-height: 80px; resize: vertical;
}
.form-textarea:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }

.btn-invoice {
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    color: var(--text-primary);
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}
.btn-invoice:hover { background: var(--bg-hover); border-color: var(--accent); color: var(--accent); box-shadow: 0 2px 8px var(--accent-glow); }
.btn-invoice.blue:hover { border-color: #3b82f6; color: #3b82f6; box-shadow: 0 2px 8px rgba(59,130,246,0.2); }

.shipping-divider { margin: 24px 0 16px; padding-top: 16px; border-top: 1px solid var(--border-subtle); font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }

.doc-btns { display: flex; gap: 8px; }
.small-text { font-size: 12px; font-weight: 600; color: var(--text-primary); }
.sub-text { font-size: 10px; color: var(--text-muted); margin-top: 2px; }
.dest-cell { max-width: 200px; }
.num-text { white-space: nowrap; font-variant-numeric: tabular-nums; }

.inventory-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
.inventory-table th { 
    text-align: left; padding: 14px 16px; font-size: 11px; color: var(--text-muted); 
    text-transform: uppercase; letter-spacing: 0.8px; font-weight: 700;
    border-bottom: 2px solid var(--border-default);
    background: var(--bg-elevated);
}
.inventory-table td { 
    padding: 16px; font-size: 13px; color: var(--text-secondary); 
    border-bottom: 1px solid var(--border-subtle);
    vertical-align: middle;
}
.inventory-table tr:hover { background: var(--bg-hover); }

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

.empty-state { text-align: center; color: var(--text-muted); padding: 32px !important; }
</style>
