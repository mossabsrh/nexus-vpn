// Render the technology-stack montage (Figure IV.1) to a crisp PNG with Playwright (Chromium).
// Run from the repository root (where node_modules/playwright lives):
//   node docs/memoire/build/render-logos.js \
//     docs/memoire/figures/logos/montage.html docs/memoire/figures/tech-stack.png
const { chromium } = require('playwright');

(async () => {
  const [, , input, output] = process.argv;
  const abs = require('path').resolve(input).replace(/\\/g, '/');
  const browser = await chromium.launch();
  const page = await browser.newPage({ deviceScaleFactor: 2 });
  await page.goto('file:///' + abs.replace(/^\/+/, ''));
  await page.waitForTimeout(400); // let the SVGs paint
  await (await page.$('#stack')).screenshot({ path: output });
  await browser.close();
  console.log('rendered montage ->', output);
})().catch((e) => { console.error(e); process.exit(1); });
