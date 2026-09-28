import test from "node:test";
import assert from "node:assert/strict";
import fs from "node:fs";
test("The real migration map remains unapproved", () => {
  const map = fs.readFileSync(
    "wordpress-proposal/OLD-TO-NEW-URL-MAP-PROPOSED.csv",
    "utf8",
  );
  assert.equal(
    map.split("\n").filter((line) => line.includes("PENDING")).length,
    29,
  );
  assert.ok(!fs.existsSync("migration-approved.json"));
});
