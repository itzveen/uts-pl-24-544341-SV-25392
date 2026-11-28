<script setup>
import { reactive, ref } from 'vue'
import http from '../api/http'
import { formatDate, titleCase } from '../utils'

const props = defineProps({
  booking: { type: Object, required: true },
})
const emit = defineEmits(['saved', 'cancel'])

const form = reactive({
  attendee_count: 1,
  notes: '',
})

const errors = ref({})
const message = ref('')
const saving = ref(false)

async function submit() {
  saving.value = true
  errors.value = {}
  message.value = ''

  const payload = {
    attendee_count: Number(form.attendee_count),
    notes: form.notes || null,
  }

  try {
    await http.post(`/bookings/${props.booking.id}/check-in`, payload)
    emit('saved', 'Check-in berhasil dicatat.')
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {}
      message.value = errors.value.booking?.[0] || 'Periksa kembali isian check-in.'
    } else if (e.response?.status === 409) {
      message.value = e.response.data?.message || 'Booking ini sudah melakukan check-in.'
    } else {
      message.value = 'Gagal mencatat check-in. Coba lagi.'
    }
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="modal-backdrop" @click.self="emit('cancel')">
    <div class="modal modal-small">
      <h2>Check-in Booking</h2>
      <p class="muted" style="margin-bottom: 14px">
        {{ booking.customer_name }} — {{ titleCase(booking.facility) }}
        ({{ formatDate(booking.booking_date) }}, {{ booking.start_time }}–{{ booking.end_time }})
      </p>

      <p v-if="message" class="alert alert-error">{{ message }}</p>

      <form @submit.prevent="submit" novalidate>
        <div class="field">
          <label for="attendee_count">Jumlah peserta hadir</label>
          <input id="attendee_count" v-model="form.attendee_count" type="number" min="1" max="50" />
          <small v-if="errors.attendee_count" class="error">{{ errors.attendee_count[0] }}</small>
        </div>

        <div class="field">
          <label for="checkin_notes">Catatan kedatangan (opsional)</label>
          <textarea id="checkin_notes" v-model="form.notes" rows="2"></textarea>
          <small v-if="errors.notes" class="error">{{ errors.notes[0] }}</small>
        </div>

        <div class="modal-actions">
          <button type="button" class="btn btn-outline" @click="emit('cancel')">Batal</button>
          <button type="submit" class="btn btn-primary" :disabled="saving">
            {{ saving ? 'Menyimpan...' : 'Konfirmasi Check-in' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
