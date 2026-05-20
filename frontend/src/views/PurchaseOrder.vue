<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { useAuthStore } from '@/stores/auth'
import { adminNavLinks, petugasNavLinks, manajerNavLinks } from '@/config/navLinks'

const authStore = useAuthStore()
const navLinks = authStore.userRole === 'Admin' ? adminNavLinks : 
                 authStore.userRole === 'Petugas' ? petugasNavLinks : manajerNavLinks

const isAdmin = computed(() => authStore.userRole === 'Admin')
const isManajer = computed(() => authStore.userRole === 'Manajer')
const isPetugas = computed(() => authStore.userRole === 'Petugas')

const purchaseOrders = ref([])
const suppliers = ref([])
const barangs = ref([])
const msg = ref({ text: '', type: '' })
const isSubmitting = ref(false)

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
    const [poRes, supRes, brgRes] = await Promise.all([
      api.get('/purchase-orders'),
      api.get('/suppliers'),
      api.get('/products')
    ])
    purchaseOrders.value = poRes.data.data
    suppliers.value = supRes.data.data || supRes.data // Handle both API response formats
    barangs.value = brgRes.data.data || brgRes.data
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
