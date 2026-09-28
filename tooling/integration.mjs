import { start } from "./runtime.mjs";
import fs from "node:fs/promises";
const server = await start(8882);
try {
  const code = await fs.readFile("tests/integration/clinical.php", "utf8");
  const result = await server.playground.run({ code });
  console.log(result.text);
  if (result.errors) console.error(result.errors);
  const report = JSON.parse(result.text);
  await fs.mkdir("test-results", { recursive: true });
  await fs.writeFile(
    "test-results/integration.json",
    JSON.stringify(report, null, 2),
  );
  if (report.failed) process.exitCode = 1;
} finally {
  await server[Symbol.asyncDispose]();
}
