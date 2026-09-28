import { start } from "./runtime.mjs";
import fs from "node:fs/promises";
const code = await fs.readFile("tests/fixtures/local-preview.php", "utf8");
const server = await start(8881, [{ step: "runPHP", code }]);
console.log(
  `Local WordPress: ${server.serverUrl} — disposable preview, no live writes`,
);
process.on("SIGINT", async () => {
  await server[Symbol.asyncDispose]();
  process.exit();
});
