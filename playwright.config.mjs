import { defineConfig } from "@playwright/test";
export default defineConfig({
  outputDir: "test-results/playwright",
  testDir: "tests/e2e",
  timeout: 45000,
  workers: 1,
  use: { baseURL: "http://127.0.0.1:8881", headless: true },
  reporter: [["list"], ["json", { outputFile: "test-results/browser.json" }]],
});
