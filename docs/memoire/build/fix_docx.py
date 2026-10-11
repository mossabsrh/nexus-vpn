"""Post-process the pandoc-generated .docx before the PDF step.

Pandoc injects a 'Source Code' paragraph style based on the (justified) Normal style, so code
blocks are justified — each line's last token is pushed to the right margin. Code must be
left-aligned. This style only exists in the *output* docx, so we fix it here rather than in the
reference template. Run: python -I fix_docx.py <docx>
"""
import re
import sys
from copy import deepcopy
from docx import Document
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_LINE_SPACING, WD_BREAK
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.shared import Pt
from docx.oxml.ns import qn
from docx.oxml import OxmlElement

doc = Document(sys.argv[1])
DXA = 567  # twips per cm
TEXT_CM = 16.0  # A4 text width: 21 - 3 (left) - 2 (right)

# 0. Chapter title pages: each Heading 1 is centred and pushed down its own page by the reference
# template; add a page break *after* the title so the chapter content begins on the next page.
for p in doc.paragraphs:
    if p.style is not None and p.style.name == 'Heading 1':
        p.add_run().add_break(WD_BREAK.PAGE)

# 1. Code blocks: left-aligned monospace (see module docstring).
CODE_PT = 8.5  # fits reflowed lines (<= ~80 chars) on one line without breaking tokens
try:
    sc = doc.styles['Source Code']
    sc.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.LEFT
    sc.paragraph_format.line_spacing_rule = WD_LINE_SPACING.SINGLE
    sc.font.size = Pt(CODE_PT)
except KeyError:
    print('no Source Code style found')
# pandoc gives each highlighted token its own run properties, which override the style size, so set
# the size on every run inside a code paragraph too.
for p in doc.paragraphs:
    if p.style is not None and p.style.name in ('Source Code', 'SourceCode'):
        for run in p.runs:
            run.font.size = Pt(CODE_PT)

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

# 3. Separate consecutive footnote markers: pandoc renders adjacent references ([^a][^b]) as jammed
# superscripts ("1213"); the guide wants them separated. Insert a superscript comma between two
# adjacent footnote reference runs so they read "12,13".
def _is_footnote_ref(r):
    return r.find(qn('w:footnoteReference')) is not None

def _superscript_comma():
    r = OxmlElement('w:r')
    rpr = OxmlElement('w:rPr')
    va = OxmlElement('w:vertAlign'); va.set(qn('w:val'), 'superscript')
    rpr.append(va)
    r.append(rpr)
    t = OxmlElement('w:t'); t.set(qn('xml:space'), 'preserve'); t.text = ','
    r.append(t)
    return r

def _separate(paragraphs):
    count = 0
    for p in paragraphs:
        prev = None
        for r in p._p.findall(qn('w:r')):
            if prev is not None and _is_footnote_ref(prev) and _is_footnote_ref(r):
                prev.addnext(_superscript_comma())
                count += 1
            prev = r
    return count

seps = _separate(doc.paragraphs)
for tbl in doc.tables:
    for row in tbl.rows:
        for cell in row.cells:
            seps += _separate(cell.paragraphs)

# 4. Tables restyled to the 19/20 reference: full thin-black grid, bold centred header repeated on
# page breaks, cells vertically centred, short values centred and longer text left-aligned, purposeful
# column widths (longest token never broken), rows never split across pages; the data dictionary gets
# its entity column merged vertically; use-case tables get a narrow bold label column.
def _mk(tag, **attrs):
    e = OxmlElement(tag)
    for k, v in attrs.items():
        e.set(qn('w:' + k), v)
    return e

def _tbl_borders(tbl):
    tblPr = tbl._tbl.tblPr
    for old in tblPr.findall(qn('w:tblBorders')):
        tblPr.remove(old)
    b = OxmlElement('w:tblBorders')
    for edge in ('top', 'left', 'bottom', 'right', 'insideH', 'insideV'):
        b.append(_mk('w:' + edge, val='single', sz='4', space='0', color='000000'))
    tblPr.append(b)

def _fixed_layout(tbl):
    tblPr = tbl._tbl.tblPr
    lay = tblPr.find(qn('w:tblLayout'))
    if lay is None:
        lay = OxmlElement('w:tblLayout'); tblPr.append(lay)
    lay.set(qn('w:type'), 'fixed')

def _cell_margins(tbl):
    # Tighter horizontal cell padding so narrow columns keep their word on one line.
    tblPr = tbl._tbl.tblPr
    for old in tblPr.findall(qn('w:tblCellMar')):
        tblPr.remove(old)
    m = OxmlElement('w:tblCellMar')
    for side, cm in (('top', 0.05), ('bottom', 0.05), ('left', 0.12), ('right', 0.12)):
        m.append(_mk('w:' + side, w=str(int(cm * DXA)), type='dxa'))
    tblPr.append(m)

def _valign_center(cell):
    tcPr = cell._tc.get_or_add_tcPr()
    v = tcPr.find(qn('w:vAlign'))
    if v is None:
        v = OxmlElement('w:vAlign'); tcPr.append(v)
    v.set(qn('w:val'), 'center')

def _cantsplit(row):
    trPr = row._tr.get_or_add_trPr()
    trPr.append(_mk('w:cantSplit', val='true'))

def _tblheader(row):
    trPr = row._tr.get_or_add_trPr()
    trPr.append(_mk('w:tblHeader', val='true'))

def _cell_style(cell, size, bold, align):
    for para in cell.paragraphs:
        if align is not None:
            para.alignment = align
        para.paragraph_format.space_after = Pt(1)
        para.paragraph_format.space_before = Pt(1)
        for run in para.runs:
            run.font.size = Pt(size)
            if bold is not None:
                run.font.bold = bold

def _set_widths(tbl, widths_cm):
    total = sum(widths_cm)
    tblPr = tbl._tbl.tblPr
    tblW = tblPr.find(qn('w:tblW'))
    if tblW is None:
        tblW = OxmlElement('w:tblW'); tblPr.append(tblW)
    tblW.set(qn('w:type'), 'dxa'); tblW.set(qn('w:w'), str(int(total * DXA)))
    grid = tbl._tbl.find(qn('w:tblGrid'))
    if grid is not None:
        for gc in list(grid.findall(qn('w:gridCol'))):
            grid.remove(gc)
        for w in widths_cm:
            grid.append(_mk('w:gridCol', w=str(int(w * DXA))))
    for row in tbl.rows:
        seen = set()
        for j, cell in enumerate(row.cells):
            tc = cell._tc
            if id(tc) in seen or j >= len(widths_cm):
                continue
            seen.add(id(tc))
            tcPr = tc.get_or_add_tcPr()
            tcW = tcPr.find(qn('w:tcW'))
            if tcW is None:
                tcW = OxmlElement('w:tcW'); tcPr.append(tcW)
            tcW.set(qn('w:type'), 'dxa'); tcW.set(qn('w:w'), str(int(widths_cm[j] * DXA)))

def _auto_widths(tbl):
    ncol = len(tbl.columns)
    need = [1.0] * ncol
    weight = [1.0] * ncol
    for row in tbl.rows:
        for j in range(ncol):
            try:
                t = row.cells[j].text.strip()
            except Exception:
                continue
            # Word breaks lines on whitespace and hyphens only (not on '/'), so a path like
            # /api/admin/* is one unbreakable token; size the column to the longest such token.
            longest = max((len(tok) for tok in re.split(r'[\s\-]+', t)), default=1)
            need[j] = max(need[j], longest * 0.2 + 0.4)   # fit the longest unbreakable token + padding
            weight[j] = max(weight[j], len(t))
    # Cap each column's must-have width so one very long token (e.g. a long test name or identifier,
    # which simply wraps) can't crowd the other columns below the width their own tokens need.
    need = [min(w, TEXT_CM * 0.5) for w in need]
    base = sum(need)
    if base >= TEXT_CM:
        return [w * TEXT_CM / base for w in need]
    extra, wsum = TEXT_CM - base, sum(weight)
    return [need[j] + extra * weight[j] / wsum for j in range(ncol)]

def _vmerge_first_col(tbl):
    prev = None
    for i, row in enumerate(tbl.rows):
        if i == 0:
            continue
        cell = row.cells[0]
        txt = cell.text.strip()
        tcPr = cell._tc.get_or_add_tcPr()
        for old in tcPr.findall(qn('w:vMerge')):
            tcPr.remove(old)
        if txt and txt == prev:
            tcPr.append(_mk('w:vMerge', val='continue'))
            for para in cell.paragraphs:
                for r in list(para.runs):
                    r._r.getparent().remove(r._r)
        else:
            tcPr.append(_mk('w:vMerge', val='restart'))
            prev = txt

tbl_n = 0
for tbl in doc.tables:
    rows = tbl.rows
    if not rows:
        continue
    ncol = len(tbl.columns)
    hdr = [rows[0].cells[j].text.strip() for j in range(ncol)]
    is_dict = hdr[:6] == ['Entity', 'Field', 'Meaning', 'Type', 'Size', 'Constraint']
    is_usecase = (ncol == 2 and hdr[0] == '' and hdr[1] == '')
    _tbl_borders(tbl)
    _fixed_layout(tbl)
    _cell_margins(tbl)
    tbl.alignment = WD_TABLE_ALIGNMENT.CENTER
    size = 10 if is_dict else 11
    if is_dict:
        _set_widths(tbl, [3.4, 4.0, 3.25, 1.2, 0.95, 3.2])
    elif is_usecase:
        _set_widths(tbl, [3.3, 12.7])
    else:
        _set_widths(tbl, _auto_widths(tbl))
    for i, row in enumerate(rows):
        _cantsplit(row)
        is_header = (i == 0 and not is_usecase)
        if is_header:
            _tblheader(row)
        for j, cell in enumerate(row.cells):
            _valign_center(cell)
            txt = cell.text.strip()
            if is_header:
                _cell_style(cell, size, True, WD_ALIGN_PARAGRAPH.CENTER)
            elif is_usecase:
                _cell_style(cell, size, None, WD_ALIGN_PARAGRAPH.LEFT)
            else:
                short = len(txt) <= 14 and '\n' not in txt
                _cell_style(cell, size, None,
                            WD_ALIGN_PARAGRAPH.CENTER if short else WD_ALIGN_PARAGRAPH.LEFT)
    if is_dict:
        _vmerge_first_col(tbl)
    tbl_n += 1

# 5. Chapter title pages carry no running header and no page number (methodology guide): start each
# chapter in its own section (NEXT_PAGE) and turn on "different first page" with an empty first-page
# header, so the title page (the first page of each chapter's section) shows no header bar or number,
# while the body pages keep the navy header. The default header (navy bar + STYLEREF + PAGE) is copied
# with the section properties, so every section keeps it.
body = doc.element.body
final_sectPr = body.find(qn('w:sectPr'))

def _nextpage_sectpr():
    sp = deepcopy(final_sectPr)
    t = sp.find(qn('w:type'))
    if t is None:
        t = OxmlElement('w:type'); sp.insert(0, t)
    t.set(qn('w:val'), 'nextPage')
    return sp

h1s = [p for p in doc.paragraphs if p.style is not None and p.style.name == 'Heading 1']
for p in h1s[1:]:
    el = p._p
    prev = el.getprevious()
    sp = _nextpage_sectpr()
    if prev is not None and prev.tag == qn('w:p'):
        pPr = prev.find(qn('w:pPr'))
        if pPr is None:
            pPr = OxmlElement('w:pPr'); prev.insert(0, pPr)
        old = pPr.find(qn('w:sectPr'))
        if old is not None:
            pPr.remove(old)
        pPr.append(sp)
    else:
        newp = OxmlElement('w:p')
        pPr = OxmlElement('w:pPr'); newp.append(pPr); pPr.append(sp)
        el.addprevious(newp)

for section in doc.sections:
    section.different_first_page_header_footer = True
    fph = section.first_page_header  # creates an empty first-page header part + reference
    for para in fph.paragraphs:
        for r in list(para.runs):
            r._r.getparent().remove(r._r)

# Footnotes must number continuously across the whole document. The copied section properties carry a
# <w:footnotePr> that would restart footnote numbering each section (which LibreOffice renders as "0");
# strip it from every section so numbering stays continuous.
for sectPr in doc.element.body.iter(qn('w:sectPr')):
    for tag in ('w:footnotePr', 'w:endnotePr'):
        for e in sectPr.findall(qn(tag)):
            sectPr.remove(e)
print('sections (chapters):', len(doc.sections))

doc.save(sys.argv[1])
print('post-processed: code left-aligned, %d captions centred, %d tables styled, %d footnote separators:'
      % (n, tbl_n, seps), sys.argv[1])
