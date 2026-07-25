# Mockup Inventory — `darkblue-locust-788234.hostingersite.com`

Extracted **25 Jul 2026**. This is the freelance designer's mockup, captured before we
rebuild it as the production WooCommerce theme (see `PLAN.md`).

**What the mockup actually is:** not a static comp — it is already a live
**WordPress 7.0.2 + WooCommerce 10.8.1 + Elementor 4.1.4** install on Astra, with
**88 real WooCommerce products** and **173 price variations** entered. That is much
better news than the plan assumed: the product catalogue does not need to be re-keyed,
it needs to be *cleaned* and re-themed.

Everything below was pulled from the live Store API and WP REST API (exact values, not
scraped from rendered HTML), so prices are authoritative as of the capture date.

| Source | Endpoint | Result |
|---|---|---|
| Products | `/wp-json/wc/store/v1/products?per_page=100` | 88 products |
| Variations | `/wp-json/wc/store/v1/products/<id>` × 173 | 173 variations, all fetched |
| Categories | `/wp-json/wc/store/v1/products/categories` | 8 categories (1 empty) |
| Pages | `/wp-json/wp/v2/pages?per_page=100` | 17 pages |
| Media | `/wp-json/wp/v2/media?per_page=100` (2 pages) | 119 items |

Machine-readable companions:
- `data/products.json` — all 88 products + 173 variations, prices in pesos
- `data/asset-manifest.json` — every downloaded asset, original URL, original and web dimensions
- `assets/` — 118 optimized images (see [Assets](#assets))

---

## ⚠️ Read this before confirming prices

The catalogue is **not clean**. Nine issues need your decision — they are listed in
[Data issues](#data-issues-need-your-decision) with specifics. The headline ones:

1. **Four products are priced ₱0 and cannot be bought** (Bento Cakes: Red Velvet Cake,
   Carrot Cake, Ube Halaya, Bacon Butter).
2. **Mini Cake Sampler has two conflicting prices** — ₱2,000 in Basic Cakes vs ₱2,500 in
   Seasonal Occassions.
3. **Three products are duplicated** across categories (Bento Party, Cake Bouquet, Mini
   Cake Sampler).
4. **Bento Cakes is represented twice** — once as a 9-variation product, once as 14
   standalone products, with prices that mostly agree but not entirely.
5. **Dark Roast Coffee cake shows Tiramisu photos.**
6. **A Cake Tins variation is mislabelled** — Matcha has two "Sharing" sizes (₱750 and
   ₱3,000); the second is almost certainly "Party".
7. **65 of 88 products have no photo; 48 of 88 have no description.**
8. **GiftLab PH and Lucille's have no products at all** — their menus exist only as page
   copy, so nothing on those two brands is currently purchasable.
9. **Six images are broken (404) on the mockup itself.**

---

## Scope finding: this is three brands, not one

`PLAN.md` describes "Cupcake Lab PH, a Philippine cupcake business." The mockup is
broader — it is the **MCJC Group** house of three brands (contact email `ask@mcjcgroup.com`,
which matches the MCJC Telegram workspace in the plan):

| Brand | Positioning (from mockup copy) | Products in Woo | Socials |
|---|---|---|---|
| **Cupcake Lab** | "Where your friendly cupcake meets exact science." Cakes, cupcakes, desserts | **All 88** | [IG](https://www.instagram.com/cupcakelabph) · [FB](https://www.facebook.com/CupcakeLabph) · [TikTok](https://www.tiktok.com/@ms.cupcakelab) |
| **Lucille's** | "Luxury in every layer, Love in every bite." Wedding & event cakes | **0** — page copy only | [IG](https://www.instagram.com/lucillesph) · [FB](https://www.facebook.com/Lucillesph) |
| **GiftLab PH** | "Gifting on a whole 'nother level." Balloons, gift boxes, florals | **0** — page copy only | [IG](https://www.instagram.com/giftlabph) · [FB](https://www.facebook.com/giftlabph) |

Founded 2012; owner **Ms. Cay Cuasay** (pastry chef, featured on *Gourmet Takeaway*).

**This affects the build.** Decide whether the production site is Cupcake-Lab-only (per
the plan) or all three brands under one WooCommerce install. The plan's Meta catalog sync,
Telegram routing, and PayMongo setup were all scoped to one brand.

---

## Contact & business details

| Field | Value |
|---|---|
| Location | Cubao, Quezon City |
| Phone | 0998 853 8586 (`tel:09988538586`) |
| Email | ask@mcjcgroup.com |
| Currency | PHP, ₱ prefix, 2 decimals |
| Founded | 2012 |

---

## Products

88 products across 7 categories. Prices below are **exact** Store API values converted
from centavos. `⚠️` marks a non-purchasable product.

For variable products the range is min–max across variations; individual variation prices
are in the "Sizes / variations" column.

### Cupcake Flavors — 26 products

| Product | ID | Type | Price (₱) | Sizes / variations | Photo | Description |
|---|---|---|---|---|---|---|
| Almond Brownie | 2004 | variable | ₱40 – ₱80 | Regular ₱80 · Mini ₱40 | **none** | (No Frosting) |
| Banana Crumb | 2017 | variable | ₱40 – ₱80 | Regular ₱80 · Mini ₱40 | **none** | (No Frosting) |
| Banana Crumb + Buttercream | 2023 | variable | ₱50 – ₱95 | Regular ₱95 · Mini ₱50 | **none** | **none** |
| Butter Cake + Lemon Curd +Buttercream | 2034 | variable | ₱50 – ₱110 | Regular ₱110 · Mini ₱50 | **none** | **none** |
| Carrot + Cream Cheese | 2044 | variable | ₱50 – ₱120 | Regular ₱120 · Mini ₱50 | **none** | **none** |
| Cheesecake | 2050 | variable | ₱70 – ₱140 | Regular ₱140 · Mini ₱70 | **none** | **none** |
| Chiffon + Buttercream | 2060 | variable | ₱30 – ₱50 | Regular ₱50 · Mini ₱30 | **none** | **none** |
| Chiffon + Jam + Buttercream | 2069 | variable | ₱40 – ₱80 | Regular ₱80 · Mini ₱40 | **none** | **none** |
| Chocolate + Buttercream | 2097 | variable | ₱40 – ₱95 | Mini ₱40 · Regular ₱95 | **none** | **none** |
| Chocolate + Buttercream + Yema | 2084 | variable | ₱50 – ₱100 | Mini ₱50 · Regular ₱100 | **none** | **none** |
| Chocolate Caramel | 2076 | variable | ₱60 – ₱140 | Regular ₱140 · Mini ₱60 | **none** | **none** |
| Chocolate Chip | 2091 | variable | ₱60 – ₱140 | Mini ₱60 · Regular ₱140 | **none** | **none** |
| Dark Roast Coffee | 2103 | variable | ₱40 – ₱120 | Mini ₱40 · Regular ₱120 | **none** | **none** |
| Ferrero | 2110 | variable | ₱60 – ₱140 | Mini ₱60 · Regular ₱140 | **none** | **none** |
| Green Tea + White Chocolate | 2117 | variable | ₱50 – ₱120 | Mini ₱50 · Regular ₱120 | **none** | **none** |
| Jack Black | 2123 | variable | ₱60 – ₱140 | Mini ₱60 · Regular ₱140 | **none** | **none** |
| Kinder Bueno | 2130 | variable | ₱50 – ₱120 | Mini ₱50 · Regular ₱120 | **none** | **none** |
| Lemon Butter + Buttercream | 2137 | variable | ₱40 – ₱95 | Mini ₱40 · Regular ₱95 | **none** | **none** |
| Oreo Red Velvet | 2144 | variable | ₱50 – ₱120 | Mini ₱50 · Regular ₱120 | **none** | **none** |
| Oreo Surprise | 2153 | variable | ₱45 – ₱115 | Mini ₱45 · Regular ₱115 | **none** | **none** |
| Peanut Butter Smores | 2159 | variable | ₱40 – ₱95 | Mini ₱40 · Regular ₱95 | **none** | **none** |
| Red Velvet + Buttercream | 2165 | variable | ₱40 – ₱95 | Mini ₱40 · Regular ₱95 | **none** | **none** |
| Red Velvet + Cream Cheese | 2171 | variable | ₱50 – ₱120 | Mini ₱50 · Regular ₱120 | **none** | **none** |
| Tiramisu + Mascarpone | 2177 | variable | ₱55 – ₱130 | Mini ₱55 · Regular ₱130 | **none** | **none** |
| Ube + Swiss Buttercream | 2190 | variable | ₱40 – ₱95 | Mini ₱40 · Regular ₱95 | **none** | **none** |
| Ube + Ube Mascarpone | 2184 | variable | ₱50 – ₱120 | Mini ₱50 · Regular ₱120 | **none** | **none** |

### Bento Cakes — 15 products

| Product | ID | Type | Price (₱) | Sizes / variations | Photo | Description |
|---|---|---|---|---|---|---|
| Bacon Butter | 1697 | simple | **₱0 ⚠️** | — | 1 | Brown Sugar pound cake, studded with fried country bacon, frosted with swiss buttercream and cheddar cheese. |
| Bento Cakes | 1820 | variable | ₱500 – ₱2,000 | Lemon butter ₱500 · Red Velvet ₱600 · Chocolate Yema ₱500 · Carrot ₱650 · Chocolate Chip ₱700 · Ube Halaya ₱700 · Dark Roast Coffee ₱600 · Chiffon+Jam+Buttercream ₱600 · 4pcs Mini Cake Sampler ₱2,000 | 5 | Mini Version of basic cakes with individual package. |
| Butter Bacon | 1415 | simple | ₱600 | — | **none** | **none** |
| Carrot | 1409 | simple | ₱600 | — | **none** | **none** |
| Carrot Cake | 1706 | simple | **₱0 ⚠️** | — | 1 | Moist carrot cake with cream cheese frosting and walnuts, topped with sugar carrot decorations. |
| Chocolate Bacon | 1416 | simple | ₱600 | — | **none** | **none** |
| Chocolate Chip | 1410 | simple | ₱600 | — | **none** | **none** |
| Chocolate Yema | 1408 | simple | ₱500 | — | **none** | **none** |
| Dark Roast Coffee | 1413 | simple | ₱500 | — | **none** | **none** |
| Lemon Butter | 1406 | simple | ₱500 | — | **none** | **none** |
| Matcha | 1414 | simple | ₱600 | — | **none** | **none** |
| Red Velvet | 1407 | simple | ₱550 | — | **none** | **none** |
| Red Velvet Cake | 1707 | simple | **₱0 ⚠️** | — | 1 | Our best-seller! Ultra moist red velvet cake, cream cheese frosting, topped with white chocolate shavings. |
| Ube Halaya | 1705 | simple | **₱0 ⚠️** | — | 1 | Moist yet fluffy ube cake. filled with swiss buttercream and ube halaya, frosted with ube swiss buttercream and ube cake crumbs. |
| Ube Halaya | 1412 | simple | ₱600 | — | **none** | **none** |

### Basic Cakes — 13 products

| Product | ID | Type | Price (₱) | Sizes / variations | Photo | Description |
|---|---|---|---|---|---|---|
| Bacon Butter | 572 | variable | ₱1,200 – ₱2,000 | 6 INCH ₱1,200 · 8 INCH ₱1,600 · 10 INCH ₱2,000 | 1 | A rich brown sugar pound cake studded with crisp country bacon, frosted with smooth Swiss buttercream and finished with a hint of cheddar cheese fo… |
| Bibingka Cheesecake | 1765 | simple | ₱1,500 | — | 2 | Fluffy and cream cheesecake, with a graham crust, topped with salted duck egg / pulang itlog. |
| Carrot Cake | 506 | variable | ₱1,500 – ₱4,500 | 12 INCH ₱3,700 · 14 INCH ₱4,500 · 6 INCH ₱1,500 · 8 INCH ₱2,000 · 10 INCH ₱3,000 | 1 | Moist carrot cake with cream cheese frosting and walnuts, topped with sugar carrot decorations. |
| Cheesecake | 1809 | variable | ₱1,500 – ₱3,500 | 6 INCH ₱1,500 · 8 INCH ₱2,000 · 10 INCH ₱3,000 · 12 INCH ₱3,500 | 2 | Rich , creamy cake made with a smooth blend of cream cheese, sugar, and eggs on a buttery crust Fruit Jam add on |
| Chocolate Bacon | 579 | variable | ₱1,200 – ₱2,000 | 6 INCH ₱1,200 · 8 INCH ₱1,600 · 10 INCH ₱2,000 | 1 | A rich brown sugar pound cake studded with crisp country bacon, frosted with soft, silky chocolate frosting for a bold yet well-balanced sweet-savo… |
| Chocolate Cake | 567 | variable | ₱1,300 – ₱3,500 | 12 INCH ₱3,500 · 6 INCH ₱1,300 · 8 INCH ₱1,700 · 10 INCH ₱2,700 | 1 | Our take on the classic chocolate cake a rich, decadent cake layered with smooth chocolate frosting for a timeless, satisfying indulgence . |
| Chocolate Chip Cake | 500 | variable | ₱1,700 – ₱5,000 | 12 INCH ₱5,000 · 6 INCH ₱1,700 · 8 INCH ₱2,500 · 10 INCH ₱4,000 | 1 | Carefully baked chocolate chip cake with a soft crumb, layered with smooth cookie dough buttercream and topped with chocolate chip cookies, made to… |
| Dark Roast Coffee | 561 | variable | ₱1,500 – ₱4,500 | 12 INCH ₱3,700 · 14 INCH ₱4,500 · 6 INCH ₱1,500 · 8 INCH ₱2,000 · 10 INCH ₱3,000 | 2 | Moist coffee cake made from coffee extra house-made from SAGADA beans, covered in a smooth Mexican frosting. |
| Jack Daniels | 1785 | variable | ₱2,000 – ₱4,500 | 6 INCH ₱2,000 · 8 INCH ₱3,000 · 10 INCH ₱4,500 | **none** | A moist Jack Daniels infused chocolate cake, with Mexican buttercream frosting and Jack Daniels truffles. |
| Mini Cakesampler | 1482 | simple | ₱2,000 | — | **none** | Four inch cakes of our best-selling flavors, Red Velvet, Carrot, Chocolate and Chocolate Chip |
| Oreo Red Velvet | 1800 | variable | ₱1,500 – ₱4,500 | 10 INCH ₱3,500 · 12 INCH ₱4,500 · 6 INCH ₱1,500 · 8 INCH ₱2,500 | **none** | Our Signature Red Velvet cake, studded with oreos in the cream cheese frosting. |
| Signature Red Velvet Cake | 483 | variable | ₱1,500 – ₱4,500 | 12 INCH ₱3,700 · 14 INCH ₱4,500 · 6 INCH ₱1,500 · 8 INCH ₱2,000 · 10 INCH ₱3,000 | 2 | Our best-seller! Ultra moist red velvet cake, cream cheese frosting, topped with white chocolate shavings. |
| Ube Halaya Cake | 555 | variable | ₱1,300 – ₱3,700 | 12 INCH ₱3,700 · 6 INCH ₱1,300 · 8 INCH ₱1,700 · 10 INCH ₱2,800 | 1 | Moist yet fluffy ube cake, filled with swiss buttercream and ube halaya, frosted with ube swiss buttercream and ube cake crumbs. |

### Cake Tins — 12 products

| Product | ID | Type | Price (₱) | Sizes / variations | Photo | Description |
|---|---|---|---|---|---|---|
| Butter Bacon | 1470 | variable | ₱450 – ₱4,000 | Solo ₱450 · Sharing ₱1,000 · Party ₱4,000 | **none** | **none** |
| Carrot | 1454 | variable | ₱350 – ₱3,000 | Solo ₱350 · Sharing ₱750 · Party ₱3,000 | **none** | **none** |
| Chocolate Bacon | 1474 | variable | ₱450 – ₱4,000 | Solo ₱450 · Sharing ₱1,000 · Party ₱4,000 | **none** | **none** |
| Chocolate Caramel | 1450 | variable | ₱300 – ₱2,500 | Solo ₱300 · Sharing ₱700 · Party ₱2,500 | **none** | **none** |
| Chocolate Chip | 1446 | variable | ₱500 – ₱4,000 | Solo ₱500 · Sharing ₱1,000 · Party ₱4,000 | **none** | **none** |
| Dark Roast Coffee | 1466 | variable | ₱350 – ₱2,500 | Solo ₱350 · Sharing ₱750 · Party ₱2,500 | **none** | **none** |
| Lemon Butter | 1438 | variable | ₱300 – ₱2,500 | Solo ₱300 · Sharing ₱700 · Party ₱2,500 | **none** | **none** |
| Matcha | 1458 | variable | ₱350 – ₱3,000 | Solo ₱350 · Sharing ₱750 · Sharing ₱3,000 | **none** | **none** |
| Red Velvet | 1442 | variable | ₱300 – ₱2,500 | Solo ₱300 · Sharing ₱700 · Party ₱2,500 | **none** | **none** |
| The Brownie | 1478 | variable | ₱500 – ₱4,000 | Solo ₱500 · Sharing ₱1,000 · Party ₱4,000 | **none** | **none** |
| Tiramisu | 1866 | variable | ₱350 – ₱2,500 | Solo ₱350 · Sharing ₱750 · Party ₱2,500 | **none** | **none** |
| Ube Halaya | 1462 | variable | ₱350 – ₱3,000 | Solo ₱350 · Sharing ₱750 · Party ₱3,000 | **none** | **none** |

### Seasonal Occassions — 12 products

| Product | ID | Type | Price (₱) | Sizes / variations | Photo | Description |
|---|---|---|---|---|---|---|
| 2D Cake Bouquet | 1996 | simple | ₱2,500 | — | **none** | Cupcakes in the form of a gorgeous bouquet! 12 cupcakes((maximum of 2 flavors), with a collor scheme of your choice. Comes with a ribbon and card. |
| 3D Cupcake Bouquet | 1979 | variable | ₱1,500 – ₱11,500 | 53pcs ₱11,500 · 7pcs ₱1,500 · 12pcs ₱2,500 · 19pcs ₱4,000 · 30pcs ₱6,500 | **none** | 7 Cupcakes(1 Flavor), with a color scheme of your choice. Comes in a box with a ribbon and card. Flavors: Red Velvet, Chocolate or Butter. |
| Bento Party | 2427 | simple | ₱2,000 | — | 1 | A 4″ bento cake surrounded by 8 assorted cupcakes, especially designed for Mother’s Day! |
| Box of Cupcakes | 2413 | variable | ₱900 – ₱1,350 | 4 PCS ₱900 · 6 PCS ₱1,350 | 1 | A box of delicious Mother’s Day themed cupcakes. Flavors: Chocolate, Red Velvet, Chocolate Chip Carrot, Oreo Surprise and Butter. |
| Bubble Balloon Cake | 2443 | simple | ₱2,500 | — | 1 | 10 Inch Mother’s day Bubble balloon paired with a lovely 8-inch floral cake. Flavors: Red Velvet, Chocolate, Carrot, Ube, or Dark Roast Coffee. |
| Cake Bouquet | 1992 | variable | ₱3,500 – ₱4,500 | 6 INCH ₱3,500 · 8 INCH ₱4,500 | **none** | Buttercream cake, wrapped and packaged like a bouquet. Flavors: Chocolate, Red Velvet, Chocolate Chip, or Carrot. |
| Cake Pop Bouquet | 1975 | simple | ₱700 | — | **none** | 6 cake pops, decorated for valentine’s day and wrapped in a bouquet. Flavors: Red Velvet, Butter, or Chocolate. |
| Cakes & Flowers Box | 2437 | simple | ₱2,500 | — | 1 | 4-inch custom floral bento cake with a beautiful mini bouquet of fresh or dried flowers. Flavors: Red Velvet, Chocolate, Carrot, Ube, or Dark Roast… |
| Flower Pot Cake | 2442 | simple | ₱1,000 | — | 1 | A charming pink mini cake designed like a flower pot, topped with a delicate rose sweet, elegant, and perfect for Mother’s day. Flavors: Red Velvet… |
| Mini Cakes Sampler | 2431 | simple | ₱2,500 | — | 1 | 4 Mother’s Day themed mini cakes with flavors of Red Velvet Carrot, Chocolate & Chocolate Chip. |
| Monogram Cupcakes | 2433 | simple | ₱2,500 | — | 1 | 5-7 cupcakes in your choice of colors, customized in a letter or number, mixed with cake pops and an assortment of treats! Flavors: Red Velvet, Cho… |
| Piñata Dessert Box | 2423 | simple | ₱3,000 | — | 1 | A Chocolate heart piñata filled with delectable treats, surrounded by chocolates, cakesicles and sugar cookies! |

### Customize Cakes — 6 products

| Product | ID | Type | Price (₱) | Sizes / variations | Photo | Description |
|---|---|---|---|---|---|---|
| Bento Party | 1708 | simple | ₱2,000 | — | **none** | A custom decorated set of 4″ Bento cake + 8 cupcakes. You may choose 1 flavor for the bento and another for the cupcakes! Design includes 4 2D topp… |
| Buttercream | 1491 | variable | ₱7,000 – ₱14,000 | 6 Inches ₱7,000 · 8 Inches ₱8,000 · 10 Inches ₱10,000 · 12 Inches ₱12,000 · 14 Inches ₱14,000 | **none** | Additional Options: Additional 3D Character – P500.00/Character Convert to 3-Dimensionall – *1.30 of the layer rate Convert to Gravity-Defying – *1… |
| Cake Bouquet | 2203 | variable | ₱3,500 – ₱4,500 | 6 INCH ₱3,500 · 8 INCH ₱4,500 | **none** | A buttercream cake, wrapped and packaged like a bouquet. Flavors: Red Velvet, Chocolate, Carrot and Butter. |
| Dessert Box | 2199 | simple | ₱3,000 | — | **none** | A heart-shaped piñata with a heart-shaped cake inside, 4 sugar cookies, 2 chocolate covered oreos and one cakesicle, surrounded with marshmallows, … |
| Dummy Styro Layer | 2473 | variable | ₱5,000 – ₱11,000 | 6 INCH Single ₱7,000 · 8 INCH Single ₱8,000 · 10 INCH Single ₱9,000 · 12 INCH Single ₱10,000 · 14 INCH Single ₱11,000 · 6 INCH Additional ₱5,000 · 8 INCH Additional ₱6,000 · 10 INCH Additional ₱7,000 · 12 INCH Additional ₱8,000 · 14 INCH Additional ₱9,500 | **none** | **none** |
| Fondant/Ganache | 2228 | variable | ₱9,000 – ₱16,500 | 6 INCH ₱9,000 · 8 INCH ₱10,000 · 9 INCH ₱12,500 · 10 INCH ₱13,500 · 12 INCH ₱15,000 · 14 INCH ₱16,500 | **none** | **none** |

### DIY Cake & Cupcake — 4 products

| Product | ID | Type | Price (₱) | Sizes / variations | Photo | Description |
|---|---|---|---|---|---|---|
| 2 Cupcakes DIY Kit | 2470 | simple | ₱350 | — | **none** | 2 cupcakes 2 colors of buttercream 2 kinds of sprinkles 2 fondant toppers Design guide Kit Checklist |
| 4 Cupcakes DIY Kit | 1333 | simple | ₱550 | — | **none** | 4 cupcakes 4 colors of buttercream 3 kinds of sprinkles 4 sets of fondant toppers Design guide Kit Checklist |
| DIY Cake Kit | 1340 | simple | ₱700 | — | **none** | Blank bento cake 3 colors buttercream Sprinkles 1 spatula Fondant toppers Cardstock toppers |
| DIY Piñata Kit | 1344 | simple | ₱1,100 | — | **none** | 1 chocolate piñata wooden mallet marshmallows and meringue 4 cupcakes 4 colors of buttercream 3 kinds of sprinkles 4 sets of fondant toppers design… |

---

## Page structure

17 published pages. Template `elementor_header_footer` = full-width Elementor canvas
(no Astra header/footer); those are the designer's custom-built pages.

| Page | Slug | Template | Notes |
|---|---|---|---|
| Homepage | `/home` | Elementor | Custom-coded (hand-written CSS + sections), **not** Elementor widgets |
| Cupcakelab | `/cupcakelab` | Elementor | Shop/catalog page with filters + quick-order modal |
| Lucille's | `/lucilles` | Elementor | Wedding packages, copy only — no products |
| Gifts | `/giftlab` | Elementor | Largest page (102 KB). Full GiftLab menu, copy only |
| Seasonal | `/seasonal` | Elementor | Mother's Day menu — the 12 Seasonal Occassions products |
| About | `/about` | Elementor | Brand story, timeline, mission, owner bio |
| Send an Inquiry | `/contact` | Elementor | Contact info + form |
| Socials | `/socials` | Elementor | Social links, press quotes, video embeds |
| Dessert Carts | `/dessert-carts` | Elementor | 2 experiences × 2 packages, min 100 pax |
| Dessert Tables | `/dessert-tables` | Elementor | **"Coming Soon" + countdown — unbuilt** |
| Two-Layer Wedding Cakes | `/twolayerweddingcakes` | Elementor | Lucille's signature package detail |
| Book an Event | `/book-an-event` | Elementor | **Empty — no headings or copy** |
| Events | `/events` | Elementor | **Empty — no headings or copy** |
| Event Foodcart | `/event-foodcart` | default | **Placeholder — "email@email.com (123) 123 123"** |
| Cart | `/cart` | default | Woo default |
| Checkout | `/checkout` | default | Woo default — **no Viber field, no delivery/pickup fields yet** |
| My account | `/my-account` | default | Woo default |

### Homepage section order

The homepage is hand-coded (a `:root` design-token block + `<section>` markup), which is
why it has its own palette distinct from Astra's. Sections in DOM order:

1. `#home` — `.hero` — "A Symphony of Sweetness"
2. `#occasions` — `.occasions-section` — five seasonal menu cards
3. `#brands` — three-brand grid
4. `#featured` — "Featured Creations" (3 cards)
5. `#about` — "Crafting Memories, One Sweet Detail at a Time"
6. `.trust-section` — "Loved by Celebration Enthusiasts" + footer "The Boutique Hub"

Also present: a **"Quick Order" modal** with a "Choose your size" step, used on both the
homepage and `/cupcakelab` — worth preserving, it is the closest thing the mockup has to
the plan's IG-to-checkout fast path.

### Seasonal menus — only one is real

The homepage advertises five seasonal menus. Four are placeholders:

| Menu | Date shown | Status |
|---|---|---|
| Mother's Day | May 10 | ✅ **Live** — 12 products, full copy |
| Father's Day | June 15, 2026 | Placeholder: "being crafted with love" |
| Valentine's Day | February 14 | Placeholder: "being prepared" |
| Christmas | December 25 | Placeholder: "still being baked to perfection" |
| Halloween | October 31 | Placeholder: "being conjured up" |

---

## Design system

### Two conflicting palettes

The site carries **two** palettes. The Astra theme globals are the older set; the
homepage's hand-coded `:root` block is the designer's newer direction. They do not match
(berry `#b3003c` vs tomato `#cc1716`).

**Recommendation: build on the homepage tokens** (newer, more complete, includes shadows
and radii) and retire the Astra set — but confirm, because the inner pages still render
with Astra's berry.

#### A. Homepage tokens (newer — recommended source of truth)

| Token | Value | Role |
|---|---|---|
| `--bg` | `#fffaf7` | Page background, warm off-white |
| `--bg-soft` | `#fff1f5` | Alternating section background |
| `--surface` | `#ffffff` | Cards |
| `--surface-2` | `#fff7fb` | Card hover / secondary surface |
| `--text` | `#8a1220` | Body text (deep berry, not black) |
| `--text-dark` | `#580702` | Headings |
| `--muted` | `#b14d63` | Secondary text |
| `--line` | `#f3d6d6` | Borders, dividers |
| `--brand` | `#cc1716` | Primary CTA, accents |
| `--brand-dark` | `#580702` | CTA hover, headings |
| `--brand-soft` | `#ffa9a8` | Soft accent |
| `--brand-soft-2` | `#ffd9d9` | Tint / badge background |
| `--shadow` | `0 14px 34px rgba(88,7,2,0.10)` | Card resting |
| `--shadow-hover` | `0 20px 44px rgba(88,7,2,0.16)` | Card hover |
| `--radius` | `28px` | Cards, buttons — very round |
| `--container` | `1700px` | Max content width |

#### B. Astra globals (older — currently drives inner pages)

| Token | Value | Role |
|---|---|---|
| `--ast-global-color-0` / `-2` | `#b3003c` | Primary berry / links / text |
| `--ast-global-color-1` | `#e8a0b6` | Soft pink accent |
| `--ast-global-color-3` | `#3b2f2f` | Dark brown |
| `--ast-global-color-4` / `-5` / `-7` | `#f8f3ef` | Cream backgrounds |
| `--ast-global-color-6` | `#000000` | Alternate background |
| `--ast-global-color-8` | `#f1e9e5` | Subtle warm grey |

Other hex values appearing in volume: `#e0185c`, `#a0003e`, `#ffe0ef`, `#901040`,
`#c0295a` (pink/berry gradient ramp), plus `#c06000` / `#804000` (amber) and `#2255cc` /
`#10348a` (blue) used for badges and links.

### Typography

| Family | Weights loaded | Role |
|---|---|---|
| **Cormorant Garamond** | 500, 600, 700 | `--font-heading` — all display/headings |
| **Manrope** | 400–800 | `--font-body` — body copy, UI |
| Inter | 900 | One-off heavy display |
| DM Serif Display | 400 | Astra/Elementor leftover |
| Playfair Display | 600 | Astra/Elementor leftover |
| Cardo | — | WP preset leftover |
| Roboto / Roboto Slab | full range | **Elementor defaults — dead weight** |

**Cormorant Garamond + Manrope is the real pairing.** The other six families are
leftovers from Astra/Elementor/WP presets. Dropping them in our theme removes five
Google Fonts requests — a straight performance win, and worth doing since the plan puts
this on shared hosting.

---

## Copy

Full extracted copy per page is in the capture; the pieces worth carrying into the
production theme:

### Taglines
- Site: **"A Symphony of Sweetness"**
- Cupcake Lab: **"Where your friendly cupcake meets exact science."**
- Cupcake Lab (shop): **"Cakes worth celebrating."**
- Lucille's: **"Luxury in every layer, Love in every bite."**
- GiftLab PH: **"Gifting on a whole 'nother level 💝💐✨"**

### About / brand story
> We are a family of celebration brands dedicated to making life's special moments more
> beautiful. Through Cupcake Lab, Lucille's, and GiftLab PH, we create cakes, desserts,
> wedding pieces, and curated gifts designed with elegance, creativity, and heart.

> Since 2012, CupcakeLab has grown into a polished family of brands focused on
> celebrations that feel personal, elegant, and unforgettable. From your everyday cupcake
> craving to wedding centerpieces and curated gifting, every detail is designed to feel
> premium yet warm.

Mission:
> Our mission is to make every celebration feel more meaningful through creations that
> are thoughtfully designed, beautifully presented, and made with genuine care.

### Ordering terms (important — these become theme + checkout rules)
> 📦 All items are **made-to-order**. Please **pre-order at least 3–5 days in advance**.
> For custom requests, questions, or bulk orders — reach out to us here. 💕

- **Monogram Cupcakes** — "Custom box — **7 days lead time**"
- **Dessert Carts / Interactive Cake Bar** — "Rate is per person, **minimum of 100 pax**"

These are the lead-time rules the plan's Stage 2 says to enforce at checkout: **3–5 days
default, 7 days for custom boxes.**

### Press quotes (on `/socials`)
> "Cupcake Lab is definitely THE cupcake store for the elite! It's cupcakes are so classy
> and sophisticated; trust me, once you go Cupcake Lab, you can never go back."

> "Their red velvet is love! The kind of red velvet that I really like! The cream cheese
> frosting has just enough sweetness and the bread is really moist. Those candy sprinkles
> are cute too!"

### Prices that exist only as page copy (not in WooCommerce)

These appear in `/giftlab` and `/lucilles` text but have **no product record**, so they
cannot currently be bought:

| Item | Price in copy | Source page |
|---|---|---|
| Signature Gift Lab Box 01 | ₱5,000 | `/giftlab` |
| Signature Gift Lab Box 02 | ₱5,000 | `/giftlab` |
| Signature Gift Lab Box 03 | ₱6,500 | `/giftlab` |
| Foil Balloon with Stand | ₱450 | `/giftlab` |
| Convert to Edible (8–12 in) | +₱2,000 per layer | `/lucilles` |
| Convert to Edible (14–18 in) | +₱3,000 per layer | `/lucilles` |
| Additional 3D Character | +₱500 per character | Product: Buttercream (2199) |
| Convert to 3-Dimensional | ×1.30 of layer rate | Product: Buttercream (2199) |
| Convert to Gravity-Defying | ×1.30 of layer rate | Product: Buttercream (2199) |

GiftLab also lists ~25 unpriced menu items (Bubble Balloons, Balloon Marquee, Balloon
Bouquet sizes, Cakes, Packages A–F). **We need prices for these before GiftLab can sell.**

---

## Assets

**118 images** in `assets/`, downloaded from the mockup's media library plus every image
referenced in page and product content.

Originals totalled **163 MB** of 1–2.2 MB PNGs — too heavy to commit and far too heavy to
serve from shared hosting. They are committed as **WebP, max 1800 px, quality 82**:
**7.6 MB total, a 95% reduction** with no visible quality loss at display sizes.

`data/asset-manifest.json` records each file's original URL, original dimensions and byte
size, so full-resolution originals can be re-fetched from the mockup at any time.

### Broken on the mockup (404 — nothing to download)

These are registered in the media library or referenced in page content, but the files are
missing from the server. Typical of an incomplete migration — the designer will need to
re-upload them:

| File | Referenced by |
|---|---|
| `Easy-Pink-cupcakes-A_Pink-cupcakes4303-1.jpg` | page content |
| `ChatGPT-Image-Feb-19-2026-11_53_43-PM.webp` | media library |
| `ChatGPT-Image-Mar-12-2026-08_56_07-AM.png` | media library |
| `ChatGPT-Image-Mar-31-2026-10_23_22-PM.png` | media library |
| `Pink_And_Gold_Cupcakes_withGoldLeafBox6_1080x.webp` | media library |
| `Pink_And_Gold_Cupcakes_withGoldLeaf_1200x1200.webp` | media library |

Note: two filenames above are stock-photo names (`Pink_And_Gold_Cupcakes_…_1200x1200`),
and several are `ChatGPT-Image-*`, i.e. AI-generated. Worth checking with the designer
which product photos are **real Cupcake Lab product shots** vs. AI/stock placeholders
before any of them go into a Meta catalog — Meta's catalog policy and basic customer
expectation both argue against selling a cake with a generated photo.

### Photo coverage

| | Count |
|---|---|
| Products with ≥1 photo | 23 of 88 (26%) |
| Products with **no** photo | **65 of 88 (74%)** |
| Media library items never referenced anywhere | 53 |

The 53 unreferenced images are likely the missing product photos, just never attached.
Matching them to products is a manual pass someone with eyes on the product line needs to
do — filenames like `Chocolate-caramel-cupcake-9pcs.png` and `Almond-Brownie-w_o-liner.png`
map obviously, but many do not.

---

## Data issues (need your decision)

### 1. Four products priced ₱0 and not purchasable

| Product | ID | Category | Photo | Likely duplicate of |
|---|---|---|---|---|
| Red Velvet Cake | 1707 | Bento Cakes | ✅ | Red Velvet (1407) @ ₱550 |
| Carrot Cake | 1706 | Bento Cakes | ✅ | Carrot (1409) @ ₱600 |
| Ube Halaya | 1705 | Bento Cakes | ✅ | Ube Halaya (1412) @ ₱600 |
| Bacon Butter | 1697 | Bento Cakes | ✅ | Butter Bacon (1415) @ ₱600 |

Awkwardly, these four are the ones **with** photos and descriptions, while their priced
twins have neither. **Recommendation: keep the ₱0 records' photos and copy, move them onto
the priced records, then delete the ₱0 records.**

### 2. Price conflict — Mini Cake Sampler

| Product | ID | Category | Price |
|---|---|---|---|
| Mini Cakesampler | 1482 | Basic Cakes | **₱2,000** |
| Mini Cakes Sampler | 2431 | Seasonal Occassions | **₱2,500** |

Same description (4 mini cakes: Red Velvet, Carrot, Chocolate, Chocolate Chip). Also sold
as a Bento Cakes variation "4pcs Mini Cake Sampler" @ **₱2,000**.
**Which price is correct — ₱2,000 or ₱2,500?** (Possibly ₱2,500 is a Mother's Day premium,
in which case it should be a seasonal variant, not a separate product.)

### 3. Duplicate products across categories

| Product | Customize Cakes | Seasonal Occassions | Prices agree? |
|---|---|---|---|
| Bento Party | 1708 @ ₱2,000 | 2427 @ ₱2,000 | ✅ yes |
| Cake Bouquet | 2203 @ ₱3,500–4,500 | 1992 @ ₱3,500–4,500 | ✅ yes |
| Mini Cake Sampler | 1482 @ ₱2,000 (Basic) | 2431 @ ₱2,500 | ❌ **no** |

Only the Seasonal copies have photos. In WooCommerce a product can sit in multiple
categories, so these should be **merged, not duplicated** — otherwise stock, reporting and
the Meta catalog all double-count.

### 4. Bento Cakes modelled twice

- **`Bento Cakes` (1820)** — one variable product, 9 flavor variations, ₱500–₱2,000, 5 photos
- **14 standalone simple products** — Lemon Butter ₱500, Red Velvet ₱550, Chocolate Yema ₱500,
  Dark Roast Coffee ₱500, Carrot ₱600, Chocolate Chip ₱600, Ube Halaya ₱600, Matcha ₱600,
  Butter Bacon ₱600, Chocolate Bacon ₱600, + the four ₱0 records

Prices agree between the two models **except Carrot** (₱650 as a variation vs ₱600 standalone)
and **Chocolate Chip / Ube Halaya** (₱700 as variations vs ₱600 standalone).

**Recommendation: keep the variable product, delete the standalones.** One product page with
a flavor selector is better for the IG→checkout path than 14 near-identical pages, and it
matches how the plan's Stage 2 describes flavor options. But confirm the correct prices —
the variation prices may be the newer, intended ones.

### 5. Wrong photos — Dark Roast Coffee cake

`Dark Roast Coffee` (561, Basic Cakes, ₱1,500–4,500) is described as *"Moist coffee cake
made from coffee extract house-made from SAGADA beans, covered in a smooth Mexican
frosting"* but its three images are `Tirmisu-Cake-Whole.png` (twice) and
`Tiramisu-Cake-Sliced.png`. Needs the correct photos.

Related: `Cheesecake` (1809, Basic Cakes, ₱1,500–3,500) uses `Bibingka-Cheesecake-*.png`
while `Bibingka Cheesecake` (1765) is a separate ₱1,500 product with the same images.
Confirm whether these are two products or one.

### 6. Mislabelled variation — Cake Tins Matcha

`Matcha` (1458) has three variations: **Solo ₱350, Sharing ₱750, Sharing ₱3,000.** Every
other Cake Tins product uses Solo / Sharing / **Party**. The ₱3,000 variation should almost
certainly be **Party** — as written, the product page shows two identical "Sharing" options
at different prices, which will confuse customers and mis-route orders.

### 7. Empty and placeholder pages

`/events` and `/book-an-event` have **no content at all**. `/dessert-tables` is a
"Coming Soon" countdown. `/event-foodcart` still has template dummy text
(`email@email.com`, `(123) 123 123`).

Per the plan §4, PayMongo's card activation depends on an acquirer reviewing a complete,
credible site — **placeholder pages and dummy contact details are exactly what fails that
review.** Either finish these four pages or unpublish them before we submit the live URL.

### 8. Stale category

Category **"CupcakeLab"** (id 28) reports a count of 12 but contains **zero** products.
Cosmetic, but it will render an empty category in nav and in the Meta catalog feed.

### 9. Leftover seasonal copy

`Cake Pop Bouquet` (1975, Seasonal Occassions) reads *"6 cake pops, decorated for
**valentine's day** and wrapped in a bouquet"* while sitting in the Mother's Day menu. The
`/seasonal` page copy for the same product correctly says Mother's Day — so the product
record is stale.

More broadly: all 12 Seasonal Occassions products have **Mother's Day baked into their
descriptions**. When the Father's Day menu goes live these are unusable as-is. Worth
deciding now whether seasonal framing lives in the product description (rewrite every
season) or in the category/campaign layer (write descriptions once, seasonal-neutral).

### Missing for checkout

None of the plan's Stage 3 checkout fields exist yet — no **Viber number**, no
delivery/pickup toggle, no delivery date with lead-time enforcement, no delivery zone
fees, no dedication/topper text field, no custom-design image upload. All of that is
Phase 2 work to build, not extract.

---

## What carries into the production build

**Keep:**
- All 88 products + 173 variations (after the cleanup decisions above) — `data/products.json`
- Cormorant Garamond + Manrope pairing
- Homepage `:root` design tokens (palette, shadows, 28 px radius)
- Homepage section order and the Quick Order modal pattern
- Brand copy, taglines, press quotes, About narrative
- Lead-time rules (3–5 days; 7 days custom) → checkout validation
- 118 optimized images

**Drop:**
- Elementor + Astra entirely (the plan calls for a custom theme; the homepage is already
  hand-coded, so this is mostly a port not a rewrite)
- Roboto, Roboto Slab, Inter, DM Serif Display, Playfair Display, Cardo (5 fonts, dead weight)
- The Astra global palette, once the berry-vs-tomato question is settled
- Duplicate and ₱0 product records

**Build new:** everything in `PLAN.md` §2 Stage 3 onward — checkout fields, PayMongo QRPh
instructions, manual bank transfer, webhook handler, Telegram/Slack routing, policy pages.
