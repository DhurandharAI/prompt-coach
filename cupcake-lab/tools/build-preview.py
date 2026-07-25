#!/usr/bin/env python3
"""
Build a self-contained visual preview of the Cupcake Lab theme.

The theme cannot render without WordPress + WooCommerce + MySQL, so this produces
a static page that reproduces the theme's real design tokens, real catalogue data
and real product photos. It is a faithful look at the design, not a running site
-- the page says so itself.

Everything is inlined (fonts and images as data URIs) because the artifact CSP
blocks requests to external hosts.

    python3 tools/build-preview.py <fonts-dir> <output.html>
"""

import base64
import json
import pathlib
import sys

from PIL import Image

ROOT = pathlib.Path(__file__).resolve().parent.parent

# Products with photos, chosen to span price points and categories.
WITH_PHOTOS = [
    "Signature Red Velvet Cake",
    "Ube Halaya Cake",
    "Bento Cakes",
    "Piñata Dessert Box",
    "Chocolate Chip Cake",
    "Box of Cupcakes",
    "Flower Pot Cake",
    "Bibingka Cheesecake",
]

# Products with no photo. Included deliberately: 54 of 71 products are in this
# state, so the placeholder is the common case, not an edge case.
NO_PHOTOS = [
    "Ferrero",
    "Cake Bouquet",
    "DIY Piñata Kit",
    "Jack Daniels",
]


def data_uri(path: pathlib.Path, mime: str) -> str:
    return f"data:{mime};base64," + base64.b64encode(path.read_bytes()).decode()


def webp_map() -> dict:
    """Original mockup filename -> the optimized WebP committed in assets/.

    The catalogue keeps the mockup's original .png/.jpeg names, while assets/
    holds the converted .webp. Without this mapping every lookup misses.
    """
    with open(ROOT / "data" / "asset-manifest.json", encoding="utf-8") as fh:
        manifest = json.load(fh)

    out = {}
    for asset in manifest:
        for url in asset["source_urls"]:
            out[url.rsplit("/", 1)[-1]] = asset["file"]
    return out


ASSET_MAP = webp_map()


def thumb(name: str, width: int = 560, quality: int = 72) -> str:
    """Resize an asset down for the preview and return it as a data URI."""
    src = ROOT / "assets" / ASSET_MAP.get(name, name)
    if not src.exists():
        print(f"  warning: no asset for {name}", file=sys.stderr)
        return ""

    im = Image.open(src)
    im = im.convert("RGB")
    if im.width > width:
        im = im.resize((width, round(im.height * width / im.width)), Image.LANCZOS)

    tmp = ROOT / "tools" / ".preview-tmp.webp"
    im.save(tmp, "WEBP", quality=quality, method=6)
    uri = data_uri(tmp, "image/webp")
    tmp.unlink()
    return uri


def peso(value: float) -> str:
    return f"₱{value:,.0f}"


def price_label(product: dict) -> str:
    rng = product.get("price_range_php")
    if rng and rng[0] != rng[1]:
        return f"{peso(rng[0])} – {peso(rng[1])}"
    return peso(product["price_php"])


def variation_summary(product: dict, limit: int = 4) -> str:
    variations = product.get("variations") or []
    if not variations:
        return ""
    bits = []
    for v in variations[:limit]:
        label = (v["label"] or "").split(": ", 1)[-1]
        bits.append(f"{label} {peso(v['price_php'])}")
    if len(variations) > limit:
        bits.append(f"+{len(variations) - limit} more")
    return " · ".join(bits)


def esc(text: str) -> str:
    return (
        text.replace("&", "&amp;")
        .replace("<", "&lt;")
        .replace(">", "&gt;")
        .replace('"', "&quot;")
    )


def card(product: dict) -> str:
    """One product card, matching the theme's real card markup."""
    desc = product["short_description"] or product["description"]
    desc = " ".join(desc.split()[:16]) + ("…" if len(desc.split()) > 16 else "")

    placeholder = (
        '<div class="cl-card__media cl-card__media--empty">'
        f"<span>{esc(product['name'])}</span></div>"
    )

    uri = thumb(product["images"][0]) if product["images"] else ""

    # Fall back to the placeholder rather than emitting nothing: a card with no
    # media at all collapses, which looks like a bug instead of a missing photo.
    media = (
        f'<div class="cl-card__media"><img src="{uri}" alt="{esc(product["name"])}" '
        f'loading="lazy" width="560" height="420"></div>'
        if uri
        else placeholder
    )

    variations = variation_summary(product)
    var_html = (
        f'<p class="cl-card__variations">{esc(variations)}</p>' if variations else ""
    )
    desc_html = f'<p class="cl-card__desc">{esc(desc)}</p>' if desc else ""

    return f"""<li class="cl-product">
  <div class="cl-card">
    {media}
    <div class="cl-card__body">
      <h3 class="cl-card__title">{esc(product['name'])}</h3>
      {desc_html}
      {var_html}
      <p class="cl-card__price">{price_label(product)}</p>
      <div class="cl-card__actions">
        <button type="button" class="cl-btn cl-btn--block" disabled>Quick order</button>
      </div>
    </div>
  </div>
</li>"""


def main() -> int:
    if len(sys.argv) < 3:
        print(__doc__)
        return 2

    fonts_dir = pathlib.Path(sys.argv[1])
    out_path = pathlib.Path(sys.argv[2])

    with open(ROOT / "data" / "catalog-clean.json", encoding="utf-8") as fh:
        catalog = {p["name"]: p for p in json.load(fh)}

    cormorant = data_uri(fonts_dir / "cormorant.woff2", "font/woff2")
    manrope = data_uri(fonts_dir / "manrope.woff2", "font/woff2")

    chosen = [catalog[n] for n in WITH_PHOTOS if n in catalog]
    chosen += [catalog[n] for n in NO_PHOTOS if n in catalog]
    cards = "\n".join(card(p) for p in chosen)

    template = (ROOT / "tools" / "preview-template.html").read_text(encoding="utf-8")
    html = (
        template.replace("__CORMORANT__", cormorant)
        .replace("__MANROPE__", manrope)
        .replace("__CARDS__", cards)
    )

    out_path.write_text(html, encoding="utf-8")
    size = out_path.stat().st_size
    print(f"wrote {out_path} ({size/1024:.0f} KB, {len(chosen)} products)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
