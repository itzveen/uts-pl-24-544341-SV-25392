<script setup>
import { computed, reactive, ref } from 'vue'
import http from '../api/http'
import { titleCase } from '../utils'

const props = defineProps({
  booking: { type: Object, default: null },
  facilities: { type: Array, required: true },
  statuses: { type: Array, required: true },
})
const emit = defineEmits(['saved', 'cancel'])

const isEdit = computed(() => !!props.booking)

const form = reactive({
  customer_name: props.booking?.customer_name ?? '',
  facility: props.booking?.facility ?? props.facilities[0] ?? '',
  booking_date: props.booking?.booking_date ?? '',
  start_time: props.booking?.start_time ?? '',
  end_time: props.booking?.end_time ?? '',
  status: props.booking?.status ?? 'pending',
  notes: props.booking?.notes ?? '',
})

const errors = ref({})
const message = ref('')
const saving = ref(false)

// Tanggal lokal hari ini (YYYY-MM-DD) sebagai batas minimum saat menambah booking.
const today = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10)

async function submit() {
  saving.value = true
  errors.value = {}
  message.value = ''

  const payload = { ...form, notes: form.notes || null }

  try {
    if (isEdit.value) {
      await http.put(`/bookings/${props.booking.id}`, payload)
      emit('saved', 'Booking berhasil diubah.')
    } else {
      await http.post('/bookings', payload)
      emit('saved', 'Booking berhasil ditambahkan.')
    }
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {}
      message.value = 'Periksa kembali isian formulir.'
    } else {
      message.value = 'Gagal menyimpan data. Coba lagi.'
    }
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="modal-backdrop" @click.self="emit('cancel')">
    <div class="modal">
      <h2>{{ isEdit ? 'Ubah Booking' : 'Tambah Booking' }}</h2>

      <p v-if="message" class="alert alert-error">{{ message }}</p>

      <form @submit.prevent="submit" novalidate>
        <div class="field">
          <label for="customer_name">Nama pemesan</label>
          <input id="customer_name" v-model="form.customer_name" type="text" />
          <small v-if="errors.customer_name" class="error">{{ errors.customer_name[0] }}</small>
        </div>

        <div class="field">
          <label for="facility">Fasilitas</label>
          <select id="facility" v-model="form.facility">
            <option v-for="f in facilities" :key="f" :value="f">{{ titleCase(f) }}</option>
          </select>
          <small v-if="errors.facility" class="error">{{ errors.facility[0] }}</small>
        </div>

        <div class="field">
          <label for="booking_date">Tanggal</label>
          <input id="booking_date" v-model="form.booking_date" type="date" :min="isEdit ? undefined : today" />
          <small v-if="errors.booking_date" class="error">{{ errors.booking_date[0] }}</small>
        </div>

        <div class="row">
          <div class="field">
            <label for="start_time">Jam mulai</label>
            <input id="start_time" v-model="form.start_time" type="time" />
          </div>
          <div class="field">
            <label for="end_time">Jam selesai</label>
            <input id="end_time" v-model="form.end_time" type="time" />
          </div>
        </div>
        <small v-if="errors.start_time" class="error block">{{ errors.start_time[0] }}</small>
        <small v-if="errors.end_time" class="error block">{{ errors.end_time[0] }}</small>

        <div class="field">
          <label for="status">Status</label>
          <select id="status" v-model="form.status">
            <option v-for="s in statuses" :key="s" :value="s">{{ titleCase(s) }}</option>
          </select>
          <small v-if="errors.status" class="error">{{ errors.status[0] }}</small>
        </div>

        <div class="field">
          <label for="notes">Catatan (opsional)</label>
          <textarea id="notes" v-model="form.notes" rows="2"></textarea>
          <small v-if="errors.notes" class="error">{{ errors.notes[0] }}</small>
        </div>

        <div class="modal-actions">
          <button type="button" class="btn btn-outline" @click="emit('cancel')">Batal</button>
          <button type="submit" class="btn btn-primary" :disabled="saving">
            {{ saving ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>