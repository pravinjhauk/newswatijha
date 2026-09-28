import lighthouse from "lighthouse";
import { launch } from "chrome-launcher";
import { chromium } from "@playwright/test";
import fs from "node:fs/promises";
const chrome = await launch({
  chromePath: chromium.executablePath(),
  chromeFlags: ["--headless", "--no-sandbox"],
});
try {
  const { lhr, report } = await lighthouse("http://127.0.0.1:8881/", {
    port: chrome.port,
    output: "html",
    onlyCategories: ["performance", "accessibility", "best-practices"],
    logLevel: "error",
  });
  await fs.mkdir("test-results", { recursive: true });
  await fs.writeFile("test-results/lighthouse.html", report);
  await fs.writeFile(
    "test-results/lighthouse.json",
    JSON.stringify(lhr, null, 2),
  );
  console.log(
    JSON.stringify(
      {
        scores: Object.fromEntries(
          Object.entries(lhr.categories).map(([key, value]) => [
            key,
            value.score,
          ]),
        ),
        metrics: Object.fromEntries(
          [
            "largest-contentful-paint",
            "cumulative-layout-shift",
            "total-blocking-time",
          ].map((key) => [key, lhr.audits[key].displayValue]),
        ),
      },
      null,
      2,
    ),
  );
} finally {
  await chrome.kill();
}
