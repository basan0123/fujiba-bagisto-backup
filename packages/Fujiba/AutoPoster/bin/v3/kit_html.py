#!/usr/bin/env python3
"""Render docs/production/production-kit.md to a self-contained printable HTML page.

    python3 kit_html.py <in.md> <out.html> [contact_sheet.jpg]

Minimal Markdown subset (headings, paragraphs, lists, task lists, tables, code blocks, bold,
inline code). The contact sheet (optional) is embedded as a data URI. Only Google Fonts external.
"""
import base64
import html
import re
import sys


def inline(s):
    s = html.escape(s)
    s = re.sub(r'`([^`]+)`', r'<code>\1</code>', s)
    s = re.sub(r'\*\*([^*]+)\*\*', r'<strong>\1</strong>', s)
    return s


def convert(md):
    out, lines, i = [], md.split('\n'), 0
    while i < len(lines):
        ln = lines[i]
        if ln.startswith('```'):
            j = i + 1
            while not lines[j].startswith('```'):
                j += 1
            out.append('<pre>' + html.escape('\n'.join(lines[i + 1:j])) + '</pre>')
            i = j + 1
            continue
        m = re.match(r'(#{1,4}) (.*)', ln)
        if m:
            n = len(m.group(1))
            out.append(f'<h{n}>{inline(m.group(2))}</h{n}>')
        elif ln.startswith('|'):
            rows = []
            while i < len(lines) and lines[i].startswith('|'):
                rows.append([c.strip() for c in lines[i].strip('|').split('|')])
                i += 1
            t = '<table><thead><tr>' + ''.join(f'<th>{inline(c)}</th>' for c in rows[0]) + '</tr></thead><tbody>'
            for r in rows[2:]:
                t += '<tr>' + ''.join(f'<td>{inline(c)}</td>' for c in r) + '</tr>'
            out.append(t + '</tbody></table>')
            continue
        elif re.match(r'(\d+\.|-) ', ln):
            tag = 'ol' if ln[0].isdigit() else 'ul'
            items = []
            while i < len(lines) and re.match(r'(\d+\.|-) ', lines[i]):
                it = re.sub(r'^(\d+\.|-) ', '', lines[i])
                it = inline(it).replace('[ ] ', '<span class="box"></span>', 1)
                items.append(f'<li>{it}</li>')
                i += 1
            out.append(f'<{tag}>' + ''.join(items) + f'</{tag}>')
            continue
        elif ln.strip():
            out.append(f'<p>{inline(ln)}</p>')
        i += 1
    return '\n'.join(out)


CSS = """
:root{--ink:#1d1a22;--muted:#6e6860;--rose:#b25c68;--line:#e2d9c8;--paper:#fbf8f2}
body{background:var(--paper);color:var(--ink);font:16px/1.6 'Noto Sans','Noto Sans Myanmar','Noto Sans Devanagari','Noto Sans JP',sans-serif;margin:0}
main{max-width:860px;margin:0 auto;padding:32px 16px 64px}
h1{font-family:'Playfair Display',serif;font-size:2.2em;margin:.2em 0;color:var(--rose)}
h2{font-family:'Playfair Display',serif;border-bottom:2px solid var(--line);padding-bottom:4px;margin-top:2em}
h3{margin-top:1.5em}
table{border-collapse:collapse;width:100%;font-size:.9em;margin:1em 0;display:block;overflow-x:auto}
th,td{border:1px solid var(--line);padding:6px 8px;text-align:left;vertical-align:top}
th{background:#f1e9da}
code{background:#f1e9da;padding:1px 4px;border-radius:3px;font-size:.88em;word-break:break-all}
pre{background:#fff;border:1px solid var(--line);padding:12px;white-space:pre-wrap;font-size:.9em}
.box{display:inline-block;width:14px;height:14px;border:1.5px solid var(--ink);margin-right:8px;vertical-align:-2px}
ul,ol{padding-left:1.4em} img{max-width:100%;border:1px solid var(--line)}
@media print{body{background:#fff;font-size:11pt}main{max-width:none;padding:0}h2{break-before:page}h2:first-of-type{break-before:auto}
table,pre,img,li{break-inside:avoid}table{display:table}@page{size:A4;margin:14mm}}
"""


def main():
    md = open(sys.argv[1], encoding='utf-8').read()
    body = convert(md)
    if len(sys.argv) > 3:
        b64 = base64.b64encode(open(sys.argv[3], 'rb').read()).decode()
        body += f'<h2>Contact sheet (preview concepts)</h2><img alt="contact sheet" src="data:image/jpeg;base64,{b64}">'
    page = ('<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            '<title>Fujiba Production Kit</title><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;700'
            '&family=Noto+Sans+Myanmar&family=Noto+Sans+Devanagari&family=Noto+Sans+JP&family=Playfair+Display:wght@700&display=swap">'
            f'<style>{CSS}</style></head><body><main>{body}</main></body></html>')
    open(sys.argv[2], 'w', encoding='utf-8').write(page)


if __name__ == '__main__':
    main()
