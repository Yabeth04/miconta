<template>
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between gap-3 mb-4">
      <div>
        <h1 class="text-h4 font-weight-medium mb-1">
          Música
        </h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Pegá una URL de YouTube y descargá el audio en MP3
        </p>
      </div>
    </div>

    <VAlert
      v-if="statusLoaded && !toolsReady"
      type="warning"
      variant="tonal"
      rounded="lg"
      class="mb-4"
    >
      Faltan yt-dlp o FFmpeg. En la raíz del proyecto ejecutá
      <code>bash scripts/install-music-tools.sh</code>
      y recargá esta página.
    </VAlert>

    <VAlert
      v-if="error"
      type="error"
      variant="tonal"
      rounded="lg"
      class="mb-4"
      closable
      @click:close="error = ''"
    >
      {{ error }}
    </VAlert>

    <VCard
      rounded="lg"
      max-width="720"
    >
      <VCardText>
        <p class="text-caption text-medium-emphasis mb-3">
          URL del video o canción
        </p>

        <VForm @submit.prevent="download">
          <div class="music-download__row">
            <VTextField
              v-model="url"
              class="music-download__url"
              label="https://www.youtube.com/watch?v=…"
              placeholder="Pegá el enlace de YouTube"
              variant="outlined"
              rounded="lg"
              prepend-inner-icon="ri-youtube-line"
              :disabled="downloading"
              :error-messages="urlError"
              hide-details="auto"
              @update:model-value="urlError = ''"
            />

            <VBtn
              class="music-download__action"
              type="submit"
              color="primary"
              rounded="lg"
              prepend-icon="ri-download-2-line"
              :loading="downloading"
              :disabled="!toolsReady || !url.trim()"
            >
              Descargar MP3
            </VBtn>
          </div>

          <p
            v-if="downloading"
            class="text-caption text-medium-emphasis mb-0 mt-3"
          >
            Extrayendo audio… puede tardar un momento.
          </p>
          <p
            v-else-if="lastFilename"
            class="text-caption text-medium-emphasis mb-0 mt-3"
          >
            Listo: {{ lastFilename }}
          </p>
          <p
            v-else
            class="text-caption text-medium-emphasis mb-0 mt-3"
          >
            Solo URLs de YouTube. El archivo se descarga en tu dispositivo.
          </p>
        </VForm>
      </VCardText>
    </VCard>
  </div>
</template>

<script>
import { axios } from '@/plugins/axios'

const YOUTUBE_HOSTS = [
  'youtube.com',
  'www.youtube.com',
  'm.youtube.com',
  'music.youtube.com',
  'youtu.be',
  'www.youtu.be',
]

export default {
  name: 'ModuleMusic',
  data() {
    return {
      url: '',
      urlError: '',
      error: '',
      downloading: false,
      statusLoaded: false,
      toolsReady: false,
      lastFilename: '',
    }
  },
  mounted() {
    this.loadStatus()
  },
  methods: {
    async loadStatus() {
      try {
        const { data } = await axios.get('/api/music/status')
        this.toolsReady = Boolean(data?.ready)
      }
      catch {
        this.toolsReady = false
        this.error = 'No se pudo verificar yt-dlp / FFmpeg.'
      }
      finally {
        this.statusLoaded = true
      }
    },
    isYoutubeUrl(value) {
      try {
        const parsed = new URL(value)
        return YOUTUBE_HOSTS.includes(parsed.hostname.toLowerCase())
      }
      catch {
        return false
      }
    },
    filenameFromDisposition(header) {
      if (!header)
        return 'audio.mp3'

      const utfMatch = /filename\*\s*=\s*UTF-8''([^;]+)/i.exec(header)
      if (utfMatch?.[1]) {
        try {
          return decodeURIComponent(utfMatch[1].trim().replace(/"/g, ''))
        }
        catch {
          // fall through
        }
      }

      const plainMatch = /filename\s*=\s*"([^"]+)"|filename\s*=\s*([^;]+)/i.exec(header)
      const raw = (plainMatch?.[1] || plainMatch?.[2] || '').trim()

      return raw || 'audio.mp3'
    },
    async readBlobError(blob) {
      try {
        const text = await blob.text()
        const json = JSON.parse(text)

        return json.message || json.error || 'No se pudo descargar el audio.'
      }
      catch {
        return 'No se pudo descargar el audio.'
      }
    },
    triggerBrowserDownload(blob, filename) {
      const objectUrl = URL.createObjectURL(blob)
      const anchor = document.createElement('a')

      anchor.href = objectUrl
      anchor.download = filename
      anchor.rel = 'noopener'
      document.body.appendChild(anchor)
      anchor.click()
      anchor.remove()
      URL.revokeObjectURL(objectUrl)
    },
    async download() {
      this.error = ''
      this.urlError = ''
      this.lastFilename = ''

      const url = String(this.url || '').trim()

      if (!url) {
        this.urlError = 'Pegá una URL.'

        return
      }

      if (!/^https?:\/\//i.test(url)) {
        this.urlError = 'La URL debe empezar con http:// o https://'

        return
      }

      if (!this.isYoutubeUrl(url)) {
        this.urlError = 'Solo se admiten URLs de YouTube.'

        return
      }

      this.downloading = true

      try {
        const response = await axios.post(
          '/api/music/download',
          { url },
          {
            responseType: 'blob',
            timeout: 320000,
          },
        )

        const contentType = String(response.headers['content-type'] || '')

        if (contentType.includes('application/json')) {
          this.error = await this.readBlobError(response.data)

          return
        }

        const filename = this.filenameFromDisposition(response.headers['content-disposition'])
        const blob = new Blob([response.data], { type: 'audio/mpeg' })

        this.triggerBrowserDownload(blob, filename)
        this.lastFilename = filename
        this.$toast.success('MP3 listo', { timeout: 2000, closeOnClick: true })
      }
      catch (err) {
        const data = err?.response?.data

        if (data instanceof Blob) {
          this.error = await this.readBlobError(data)
        }
        else {
          this.error = data?.message
            || err?.message
            || 'No se pudo descargar el audio.'
        }
      }
      finally {
        this.downloading = false
      }
    },
  },
}
</script>

<style lang="scss" scoped>
.music-download__row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  align-items: flex-start;
}

.music-download__url {
  flex: 1 1 16rem;
  min-width: 0;
}

.music-download__action {
  flex: 0 0 auto;
  white-space: nowrap;
}
</style>
