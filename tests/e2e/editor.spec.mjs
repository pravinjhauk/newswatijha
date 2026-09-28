import { test, expect } from "@playwright/test";
test("Native homepage blocks validate and clinical controls are available", async ({
  page,
}) => {
  const failures = [];
  page.on("console", (message) => {
    if (message.type() === "error") failures.push(message.text());
  });
  await page.goto("/wp-login.php");
  await page.getByLabel("Username or Email Address").fill("admin");
  await page.getByLabel("Password", { exact: true }).fill("password");
  await Promise.all([
    page.waitForURL("**/wp-admin/"),
    page.getByRole("button", { name: "Log In", exact: true }).click(),
  ]);
  await page.goto("/wp-admin/edit.php?post_type=page");
  await page
    .locator("a.row-title")
    .filter({ hasText: /^Home$/ })
    .click();
  const close = page.getByRole("button", { name: "Close", exact: true });
  await close.click({ timeout: 5000 }).catch(() => {
    /* The welcome guide may already be dismissed. */
  });
  await expect(
    page.getByRole("button", { name: "Practice content and clinical review" }),
  ).toBeVisible();
  await page
    .getByRole("button", { name: "Practice content and clinical review" })
    .click();
  await expect(
    page.getByLabel("Clinical information", { exact: true }),
  ).toBeVisible();
  await expect(
    page
      .frameLocator('iframe[name="editor-canvas"]')
      .getByText("Specialist care for prolapse & pelvic floor problems", {
        exact: true,
      }),
  ).toBeVisible();
  expect(
    failures.filter(
      (message) =>
        message.includes("Block validation failed") ||
        message.includes("TypeError"),
    ),
  ).toEqual([]);
});
