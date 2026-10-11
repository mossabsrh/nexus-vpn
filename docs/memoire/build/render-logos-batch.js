// Rasterize each technology logo (svg/png/ico) in a source dir to a uniform-height, trimmed,
// transparent PNG (for the per-technology logos in Chapter IV.1).
// Run from the repo root:
//   NODE_PATH=./node_modules node docs/memoire/build/render-logos-batch.js <srcDir> <outDir>
const { chromium } = require('playwright')
const fs = require('fs')
const path = require('path')

;(async () => {
  const srcDir = path.resolve(process.argv[2])
  const outDir = path.resolve(process.argv[3])
  fs.mkdirSync(outDir, { recursive: true })
  const files = fs.readdirSync(srcDir).filter((f) => /\.(svg|png|ico)$/i.test(f))

  // A gallery page that references the logos relatively, so Chromium loads them as same-origin files.
  const imgs = files
    .map((f, i) => `<img id="l${i}" src="${f}" style="height:120px;width:auto;display:block;margin:20px">`)
    .join('\n')
  const galleryPath = path.join(srcDir, '_gallery.html')
  fs.writeFileSync(galleryPath, `<!doctype html><meta charset="utf-8"><body style="margin:0;background:#fff">${imgs}</body>`)

  const browser = await chromium.launch()
  const page = await browser.newPage({ deviceScaleFactor: 2 })
  await page.goto('file:///' + galleryPath.replace(/\\/g, '/').replace(/^\/+/, ''))
  await page.waitForTimeout(500)
  for (let i = 0; i < files.length; i++) {
    const out = files[i].replace(/\.(svg|png|ico)$/i, '.png')
    await (await page.$(`#l${i}`)).screenshot({ path: path.join(outDir, out), omitBackground: true })
    console.log('OK', out)
  }
  await browser.close()
  fs.unlinkSync(galleryPath)
  console.log('done:', files.length, 'logos ->', outDir)
})().catch((e) => { console.error(e); process.exit(1) })
