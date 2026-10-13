"""Build the mémoire reference.docx from pandoc's default, applying the methodology-guide formatting:
margins 3cm L / 2cm R / 2.5cm T&B, Times New Roman 12 justified single-spaced body, headings 16/14/12 bold,
footnotes Times New Roman 10, page number top-right. Run: python -I make_reference.py <in.docx> <out.docx>

Heading style (Q-12): the guide's rule text says "bold underlined" while its example shows "bold italic".
We default to clean BOLD (no underline/italic). To switch, change set_font(..., underline=True) or italic=True
for Heading 1/2/3 below."""
import sys
from docx import Document
from docx.shared import Pt, Cm, Mm, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_LINE_SPACING, WD_TAB_ALIGNMENT
from docx.enum.section import WD_ORIENT
from docx.enum.style import WD_STYLE_TYPE
from docx.oxml.ns import qn
from docx.oxml import OxmlElement

doc = Document(sys.argv[1])

NAVY = '1F3864'  # dark navy for the running-header bar and the chapter-title accent

for section in doc.sections:
    # A4 explicitly: pandoc's default reference.docx is US Letter, which LibreOffice would keep.
    section.orientation = WD_ORIENT.PORTRAIT
    section.page_width = Mm(210)
    section.page_height = Mm(297)
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
        # Heading 1 is a plain heading here. fix_docx.py decides per part how each Heading 1 is laid out:
        # chapters get the centred, pushed-down title page with the navy accent bar; the general
        # introduction/conclusion/bibliography/appendices get a plain top heading; the front-matter
        # parts (dedication, lists, …) get a centred heading with no running header or page number.
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

# Cover / title-page styles (pandoc maps the custom-style divs in cover.md to these). The guide's
# exact sizes: institute 18 bold, diploma 16 bold, speciality 14 bold, THEME 16 bold, title 20 bold,
# presented-by / supervised-by 14 bold, promotion 14 bold.
def cover_style(name, size, bold=True, italic=False, before=0, after=10, color=None):
    try:
        st = doc.styles[name]
    except KeyError:
        st = doc.styles.add_style(name, WD_STYLE_TYPE.PARAGRAPH)
    set_font(st, size=size, bold=bold, italic=italic, color=color)
    pf = st.paragraph_format
    pf.alignment = WD_ALIGN_PARAGRAPH.CENTER
    pf.line_spacing_rule = WD_LINE_SPACING.SINGLE
    pf.space_before = Pt(before); pf.space_after = Pt(after)

cover_style('CoverInstitute', 18, before=6, after=4)
cover_style('CoverMinistry', 14, before=4, after=2)
cover_style('CoverInstituteFr', 13, bold=False, before=2, after=0)
cover_style('CoverCentre', 12, bold=False, italic=True, before=0, after=10)
cover_style('CoverLogos', 11, bold=False, before=24, after=24, color=RGBColor(0x80, 0x80, 0x80))
cover_style('CoverDiploma', 16, before=24, after=10)
cover_style('CoverSpeciality', 14, before=4, after=10)
cover_style('CoverTheme', 16, before=30, after=8)
cover_style('CoverTitle', 20, before=6, after=6)
cover_style('CoverCase', 14, before=2, after=8)
cover_style('CoverNote', 10, bold=False, italic=True, before=0, after=24, color=RGBColor(0x80, 0x80, 0x80))
cover_style('CoverPeople', 14, before=18, after=4)
cover_style('CoverPromotion', 14, before=30, after=0)

# Abstracts page (ملخص / Résumé / Abstract on one page, as in the reference): centred bold titles,
# justified paragraphs; the Arabic styles are right-to-left (paragraph bidi + run rtl). Complex-script
# size/bold (szCs/bCs) are set too, since Word and LibreOffice size Arabic text from them.
def cs_props(st, size, bold=False, rtl=False):
    rpr = st.element.get_or_add_rPr()
    for tag in ('w:bCs', 'w:szCs', 'w:rtl'):
        for old in rpr.findall(qn(tag)):
            rpr.remove(old)
    if bold:
        rpr.append(OxmlElement('w:bCs'))
    sz = OxmlElement('w:szCs'); sz.set(qn('w:val'), str(size * 2)); rpr.append(sz)
    if rtl:
        rpr.append(OxmlElement('w:rtl'))
        pPr = st.element.get_or_add_pPr()
        if pPr.find(qn('w:bidi')) is None:
            pPr.insert(0, OxmlElement('w:bidi'))

cover_style('AbstractTitle', 14, before=14, after=8)
cs_props(doc.styles['AbstractTitle'], 16, bold=True)
for name, rtl in (('AbstractBody', False), ('AbstractKeywords', False),
                  ('AbstractBodyAr', True), ('AbstractKeywordsAr', True)):
    cover_style(name, 12, bold=False, before=0, after=6)
    doc.styles[name].paragraph_format.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    cs_props(doc.styles[name], 14 if rtl else 12, rtl=rtl)

# Dedication and acknowledgements pages: italic dedication text with the author's name bold-italic at
# the bottom right (as in the reference); acknowledgements in a slightly larger, airy justified body.
cover_style('DedicationBody', 13, bold=False, italic=True, before=0, after=10)
doc.styles['DedicationBody'].paragraph_format.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
doc.styles['DedicationBody'].paragraph_format.line_spacing = 1.3
cover_style('DedicationLead', 14, bold=True, italic=True, before=60, after=24)
cover_style('DedicationSign', 13, bold=True, italic=True, before=120, after=0)
doc.styles['DedicationSign'].paragraph_format.alignment = WD_ALIGN_PARAGRAPH.RIGHT
cover_style('AckBody', 12.5, bold=False, before=0, after=12)
doc.styles['AckBody'].paragraph_format.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
doc.styles['AckBody'].paragraph_format.line_spacing = 1.3
doc.styles['AckBody'].paragraph_format.first_line_indent = Cm(1)

# Reference look: the theme title sits in a light-blue box with a navy border, and the promotion in
# its own box (colours/borders only — fonts and sizes are unchanged). Title and case line share one box.
def box_style(name, fill='EAF2FB', edges=('top', 'left', 'bottom', 'right'), sz='18'):
    st = doc.styles[name]
    pPr = st.element.get_or_add_pPr()
    for old in pPr.findall(qn('w:shd')):
        pPr.remove(old)
    shd = OxmlElement('w:shd'); shd.set(qn('w:val'), 'clear'); shd.set(qn('w:color'), 'auto'); shd.set(qn('w:fill'), fill)
    pPr.append(shd)
    for old in pPr.findall(qn('w:pBdr')):
        pPr.remove(old)
    pbdr = OxmlElement('w:pBdr')
    for edge in edges:
        e = OxmlElement('w:' + edge)
        e.set(qn('w:val'), 'single'); e.set(qn('w:sz'), sz); e.set(qn('w:space'), '8'); e.set(qn('w:color'), NAVY)
        pbdr.append(e)
    pPr.append(pbdr)

# Identical borders on both paragraphs, so Word/LibreOffice merge them into one continuous box
# (different edge sets left a gap in the side borders between the two paragraphs).
box_style('CoverTitle')
box_style('CoverCase')
box_style('CoverPromotion', edges=('top', 'left', 'bottom', 'right'))

# The navy running header is built per chapter in fix_docx.py (plain chapter title + PAGE field),
# after the per-chapter Word sections are created. We no longer use a STYLEREF "Heading 1" field:
# French Word localises the style name to "Titre 1", so STYLEREF "Heading 1" renders an error in the
# header. Writing the title as plain text avoids that entirely.

doc.save(sys.argv[2])
print('reference.docx written:', sys.argv[2])
