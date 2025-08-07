//_____________________________________ scraper.js _____________________________________//
const { chromium } = require("playwright");

(async () => {
  const url = process.argv[2];
  if (!url) {
    console.error("❌ No URL provided.");
    process.exit(1);
  }

  try {
    const browser = await chromium.launch({
      headless: true,
      executablePath: "C:\\PlaywrightBrowsers\\chromium-1179\\chrome-win\\chrome.exe",
    });

    const context = await browser.newContext({
      userAgent: "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/90.0.4430.93 Safari/537.36",
      viewport: { width: 1280, height: 720 },
    });

    const page = await context.newPage();

    // ✅ Wait until all network activity is finished
    await page.goto(url, { waitUntil: "networkidle" });

    // ✅ Optional: Give JS 1 extra second to run just in case
    await page.waitForTimeout(1000);

    const content = await page.content();
    console.log(content);

    await browser.close();
  } catch (error) {
    console.error("❌ Scraper error:", error.message);
    process.exit(1);
  }
})();
