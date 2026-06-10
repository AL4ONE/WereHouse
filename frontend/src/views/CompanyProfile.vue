<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { trainingManajerNavLinks as navLinks } from '@/config/navLinks'

const profile = ref({
  company_name: '',
  company_address: '',
  company_phone: '',
  company_logo_initials: '',
})
const isLoading = ref(false)
const isSaving = ref(false)
const msg = ref({ text: '', type: '' })

async function fetchProfile() {
  isLoading.value = true
  try {
    const res = await api.get('/company-profile')
    profile.value = res.data.data
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

async function saveProfile() {
  isSaving.value = true
  msg.value = { text: '', type: '' }
  try {
    const res = await api.post('/company-profile', profile.value)
    profile.value = res.data.data
    msg.value = { text: 'Profil perusahaan berhasil disimpan!', type: 'ok' }
  } catch (e) {
    msg.value = { text: e.response?.data?.message || 'Gagal menyimpan profil', type: 'error' }
  } finally {
    isSaving.value = false
  }
}

onMounted(() => {
  fetchProfile()
})
</script>

<template>
  <DashboardLayout :navLinks="navLinks">
    <div class="hero">
      <h1>Company <span class="gradient-text">Profile</span></h1>
      <p>Edit informasi perusahaan untuk laporan & invoice di mode latihan</p>
    </div>

    <div v-if="msg.text" class="alert" :class="msg.type">
      <svg v-if="msg.type==='ok'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      {{ msg.text }}
    </div>

    <div v-if="isLoading" class="loading-state">
      <div class="spinner-lg"></div>
      <span>Loading profile...</span>
    </div>

    <div v-else class="content-grid">
      <div class="glass-card form-card">
        <h3 class="card-title">Edit Company Info</h3>
        <form @submit.prevent="saveProfile">
          <div class="field">
            <label>Company Name</label>
            <input v-model="profile.company_name" placeholder="e.g. PT LATIHAN JAYA" required />
          </div>
          <div class="field">
            <label>Company Address</label>
            <textarea v-model="profile.company_address" placeholder="Full address..." class="form-textarea" required></textarea>
          </div>
          <div class="field">
            <label>Phone Number</label>
            <input v-model="profile.company_phone" placeholder="e.g. 021-0000000" />
          </div>
          <div class="field">
            <label>Logo Initials (max 5 chars)</label>
            <input v-model="profile.company_logo_initials" placeholder="e.g. LJ" maxlength="5" required />
            <small class="hint">Akan ditampilkan di logo invoice & surat jalan</small>
          </div>
          <button type="submit" class="btn-submit" :disabled="isSaving">
            <span v-if="isSaving" class="spinner"></span>
            {{ isSaving ? 'Saving...' : 'Save Profile' }}
          </button>
        </form>
      </div>

      <div class="glass-card preview-card">
        <h3 class="card-title">Preview Invoice Header</h3>
        <div class="invoice-preview">
          <div class="inv-header-preview">
            <div class="inv-brand-preview">
              <div class="inv-logo-preview">{{ profile.company_logo_initials || 'XX' }}</div>
              <div class="inv-company-preview">
                <h2>{{ profile.company_name || 'Company Name' }}</h2>
                <p>{{ profile.company_address || 'Company Address' }}</p>
                <p v-if="profile.company_phone">Telp. {{ profile.company_phone }}</p>
              </div>
            </div>
            <div class="inv-title-preview">
              <h3>INVOICE</h3>
              <span class="inv-num-preview">#TRN-INV-XXXXXXXX-XXXX</span>
            </div>
          </div>
        </div>

        <div class="preview-divider"></div>

        <h3 class="card-title" style="margin-top: 20px;">Preview Surat Jalan Header</h3>
        <div class="invoice-preview sj-theme">
          <div class="inv-header-preview">
            <div class="inv-brand-preview">
              <div class="inv-logo-preview sj-logo">{{ profile.company_logo_initials || 'XX' }}</div>
              <div class="inv-company-preview">
                <h2>{{ profile.company_name || 'Company Name' }}</h2>
                <p>{{ profile.company_address || 'Company Address' }}</p>
              </div>
            </div>
            <div class="inv-title-preview">
              <h3>SURAT JALAN</h3>
              <span class="inv-num-preview">#TRN-SJ-XXXXXXXX-XXXX</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<style scoped>
.hero { margin-bottom: 24px; }
.hero h1 { font-size: 26px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; }
.hero p { font-size: 14px; color: var(--text-muted); }
.gradient-text { background: linear-gradient(135deg, #06b6d4, #0ea5e9); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }

.alert { padding: 12px 16px; border-radius: var(--radius-sm); font-size: 13px; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
.alert.ok { background: var(--success-bg); color: var(--success); border: 1px solid rgba(52,211,153,0.15); }
.alert.error { background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(251,113,133,0.15); }

.loading-state { text-align: center; padding: 48px; color: var(--text-muted); display: flex; flex-direction: column; align-items: center; gap: 12px; }
.spinner-lg { width: 32px; height: 32px; border: 3px solid var(--border-default); border-top-color: #06b6d4; border-radius: 50%; animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.content-grid { display: grid; grid-template-columns: 400px 1fr; gap: 24px; align-items: start; }
@media (max-width: 900px) { .content-grid { grid-template-columns: 1fr; } }

.glass-card { background: var(--bg-surface); border: 1px solid var(--border-default); border-radius: var(--radius-lg); }
.form-card { padding: 28px; }
.preview-card { padding: 28px; }
.card-title { font-size: 16px; font-weight: 700; color: var(--text-primary); margin-bottom: 20px; }

.field { margin-bottom: 18px; }
.field label { display: block; font-size: 13px; color: var(--text-secondary); margin-bottom: 8px; font-weight: 500; }
.field input { width: 100%; padding: 11px 14px; background: var(--bg-base); border: 1px solid var(--border-default); border-radius: var(--radius-sm); color: var(--text-primary); font-size: 14px; font-family: inherit; outline: none; }
.field input:focus { border-color: #06b6d4; box-shadow: 0 0 0 3px rgba(6,182,212,0.15); }
.field input::placeholder { color: var(--text-muted); }
.form-textarea { width: 100%; padding: 11px 14px; background: var(--bg-base); border: 1px solid var(--border-default); border-radius: var(--radius-sm); color: var(--text-primary); font-size: 14px; font-family: inherit; outline: none; min-height: 80px; resize: vertical; }
.form-textarea:focus { border-color: #06b6d4; box-shadow: 0 0 0 3px rgba(6,182,212,0.15); }
.hint { font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block; }

.btn-submit { width: 100%; border: none; padding: 12px 20px; border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; cursor: pointer; font-family: inherit; color: #fff; display: flex; align-items: center; justify-content: center; gap: 8px; background: linear-gradient(135deg, #06b6d4, #0ea5e9); box-shadow: 0 2px 12px rgba(6,182,212,0.25); }
.btn-submit:hover:not(:disabled) { transform: translateY(-1px); }
.btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }
.spinner { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; }

/* Invoice Preview */
.invoice-preview { background: #fff; border-radius: 12px; padding: 24px; border: 1px solid #e2e8f0; }
.inv-header-preview { display: flex; align-items: flex-start; justify-content: space-between; }
.inv-brand-preview { display: flex; align-items: center; gap: 14px; }
.inv-logo-preview { width: 48px; height: 48px; background: linear-gradient(135deg, #0ea5e9, #3b82f6); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 16px; box-shadow: 0 2px 8px rgba(59,130,246,0.25); flex-shrink: 0; }
.inv-logo-preview.sj-logo { background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 2px 8px rgba(16,185,129,0.25); }
.inv-company-preview h2 { font-size: 16px; font-weight: 800; color: #0f172a; letter-spacing: -0.3px; margin: 0; }
.inv-company-preview p { font-size: 11px; color: #64748b; margin: 2px 0 0; }
.inv-title-preview { text-align: right; }
.inv-title-preview h3 { font-size: 22px; font-weight: 800; color: #0f172a; letter-spacing: 1px; margin: 0 0 4px; }
.inv-num-preview { font-size: 10px; color: #94a3b8; font-weight: 600; background: #f8fafc; padding: 3px 10px; border-radius: 99px; border: 1px solid #e2e8f0; }
.preview-divider { height: 1px; background: var(--border-subtle); margin: 20px 0; }
</style>
