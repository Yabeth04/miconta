#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BIN_DIR="${ROOT}/bin"
mkdir -p "${BIN_DIR}"

ARCH="$(uname -m)"
case "${ARCH}" in
  x86_64|amd64) FFMPEG_ARCH="amd64" ;;
  aarch64|arm64) FFMPEG_ARCH="arm64" ;;
  *)
    echo "Arquitectura no soportada para el binario estático de FFmpeg: ${ARCH}"
    exit 1
    ;;
esac

echo "→ Descargando yt-dlp…"
curl -fsSL -L "https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp" \
  -o "${BIN_DIR}/yt-dlp"
chmod +x "${BIN_DIR}/yt-dlp"

echo "→ Descargando FFmpeg estático (${FFMPEG_ARCH})…"
TMP_DIR="$(mktemp -d)"
trap 'rm -rf "${TMP_DIR}"' EXIT

curl -fsSL -o "${TMP_DIR}/ffmpeg.tar.xz" \
  "https://johnvansickle.com/ffmpeg/releases/ffmpeg-release-${FFMPEG_ARCH}-static.tar.xz"
tar -xJf "${TMP_DIR}/ffmpeg.tar.xz" -C "${TMP_DIR}"

FFMPEG_DIR="$(find "${TMP_DIR}" -maxdepth 1 -type d -name "ffmpeg-*-${FFMPEG_ARCH}-static" | head -n 1)"
cp "${FFMPEG_DIR}/ffmpeg" "${BIN_DIR}/ffmpeg"
cp "${FFMPEG_DIR}/ffprobe" "${BIN_DIR}/ffprobe"
chmod +x "${BIN_DIR}/ffmpeg" "${BIN_DIR}/ffprobe"

echo "✓ yt-dlp: $("${BIN_DIR}/yt-dlp" --version)"
echo "✓ ffmpeg: $("${BIN_DIR}/ffmpeg" -version | head -n 1)"
echo "Listo. Binarios en ${BIN_DIR}"
