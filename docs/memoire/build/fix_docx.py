"""Post-process the pandoc-generated .docx before the PDF step.

Pandoc injects a 'Source Code' paragraph style based on the (justified) Normal style, so code
blocks are justified — each line's last token is pushed to the right margin. Code must be
left-aligned. This style only exists in the *output* docx, so we fix it here rather than in the
reference template. Run: python -I fix_docx.py <docx>
"""
import re
import sys
from docx import Document
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_LINE_SPACING
from docx.shared import Pt

doc = Document(sys.argv[1])

# 1. Code blocks: left-aligned monospace (see module docstring).
try:
    sc = doc.styles['Source Code']
    sc.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.LEFT
    sc.paragraph_format.line_spacing_rule = WD_LINE_SPACING.SINGLE
    sc.font.size = Pt(9)
except KeyError:
    print('no Source Code style found')

# 2. Figure / table / listing captions: left-align them (they are plain Normal paragraphs and
# would otherwise inherit the justified body alignment, which spreads short caption lines). The
# pattern requires the "N.N —" after the keyword, so in-text references ("Figure III.5 shows ...")
# are not matched.
caption = re.compile(r'^(Figure|Table|Listing)\s+[IVXLCDM]+\.\d+\s+[—\-]')
n = 0
for p in doc.paragraphs:
    if caption.match(p.text):
        # Centered captions, as in the 19/20 reference (the guide only requires the source below).
        p.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.CENTER
        n += 1

doc.save(sys.argv[1])
print('post-processed: code left-aligned, %d captions centred:' % n, sys.argv[1])
