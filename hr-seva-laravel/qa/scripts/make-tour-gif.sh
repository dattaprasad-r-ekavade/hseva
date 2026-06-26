#!/usr/bin/env bash
# Convert the latest Playwright onboarding-tour video to a compressed GIF for docs/PRs.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ARTIFACTS="$ROOT/artifacts/onboarding-tour"
OUT_DIR="$ROOT/artifacts"
OUT_GIF="$OUT_DIR/onboarding-guided-tour.gif"
OUT_MP4="$OUT_DIR/onboarding-guided-tour.mp4"

VIDEO="$(find "$ARTIFACTS" -name 'video.webm' -printf '%T@ %p\n' 2>/dev/null | sort -rn | head -1 | cut -d' ' -f2-)"

if [[ -z "${VIDEO:-}" || ! -f "$VIDEO" ]]; then
  echo "No Playwright video found under $ARTIFACTS — run: npm run test:tour" >&2
  exit 1
fi

mkdir -p "$OUT_DIR"

if command -v ffmpeg >/dev/null 2>&1; then
  ffmpeg -y -i "$VIDEO" -vf "fps=10,scale=1280:-1:flags=lanczos,split[s0][s1];[s0]palettegen[p];[s1][p]paletteuse" "$OUT_GIF"
  ffmpeg -y -i "$VIDEO" -c:v libx264 -pix_fmt yuv420p -movflags +faststart "$OUT_MP4"
  echo "Wrote $OUT_GIF"
  echo "Wrote $OUT_MP4"
else
  cp "$VIDEO" "$OUT_DIR/onboarding-guided-tour.webm"
  echo "ffmpeg not found — copied webm to $OUT_DIR/onboarding-guided-tour.webm"
fi
