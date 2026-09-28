import test from "node:test";
import assert from "node:assert/strict";
import fs from "node:fs";
import path from "node:path";
import { gzipSync } from "node:zlib";
const theme = "wp-content/themes/swatijha-theme";
function files(dir) {
  return fs
    .readdirSync(dir, { withFileTypes: true })
    .flatMap((entry) =>
      entry.isDirectory()
        ? files(path.join(dir, entry.name))
        : [path.join(dir, entry.name)],
    );
}
test("Production assets require no design vendor or proprietary builder", () => {
  for (const file of files("wp-content").filter((file) =>
    /\.(php|js|css|json|html)$/.test(file),
  )) {
    assert.doesNotMatch(
      fs.readFileSync(file, "utf8"),
      /https?:\/\/[^\s"']*(higgsfield|elementor)/i,
      file,
    );
  }
});
test("Approved portrait exists and rejected concept is absent from theme", () => {
  assert.ok(fs.existsSync(`${theme}/assets/images/professor-swati-jha.jpg`));
  assert.ok(!files(theme).some((file) => /ribbon|concept/i.test(file)));
});
test("CSS and font budgets", () => {
  assert.ok(
    gzipSync(fs.readFileSync(`${theme}/assets/css/theme.css`)).length < 60000,
  );
  const fonts = files(`${theme}/assets/fonts`).filter((file) =>
    file.endsWith(".woff2"),
  );
  assert.ok(
    fonts.reduce((sum, file) => sum + fs.statSync(file).size, 0) < 120000,
  );
});
test("Stable WordPress and Node versions are pinned", () => {
  assert.match(fs.readFileSync(".nvmrc", "utf8"), /^v22\.\d+\.\d+/);
  const p = JSON.parse(fs.readFileSync("package.json"));
  for (const value of Object.values(p.devDependencies))
    assert.match(value, /^\d+\.\d+\.\d+$/);
});
