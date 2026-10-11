// Render a Mermaid .mmd source to a white-background PNG with Playwright (Chromium) + Mermaid from a CDN.
// Run from the repo root:
//   NODE_PATH=./node_modules node docs/memoire/build/render-mermaid.js <in.mmd> <out.png> [scale]
const { chromium } = require('playwright')
const fs = require('fs')

;(async () => {
  const [, , input, output, scaleArg] = process.argv
  const scale = Number(scaleArg || 3)
  const src = fs.readFileSync(input, 'utf8')
  const html = `<!doctype html><html><head><meta charset="utf-8">
<script src="https://cdn.jsdelivr.net/npm/mermaid@11.4.1/dist/mermaid.min.js"></script></head>
<body style="margin:0;background:#fff">
<div id="c" class="mermaid">${src.replace(/</g, '&lt;')}</div>
<script>
  mermaid.initialize({ startOnLoad:false, theme:'neutral', securityLevel:'loose',
    flowchart:{ htmlLabels:true }, themeVariables:{ fontSize:'16px' } });
  window.__done = mermaid.run({ nodes: document.querySelectorAll('#c') }).then(()=>true).catch(e=>{document.title='ERR:'+e;return false});
</script></body></html>`
  const browser = await chromium.launch()
  const page = await browser.newPage({ deviceScaleFactor: scale })
  await page.setContent(html, { waitUntil: 'networkidle' })
  const ok = await page.evaluate(() => window.__done)
  if (!ok) { console.error('mermaid error:', await page.title()); process.exit(1) }
  await page.waitForTimeout(300)
  const el = await page.$('#c svg')
  if (!el) { console.error('no SVG produced'); process.exit(1) }
  await el.screenshot({ path: output })
  await browser.close()
  console.log('rendered mermaid ->', output)
})().catch((e) => { console.error(e); process.exit(1) })
