#!/usr/bin/env bash
# Build the mémoire Word + PDF from the Markdown chapters.
#   Usage:  bash build/build.sh ch2 ch3        (from docs/memoire)
#   Output: build/out/memoire-<slug>.docx and .pdf, plus build/out/pages/*.png for visual review.
#
# Pipeline: preprocess.py (strip draft comments/Notes heading/HR, fix image paths + sizes, concat)
#           -> pandoc (Markdown -> Word, using the methodology-guide reference.docx)
#           -> LibreOffice (Word -> PDF)
#           -> PyMuPDF (PDF -> one PNG per page, for checking).
set -euo pipefail

PANDOC="/c/Users/My_Pc/AppData/Local/Pandoc/pandoc.exe"
SOFFICE="/c/Program Files/LibreOffice/program/soffice.exe"

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"   # docs/memoire/build
ROOT="$(cd "$HERE/.." && pwd)"                          # docs/memoire
cd "$ROOT"

chapters=("$@"); [ ${#chapters[@]} -gt 0 ] || chapters=(ch2 ch3)
slug=$(IFS=-; echo "${chapters[*]}")
mkdir -p build/out/pages
combined="build/out/combined.md"
docx="build/out/memoire-${slug}.docx"
pdf="build/out/memoire-${slug}.pdf"

# 1. (Re)build the reference.docx from pandoc's default + the methodology-guide styles.
if [ ! -f build/reference-default.docx ]; then
  "$PANDOC" -o build/reference-default.docx --print-default-data-file reference.docx
fi
python -I build/make_reference.py build/reference-default.docx build/reference.docx

# 2. Preprocess + concatenate the chapter Markdown.
src=(); for c in "${chapters[@]}"; do src+=("chapters/${c}.md"); done
python -I build/preprocess.py "$combined" "$ROOT" "${src[@]}"

# 3. Markdown -> Word (footnotes, figures and tables are native; run from docs/memoire so figures/ resolves).
# -implicit_figures: we write our own bold caption line under each image, so disable pandoc's
# auto-caption (from the alt text) to avoid a duplicate caption under every figure.
"$PANDOC" "$combined" --reference-doc=build/reference.docx \
  --from=markdown-implicit_figures --to=docx --resource-path="$ROOT" -o "$docx"
python -I build/fix_docx.py "$docx"
echo "docx written: $docx"

# 4. Word -> PDF.
"$SOFFICE" --headless --convert-to pdf --outdir build/out "$docx" >/dev/null 2>&1 || true
[ -f "$pdf" ] && echo "pdf written:  $pdf"

# 5. PDF -> one PNG per page (for visual review).
python -I - "$pdf" build/out/pages <<'PY'
import sys, pymupdf
doc = pymupdf.open(sys.argv[1]); out = sys.argv[2]
for i, page in enumerate(doc, 1):
    page.get_pixmap(dpi=110).save(f"{out}/p{i:02d}.png")
print(f"rendered {doc.page_count} pages -> {out}/")
PY
