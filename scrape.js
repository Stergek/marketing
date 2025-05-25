const puppeteer = require('puppeteer');

// Custom delay function to replace waitForTimeout
const delay = (ms) => new Promise(resolve => setTimeout(resolve, ms));

(async () => {
  const browser = await puppeteer.launch({ headless: true });
  const page = await browser.newPage();

  // Set Accept-Language header to English
  await page.setExtraHTTPHeaders({
    'Accept-Language': 'en-US,en;q=0.9'
  });

  const url = process.argv[2];
  const outputPath = process.argv[3];

  // Append locale parameter to force English
  const fullUrl = url.includes('?') ? `${url}&locale=en_US` : `${url}?locale=en_US`;

  await page.goto(fullUrl, { waitUntil: 'networkidle2' });
  await delay(5000); // Wait for dynamic content to load

  // Use CDP session to capture MHTML snapshot
  const client = await page.target().createCDPSession();
  const { data } = await client.send('Page.captureSnapshot', { format: 'mhtml' });
  require('fs').writeFileSync(outputPath, data);
  console.log(`Page saved as ${outputPath}`);

  await browser.close();
})();