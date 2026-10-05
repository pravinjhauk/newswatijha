// Usage: SJ_BASIC_AUTH=user:pass node tooling/check-redirects.mjs --base=https://new.swatijha.com [--map file.csv] [--preview]
// Behind a proxy, run with NODE_USE_ENV_PROXY=1.
import fs from "node:fs/promises";
import { check } from "./redirects-lib.mjs";
const args = Object.fromEntries(
  process.argv.slice(2).map((arg) => {
    const [key, value] = arg.replace(/^--/, "").split("=");
    return [key, value ?? true];
  }),
);
if (!args.base) {
  console.error("Supply --base=https://host to test.");
  process.exit(1);
}
const headers = process.env.SJ_BASIC_AUTH
  ? {
      authorization: `Basic ${Buffer.from(process.env.SJ_BASIC_AUTH).toString("base64")}`,
    }
  : {};
const csv = await fs.readFile(
  args.map || "wordpress-proposal/URL-MAP-FOR-APPROVAL.csv",
  "utf8",
);
const report = await check(csv, {
  base: args.base,
  preview: Boolean(args.preview),
  headers,
});
await fs.mkdir("test-results", { recursive: true });
await fs.writeFile(
  "test-results/redirects.json",
  JSON.stringify(report, null, 2),
);
for (const r of report.results)
  console.log(
    `${r.passed ? "PASS" : "FAIL"} ${r.id} ${r.url} → ${r.status}${r.location ? ` ${r.location} → ${r.final_status}` : ""}${r.error ? ` (${r.error})` : ""}`,
  );
console.log(`${report.checked - report.failed}/${report.checked} passed`);
if (report.failed) process.exitCode = 1;
