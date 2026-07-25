# Cupcake Lab PH — ecommerce build

Turning Instagram and Facebook engagement into paid, trackable orders.
The approved plan is [`PLAN.md`](PLAN.md); this README is how to deploy what has
been built against it.

---

## What is here

| Path | What it is |
|---|---|
| [`PLAN.md`](PLAN.md) | The approved plan. Architecture, costs, phases. |
| [`journey.html`](journey.html) | Customer journey walkthrough. |
| [`mockup-inventory.md`](mockup-inventory.md) | Full extract of the designer's mockup: products, prices, palette, fonts, page structure, copy, and the catalogue's data problems. |
| `theme/cupcakelab/` | The WooCommerce theme, rebuilt from the mockup. |
| `plugin/cupcakelab-ops/` | PayMongo webhook, Telegram/Slack routing, cron digests, bank transfer. See its own [README](plugin/cupcakelab-ops/README.md). |
| `data/products.json` | Raw extract: 88 products, 173 variations. Never edited. |
| `data/catalog-clean.json` | After cleanup: 71 products, 175 variations. |
| `data/woocommerce-import.csv` | Ready for WooCommerce's product CSV importer. |
| `data/cleanup-report.md` | What the cleanup changed, and what still needs a human. |
| `data/asset-manifest.json` | Every asset: original URL, original and web dimensions. |
| `tools/clean-catalog.py` | The cleanup, as a re-runnable script. |
| `assets/` | 118 product and brand images, optimized. |

---

## Build status against PLAN.md

| Phase | Scope | State |
|---|---|---|
| **1. Foundation** | Mockup rebuilt as theme, products/photos/copy extracted | ✅ Theme built, catalogue extracted and cleaned. ⬜ Hostinger plan, domain, WordPress install, staging subdomain — needs account access |
| **2. Commerce** | Product options, delivery/pickup rules, PayMongo, policy pages | ✅ Lead times, delivery zones, checkout fields, bank transfer. ⬜ PayMongo keys, ⬜ **policy pages (launch-blocking — see below)** |
| **3. Ops wiring** | Webhook, Telegram, Slack, crons, Viber SOP | ✅ All built and unit-tested. ⬜ Needs bot token, channel IDs, Slack URL |
| **4. Social & launch** | Meta catalog, tagging, link-in-bio, analytics | ⬜ Not started |

---

## Deploy

### 1. WordPress

Install WordPress + WooCommerce on Hostinger. Set:

- **Settings → General → Timezone: Asia/Manila.** The cron digests and lead-time
  rules resolve against the site timezone, so this is not cosmetic.
- **WooCommerce → Settings → General → Currency: Philippine peso (₱).**
- **WooCommerce → Settings → Advanced → Features → enable HPOS.** The theme and
  plugin both declare compatibility.

### 2. Theme

Copy `theme/cupcakelab/` to `wp-content/themes/` and activate.

Then create the menus under **Appearance → Menus**:

- **Primary** — Shop, Seasonal, About, Contact
- **Legal** — Terms, Privacy, Refunds, Delivery

The theme falls back to sensible defaults if these do not exist, so nothing breaks
before you get to it.

The theme deliberately ships **no WooCommerce template overrides**. Cards, the
lead-time notice and the Quick Order button are all attached through Woo's action
hooks instead, so WooCommerce updates cannot leave stale copies of its templates
behind. Only `style.css` and the hook callbacks in `inc/` need maintaining.

### 3. Products

**WooCommerce → Products → Import** and feed it `data/woocommerce-import.csv`.

Upload `assets/` to the media library **first** — the CSV's `Images` column
references those filenames, already remapped to the committed WebP versions.

Attribute values arrive as custom (per-product) attributes. If you want faceted
filtering later, create global attributes for `Size`, `Flavor` and `Edition` and
re-import.

### 4. Ops plugin

See [`plugin/cupcakelab-ops/README.md`](plugin/cupcakelab-ops/README.md) — it
covers the `wp-config.php` constants, getting the Telegram channel IDs, the
PayMongo webhook, the real cron job, and how to verify the webhook signature
before going live.

### 5. Policy pages — do not skip

PLAN.md §4 is explicit: **acquirer review of the website is a common cause of card
activation delay.** Reviewers look for a publicly reachable site with clear
business identity, contact details, and policies. A staging URL or a site with
placeholder pages typically fails.

The mockup currently has four pages that would fail such a review:

- `/events` — completely empty
- `/book-an-event` — completely empty
- `/dessert-tables` — "Coming Soon" countdown
- `/event-foodcart` — still contains `email@email.com` and `(123) 123 123`

**Either finish them or unpublish them before submitting the live URL.**

Four policy pages need writing. These are commitments the business makes to
customers, so they need Cay's sign-off rather than boilerplate — what each must
cover for the review to pass:

| Page | Must state |
|---|---|
| **Terms of Service** | Who the seller is (registered name, Cubao address, contact), what is sold, how orders are accepted, prices in PHP, that everything is made to order |
| **Privacy Policy** | What is collected (name, phone, Viber number, email, address), why, who it is shared with (PayMongo, delivery riders), how long it is kept, how to request deletion. The Philippine Data Privacy Act (RA 10173) applies |
| **Refunds & Cancellations** | The cancellation window against the 3–5 day lead time, what happens once baking starts, how refunds are issued and how long they take, what happens for a wrong or damaged order |
| **Delivery Terms** | Zones and fees, time windows, the lead-time rule, what happens if nobody is at the address, pickup terms |

The theme's footer already links all four at `/terms`, `/privacy`, `/refunds` and
`/delivery` — create the pages at those slugs.

Also ask PayMongo directly whether the card holdup is documents or website review,
and what specifically is missing. PLAN.md §4 recommends this and it is still the
fastest way to unblock.

---

## What the theme does beyond looking like the mockup

- **Lead times are enforced, not just stated.** The mockup says "pre-order 3–5
  days in advance" in body copy. Checkout now rejects a too-early date
  server-side, with the longest lead time in the cart winning. Custom categories
  get 7 days, matching the Monogram Cupcakes note.
- **Checkout collects what ops actually needs** — Viber number, delivery vs
  pickup, date and time window, dedication text, corporate flag, and a reference
  image for custom orders.
- **Viber numbers are normalised** to `09XXXXXXXXX` on save, because ops copy
  them by hand out of Telegram.
- **Delivery fees by zone**, with an explicit "outside Metro Manila — we will
  quote you" path that charges nothing at checkout.
- **The no-photo case is designed for.** 54 of 71 products have no photo, so
  cards without one render a typographic placeholder rather than a broken image.
- **One font request instead of five.** The mockup loaded seven families; six were
  Astra/Elementor/WP leftovers.
- **Accessibility the mockup lacked** — visible focus rings, a skip link, keyboard
  and Escape handling on the Quick Order dialog, `aria-live` status messages, and
  a palette whose contrast was measured (body 9.3:1, brand 5.5:1) rather than
  assumed.

---

## Still open

### Needs a decision or data from you

1. **Bento flavour prices.** Five flavours disagree between the two source
   records; the cleanup resolved them in favour of the newer variable product and
   flagged it. See the "Applied under an assumption" section of
   [`data/cleanup-report.md`](data/cleanup-report.md). If the standalone prices
   were the live ones, set `BENTO_PRICE_SOURCE = "standalone"` in
   `tools/clean-catalog.py` and re-run.
2. **Delivery zone fees.** The zones in `inc/checkout-fields.php` are placeholder
   rates and need confirming against real Lalamove costs.
3. **PayMongo fee rates.** Indicative only, per PLAN.md §4. Confirm at
   paymongo.com/pricing, then set them via the `cupcakelab_ops_fee_rates` filter.
4. **54 missing product photos.** 53 images sit unattached in the mockup's media
   library and are probably these. Matching them needs someone who knows the
   product line by sight.
5. **48 missing descriptions**, almost all of them cupcake flavours.
6. **Six images 404 on the mockup** and need re-uploading. Two are stock-photo
   filenames and several are `ChatGPT-Image-*` — worth confirming which product
   shots are genuine before any go into a Meta catalog.
7. **Seasonal copy.** All 12 Seasonal Occassions products have "Mother's Day"
   written into their descriptions, so they cannot be reused next season. Cake
   Pop Bouquet still says "Valentine's". Recommend making product copy
   season-neutral and carrying the seasonal framing in the category instead.

### Not yet built

- Policy pages (above) — launch-blocking for card activation
- Meta catalog sync and IG/FB product tagging (Phase 4)
- Link-in-bio page at `/links`, UTM convention, analytics (Phase 4)
- QRPh checkout instructions — PLAN.md §4 flags the mobile UX as the main
  conversion risk, since traffic arrives on the same phone that has to scan the
  code. Needs the screenshot → open bank app → upload-from-gallery walkthrough
  written into the payment step.
- `#photos` routing is implemented in `Router::product_photo()` but has no admin
  UI yet to trigger it.

---

## Scope note

The mockup covers three brands — Cupcake Lab, Lucille's and GiftLab PH, under
MCJC Group. Agreed scope for this build is **Cupcake Lab only**; all 88 extracted
products are already its catalogue, and the other two stay as brochure pages.

Before either of those can sell, they need prices that do not exist anywhere yet:
GiftLab lists roughly 25 menu items with no prices, and Lucille's wedding
packages are described without any.
