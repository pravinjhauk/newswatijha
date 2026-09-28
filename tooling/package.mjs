import fs from "node:fs/promises";
import { execFileSync } from "node:child_process";
import { createHash } from "node:crypto";
import path from "node:path";
await fs.mkdir("dist", { recursive: true });
const revision = execFileSync("git", ["rev-parse", "HEAD"], {
  encoding: "utf8",
}).trim();
const artifacts = [];
for (const [folder, name] of [
  ["themes", "swatijha-theme"],
  ["plugins", "swatijha-core"],
]) {
  const target = path.resolve(`dist/${name}-0.1.0.zip`);
  await fs.rm(target, { force: true });
  execFileSync("zip", ["-qr", target, name, "-x", "*/.DS_Store"], {
    cwd: `wp-content/${folder}`,
  });
  const bytes = await fs.readFile(target);
  artifacts.push({
    file: path.basename(target),
    bytes: bytes.length,
    sha256: createHash("sha256").update(bytes).digest("hex"),
  });
}
await fs.writeFile(
  "dist/release-manifest.json",
  JSON.stringify({ version: "0.1.0", revision, artifacts }, null, 2),
);
console.log(artifacts);
