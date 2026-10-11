"""Prepare chapter Markdown for pandoc: strip the draft HTML comments and the "## Notes" heading (the
footnote definitions under it are kept and become real Word footnotes), rewrite image paths from
../figures/ to figures/ (so pandoc run from docs/memoire resolves them), and add a width/height
attribute to each image so it fits the ~16 cm text area (<= 15 cm wide, <= 20 cm tall, aspect kept).
Several chapter files are concatenated. Run:
  python -I preprocess.py <out.md> <base_dir> <chapter1.md> [chapter2.md ...]
"""
import os
import re
import struct
import sys


def png_size(path):
    try:
        with open(path, 'rb') as f:
            head = f.read(26)
        if head[:8] != b'\x89PNG\r\n\x1a\n':
            return None
        return struct.unpack('>II', head[16:24])
    except OSError:
        return None


def size_attr(path):
    sz = png_size(path)
    if not sz:
        return ''
    w, h = sz
    # width 15 cm unless that makes it taller than 20 cm, then cap the height
    if 15.0 * h / w <= 20.0:
        return '{width=15cm}'
    return '{height=20cm}'


CAPTION_RE = re.compile(
    r'^(?P<title>\*\*(?:Figure|Table|Listing)\b.+?\*\*)\s+(?P<rest>\*Source:.*)$')


def split_captions(text):
    """Move each element's caption *title* ABOVE the element (bold, centred) and leave the *source*
    BELOW it (italic, centred). Input captions are single lines: '**Figure X — title.** *Source: …*'.
    The element above is a table (lines starting with '|'), an image, or a fenced code block."""
    lines = text.split('\n')
    out = []
    for line in lines:
        m = CAPTION_RE.match(line.strip())
        if not m:
            out.append(line)
            continue
        title, rest = m.group('title'), m.group('rest')
        j = len(out)
        while j > 0 and out[j - 1].strip() == '':
            j -= 1
        if j == 0:
            out.append(line)                      # nothing above -> leave as-is
            continue
        last = out[j - 1].lstrip()
        if last.startswith('|'):                  # table
            k = j
            while k > 0 and out[k - 1].lstrip().startswith('|'):
                k -= 1
            start = k
        elif out[j - 1].rstrip().endswith('```'):  # fenced code block
            k = j - 2
            while k > 0 and not out[k].lstrip().startswith('```'):
                k -= 1
            start = k
        elif re.match(r'!\[[^\]]*\]\([^)]+\)', last):  # image
            start = j - 1
        else:
            out.append(line)                      # unknown element -> leave as-is
            continue
        out[start:start] = [title, '']            # title + blank above the element
        if out and out[-1].strip() != '':
            out.append('')
        out.append(rest)                          # source (+ any trailing footnote markers) below
    return '\n'.join(out)


def process(text, base_dir):
    text = re.sub(r'<!--.*?-->', '', text, flags=re.DOTALL)
    text = re.sub(r'^## Notes\s*$', '', text, flags=re.MULTILINE)
    # Drop standalone thematic breaks (---) used only to separate the Notes block;
    # otherwise they render as a stray horizontal rule at the chapter end. Pipe-table
    # separators (| --- |) contain '|' and are not matched.
    text = re.sub(r'^-{3,}\s*$', '', text, flags=re.MULTILINE)
    # Pandoc's docx writer ignores raw <u> HTML; its native way to underline is a span with
    # the .underline class. Convert <u>..</u> (used for primary keys in the relational model).
    text = re.sub(r'<u>(.*?)</u>', r'[\1]{.underline}', text, flags=re.DOTALL)

    def repl(m):
        alt, path, given = m.group(1), m.group(2), m.group(3)
        newpath = path.replace('../figures/', 'figures/')
        # Keep an explicit size the author wrote (e.g. {width=13cm} for a screenshot); otherwise
        # auto-fit to the text area based on the PNG's own dimensions.
        attr = given if given else size_attr(os.path.join(base_dir, newpath))
        return f'![{alt}]({newpath}){attr}'

    text = re.sub(r'!\[([^\]]*)\]\(([^)]+)\)(\{[^}]*\})?', repl, text)
    text = split_captions(text)
    return text.strip() + '\n'


def main():
    out, base, files = sys.argv[1], sys.argv[2], sys.argv[3:]
    parts = []
    for fp in files:
        with open(fp, encoding='utf-8') as f:
            parts.append(process(f.read(), base))
    with open(out, 'w', encoding='utf-8') as f:
        f.write('\n\n'.join(parts))
    print('combined markdown written:', out, '(%d chapters)' % len(files))


if __name__ == '__main__':
    main()
