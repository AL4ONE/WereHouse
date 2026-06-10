<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '@/api'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { useAuthStore } from '@/stores/auth'
import { adminNavLinks, petugasNavLinks, trainingAdminNavLinks, trainingPetugasNavLinks } from '@/config/navLinks'

const authStore = useAuthStore()
const isTraining = authStore.isTraining
const baseRole = authStore.baseRole

const navLinks = isTraining
  ? (baseRole === 'Admin' ? trainingAdminNavLinks : trainingPetugasNavLinks)
  : (authStore.userRole === 'Admin' ? adminNavLinks : petugasNavLinks)

const companyProfile = ref(null)

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
    msg.value = { text: `Barcode detected: ${match.name}`, type: 'ok' }
    setTimeout(() => {
      stockInput.value?.focus()
    }, 100)
  } else {
    msg.value = { text: 'Barcode not registered!', type: 'error' }
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

function makeCurrencyComputed(field) {
  return computed({
    get() {
      const val = form.value[field]
      if (val === 0 || val === '' || val === null) return ''
      return Number(val).toLocaleString('id-ID')
    },
    set(v) {
      const raw = String(v).replace(/\./g, '').replace(/[^0-9]/g, '')
      form.value[field] = raw ? Number(raw) : 0
    }
  })
}
const discountFormatted = makeCurrencyComputed('discount')
const shippingFormatted = makeCurrencyComputed('shipping_cost')
const dpFormatted = makeCurrencyComputed('down_payment')

async function fetchCompanyProfile() {
  if (!isTraining) return
  try {
    const res = await api.get('/company-profile')
    companyProfile.value = res.data.data
  } catch (e) { console.log(e) }
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
    msg.value = { text: 'Outbound stock record successful!', type: 'ok' }
    form.value = { barang_id: '', destination: '', recipient_address: '', recipient_phone: '', stock: 1, discount: 0, shipping_cost: 0, down_payment: 0, po_number: '', vehicle: '', vehicle_plate: '', pic_name: '' }
    fetchBarangs()
    fetchInventoryOut()
  } 
  catch (e) { 
    console.log(e) 
    msg.value = { text: e.response?.data?.message || 'Failed to save outbound stock', type: 'error' }
  }
  finally { 
    isLoading.value = false 
  }
}

function printInvoice(item) {
  const finalTotal = (Number(item.total_harga) - Number(item.discount)) - Number(item.down_payment) + Number(item.shipping_cost)
  const tgl = new Date(item.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
  const fmt = (n) => Number(n).toLocaleString('id-ID')

  const cp = companyProfile.value
  const cName = isTraining && cp ? cp.company_name : 'PT MAJU MAKMUR'
  const cAddr = isTraining && cp ? cp.company_address : 'Jln. Mawar No. 10, Madiun, Jawa Timur 130001'
  const cInitials = isTraining && cp ? cp.company_logo_initials : 'MM'

  const win = window.open('', '', 'width=900,height=800')
  win.document.write(`<html><head><title>Invoice - ${item.invoice_number}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Inter', 'Segoe UI', sans-serif; padding: 40px 50px; color: #1e293b; line-height: 1.5; background: #fff; }

  @page { margin: 0; size: A4; }
  @media print { body { padding: 1.5cm 2cm; -webkit-print-color-adjust: exact; print-color-adjust: exact; } }

  /* Premium Header */
  .inv-header { display: flex; align-items: flex-start; justify-content: space-between; padding-bottom: 24px; border-bottom: 2px solid #f1f5f9; margin-bottom: 32px; }
  .inv-brand { display: flex; align-items: center; gap: 16px; }
  .inv-logo { width: 56px; height: 56px; background: linear-gradient(135deg, #0ea5e9, #3b82f6); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 20px; box-shadow: 0 4px 12px rgba(59,130,246,0.25); }
  .inv-company h1 { font-size: 22px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
  .inv-company p { font-size: 12px; color: #64748b; margin-top: 2px; }
  .inv-title-block { text-align: right; }
  .inv-title-block h2 { font-size: 32px; font-weight: 800; color: #0f172a; letter-spacing: 1px; margin-bottom: 4px; }
  .inv-title-block .inv-num { font-size: 13px; color: #64748b; font-weight: 600; background: #f8fafc; padding: 4px 12px; border-radius: 99px; display: inline-block; border: 1px solid #e2e8f0; }

  /* Meta / Customer Info */
  .inv-meta-row { display: flex; justify-content: space-between; margin-bottom: 32px; gap: 40px; background: #f8fafc; padding: 24px; border-radius: 12px; border: 1px solid #f1f5f9; }
  .inv-customer { flex: 1; }
  .inv-customer .label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #94a3b8; margin-bottom: 8px; }
  .inv-customer h3 { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
  .inv-customer p { font-size: 13px; color: #475569; margin-bottom: 2px; }
  
  .inv-details { width: 240px; }
  .inv-details table { width: 100%; }
  .inv-details td { font-size: 13px; padding: 6px 0; }
  .inv-details .dl { color: #64748b; font-weight: 500; width: 100px; }
  .inv-details .dv { color: #0f172a; font-weight: 700; text-align: right; }

  /* Table */
  .inv-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 24px; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
  .inv-table thead th { background: #f8fafc; color: #475569; padding: 14px 16px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; border-bottom: 1px solid #e2e8f0; }
  .inv-table thead th:last-child { text-align: right; }
  .inv-table tbody td { padding: 16px; font-size: 14px; border-bottom: 1px solid #f1f5f9; color: #334155; }
  .inv-table tbody tr:last-child td { border-bottom: none; }
  .inv-table .tc { text-align: center; }
  .inv-table .tr { text-align: right; }
  .inv-table .rp-flex { display: flex; justify-content: space-between; }

  /* Bottom Section */
  .inv-bottom { display: flex; justify-content: space-between; gap: 40px; margin-top: 16px; align-items: flex-start; }
  .inv-notes { flex: 1; }
  .inv-notes .label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #94a3b8; margin-bottom: 8px; }
  .inv-notes p { font-size: 12px; color: #64748b; line-height: 1.6; background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #f1f5f9; }
  
  /* Totals */
  .inv-summary { width: 340px; }
  .inv-summary table { width: 100%; border-collapse: collapse; }
  .inv-summary td { padding: 8px 16px; font-size: 13px; }
  .inv-summary .sl { color: #64748b; text-align: left; font-weight: 500; }
  .inv-summary .sv { text-align: right; font-weight: 600; color: #0f172a; font-variant-numeric: tabular-nums; }
  
  .inv-summary tr.total-space td { padding: 4px 0; }
  .inv-summary .total-final { background: linear-gradient(135deg, #0f172a, #1e293b); }
  .inv-summary .total-final td { padding: 14px 16px; }
  .inv-summary .total-final .sl { color: #cbd5e1; font-weight: 600; font-size: 14px; border-radius: 8px 0 0 8px; }
  .inv-summary .total-final .sv { color: #fff; font-weight: 800; font-size: 16px; border-radius: 0 8px 8px 0; }

  /* Signature */
  .inv-signature { display: flex; justify-content: flex-end; margin-top: 40px; }
  .sig-block { width: 200px; text-align: center; }
  .sig-block .sig-title { font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 80px; }
  .sig-block .sig-line { border-top: 1px solid #cbd5e1; padding-top: 8px; }
  .sig-block .sig-name { font-size: 14px; font-weight: 700; color: #0f172a; }
  .sig-block .sig-role { font-size: 11px; color: #94a3b8; margin-top: 2px; }

  .inv-footer { margin-top: 50px; padding-top: 20px; border-top: 1px solid #f1f5f9; text-align: center; }
  .inv-footer p { font-size: 12px; color: #94a3b8; font-weight: 500; letter-spacing: 0.5px; }
</style></head><body>
  <div class="inv-header">
    <div class="inv-brand">
      <div class="inv-logo">${cInitials}</div>
      <div class="inv-company">
        <h1>${cName}</h1>
        <p>${cAddr}</p>
      </div>
    </div>
    <div class="inv-title-block">
      <h2>INVOICE</h2>
      <div class="inv-num">#${item.invoice_number}</div>
    </div>
  </div>

  <div class="inv-meta-row">
    <div class="inv-customer">
      <div class="label">To</div>
      <h3>${item.destination}</h3>
      <p>${item.recipient_address || '-'}</p>
      <p>Telp. ${item.recipient_phone || '-'}</p>
    </div>
    <div class="inv-details">
      <table>
        <tr><td class="dl">Date</td><td class="dv">${tgl}</td></tr>
        <tr><td class="dl">Invoice No.</td><td class="dv">${item.invoice_number.split('-').pop()}</td></tr>
      </table>
    </div>
  </div>

  <table class="inv-table">
    <thead><tr><th style="width:50px">No.</th><th>Product Name</th><th style="width:140px;text-align:right">Price</th><th style="width:80px;text-align:center">Qty</th><th style="width:160px">Total</th></tr></thead>
    <tbody>
      <tr>
        <td class="tc">1</td>
        <td style="font-weight:600">${item.barang.name}</td>
        <td class="tr"><span class="rp-flex"><span>Rp</span><span>${fmt(item.harga_satuan)}</span></span></td>
        <td class="tc">${item.stock}</td>
        <td class="tr"><span class="rp-flex"><span>Rp</span><span>${fmt(item.total_harga)}</span></span></td>
      </tr>
    </tbody>
  </table>

  <div class="inv-bottom">
    <div class="inv-notes">
      <div class="label">Notes</div>
      <p>To make a payment, please transfer to BCA Bank Acc No: xxxxxx or Mandiri Bank Acc No: xxxxxx for the amount stated on this invoice.</p>
    </div>
    <div class="inv-summary">
      <table>
        <tr><td class="sl">Amount</td><td class="sv">Rp ${fmt(item.total_harga)}</td></tr>
        <tr><td class="sl">Discount</td><td class="sv">Rp ${fmt(item.discount)}</td></tr>
        <tr><td class="sl">Down Payment</td><td class="sv">Rp ${fmt(item.down_payment)}</td></tr>
        <tr><td class="sl">Shipping Cost</td><td class="sv">Rp ${fmt(item.shipping_cost)}</td></tr>
        <tr class="total-space"><td></td><td></td></tr>
        <tr class="total-final"><td class="sl">Total Payment</td><td class="sv">Rp ${fmt(finalTotal)}</td></tr>
      </table>
    </div>
  </div>

  <div class="inv-signature">
    <div class="sig-block">
      <div class="sig-title">Manajer</div>
      <div class="sig-line">
        <div class="sig-name">(...........................)</div>
        <div class="sig-role">Manajer</div>
      </div>
    </div>
  </div>

  <div class="inv-footer">
    <p>Thank you for trusting our products.</p>
  </div>
</body></html>`)
  win.document.close()
  setTimeout(() => { win.print(); win.close(); }, 500)
}

function printSuratJalan(item) {
  const cp = companyProfile.value
  const cName = isTraining && cp ? cp.company_name : 'PT MAJU MAKMUR'
  const cAddr = isTraining && cp ? cp.company_address : 'Jln. Mawar No. 10, Madiun, Jawa Timur 130001'
  const cInitials = isTraining && cp ? cp.company_logo_initials : 'MM'

  const win = window.open('', '', 'width=900,height=800')
  win.document.write(`
    <html>
      <head>
        <title>Surat Jalan - ${item.invoice_number}</title>
        <style>
          @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
          * { margin: 0; padding: 0; box-sizing: border-box; }
          body { font-family: 'Inter', 'Segoe UI', sans-serif; padding: 40px 50px; color: #1e293b; line-height: 1.5; background: #fff; }

          @page { margin: 0; size: A4; }
          @media print { body { padding: 1.5cm 2cm; -webkit-print-color-adjust: exact; print-color-adjust: exact; } }

          /* Premium Header */
          .sj-header { display: flex; align-items: flex-start; justify-content: space-between; padding-bottom: 24px; border-bottom: 2px solid #f1f5f9; margin-bottom: 24px; }
          .sj-brand { display: flex; align-items: center; gap: 16px; }
          .sj-logo { width: 56px; height: 56px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 20px; box-shadow: 0 4px 12px rgba(16,185,129,0.25); }
          .sj-company h1 { font-size: 22px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
          .sj-company p { font-size: 12px; color: #64748b; margin-top: 2px; }
          .sj-title-block { text-align: right; }
          .sj-title-block h2 { font-size: 32px; font-weight: 800; color: #0f172a; letter-spacing: 1px; margin-bottom: 4px; }
          .sj-title-block .sj-num { font-size: 14px; color: #64748b; font-weight: 600; background: #f8fafc; padding: 4px 12px; border-radius: 99px; display: inline-block; border: 1px solid #e2e8f0; }

          .sj-info-row { display: flex; gap: 16px; margin-bottom: 20px; }
          .sj-info-chip { font-size: 13px; color: #475569; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 8px; padding: 10px 16px; font-weight: 500; }
          .sj-info-chip strong { color: #0f172a; font-weight: 700; margin-left: 6px; }

          .sj-meta { display: flex; justify-content: space-between; margin-bottom: 32px; gap: 40px; }
          .sj-customer { flex: 1; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 12px; padding: 24px; }
          .sj-customer .label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #94a3b8; margin-bottom: 8px; }
          .sj-customer h3 { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
          .sj-customer p { font-size: 13px; color: #475569; margin-bottom: 2px; }
          
          .sj-shipping { width: 300px; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 12px; padding: 24px; }
          .sj-shipping .label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #94a3b8; margin-bottom: 8px; }
          .sj-shipping table { width: 100%; }
          .sj-shipping td { font-size: 13px; padding: 6px 0; }
          .sj-shipping .dl { color: #64748b; font-weight: 500; width: 110px; }
          .sj-shipping .dv { color: #0f172a; font-weight: 700; }

          /* Table */
          .sj-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 40px; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
          .sj-table thead th { background: #f8fafc; color: #475569; padding: 14px 16px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; border-bottom: 1px solid #e2e8f0; }
          .sj-table tbody td { padding: 16px; font-size: 14px; border-bottom: 1px solid #f1f5f9; color: #334155; }
          .sj-table tbody tr:last-child td { border-bottom: none; }
          .sj-table .tc { text-align: center; }
          .sj-table tbody tr.empty td { height: 32px; }

          .sj-sig { display: flex; justify-content: space-between; margin-top: 50px; padding: 0; gap: 24px; }
          .sj-sig-box { flex: 1; text-align: center; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
          .sj-sig-label { font-size: 13px; font-weight: 700; color: #475569; background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 12px; }
          .sj-sig-space { height: 100px; background: #fff; }
        </style>
      </head>
      <body>
        <div class="sj-header">
          <div class="sj-brand">
            <div class="sj-logo">${cInitials}</div>
            <div class="sj-company">
              <h1>${cName}</h1>
              <p>${cAddr}</p>
            </div>
          </div>
          <div class="sj-title-block">
            <h2>SURAT JALAN</h2>
            <div class="sj-num">#${item.invoice_number.replace('INV', 'SJ')}</div>
          </div>
        </div>

        <div class="sj-info-row">
          <div class="sj-info-chip">Tanggal: <strong>${new Date(item.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })}</strong></div>
          <div class="sj-info-chip">No. PO: <strong>${item.po_number || '-'}</strong></div>
        </div>

        <div class="sj-meta">
          <div class="sj-customer">
            <div class="label">To</div>
            <h3>${item.destination}</h3>
            <p>${item.recipient_address || '-'}</p>
            <p>Telp. ${item.recipient_phone || '-'}</p>
          </div>
          <div class="sj-shipping">
            <div class="label">Shipping Info</div>
            <table>
              <tr><td class="dl">Vehicle</td><td class="dv">${item.vehicle || '-'}</td></tr>
              <tr><td class="dl">License Plate</td><td class="dv">${item.vehicle_plate || '-'}</td></tr>
              <tr><td class="dl">PIC</td><td class="dv">${item.pic_name || '-'}</td></tr>
            </table>
          </div>
        </div>

        <table class="sj-table">
          <thead>
            <tr>
              <th style="width:50px">No.</th>
              <th style="width:120px">Product Code</th>
              <th>Product Name</th>
              <th style="width:90px;text-align:center">Amount</th>
              <th style="width:130px">Notes</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="tc">1</td>
              <td style="font-weight:600">${item.barang.kode_barang}</td>
              <td>${item.barang.name}</td>
              <td class="tc" style="font-weight:700">${item.stock}</td>
              <td>${item.barang.satuan}</td>
            </tr>
            <tr class="empty"><td></td><td></td><td></td><td></td><td></td></tr>
            <tr class="empty"><td></td><td></td><td></td><td></td><td></td></tr>
          </tbody>
        </table>

        <div class="sj-sig">
          <div class="sj-sig-box">
            <div class="sj-sig-label">Recipient</div>
            <div class="sj-sig-space"></div>
          </div>
          <div class="sj-sig-box">
            <div class="sj-sig-label">Driver</div>
            <div class="sj-sig-space"></div>
          </div>
          <div class="sj-sig-box">
            <div class="sj-sig-label">Picker</div>
            <div class="sj-sig-space"></div>
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
  fetchCompanyProfile()
})
</script>

<template>
  <DashboardLayout :navLinks="navLinks">
    <div class="hero">
      <h1>Outbound <span class="gradient-text">Stock</span></h1>
    </div>

    <div v-if="msg.text" class="alert" :class="msg.type">
      <svg v-if="msg.type==='ok'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      {{ msg.text }}
    </div>

    <div class="content-grid">
      <div class="glass-card form-card">
        <h3 class="card-title">Input Outbound Stock</h3>
        <form @submit.prevent="handleSubmit">
          <div class="field barcode-scan-field">
            <label>Scan Barcode (Optional)</label>
            <div class="input-with-icon">
              <svg class="scan-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V5a2 2 0 0 1 2-2h2"></path><path d="M17 3h2a2 2 0 0 1 2 2v2"></path><path d="M21 17v2a2 2 0 0 1-2 2h-2"></path><path d="M7 21H5a2 2 0 0 1-2-2v-2"></path><line x1="8" y1="12" x2="16" y2="12"></line></svg>
              <input v-model="barcodeInput" @keydown.enter.prevent="handleBarcodeScan" placeholder="Click & scan here..." />
            </div>
            <small class="hint">Point scanner to barcode and press enter</small>
          </div>
          <div class="field">
          <label>Product</label>
          <select v-model="form.barang_id" required>
            <option value="" disabled>-- Select Product --</option>
            <option v-for="p in barangs" :key="p.id" :value="p.id">{{ p.name }} (stock: {{ p.stock_saat_ini }})</option>
          </select>
        </div>
        <div class="field">
          <label>Customer / Company Name</label>
          <input v-model="form.destination" placeholder="Example: PT Yaspi Jaya" required />
        </div>
        <div class="field">
          <label>Customer Address</label>
          <textarea v-model="form.recipient_address" placeholder="Full shipping address" class="form-textarea"></textarea>
        </div>
        <div class="field">
          <label>Customer Phone No.</label>
          <input v-model="form.recipient_phone" placeholder="Example: 0812345678" />
        </div>
        <div class="field">
          <label>Outbound Quantity</label>
          <input ref="stockInput" v-model.number="form.stock" type="number" min="1" required />
        </div>
        
        <div class="shipping-divider">Shipping Information (Optional)</div>
        <div class="field">
            <label>PIC / Driver Name</label>
            <input v-model="form.pic_name" placeholder="Driver name" />
        </div>
        <div class="field-row">
            <div class="field">
                <label>Vehicle</label>
                <input v-model="form.vehicle" placeholder="Example: Pick Up / L300" />
            </div>
            <div class="field">
                <label>License Plate</label>
                <input v-model="form.vehicle_plate" placeholder="Example: B 1234 ABC" />
            </div>
        </div>
        <div class="field-row">
            <div class="field">
                <label>Discount (Rp)</label>
                <input v-model="discountFormatted" type="text" inputmode="numeric" placeholder="0" />
            </div>
            <div class="field">
                <label>Shipping Cost (Rp)</label>
                <input v-model="shippingFormatted" type="text" inputmode="numeric" placeholder="0" />
            </div>
        </div>
        <div class="field">
            <label>Down Payment (Rp)</label>
            <input v-model="dpFormatted" type="text" inputmode="numeric" placeholder="0" />
        </div>
        <div v-if="selectedBarang" class="pricing-info">
          <p>Price per item: <strong>{{ formatRupiah(selectedBarang.harga) }}</strong></p>
          <p>Total Price: <strong>{{ formatRupiah(totalHarga) }}</strong></p>
          <p class="final-price">Final Estimate: <strong>{{ formatRupiah(totalHarga - form.discount + form.shipping_cost - form.down_payment) }}</strong></p>
        </div>
        <button type="submit" class="btn-submit orange" :disabled="isLoading">
          <span v-if="isLoading" class="spinner"></span>
          {{ isLoading ? 'Processing...' : 'Save Outbound Stock' }}
        </button>
      </form>
    </div>

    <div class="glass-card table-section">
      <h3 class="card-title">Outbound Stock History</h3>
      <div class="table-container">
        <table class="inventory-table">
          <thead>
            <tr>
              <th width="100">Date</th>
              <th width="150">Product</th>
              <th width="180">Customer / Company</th>
              <th width="120">Unit Price</th>
              <th width="80">Amount</th>
              <th width="140">Total Price</th>
              <th width="180">Documents</th>
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
                    <button v-if="baseRole === 'Admin'" class="btn-invoice blue" @click="printSuratJalan(item)">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>
                        Delivery Note
                    </button>
                </div>
              </td>
            </tr>
            <tr v-if="inventoryOuts.length === 0">
              <td colspan="7" class="empty-state">No outbound stock data.</td>
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
