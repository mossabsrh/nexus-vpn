"""Build the mémoire reference.docx from pandoc's default, applying the methodology-guide formatting:
margins 3cm L / 2cm R / 2.5cm T&B, Times New Roman 12 justified single-spaced body, headings 16/14/12 bold,
footnotes Times New Roman 10, page number top-right. Run: python -I make_reference.py <in.docx> <out.docx>

Heading style (Q-12): the guide's rule text says "bold underlined" while its example shows "bold italic".
We default to clean BOLD (no underline/italic). To switch, change set_font(..., underline=True) or italic=True
for Heading 1/2/3 below."""
import sys
from docx import Document
from docx.shared import Pt, Cm, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_LINE_SPACING, WD_TAB_ALIGNMENT
from docx.oxml.ns import qn
from docx.oxml import OxmlElement

doc = Document(sys.argv[1])

NAVY = '1F3864'  # dark navy for the running-header bar and the chapter-title accent

for section in doc.sections:
    section.left_margin = Cm(3)
    section.right_margin = Cm(2)
    section.top_margin = Cm(2.5)
    section.bottom_margin = Cm(2.5)

def set_font(style, size=None, bold=None, italic=None, underline=None, color=None, name='Times New Roman'):
    f = style.font
    f.name = name
    rpr = style.element.get_or_add_rPr()
    rfonts = rpr.find(qn('w:rFonts'))
    if rfonts is None:
        rfonts = OxmlElement('w:rFonts'); rpr.append(rfonts)
    for attr in ('w:ascii', 'w:hAnsi', 'w:cs', 'w:eastAsia'):
        rfonts.set(qn(attr), name)
    if size is not None: f.size = Pt(size)
    if bold is not None: f.bold = bold
    if italic is not None: f.italic = italic
    if underline is not None: f.underline = underline
    if color is not None: f.color.rgb = color

styles = doc.styles

for sname in ['Normal', 'Body Text', 'First Paragraph', 'Compact']:
    try:
        st = styles[sname]
        set_font(st, size=12)
        pf = st.paragraph_format
        pf.line_spacing_rule = WD_LINE_SPACING.SINGLE
        pf.space_after = Pt(6); pf.space_before = Pt(0)
        if sname in ('Normal', 'Body Text', 'First Paragraph'):
            pf.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    except KeyError:
        pass

for hname, size in {'Heading 1': 16, 'Heading 2': 14, 'Heading 3': 12, 'Heading 4': 12}.items():
    try:
        st = styles[hname]
        # Guide rule (p6 "Caractéristiques de présentation"): chapter/section/sub-section titles are
        # 16/14/12, BOLD and UNDERLINED. (The guide's own example on p8 shows bold-italic, and the
        # 19/20 reference uses bold + navy colour without underline — see review-report.md, Q-12.)
        set_font(st, size=size, bold=True, italic=False, underline=True, color=RGBColor(0, 0, 0))
        pf = st.paragraph_format
        pf.space_before = Pt(12); pf.space_after = Pt(6)
        pf.line_spacing_rule = WD_LINE_SPACING.SINGLE
        pf.keep_with_next = True
        pf.alignment = WD_ALIGN_PARAGRAPH.LEFT
        if hname == 'Heading 1':
            # The chapter title gets a title page: centred, pushed down, with a navy accent bar above
            # it (a page break after the title is added in fix_docx.py so the content starts fresh).
            pf.page_break_before = True
            pf.alignment = WD_ALIGN_PARAGRAPH.CENTER
            pf.space_before = Pt(170)
            pf.space_after = Pt(0)
            pf.keep_with_next = False
            hpPr = st.element.get_or_add_pPr()
            pbdr = OxmlElement('w:pBdr')
            top = OxmlElement('w:top')
            top.set(qn('w:val'), 'single'); top.set(qn('w:sz'), '24')
            top.set(qn('w:space'), '10'); top.set(qn('w:color'), NAVY)
            pbdr.append(top)
            hpPr.append(pbdr)
    except KeyError:
        pass

for cname in ['Caption', 'Image Caption', 'Table Caption']:
    try:
        st = styles[cname]
        set_font(st, size=11, bold=False, italic=True)
        st.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.CENTER
    except KeyError:
        pass

for sname in ['Footnote Text']:
    try:
        st = styles[sname]
        set_font(st, size=10)
        st.paragraph_format.line_spacing_rule = WD_LINE_SPACING.SINGLE
        st.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    except KeyError:
        pass

def _white_run(p, size=10, bold=True):
    run = p.add_run()
    run.font.size = Pt(size); run.font.bold = bold
    run.font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)
    rpr = run._r.get_or_add_rPr()
    rf = rpr.find(qn('w:rFonts'))
    if rf is None:
        rf = OxmlElement('w:rFonts'); rpr.append(rf)
    for a in ('w:ascii', 'w:hAnsi', 'w:cs'):
        rf.set(qn(a), 'Times New Roman')
    return run

def _field(run, instr):
    begin = OxmlElement('w:fldChar'); begin.set(qn('w:fldCharType'), 'begin')
    it = OxmlElement('w:instrText'); it.set(qn('xml:space'), 'preserve'); it.text = instr
    end = OxmlElement('w:fldChar'); end.set(qn('w:fldCharType'), 'end')
    run._r.append(begin); run._r.append(it); run._r.append(end)

def add_running_header(section):
    """A navy header bar: the chapter name (STYLEREF of Heading 1) on the left, the page number on
    the right — so the page number stays top-right (guide rule) while the bar carries the chapter name."""
    header = section.header
    header.is_linked_to_previous = False
    p = header.paragraphs[0] if header.paragraphs else header.add_paragraph()
    for r in list(p.runs):
        r._r.getparent().remove(r._r)
    pf = p.paragraph_format
    pf.space_before = Pt(3); pf.space_after = Pt(3)
    # text area width on A4 = 21 - 3(left) - 2(right) = 16 cm -> right tab for the page number
    pf.tab_stops.add_tab_stop(Cm(16), WD_TAB_ALIGNMENT.RIGHT)
    pPr = p._p.get_or_add_pPr()
    shd = OxmlElement('w:shd'); shd.set(qn('w:val'), 'clear'); shd.set(qn('w:color'), 'auto'); shd.set(qn('w:fill'), NAVY)
    pPr.append(shd)
    _field(_white_run(p), ' STYLEREF "Heading 1" \\* MERGEFORMAT ')
    _white_run(p).add_text('\t')
    _field(_white_run(p), ' PAGE ')

for section in doc.sections:
    add_running_header(section)

doc.save(sys.argv[2])
print('reference.docx written:', sys.argv[2])
