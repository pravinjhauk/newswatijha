import fs from "node:fs/promises";
import { execFileSync } from "node:child_process";
import postcss from "postcss";
import autoprefixer from "autoprefixer";
import cssnano from "cssnano";
const theme = "wp-content/themes/swatijha-theme";
const json = JSON.parse(await fs.readFile(`${theme}/theme.json`, "utf8"));
const tokens = { ...json.settings.custom.sj };
for (const item of json.settings.color.palette) tokens[item.slug] = item.color;
for (const item of json.settings.spacing.spacingSizes)
  tokens[`space-${item.slug}`] = item.size;
for (const item of json.settings.typography.fontSizes)
  tokens[`text-${item.slug}`] = item.size;
const aliases = `:root{${Object.entries(tokens)
  .map(([k, v]) => `--sj-${k}:${v}`)
  .join(";")}}`;
let source =
  aliases + (await fs.readFile(`${theme}/assets/css/components.css`, "utf8"));
for (const [name, width] of Object.entries(json.settings.custom.breakpoints)) {
  source = source
    .replaceAll(`(--sj-${name})`, `(min-width: ${width}px)`)
    .replaceAll(`(--sj-below-${name})`, `(max-width: ${width - 1}px)`);
}
const result = await postcss([autoprefixer, cssnano]).process(source, {
  from: undefined,
});
await fs.writeFile(`${theme}/assets/css/theme.css`, result.css);
const blocks = await postcss([autoprefixer, cssnano]).process(
  await fs.readFile(
    "wp-content/plugins/swatijha-core/editor/blocks.css",
    "utf8",
  ),
  { from: undefined },
);

execFileSync(
  process.execPath,
  [
    "node_modules/@wordpress/scripts/bin/wp-scripts.js",
    "build",
    "wp-content/plugins/swatijha-core/editor/editor.js",
    "--output-path=wp-content/plugins/swatijha-core/build",
  ],
  { stdio: "inherit" },
);
await fs.writeFile(
  "wp-content/plugins/swatijha-core/build/blocks.css",
  blocks.css,
);
console.log("Built theme tokens, CSS and WordPress editor assets.");
