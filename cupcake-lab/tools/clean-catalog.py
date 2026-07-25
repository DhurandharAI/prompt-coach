#!/usr/bin/env python3
"""
Apply the agreed catalogue cleanup to the raw mockup extract.

Input:  data/products.json          (raw, exactly as extracted from the mockup)
Output: data/catalog-clean.json     (cleaned, canonical product set)
        data/woocommerce-import.csv (WooCommerce product CSV importer format)
        data/cleanup-report.md       (what changed, and what still needs a human)

The raw extract is never modified, so every change here is auditable by diffing
the two JSON files.

Decisions applied (agreed 25 Jul 2026):
  1. Mini Cake Sampler  -> one product, base P2,000 with a P2,500 seasonal variant
  2. Bento Cakes        -> one variable product with a flavor selector
  3. Scope              -> Cupcake Lab only (all 88 products are already its catalogue)

Structural fixes only. Product copy is left alone on purpose -- rewriting
marketing text is the owner's call, so copy problems are reported, not edited.
"""

import csv
import json
import pathlib
import sys

ROOT = pathlib.Path(__file__).resolve().parent.parent
DATA = ROOT / "data"

# ---------------------------------------------------------------- decisions ---

# Bento flavor prices. The variable product (id 1820) and the 14 standalone
# products disagree on five flavors, always with the variable priced higher.
# Product IDs put the variable product later than the standalones, so it is the
# newer record and its prices are treated as current.
#
# ASSUMPTION, NOT CONFIRMED: the five conflicts below resolve in favour of the
# variable product. If the standalone prices are the live ones instead, change
# BENTO_PRICE_SOURCE to "standalone" and re-run.
BENTO_PRICE_SOURCE = "variable"

BENTO_PRICES = {
    #  flavor                      variable  standalone
    "Lemon Butter":               (500,      500),
    "Chocolate Yema":             (500,      500),
    "Red Velvet":                 (600,      550),   # conflict
    "Dark Roast Coffee":          (600,      500),   # conflict
    "Chiffon + Jam + Buttercream":(600,      None),
    "Carrot":                     (650,      600),   # conflict
    "Chocolate Chip":             (700,      600),   # conflict
    "Ube Halaya":                 (700,      600),   # conflict
    "Matcha":                     (None,     600),
    "Butter Bacon":               (None,     600),
    "Chocolate Bacon":            (None,     600),
}

# Bento records to fold into the single variable product and then drop.
BENTO_STANDALONE_IDS = [1406, 1407, 1408, 1409, 1410, 1412, 1413, 1414, 1415, 1416]
BENTO_ZERO_PRICE_IDS = [1697, 1705, 1706, 1707]   # P0, not purchasable

# Cross-category duplicates: (keeper_id, dropped_id). The keeper is whichever
# record carries the photos, since photos are the scarcer asset here.
DUPLICATES = [
    (2427, 1708),   # Bento Party   -- Seasonal copy has photos
    (1992, 2203),   # Cake Bouquet  -- neither has photos; keep the lower id
]

# Mini Cake Sampler: merge into one product with a seasonal variant.
SAMPLER_KEEP = 1482          # Basic Cakes, P2,000
SAMPLER_DROP = 2431          # Seasonal Occassions, P2,500
SAMPLER_VARIANTS = [("Standard", 2000), ("Seasonal / Mother's Day", 2500)]

# Products showing photos that belong to a different product.
BORROWED_IMAGES = {
    561:  "Tiramisu photos on a coffee cake -- needs its own product shots",
    1809: "Bibingka Cheesecake photos on the plain Cheesecake -- needs its own shots",
}

# Variation label typo: Cake Tins Matcha has two sizes both called "Sharing".
MATCHA_TIN_ID = 1458

DROP_CATEGORIES = ["CupcakeLab"]   # stale count of 12, contains zero products


def peso(v):
    return round(float(v), 2)


def load():
    with open(DATA / "products.json", encoding="utf-8") as fh:
        return json.load(fh)


def main():
    raw = load()
    by_id = {p["id"]: p for p in raw}
    report = []
    dropped = {}

    def drop(pid, why):
        dropped[pid] = why
        report.append(("dropped", f"[{pid}] {by_id[pid]['name']} -- {why}"))

    # -- 1. Bento Cakes: collapse to one variable product ------------------
    bento = by_id[1820]
    photos, descs = [], {}
    for pid in BENTO_ZERO_PRICE_IDS:
        p = by_id[pid]
        photos += [i for i in p["images"] if i not in photos]
        if p["short_description"]:
            descs[p["name"]] = p["short_description"]
        drop(pid, "priced P0 and not purchasable; photos and copy folded into Bento Cakes (1820)")
    for pid in BENTO_STANDALONE_IDS:
        drop(pid, "folded into the Bento Cakes flavor selector (1820)")

    idx = 0
    variations = []
    conflicts = []
    for flavor, (var_price, std_price) in BENTO_PRICES.items():
        chosen = var_price if BENTO_PRICE_SOURCE == "variable" else std_price
        if chosen is None:                       # flavor exists in only one model
            chosen = std_price if var_price is None else var_price
        if var_price is not None and std_price is not None and var_price != std_price:
            conflicts.append((flavor, var_price, std_price, chosen))
        idx += 1
        variations.append({
            "id": f"bento-{idx}",
            "label": f"Flavor: {flavor}",
            "attributes": {"Flavor": flavor},
            "price_php": peso(chosen),
        })
    bento["variations"] = variations
    bento["price_php"] = peso(min(v["price_php"] for v in variations))
    bento["price_range_php"] = [bento["price_php"],
                                peso(max(v["price_php"] for v in variations))]
    for img in photos:
        if img not in bento["images"]:
            bento["images"].append(img)
    bento["flavor_notes"] = descs
    report.append(("merged", f"Bento Cakes (1820): {len(variations)} flavors, "
                             f"P{bento['price_range_php'][0]:,.0f}-P{bento['price_range_php'][1]:,.0f}; "
                             f"absorbed {len(BENTO_STANDALONE_IDS) + len(BENTO_ZERO_PRICE_IDS)} records"))
    if conflicts:
        report.append(("needs-confirmation",
                       "Bento flavor price conflicts resolved in favour of the "
                       f"{BENTO_PRICE_SOURCE} record: " +
                       "; ".join(f"{f} P{c:,.0f} (other record said P{o:,.0f})"
                                 for f, v, s, c in conflicts
                                 for o in [s if BENTO_PRICE_SOURCE == 'variable' else v])))

    # -- 2. Mini Cake Sampler: one product, seasonal variant ---------------
    sampler = by_id[SAMPLER_KEEP]
    sampler["type"] = "variable"
    sampler["variations"] = [
        {"id": f"sampler-{n}", "label": f"Edition: {label}",
         "attributes": {"Edition": label}, "price_php": peso(price)}
        for n, (label, price) in enumerate(SAMPLER_VARIANTS, 1)
    ]
    sampler["price_php"] = peso(min(p for _, p in SAMPLER_VARIANTS))
    sampler["price_range_php"] = [peso(min(p for _, p in SAMPLER_VARIANTS)),
                                  peso(max(p for _, p in SAMPLER_VARIANTS))]
    sampler["name"] = "Mini Cake Sampler"
    for img in by_id[SAMPLER_DROP]["images"]:
        if img not in sampler["images"]:
            sampler["images"].append(img)
    for cat in by_id[SAMPLER_DROP]["categories"]:
        if cat not in sampler["categories"]:
            sampler["categories"].append(cat)
    drop(SAMPLER_DROP, "merged into Mini Cake Sampler (1482) as the seasonal variant")
    report.append(("merged", "Mini Cake Sampler (1482): P2,000 standard + P2,500 seasonal"))

    # -- 3. Cross-category duplicates --------------------------------------
    for keep_id, drop_id in DUPLICATES:
        keep, gone = by_id[keep_id], by_id[drop_id]
        for img in gone["images"]:
            if img not in keep["images"]:
                keep["images"].append(img)
        for cat in gone["categories"]:
            if cat not in keep["categories"]:
                keep["categories"].append(cat)
        if not keep["short_description"] and gone["short_description"]:
            keep["short_description"] = gone["short_description"]
        drop(drop_id, f"duplicate of [{keep_id}] {keep['name']}; categories merged onto the keeper")

    # -- 4. Variation label typo -------------------------------------------
    matcha = by_id[MATCHA_TIN_ID]
    seen = set()
    for v in matcha.get("variations", []):
        size = v["attributes"].get("Size")
        if size in seen and size == "Sharing":
            v["attributes"]["Size"] = "Party"
            v["label"] = "Size: Party"
            report.append(("fixed", f"Cake Tins Matcha ({MATCHA_TIN_ID}): second "
                                    f"\"Sharing\" P{v['price_php']:,.0f} relabelled \"Party\" "
                                    "to match every other Cake Tins product"))
        seen.add(size)

    # -- 5. Detach borrowed photos ----------------------------------------
    for pid, why in BORROWED_IMAGES.items():
        p = by_id[pid]
        if p["images"]:
            p["images_removed"] = p["images"]
            p["images"] = []
            report.append(("fixed", f"[{pid}] {p['name']}: detached borrowed photos -- {why}"))

    # -- assemble -----------------------------------------------------------
    clean = [p for p in raw if p["id"] not in dropped]
    for p in clean:
        p["categories"] = [c for c in p["categories"] if c not in DROP_CATEGORIES]

    with open(DATA / "catalog-clean.json", "w", encoding="utf-8") as fh:
        json.dump(clean, fh, indent=1, ensure_ascii=False)

    write_csv(clean)
    write_report(clean, raw, report, dropped)

    print(f"raw:   {len(raw)} products")
    print(f"clean: {len(clean)} products ({len(dropped)} dropped)")
    print(f"variations: {sum(len(p.get('variations', [])) for p in clean)}")
    return 0


def webp_map():
    """Original mockup filename -> the optimized WebP committed in assets/."""
    with open(DATA / "asset-manifest.json", encoding="utf-8") as fh:
        manifest = json.load(fh)
    out = {}
    for a in manifest:
        for url in a["source_urls"]:
            out[url.rsplit("/", 1)[-1]] = a["file"]
    return out


def write_csv(clean):
    """WooCommerce native product CSV importer format."""
    imgs = webp_map()

    def local(names):
        """Map to committed WebP filenames, dropping any that 404'd."""
        return [imgs[n] for n in dict.fromkeys(names) if n in imgs]

    cols = ["ID", "Type", "SKU", "Name", "Published", "Visibility in catalogue",
            "Short description", "Description", "In stock?", "Regular price",
            "Categories", "Images", "Parent", "Position",
            "Attribute 1 name", "Attribute 1 value(s)", "Attribute 1 visible",
            "Attribute 1 global"]
    rows = []
    for p in clean:
        vs = p.get("variations") or []
        attr_name = ""
        attr_vals = ""
        if vs:
            attr_name = next(iter(vs[0]["attributes"]), "")
            attr_vals = ", ".join(dict.fromkeys(
                v["attributes"].get(attr_name, "") for v in vs))
        rows.append({
            "ID": p["id"], "Type": "variable" if vs else "simple",
            "SKU": p["sku"], "Name": p["name"], "Published": 1,
            "Visibility in catalogue": "visible",
            "Short description": p["short_description"],
            "Description": p["description"], "In stock?": 1,
            "Regular price": "" if vs else f"{p['price_php']:.2f}",
            "Categories": ", ".join(p["categories"]),
            "Images": ", ".join(local(p["images"])),
            "Parent": "", "Position": 0,
            "Attribute 1 name": attr_name,
            "Attribute 1 value(s)": attr_vals,
            "Attribute 1 visible": 1 if vs else "",
            "Attribute 1 global": 0 if vs else "",
        })
        for n, v in enumerate(vs, 1):
            name = next(iter(v["attributes"]), "")
            rows.append({
                "ID": "", "Type": "variation", "SKU": "",
                "Name": f"{p['name']} - {v['attributes'].get(name, '')}",
                "Published": 1, "Visibility in catalogue": "visible",
                "Short description": "", "Description": "", "In stock?": 1,
                "Regular price": f"{v['price_php']:.2f}",
                "Categories": "", "Images": "",
                "Parent": f"id:{p['id']}", "Position": n,
                "Attribute 1 name": name,
                "Attribute 1 value(s)": v["attributes"].get(name, ""),
                "Attribute 1 visible": "", "Attribute 1 global": 0,
            })
    with open(DATA / "woocommerce-import.csv", "w", newline="", encoding="utf-8") as fh:
        w = csv.DictWriter(fh, fieldnames=cols)
        w.writeheader()
        w.writerows(rows)


def write_report(clean, raw, report, dropped):
    no_photo = [p for p in clean if not p["images"]]
    no_desc = [p for p in clean
               if not (p["short_description"] or p["description"])]
    seasonal = [p for p in clean
                if "mother" in (p["short_description"] + p["description"]).lower()
                or "valentine" in (p["short_description"] + p["description"]).lower()]

    L = ["# Catalogue cleanup report", "",
         f"Generated by `tools/clean-catalog.py` from `data/products.json`.", "",
         f"- Raw products: **{len(raw)}**",
         f"- Clean products: **{len(clean)}** ({len(dropped)} removed)",
         f"- Variations: **{sum(len(p.get('variations', [])) for p in clean)}**", ""]

    for kind, title in [("merged", "Merged"), ("fixed", "Fixed"),
                        ("dropped", "Removed"), ("needs-confirmation",
                                                 "⚠️ Applied under an assumption")]:
        items = [m for k, m in report if k == kind]
        if items:
            L += [f"## {title}", ""] + [f"- {m}" for m in items] + [""]

    L += ["## Still needs a human", "",
          f"### Missing photos — {len(no_photo)} of {len(clean)} products", "",
          "53 images in the mockup's media library are attached to nothing; they are",
          "most likely these products' photos. Matching them needs someone who knows",
          "the product line by sight.", ""]
    L += [f"- {p['name']} ({p['categories'][0] if p['categories'] else '-'})"
          for p in sorted(no_photo, key=lambda x: x["name"])] + [""]
    L += [f"### Missing descriptions — {len(no_desc)} of {len(clean)} products", ""]
    L += [f"- {p['name']} ({p['categories'][0] if p['categories'] else '-'})"
          for p in sorted(no_desc, key=lambda x: x["name"])] + [""]
    L += ["### Seasonal copy baked into product descriptions", "",
          "These descriptions name a specific holiday, so they cannot be reused when",
          "the next seasonal menu goes live. Recommendation: make product copy",
          "season-neutral and carry the seasonal framing in the category or campaign",
          "layer instead.", ""]
    for p in sorted(seasonal, key=lambda x: x["name"]):
        note = ""
        if "valentine" in (p["short_description"] + p["description"]).lower():
            note = "  ← says **Valentine's** while sitting in the Mother's Day menu"
        L.append(f"- {p['name']}{note}")
    L.append("")
    with open(DATA / "cleanup-report.md", "w", encoding="utf-8") as fh:
        fh.write("\n".join(L))


if __name__ == "__main__":
    sys.exit(main())
