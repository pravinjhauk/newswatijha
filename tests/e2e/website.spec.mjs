import { test, expect } from "@playwright/test";
import AxeBuilder from "@axe-core/playwright";
for (const width of [
  320, 390, 479, 480, 767, 768, 1023, 1024, 1279, 1280, 1440, 1920,
]) {
  test(`responsive layout ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 1000 });
    await page.goto("/");
    await expect(page.getByRole("heading", { level: 1 })).toHaveText(
      "Specialist care for prolapse & pelvic floor problems",
    );
    const layout = await page.evaluate(() => ({
      width: innerWidth,
      scroll: document.documentElement.scrollWidth,
      portrait: document
        .querySelector(".sj-portrait img")
        .getBoundingClientRect().width,
    }));
    expect(layout.scroll).toBeLessThanOrEqual(layout.width);
    expect(layout.portrait).toBeLessThanOrEqual(453);
    if (width < 1280) {
      await page.getByRole("button", { name: "Open menu" }).click();
      await expect(
        page.getByRole("button", { name: "Close menu" }),
      ).toBeVisible();
      await page.keyboard.press("Escape");
      await expect(
        page.getByRole("button", { name: "Open menu" }),
      ).toBeFocused();
    }
  });
}
for (const width of [390, 768, 1440])
  test(`accessibility ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 1000 });
    await page.goto("/");
    const result = await new AxeBuilder({ page })
      .withTags(["wcag2a", "wcag2aa", "wcag21aa", "wcag22aa"])
      .analyze();
    expect(result.violations).toEqual([]);
  });
test("local privacy and dependency isolation", async ({ page }) => {
  const external = [];
  page.on("request", (request) => {
    if (
      !request.url().startsWith("http://127.0.0.1:8881") &&
      !request.url().startsWith("data:") &&
      !request.url().startsWith("blob:")
    )
      external.push(request.url());
  });
  await page.goto("/");
  expect(external).toEqual([]);
  await expect(page.locator("meta[name=robots]")).toHaveAttribute(
    "content",
    /noindex/,
  );
  await page.goto("/contact/");
  await expect(
    page.getByText("Online enquiry delivery is not enabled in this preview."),
  ).toBeVisible();
});
