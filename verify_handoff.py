"""Offline integrity/dependency checks. Python standard library only."""
from pathlib import Path
from html.parser import HTMLParser
import hashlib
import json
import re

ROOT = Path(__file__).resolve().parent


def local_reference(owner, value):
    assert not re.match(r"^(?:https?:)?//", value), f"Remote render dependency: {value}"
    if value.startswith(("#", "data:")):
        return
    target = (owner.parent / value.split("#")[0].split("?")[0]).resolve()
    assert target.is_relative_to(ROOT), f"Asset outside handoff: {value}"
    assert target.is_file(), f"Missing local asset: {value}"


class ResourceCheck(HTMLParser):
    def handle_starttag(self, tag, attrs):
        attributes = dict(attrs)
        if tag in {"script", "iframe", "object", "embed"}:
            raise AssertionError(f"Unexpected executable/embed dependency: {tag}")
        if tag in {"img", "source", "video", "audio"}:
            for key in ("src", "poster"):
                if attributes.get(key):
                    local_reference(ROOT / "preview.html", attributes[key])
        if tag == "link" and attributes.get("rel") in {"stylesheet", "preload"}:
            local_reference(ROOT / "preview.html", attributes["href"])


manifest = json.loads((ROOT / "ASSET-MANIFEST.json").read_text())
for asset in manifest["assets"]:
    path = ROOT / asset["path"]
    assert path.is_file(), f"Missing asset: {path}"
    assert hashlib.sha256(path.read_bytes()).hexdigest() == asset["sha256"], path

ResourceCheck().feed((ROOT / "preview.html").read_text())
for css in [ROOT / "design-tokens.css", ROOT / "preview.css", ROOT / "assets/fonts/fonts.css"]:
    source = css.read_text()
    assert "@import" not in source, f"Unreviewed stylesheet import: {css}"
    for url in re.findall(r"url\(([^)]+)\)", source):
        local_reference(css, url.strip("\"' "))
    assert "higgsfield" not in source.lower(), f"Vendor coupling in {css}"

for required in ["DESIGN-HANDOFF.md", "DECISIONS.md", "README.md", "VERIFICATION.md"]:
    assert (ROOT / required).is_file(), required

print(f"PASS: {len(manifest['assets'])} asset checksums; local render resources; no scripts, embeds or remote stylesheet/font/image dependency.")
