<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { useAuthStore } from '@/stores/auth'
import { adminNavLinks, petugasNavLinks, manajerNavLinks, trainingAdminNavLinks, trainingPetugasNavLinks, trainingManajerNavLinks } from '@/config/navLinks'

const authStore = useAuthStore()
const baseRole = authStore.isTraining ? authStore.baseRole : authStore.userRole

const navLinks = authStore.isTraining
  ? (baseRole === 'Admin' ? trainingAdminNavLinks : baseRole === 'Petugas' ? trainingPetugasNavLinks : trainingManajerNavLinks)
  : (authStore.userRole === 'Admin' ? adminNavLinks : authStore.userRole === 'Petugas' ? petugasNavLinks : manajerNavLinks)

const isAdmin = computed(() => baseRole === 'Admin')
const isManajer = computed(() => baseRole === 'Manajer')
const isPetugas = computed(() => baseRole === 'Petugas')

const purchaseOrders = ref([])
const suppliers = ref([])
const barangs = ref([])
const msg = ref({ text: '', type: '' })
const isSubmitting = ref(false)
const companyProfile = ref(null)

const viewMode = ref('list') // 'list', 'form', 'detail'
const selectedPO = ref(null)

const defaultForm = {
  id: null,
  supplier_id: '',
  order_date: new Date().toISOString().split('T')[0],
  expected_date: '',
  notes: '',
  items: [{ barang_id: '', quantity: 1, unit_price: 0 }]
}
const form = ref(JSON.parse(JSON.stringify(defaultForm)))

const formatRupiah = (n) => 'Rp ' + Number(n).toLocaleString('id-ID')
const formatDate = (d) => d ? new Date(d).toLocaleDateString('id-ID') : '-'

function formatPriceDisplay(value) {
  if (value === 0 || value === '' || value === null) return ''
  return Number(value).toLocaleString('id-ID')
}
function parsePriceInput(e, item) {
  const raw = String(e.target.value).replace(/\./g, '').replace(/[^0-9]/g, '')
  item.unit_price = raw ? Number(raw) : 0
  e.target.value = item.unit_price ? formatPriceDisplay(item.unit_price) : ''
}

const statusConfig = {
  draft: { label: 'Draft', color: 'gray' },
  pending: { label: 'Pending Approval', color: 'orange' },
  approved: { label: 'Approved', color: 'blue' },
  received: { label: 'Received', color: 'green' },
  cancelled: { label: 'Cancelled', color: 'red' }
}

async function fetchData() {
  try {
    const requests = [
      api.get('/purchase-orders'),
      api.get('/suppliers'),
      api.get('/products')
    ]
    if (authStore.isTraining) {
      requests.push(api.get('/company-profile'))
    }
    const results = await Promise.all(requests)
    purchaseOrders.value = results[0].data.data
    suppliers.value = results[1].data.data || results[1].data
    barangs.value = results[2].data.data || results[2].data
    if (results[3]) {
      companyProfile.value = results[3].data.data
    }
  } catch (e) {
    console.error(e)
    msg.value = { text: 'Failed to fetch data', type: 'error' }
  }
}

function showCreateForm() {
  form.value = JSON.parse(JSON.stringify(defaultForm))
  viewMode.value = 'form'
  msg.value = { text: '', type: '' }
}

function showEditForm(po) {
  form.value = {
    id: po.id,
    supplier_id: po.supplier_id,
    order_date: po.order_date ? po.order_date.split('T')[0] : '',
    expected_date: po.expected_date ? po.expected_date.split('T')[0] : '',
    notes: po.notes || '',
    items: po.items.map(i => ({
      barang_id: i.barang_id,
      quantity: i.quantity,
      unit_price: i.unit_price
    }))
  }
  viewMode.value = 'form'
  msg.value = { text: '', type: '' }
}

function showDetail(po) {
  selectedPO.value = po
  viewMode.value = 'detail'
  msg.value = { text: '', type: '' }
}

function addItem() {
  form.value.items.push({ barang_id: '', quantity: 1, unit_price: 0 })
}

function removeItem(index) {
  form.value.items.splice(index, 1)
}

function onProductChange(item) {
  const product = barangs.value.find(b => b.id === item.barang_id)
  if (product) {
    item.unit_price = product.harga
  }
}

const formTotal = computed(() => {
  return form.value.items.reduce((total, item) => total + (item.quantity * item.unit_price), 0)
})

async function submitForm(saveAsDraft = false) {
  msg.value = { text: '', type: '' }
  isSubmitting.value = true
  try {
    const payload = {
      ...form.value,
      status: saveAsDraft ? 'draft' : 'pending'
    }
    
    if (form.value.id) {
      await api.post(`/purchase-orders/${form.value.id}`, payload)
      msg.value = { text: 'Purchase Order updated successfully!', type: 'ok' }
    } else {
      await api.post('/purchase-orders', payload)
      msg.value = { text: 'Purchase Order created successfully!', type: 'ok' }
    }
    await fetchData()
    viewMode.value = 'list'
  } catch (e) {
    console.error(e)
    msg.value = { text: e.response?.data?.message || 'Failed to save data', type: 'error' }
  } finally {
    isSubmitting.value = false
  }
}

async function handleAction(action, poId) {
  if (!confirm(`Are you sure you want to perform action: ${action}?`)) return
  msg.value = { text: '', type: '' }
  try {
    if (action === 'delete') {
      await api.delete(`/purchase-orders/${poId}`)
    } else {
      await api.post(`/purchase-orders/${poId}/${action}`)
    }
    msg.value = { text: `Action ${action} successful!`, type: 'ok' }
    await fetchData()
    if (viewMode.value === 'detail') {
      selectedPO.value = purchaseOrders.value.find(p => p.id === poId)
    }
  } catch (e) {
    console.error(e)
    msg.value = { text: e.response?.data?.message || `Failed to perform action ${action}`, type: 'error' }
  }
}

function printPO(po) {
  const tgl = new Date(po.order_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
  const expected = po.expected_date ? new Date(po.expected_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) : '-'
  const fmt = (n) => Number(n).toLocaleString('id-ID')
  const cp = companyProfile.value
  const cName = authStore.isTraining && cp ? cp.company_name : 'PT MAJU MAKMUR'
  const cAddr = authStore.isTraining && cp ? cp.company_address : 'Jln. Mawar No. 10, Madiun, Jawa Timur 130001'
  const cInitials = authStore.isTraining && cp ? cp.company_logo_initials : 'MM'
  
  let itemsHtml = ''
  po.items.forEach((item, index) => {
    itemsHtml += `
      <tr>
        <td class="tc">${index + 1}</td>
        <td style="font-weight:600">${item.barang?.kode_barang || '-'}</td>
        <td>${item.barang?.name || '-'}</td>
        <td class="tc">${item.quantity}</td>
        <td class="tr">Rp ${fmt(item.unit_price)}</td>
        <td class="tr" style="font-weight:600">Rp ${fmt(item.subtotal)}</td>
      </tr>
    `
  })

  const win = window.open('', '', 'width=900,height=800')
  win.document.write(`
    <html>
      <head>
        <title>Purchase Order - ${po.po_number}</title>
        <style>
          @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
          * { margin: 0; padding: 0; box-sizing: border-box; }
          body { font-family: 'Inter', sans-serif; padding: 40px 50px; color: #1e293b; line-height: 1.5; background: #fff; }
          @page { margin: 0; size: A4 portrait; }
          @media print { body { padding: 1.5cm 2cm; -webkit-print-color-adjust: exact; print-color-adjust: exact; } }

          .po-header { display: flex; align-items: flex-start; justify-content: space-between; padding-bottom: 24px; border-bottom: 2px solid #f1f5f9; margin-bottom: 32px; }
          .po-brand { display: flex; align-items: center; gap: 16px; }
          .po-logo { width: 56px; height: 56px; background: linear-gradient(135deg, #8b5cf6, #6d28d9); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 20px; box-shadow: 0 4px 12px rgba(139,92,246,0.25); }
          .po-company h1 { font-size: 22px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
          .po-company p { font-size: 12px; color: #64748b; margin-top: 2px; }
          .po-title-block { text-align: right; }
          .po-title-block h2 { font-size: 28px; font-weight: 800; color: #0f172a; letter-spacing: 1px; margin-bottom: 4px; }
          .po-title-block .po-num { font-size: 13px; color: #64748b; font-weight: 600; background: #f8fafc; padding: 4px 12px; border-radius: 99px; display: inline-block; border: 1px solid #e2e8f0; }

          .po-meta-row { display: flex; justify-content: space-between; margin-bottom: 32px; gap: 40px; }
          .po-vendor { flex: 1; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 12px; padding: 24px; }
          .po-vendor .label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #94a3b8; margin-bottom: 8px; }
          .po-vendor h3 { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
          .po-vendor p { font-size: 13px; color: #475569; margin-bottom: 2px; }

          .po-details { width: 280px; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 12px; padding: 24px; }
          .po-details table { width: 100%; }
          .po-details td { font-size: 13px; padding: 6px 0; }
          .po-details .dl { color: #64748b; font-weight: 500; width: 100px; }
          .po-details .dv { color: #0f172a; font-weight: 700; text-align: right; }

          .po-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 24px; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
          .po-table thead th { background: #f8fafc; color: #475569; padding: 14px 16px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; border-bottom: 1px solid #e2e8f0; }
          .po-table thead th:last-child { text-align: right; }
          .po-table tbody td { padding: 16px; font-size: 13px; border-bottom: 1px solid #f1f5f9; color: #334155; }
          .po-table tbody tr:last-child td { border-bottom: none; }
          .po-table .tc { text-align: center; }
          .po-table .tr { text-align: right; }

          .po-bottom { display: flex; justify-content: space-between; gap: 40px; margin-top: 16px; align-items: flex-start; }
          .po-notes { flex: 1; }
          .po-notes .label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #94a3b8; margin-bottom: 8px; }
          .po-notes p { font-size: 12px; color: #64748b; line-height: 1.6; background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #f1f5f9; }

          .po-summary { width: 340px; }
          .po-summary table { width: 100%; border-collapse: collapse; }
          .po-summary .total-final { background: linear-gradient(135deg, #0f172a, #1e293b); }
          .po-summary .total-final td { padding: 14px 16px; }
          .po-summary .total-final .sl { color: #cbd5e1; font-weight: 600; font-size: 14px; border-radius: 8px 0 0 8px; }
          .po-summary .total-final .sv { color: #fff; font-weight: 800; font-size: 16px; border-radius: 0 8px 8px 0; text-align: right; }

          .po-signature { display: flex; justify-content: space-between; margin-top: 50px; gap: 40px; padding: 0 20px;}
          .sig-block { width: 200px; text-align: center; }
          .sig-block .sig-title { font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 80px; }
          .sig-block .sig-line { border-top: 1px solid #cbd5e1; padding-top: 8px; }
          .sig-block .sig-name { font-size: 14px; font-weight: 700; color: #0f172a; }
          .sig-block .sig-role { font-size: 11px; color: #94a3b8; margin-top: 2px; }

          .po-footer { margin-top: 50px; padding-top: 20px; border-top: 1px solid #f1f5f9; text-align: center; }
          .po-footer p { font-size: 12px; color: #94a3b8; font-weight: 500; letter-spacing: 0.5px; }
        </style>
      </head>
      <body>
        <div class="po-header">
          <div class="po-brand">
            <div class="po-logo">${cInitials}</div>
            <div class="po-company">
              <h1>${cName}</h1>
              <p>${cAddr}</p>
            </div>
          </div>
          <div class="po-title-block">
            <h2>PURCHASE ORDER</h2>
            <div class="po-num">#${po.po_number}</div>
          </div>
        </div>

        <div class="po-meta-row">
          <div class="po-vendor">
            <div class="label">Vendor / Supplier</div>
            <h3>${po.supplier?.name || '-'}</h3>
            <p>${po.supplier?.alamat || '-'}</p>
            <p>Telp: ${po.supplier?.phone || '-'}</p>
            <p>Email: ${po.supplier?.email || '-'}</p>
          </div>
          <div class="po-details">
            <div class="label">PO Details</div>
            <table>
              <tr><td class="dl">Date</td><td class="dv">${tgl}</td></tr>
              <tr><td class="dl">Expected</td><td class="dv">${expected}</td></tr>
              <tr><td class="dl">Status</td><td class="dv" style="text-transform: capitalize;">${po.status}</td></tr>
            </table>
          </div>
        </div>

        <table class="po-table">
          <thead>
            <tr>
              <th style="width:50px">No.</th>
              <th style="width:120px">Item Code</th>
              <th>Description</th>
              <th style="width:80px;text-align:center">Qty</th>
              <th style="width:130px;text-align:right">Unit Price</th>
              <th style="width:140px;text-align:right">Total</th>
            </tr>
          </thead>
          <tbody>
            ${itemsHtml}
          </tbody>
        </table>

        <div class="po-bottom">
          <div class="po-notes">
            <div class="label">Notes & Terms</div>
            <p>${po.notes || '1. Please send two copies of your invoice.<br>2. Enter this order in accordance with the prices, terms, delivery method, and specifications listed above.'}</p>
          </div>
          <div class="po-summary">
            <table>
              <tr class="total-final">
                <td class="sl">Grand Total</td>
                <td class="sv">Rp ${fmt(po.total_amount)}</td>
              </tr>
            </table>
          </div>
        </div>

        <div class="po-signature">
          <div class="sig-block">
            <div class="sig-title">Prepared By</div>
            <div class="sig-line">
              <div class="sig-name">${po.creator?.name || '(...........................)'}</div>
              <div class="sig-role">Purchasing Staff</div>
            </div>
          </div>
          <div class="sig-block">
            <div class="sig-title">Authorized By</div>
            <div class="sig-line">
              <div class="sig-name">(...........................)</div>
              <div class="sig-role">General Manager</div>
            </div>
          </div>
        </div>

        <div class="po-footer">
          <p>Please deliver the items to our warehouse address by the expected delivery date.</p>
        </div>
      </body>
    </html>
  `)
  win.document.close()
  setTimeout(() => { win.print(); win.close(); }, 500)
}

onMounted(() => {
  fetchData()
})
</script>

<template>
  <DashboardLayout :navLinks="navLinks">
    <div class="hero">
      <h1>Purchase <span class="gradient-text">Order</span></h1>
      <p>Manage orders to suppliers</p>
    </div>

    <div v-if="msg.text" class="alert" :class="msg.type">
      {{ msg.text }}
    </div>

    <!-- LIST VIEW -->
    <div v-if="viewMode === 'list'" class="glass-card">
      <div class="card-header">
        <h3 class="card-title">Purchase Order List</h3>
        <button v-if="!isManajer" @click="showCreateForm" class="btn-primary">
          + Create New PO
        </button>
      </div>

      <div class="table-container">
        <table class="inventory-table">
          <thead>
            <tr>
              <th>PO Number</th>
              <th>Date</th>
              <th>Supplier</th>
              <th>Total Items</th>
              <th>Total Amount</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="po in purchaseOrders" :key="po.id">
              <td><strong>{{ po.po_number }}</strong></td>
              <td>{{ formatDate(po.order_date) }}</td>
              <td>{{ po.supplier?.name }}</td>
              <td>{{ po.items?.length || 0 }} item</td>
              <td><strong>{{ formatRupiah(po.total_amount) }}</strong></td>
              <td>
                <span class="badge" :class="'badge-' + statusConfig[po.status].color">
                  {{ statusConfig[po.status].label }}
                </span>
              </td>
              <td>
                <div class="actions">
                  <button class="btn-icon" @click="showDetail(po)" title="Details">
                    👁️
                  </button>
                  <button v-if="po.status === 'draft' && !isManajer" class="btn-icon edit" @click="showEditForm(po)" title="Edit">
                    ✏️
                  </button>
                  <button v-if="po.status === 'draft' && isAdmin" class="btn-icon delete" @click="handleAction('delete', po.id)" title="Delete">
                    🗑️
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="purchaseOrders.length === 0">
              <td colspan="7" class="empty-state">No Purchase Order data.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- FORM VIEW -->
    <div v-else-if="viewMode === 'form'" class="glass-card">
      <h3 class="card-title">{{ form.id ? 'Edit Purchase Order' : 'Create New Purchase Order' }}</h3>
      
      <form @submit.prevent="() => submitForm(false)">
        <div class="form-grid">
          <div class="field">
            <label>Supplier</label>
            <select v-model="form.supplier_id" required>
              <option value="" disabled>-- Select Supplier --</option>
              <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <div class="field">
            <label>Order Date</label>
            <input type="date" v-model="form.order_date" required />
          </div>
          <div class="field">
            <label>Expected Arrival (Optional)</label>
            <input type="date" v-model="form.expected_date" />
          </div>
          <div class="field">
            <label>Notes (Optional)</label>
            <input type="text" v-model="form.notes" placeholder="Additional notes..." />
          </div>
        </div>

        <div class="items-section">
          <h4>Order Items</h4>
          <div v-for="(item, index) in form.items" :key="index" class="item-row">
            <div class="field" style="flex: 2">
              <select v-model="item.barang_id" @change="onProductChange(item)" required>
                <option value="" disabled>-- Select Product --</option>
                <option v-for="b in barangs" :key="b.id" :value="b.id">{{ b.name }} (Stock: {{ b.stock_saat_ini }})</option>
              </select>
            </div>
            <div class="field" style="flex: 1">
              <input type="number" v-model="item.quantity" min="1" placeholder="Qty" required />
            </div>
            <div class="field" style="flex: 1.5">
              <input type="text" inputmode="numeric" :value="formatPriceDisplay(item.unit_price)" @input="parsePriceInput($event, item)" placeholder="Unit Price" required />
            </div>
            <div class="item-subtotal">
              {{ formatRupiah(item.quantity * item.unit_price) }}
            </div>
            <button type="button" class="btn-icon delete" @click="removeItem(index)" :disabled="form.items.length === 1">
              ✕
            </button>
          </div>
          <button type="button" class="btn-secondary mt-2" @click="addItem">+ Add Item</button>
        </div>

        <div class="form-footer">
          <div class="total-display">
            Estimated Total: <strong>{{ formatRupiah(formTotal) }}</strong>
          </div>
          <div class="form-actions">
            <button type="button" class="btn-cancel" @click="viewMode = 'list'" :disabled="isSubmitting">Cancel</button>
            <button type="button" class="btn-submit gray" @click="submitForm(true)" :disabled="isSubmitting">
              Save as Draft
            </button>
            <button type="submit" class="btn-submit orange" :disabled="isSubmitting">
              Submit (Pending Approval)
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- DETAIL VIEW -->
    <div v-else-if="viewMode === 'detail' && selectedPO" class="glass-card">
      <div class="detail-header">
        <div>
          <h3 class="card-title">PO Details: {{ selectedPO.po_number }}</h3>
          <p class="text-sm">Created by: {{ selectedPO.creator?.name }}</p>
        </div>
        <div class="status-badge">
          <span class="badge" :class="'badge-' + statusConfig[selectedPO.status].color">
            {{ statusConfig[selectedPO.status].label }}
          </span>
        </div>
      </div>

      <div class="info-grid mt-4">
        <div>
          <label>Supplier:</label>
          <p><strong>{{ selectedPO.supplier?.name }}</strong></p>
        </div>
        <div>
          <label>Order Date:</label>
          <p>{{ formatDate(selectedPO.order_date) }}</p>
        </div>
        <div>
          <label>Expected Arrival:</label>
          <p>{{ formatDate(selectedPO.expected_date) }}</p>
        </div>
        <div>
          <label>Notes:</label>
          <p>{{ selectedPO.notes || '-' }}</p>
        </div>
      </div>

      <div class="table-container mt-4">
        <table class="inventory-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Product</th>
              <th>Order Qty</th>
              <th>Unit Price</th>
              <th>Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, idx) in selectedPO.items" :key="item.id">
              <td>{{ idx + 1 }}</td>
              <td>{{ item.barang?.name }}</td>
              <td>{{ item.quantity }}</td>
              <td>{{ formatRupiah(item.unit_price) }}</td>
              <td><strong>{{ formatRupiah(item.subtotal) }}</strong></td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="4" style="text-align: right"><strong>Total Amount:</strong></td>
              <td><strong class="text-accent">{{ formatRupiah(selectedPO.total_amount) }}</strong></td>
            </tr>
          </tfoot>
        </table>
      </div>

        <div class="detail-actions mt-4">
        <button class="btn-cancel" @click="viewMode = 'list'">Back</button>
        
        <button class="btn-submit purple" @click="printPO(selectedPO)">
          🖨️ Print PO
        </button>

        <!-- Workflow Actions -->
        <template v-if="selectedPO.status === 'pending' && (isAdmin || isManajer)">
          <button class="btn-submit orange" @click="handleAction('approve', selectedPO.id)">✅ Approve PO</button>
        </template>
        
        <template v-if="selectedPO.status === 'approved' && !isManajer">
          <button class="btn-submit green" @click="handleAction('receive', selectedPO.id)">📦 Receive Items</button>
        </template>

        <template v-if="['draft', 'pending', 'approved'].includes(selectedPO.status) && (isAdmin || isManajer)">
          <button class="btn-submit red" @click="handleAction('cancel', selectedPO.id)">❌ Cancel PO</button>
        </template>
      </div>
    </div>

  </DashboardLayout>
</template>

<style scoped>
.hero { margin-bottom: 24px; }
.hero h1 { font-size: 26px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; }
.gradient-text { background: linear-gradient(135deg, var(--accent), #f59e0b); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.hero p { font-size: 14px; color: var(--text-muted); }

.alert { padding: 12px 16px; border-radius: var(--radius-sm); font-size: 13px; margin-bottom: 18px; }
.alert.ok { background: var(--success-bg); color: var(--success); border: 1px solid rgba(52,211,153,0.15); }
.alert.error { background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(251,113,133,0.15); }

.glass-card { background: var(--bg-surface); border: 1px solid var(--border-default); border-radius: var(--radius-lg); padding: 24px; margin-bottom: 24px; }
.card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.card-title { font-size: 18px; font-weight: 700; color: var(--text-primary); margin: 0; }
.text-sm { font-size: 12px; color: var(--text-muted); }

.btn-primary { background: linear-gradient(135deg, var(--accent), #f59e0b); color: white; border: none; padding: 10px 16px; border-radius: var(--radius-sm); font-weight: 600; cursor: pointer; }
.btn-primary:hover { box-shadow: 0 4px 12px var(--accent-glow); }
.btn-secondary { background: var(--bg-base); border: 1px dashed var(--border-strong); color: var(--text-primary); padding: 8px 16px; border-radius: var(--radius-sm); cursor: pointer; }

/* Table */
.table-container { overflow-x: auto; }
.inventory-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
.inventory-table th { padding: 12px; color: var(--text-secondary); border-bottom: 2px solid var(--border-default); }
.inventory-table td { padding: 12px; border-bottom: 1px solid var(--border-default); }
.empty-state { text-align: center; color: var(--text-muted); padding: 32px !important; }

/* Form Grid */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
.field label { display: block; font-size: 13px; margin-bottom: 6px; color: var(--text-secondary); }
.field input, .field select { width: 100%; padding: 10px; background: var(--bg-base); border: 1px solid var(--border-default); border-radius: var(--radius-sm); color: var(--text-primary); }

/* Items */
.items-section { border-top: 1px solid var(--border-default); padding-top: 20px; margin-bottom: 24px; }
.items-section h4 { margin-top: 0; margin-bottom: 16px; color: var(--text-primary); }
.item-row { display: flex; gap: 12px; align-items: flex-end; margin-bottom: 12px; }
.item-subtotal { flex: 1; padding: 10px; text-align: right; font-weight: bold; background: var(--bg-base); border-radius: var(--radius-sm); border: 1px solid transparent; }

.form-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-default); padding-top: 20px; }
.total-display { font-size: 16px; }
.total-display strong { color: var(--accent); font-size: 20px; }
.form-actions { display: flex; gap: 12px; }

.btn-cancel { padding: 10px 16px; border: 1px solid var(--border-default); background: transparent; border-radius: var(--radius-sm); color: var(--text-primary); cursor: pointer; }
.btn-submit { padding: 10px 16px; border: none; border-radius: var(--radius-sm); font-weight: 600; color: white; cursor: pointer; }
.btn-submit.orange { background: linear-gradient(135deg, var(--accent), #f59e0b); }
.btn-submit.gray { background: #6b7280; }
.btn-submit.green { background: #10b981; }
.btn-submit.red { background: #ef4444; }
.btn-submit.purple { background: linear-gradient(135deg, #8b5cf6, #6d28d9); box-shadow: 0 4px 12px rgba(139,92,246,0.3); }

/* Badges */
.badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
.badge-gray { background: #e5e7eb; color: #374151; }
.badge-orange { background: #fef3c7; color: #d97706; }
.badge-blue { background: #dbeafe; color: #2563eb; }
.badge-green { background: #d1fae5; color: #059669; }
.badge-red { background: #fee2e2; color: #dc2626; }

.actions { display: flex; gap: 6px; }
.btn-icon { background: none; border: 1px solid var(--border-default); border-radius: 4px; padding: 4px; cursor: pointer; }

/* Detail Info Grid */
.detail-header { display: flex; justify-content: space-between; align-items: flex-start; }
.info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; background: var(--bg-base); padding: 16px; border-radius: var(--radius-md); }
.info-grid label { font-size: 12px; color: var(--text-secondary); display: block; margin-bottom: 4px; }
.info-grid p { margin: 0; font-weight: 500; }
.mt-2 { margin-top: 8px; }
.mt-4 { margin-top: 16px; }
.detail-actions { display: flex; gap: 12px; justify-content: flex-end; border-top: 1px solid var(--border-default); padding-top: 16px;}
.text-accent { color: var(--accent); }
</style>
