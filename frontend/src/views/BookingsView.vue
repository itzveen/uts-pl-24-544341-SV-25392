<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import http from '../api/http'
import BookingForm from '../components/BookingForm.vue'
import { formatDate, titleCase } from '../utils'

const bookings = ref([])
const facilities = ref([])
const statuses = ref([])
const loading = ref(false)
const loadError = ref('')
const flash = ref('')

const filters = reactive({ facility: '', date: '', status: '' })

const showForm = ref(false)
const editing = ref(null)
const deleting = ref(null)
const deleteBusy = ref(false)

let flashTimer = null
function showFlash(message) {
  flash.value = message
  clearTimeout(flashTimer)
  flashTimer = setTimeout(() => (flash.value = ''), 4000)
}

async function loadOptions() {
  try {
    const { data } = await http.get('/facilities')
    facilities.value = data.data
    statuses.value = data.statuses
  } catch {
    loadError.value = 'Gagal memuat daftar fasilitas.'
  }
}

async function loadBookings() {
  loading.value = true
  loadError.value = ''
  try {
    const params = {}
    Object.entries(filters).forEach(([key, value]) => {
      if (value) params[key] = value
    })
    const { data } = await http.get('/bookings', { params })
    bookings.value = data.data
  } catch {
    loadError.value = 'Gagal memuat data booking.'
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  filters.facility = ''
  filters.date = ''
  filters.status = ''
}

function openCreate() {
  editing.value = null
  showForm.value = true
}

function openEdit(booking) {
  editing.value = booking
  showForm.value = true
}

function onSaved(message) {
  showForm.value = false
  showFlash(message)
  loadBookings()
}

async function doDelete() {
  deleteBusy.value = true
  try {
    await http.delete(`/bookings/${deleting.value.id}`)
    deleting.value = null
    showFlash('Booking berhasil dihapus.')
    await loadBookings()
  } catch {
    deleting.value = null
    loadError.value = 'Gagal menghapus booking.'
  } finally {
    deleteBusy.value = false
  }
}

watch(filters, loadBookings)

onMounted(() => {
  loadOptions()
  loadBookings()
})
</script>

<template>
  <section>
    <div class="page-head">
      <div>
        <h1>Daftar Booking</h1>
        <p class="muted">Kelola pemesanan studio dan lapangan.</p>
      </div>
      <button class="btn btn-primary" @click="openCreate" :disabled="!facilities.length">
        + Tambah Booking
      </button>
    </div>

    <p v-if="flash" class="alert alert-success">{{ flash }}</p>
    <p v-if="loadError" class="alert alert-error">{{ loadError }}</p>

    <div class="card filters">
      <div class="field">
        <label for="f-facility">Fasilitas</label>
        <select id="f-facility" v-model="filters.facility">
          <option value="">Semua</option>
          <option v-for="f in facilities" :key="f" :value="f">{{ titleCase(f) }}</option>
        </select>
      </div>
      <div class="field">
        <label for="f-date">Tanggal</label>
        <input id="f-date" v-model="filters.date" type="date" />
      </div>
      <div class="field">
        <label for="f-status">Status</label>
        <select id="f-status" v-model="filters.status">
          <option value="">Semua</option>
          <option v-for="s in statuses" :key="s" :value="s">{{ titleCase(s) }}</option>
        </select>
      </div>
      <button class="btn btn-outline" @click="resetFilters">Reset</button>
    </div>

    <div class="card table-wrap">
      <p v-if="loading" class="muted pad">Memuat data...</p>
      <p v-else-if="!bookings.length" class="muted pad">Belum ada data booking.</p>

      <table v-else>
        <thead>
          <tr>
            <th>#</th>
            <th>Pemesan</th>
            <th>Fasilitas</th>
            <th>Tanggal</th>
            <th>Jam</th>
            <th>Status</th>
            <th>Catatan</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(b, index) in bookings" :key="b.id">
            <td>{{ index + 1 }}</td>
            <td>{{ b.customer_name }}</td>
            <td>{{ titleCase(b.facility) }}</td>
            <td>{{ formatDate(b.booking_date) }}</td>
            <td>{{ b.start_time }} - {{ b.end_time }}</td>
            <td>
              <span class="badge" :class="`badge-${b.status}`">{{ titleCase(b.status) }}</span>
            </td>
            <td>{{ b.notes || '-' }}</td>
            <td class="actions">
              <button class="btn btn-small btn-outline" @click="openEdit(b)">Ubah</button>
              <button class="btn btn-small btn-danger" @click="deleting = b">Hapus</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <BookingForm
      v-if="showForm"
      :key="editing?.id ?? 'new'"
      :booking="editing"
      :facilities="facilities"
      :statuses="statuses"
      @saved="onSaved"
      @cancel="showForm = false"
    />

    <div v-if="deleting" class="modal-backdrop" @click.self="deleting = null">
      <div class="modal modal-small">
        <h2>Hapus booking?</h2>
        <p>
          Booking atas nama <strong>{{ deleting.customer_name }}</strong>
          ({{ titleCase(deleting.facility) }}, {{ formatDate(deleting.booking_date) }}) akan dihapus permanen.
        </p>
        <div class="modal-actions">
          <button class="btn btn-outline" @click="deleting = null">Batal</button>
          <button class="btn btn-danger" :disabled="deleteBusy" @click="doDelete">
            {{ deleteBusy ? 'Menghapus...' : 'Ya, hapus' }}
          </button>
        </div>
      </div>
    </div>
  </section>
</template>