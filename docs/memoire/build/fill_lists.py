"""Second pass of the full build. Read the built PDF and write the real *printed* page numbers (the ones
in the header, which start at 1 at the general introduction) into the lists of figures / tables and the
short contents. Called by build.sh full, which then rebuilds once so the numbers appear in the document.
Run: python -I fill_lists.py <pdf> <chapters_dir>
"""
import sys
import io
import re
import pymupdf

pdf, chdir = sys.argv[1], sys.argv[2]
d = pymupdf.open(pdf)

# printed(physical_1based) = physical - offset, learned from the first page that shows a header number.
offset = None
for i in range(d.page_count):
    num = None
    for w in d[i].get_text('words'):
        x0, y0, x1, y1, t = w[:5]
        if y0 < 75 and t.strip().isdigit():
            num = int(t.strip())        # rightmost/last digit in the header band
    if num is not None:
        offset = (i + 1) - num
        break
assert offset is not None, 'no header page number found'
body_start = offset + 1                 # physical page where the printed number is 1

def _lines(page, min_size=0):
    """Text lines of a page (case kept), optionally only those set in a font >= min_size pt."""
    out = []
    for b in page.get_text('dict')['blocks']:
        for ln in b.get('lines', []):
            spans = [s for s in ln['spans'] if s['size'] >= min_size]
            t = ''.join(s['text'] for s in spans).strip()
            if t:
                out.append(t)
    return out

def find_printed(key, min_size=0):
    """First page (from the general introduction on) with a line starting with `key`, case-sensitive.
    min_size > 0 restricts the match to heading-size text, so a phrase quoted in the body never wins."""
    for i in range(body_start - 1, d.page_count):
        if any(t.startswith(key) for t in _lines(d[i], min_size)):
            return (i + 1) - offset
    return None

def fill(name, make_key, min_size=0):
    path = chdir + '/' + name
    out, filled, missing = [], 0, []
    for ln in io.open(path, encoding='utf-8').read().split('\n'):
        m = re.match(r'^\|(.+)\|\s*([^|]*?)\s*\|\s*$', ln)
        if m:
            label, last = m.group(1).strip(), m.group(2).strip()
            if last == '«—»' or last.isdigit():   # a page cell («—» or a number); empty cells are skipped
                key = make_key(label)
                pg = find_printed(key, min_size) if key else None
                if pg:
                    ln = '| %s | %d |' % (label, pg)
                    filled += 1
                else:
                    missing.append(label)
        out.append(ln)
    io.open(path, 'w', encoding='utf-8', newline='\n').write('\n'.join(out))
    for lab in missing:
        print('fill_lists: NOT FOUND in %s: %s' % (name, lab))
    return filled

def caption_key(label):   # "Figure III.14a : Title" -> "Figure III.14a —" (the bold caption line)
    m = re.match(r'^((?:Figure|Table)\s+[\w.]+)\s*:', label)
    return (m.group(1) + ' —') if m else None

def contents_key(label):  # the part's Heading 1 text
    mc = re.match(r'^Chapter\s+([IVXLCDM]+)\b', label)
    if mc:
        return 'CHAPTER ' + mc.group(1) + ' —'
    return label.split(' (')[0].strip()

nf = fill('list-of-figures.md', caption_key)
nt = fill('list-of-tables.md', caption_key)
nc = fill('contents.md', contents_key, min_size=15)   # Heading 1 = 16 pt
print('fill_lists: offset=%d, filled figures=%d tables=%d contents=%d' % (offset, nf, nt, nc))
