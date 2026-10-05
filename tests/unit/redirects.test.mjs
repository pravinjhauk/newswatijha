import test from "node:test";
import assert from "node:assert/strict";
import fs from "node:fs";
import http from "node:http";
import { generate, check, pathPattern } from "../../tooling/redirects-lib.mjs";
const signed = fs.readFileSync("tests/fixtures/url-map-signed.csv", "utf8");
const real = fs.readFileSync(
  "wordpress-proposal/URL-MAP-FOR-APPROVAL.csv",
  "utf8",
);

test("The real, unsigned URL map cannot produce deployable rules", () => {
  assert.throws(() => generate(real), /not individually approved/);
});
test("An unsigned preview is marked as not deployable", () => {
  const { text } = generate(real, { preview: true });
  assert.match(text, /UNSIGNED PREVIEW - DO NOT DEPLOY/);
  assert.match(
    text,
    /\^vaginal-prolapse-signs\/\?\$ https:\/\/www\.swatijha\.com\/symptoms\/ \[R=301,L\]/,
  );
  assert.match(text, /\^liquid-archives\/blog\/\?\$ - \[G,L\]/);
});
test("Signed map generates one-hop rules and production-only host canonicalisation", () => {
  const { text } = generate(signed);
  assert.doesNotMatch(text, /DO NOT DEPLOY/);
  assert.match(
    text,
    /RewriteRule \^old-page\/\?\$ https:\/\/www\.swatijha\.com\/new-page\/ \[R=301,L\]/,
  );
  assert.match(
    text,
    /RewriteCond %\{QUERY_STRING\} \(\^\|&\)legacy-footer=home\(&\|\$\) \[NC\]\nRewriteRule \^\$ - \[G,L\]/,
  );
  assert.match(text, /\^legacy\\\.archive\/blog\/\?\$ - \[G,L\]/);
  assert.match(text, /\^xmlrpc\\\.php\/\?\$ - \[F,L\]/);
  assert.match(
    text,
    /RewriteCond %\{HTTP_HOST\} \^\(www\\\.\)\?swatijha\\\.com\$ \[NC\]/,
  );
  assert.doesNotMatch(text, /new\\\.swatijha/);
});
test("Staging origin rewrites destinations only", () => {
  const { text } = generate(signed, { origin: "https://new.swatijha.com" });
  assert.match(
    text,
    /\^old-page\/\?\$ https:\/\/new\.swatijha\.com\/new-page\/ \[R=301,L\]/,
  );
  assert.match(
    text,
    /RewriteRule \^\(\.\*\)\$ https:\/\/www\.swatijha\.com\/\$1/,
  );
});
test("A row without an explicit code is rejected when signed", () => {
  const broken = signed.replace(
    ",410,,,,APPROVED,Synthetic fixture,2026-10-05,\nT04",
    ",,,,,APPROVED,Synthetic fixture,2026-10-05,\nT04",
  );
  assert.throws(() => generate(broken), /T03: redirect_code/);
});
test("Path patterns escape regex characters and accept a missing trailing slash", () => {
  assert.equal(pathPattern("/a.b/c/"), "^a\\.b/c/?$");
  assert.equal(pathPattern("/"), "^$");
});
test("Checker asserts status, destination and a single hop", async () => {
  const server = http.createServer((req, res) => {
    const routes = {
      "/": [200],
      "/old-page/": [301, "/new-page/"],
      "/new-page/": [200],
      "/legacy.archive/blog/": [410],
      "/xmlrpc.php": [403],
    };
    const key = req.url.startsWith("/?legacy-footer") ? "legacy" : req.url;
    if (key === "legacy") return res.writeHead(410).end();
    const [status, location] = routes[key] || [404];
    res
      .writeHead(
        status,
        location
          ? { location: `http://127.0.0.1:${server.address().port}${location}` }
          : {},
      )
      .end();
  });
  await new Promise((resolve) => server.listen(0, "127.0.0.1", resolve));
  const base = `http://127.0.0.1:${server.address().port}`;
  const report = await check(signed, { base });
  server.close();
  assert.equal(report.failed, 0, JSON.stringify(report.results, null, 2));
  assert.equal(report.checked, 5);
});
