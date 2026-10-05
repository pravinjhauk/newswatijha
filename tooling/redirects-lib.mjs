// Redirect rules are generated from the signed URL map, never written by hand.
import { createHash } from "node:crypto";

export const PRODUCTION_ORIGIN = "https://www.swatijha.com";
const CODES = new Set(["301", "410", "403", "none"]);

export function parseCsv(text) {
  const rows = [];
  let row = [];
  let field = "";
  let quoted = false;
  for (let i = 0; i < text.length; i++) {
    const c = text[i];
    if (quoted) {
      if (c === '"' && text[i + 1] === '"') {
        field += '"';
        i++;
      } else if (c === '"') quoted = false;
      else field += c;
    } else if (c === '"') quoted = true;
    else if (c === ",") {
      row.push(field);
      field = "";
    } else if (c === "\n" || c === "\r") {
      if (c === "\r" && text[i + 1] === "\n") i++;
      row.push(field);
      if (row.some((value) => value !== "")) rows.push(row);
      row = [];
      field = "";
    } else field += c;
  }
  row.push(field);
  if (row.some((value) => value !== "")) rows.push(row);
  const [header, ...body] = rows;
  return body.map((values) =>
    Object.fromEntries(header.map((key, i) => [key, (values[i] ?? "").trim()])),
  );
}

// Rows describing a policy rather than an address (uploads, REST, Rank Math, future pages).
export function isDescriptive(row) {
  const url = row.old_url;
  return (
    !/^https?:\/\//.test(url) ||
    url.includes("…") ||
    url.includes(" ") ||
    (url.includes("{") && !url.includes("{path}"))
  );
}

function inferCode(row) {
  const disposition = row.recommended_disposition.toUpperCase();
  if (disposition.startsWith("410")) return "410";
  if (disposition.includes("(403)")) return "403";
  if (disposition.startsWith("KEEP") || disposition.startsWith("NO ACTION"))
    return "none";
  return "";
}

export function classify(rows, { preview = false } = {}) {
  const problems = [];
  const rules = [];
  let host = false;
  for (const row of rows) {
    if (!preview && row.approval !== "APPROVED")
      problems.push(
        `${row.id}: not individually approved (${row.approval || "blank"})`,
      );
    if (isDescriptive(row)) continue;
    let code = (row.redirect_code.split(/\s/)[0] || "").toLowerCase();
    if (!code && preview) code = inferCode(row);
    if (!CODES.has(code)) {
      problems.push(`${row.id}: redirect_code must be 301, 410, 403 or none`);
      continue;
    }
    if (row.old_url.includes("{path}")) {
      if (code === "301") host = true;
      continue;
    }
    const old = new URL(row.old_url);
    if (code === "301" && !row.new_url)
      problems.push(`${row.id}: a 301 needs a new_url`);
    rules.push({
      id: row.id,
      path: old.pathname,
      query: old.search.slice(1),
      code,
      target: row.new_url,
    });
  }
  return { problems, rules, host };
}

const escape = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");

export function pathPattern(path) {
  const trimmed = path.replace(/^\/+/, "").replace(/\/+$/, "");
  return trimmed ? `^${escape(trimmed)}/?$` : "^$";
}

export function generate(
  csvText,
  { origin = PRODUCTION_ORIGIN, preview = false, source = "URL map" } = {},
) {
  const { problems, rules, host } = classify(parseCsv(csvText), { preview });
  if (problems.length) {
    const error = new Error(
      `URL map is not signed for generation:\n- ${problems.join("\n- ")}`,
    );
    error.problems = problems;
    throw error;
  }
  const base = origin.replace(/\/$/, "");
  const hash = createHash("sha256").update(csvText).digest("hex");
  const lines = [
    "# BEGIN Swati Jha redirects",
    `# Generated from ${source} (sha256 ${hash.slice(0, 16)}). Do not edit by hand; regenerate from the signed map.`,
    "# Place this block above the WordPress block in .htaccess.",
  ];
  if (preview) lines.push("# UNSIGNED PREVIEW - DO NOT DEPLOY");
  lines.push("<IfModule mod_rewrite.c>", "RewriteEngine On");
  for (const rule of rules) {
    if (rule.code === "none") continue;
    lines.push(`# ${rule.id}`);
    if (rule.query)
      lines.push(
        `RewriteCond %{QUERY_STRING} (^|&)${escape(rule.query)}(&|$) [NC]`,
      );
    const pattern = pathPattern(rule.path);
    if (rule.code === "410") lines.push(`RewriteRule ${pattern} - [G,L]`);
    if (rule.code === "403") lines.push(`RewriteRule ${pattern} - [F,L]`);
    if (rule.code === "301") {
      // Straight to the canonical origin, so http/non-www variants still take one hop.
      const target = new URL(rule.target);
      const destination = base + target.pathname + target.search;
      lines.push(
        `RewriteRule ${pattern} ${destination} [R=301,L${rule.query ? ",QSD" : ""}]`,
      );
    }
  }
  if (host) {
    lines.push(
      "# Canonical protocol and host - production hostnames only, so staging is never redirected",
      "RewriteCond %{HTTPS} !=on [OR]",
      "RewriteCond %{HTTP_HOST} ^swatijha\\.com$ [NC]",
      "RewriteCond %{HTTP_HOST} ^(www\\.)?swatijha\\.com$ [NC]",
      `RewriteRule ^(.*)$ ${PRODUCTION_ORIGIN}/$1 [R=301,L]`,
    );
  }
  lines.push("</IfModule>", "# END Swati Jha redirects", "");
  return { text: lines.join("\n"), rules, host, hash };
}

// Requests each concrete old URL against a base and checks status, destination and a single hop.
export async function check(
  csvText,
  { base, preview = false, headers = {}, fetchImpl = fetch } = {},
) {
  const { rules } = classify(parseCsv(csvText), { preview });
  const root = base.replace(/\/$/, "");
  const results = [];
  for (const rule of rules) {
    const url = root + rule.path + (rule.query ? `?${rule.query}` : "");
    const result = { id: rule.id, url, expected: rule.code };
    try {
      const response = await fetchImpl(url, { redirect: "manual", headers });
      result.status = response.status;
      if (rule.code === "none") result.passed = response.status === 200;
      else if (rule.code === "301") {
        const target = new URL(rule.target);
        const expected = root + target.pathname + target.search;
        result.location = response.headers.get("location");
        const next = result.location
          ? await fetchImpl(new URL(result.location, url), {
              redirect: "manual",
              headers,
            })
          : null;
        result.final_status = next?.status;
        result.passed =
          response.status === 301 &&
          result.location === expected &&
          next?.status === 200;
      } else result.passed = response.status === Number(rule.code);
    } catch (error) {
      result.passed = false;
      result.error = error.message;
    }
    results.push(result);
  }
  return {
    base: root,
    checked: results.length,
    failed: results.filter((r) => !r.passed).length,
    results,
  };
}
