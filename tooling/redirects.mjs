// Usage: node tooling/redirects.mjs [--map file.csv] [--origin https://new.swatijha.com] [--preview] [--out file]
import fs from "node:fs/promises";
import path from "node:path";
import { generate, PRODUCTION_ORIGIN } from "./redirects-lib.mjs";
const args = Object.fromEntries(
  process.argv.slice(2).map((arg) => {
    const [key, value] = arg.replace(/^--/, "").split("=");
    return [key, value ?? true];
  }),
);
const map = args.map || "wordpress-proposal/URL-MAP-FOR-APPROVAL.csv";
const preview = Boolean(args.preview);
const out =
  args.out ||
  (preview ? "dist/redirects.preview.htaccess" : "dist/redirects.htaccess");
try {
  const csv = await fs.readFile(map, "utf8");
  const { text, rules, host } = generate(csv, {
    origin: args.origin || PRODUCTION_ORIGIN,
    preview,
    source: path.basename(map),
  });
  await fs.mkdir(path.dirname(out), { recursive: true });
  await fs.writeFile(out, text);
  console.log(
    `${out}: ${rules.filter((r) => r.code !== "none").length} rules, ${rules.filter((r) => r.code === "none").length} kept URLs, host canonicalisation ${host ? "on" : "off"}${preview ? " (UNSIGNED PREVIEW)" : ""}`,
  );
} catch (error) {
  console.error(error.message);
  process.exitCode = 1;
}
