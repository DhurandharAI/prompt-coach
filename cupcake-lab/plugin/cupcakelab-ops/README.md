# Cupcake Lab Ops

PayMongo webhook handling, order routing into the MCJC Telegram channels and the
Slack approved-designs repository, the manual bank transfer payment method, and
the daily production/dispatch/digest crons.

Implements PLAN.md §4, §5 and §6.

---

## Why a plugin rather than a loose PHP file

PLAN.md §5 calls for "a small PHP webhook handler on the same Hostinger account".
This is that handler, packaged as a plugin. Two reasons for the change:

- A standalone `webhook.php` in the web root has to bootstrap WordPress itself
  (`require wp-load.php`), and stays directly reachable no matter what WordPress
  thinks about routing, rewrite rules, or maintenance mode.
- A registered REST route inherits WordPress's request handling and is testable
  through the same stack as everything else.

It still runs entirely on the Hostinger account with no Zapier, Make, or other
subscription, which was the actual point of the requirement.

---

## Install

1. Copy `cupcakelab-ops/` to `wp-content/plugins/`.
2. Activate it in **Plugins**. Activation schedules the two daily digests.
3. Add the configuration constants below to `wp-config.php`, above the
   `/* That's all, stop editing! */` line.

Secrets live in `wp-config.php`, never in the options table — on shared hosting a
database dump is the likeliest way credentials leak, and options are the first
thing in it.

```php
/* ---- PayMongo ---- */
define( 'CUPCAKELAB_PAYMONGO_MODE', 'test' );          // 'test' until you go live
define( 'CUPCAKELAB_PAYMONGO_WEBHOOK_SECRET', 'whsk_…' ); // shown once, when the webhook is created

/* ---- Telegram (MCJC workspace) ---- */
define( 'CUPCAKELAB_TELEGRAM_BOT_TOKEN', '123456:ABC-…' );
define( 'CUPCAKELAB_TELEGRAM_CHANNELS', '{
    "orders":     "-1001111111111",
    "payables":   "-1002222222222",
    "design":     "-1003333333333",
    "production": "-1004444444444",
    "dispatch":   "-1005555555555",
    "general":    "-1006666666666",
    "photos":     "-1007777777777",
    "cci_orders": "-1008888888888"
}' );

/* ---- Slack (approved designs) ---- */
define( 'CUPCAKELAB_SLACK_WEBHOOK_URL', 'https://hooks.slack.com/services/…' );
```

---

## Getting the values

### PayMongo webhook

Create the webhook against this endpoint:

```
https://cupcakelab.ph/wp-json/cupcakelab/v1/paymongo
```

Subscribe to `payment.paid` and `payment.failed`. PayMongo shows the secret key
(`whsk_…`) **once, at creation**. If it is lost, roll the webhook and update the
constant.

Do the whole thing on `staging.cupcakelab.ph` with test keys first, exactly as
PLAN.md §4 says.

### Telegram

1. Message **@BotFather**, `/newbot`, and keep the token.
2. Add the bot to each of the eight MCJC channels **as an administrator with
   permission to post**. A plain member cannot post to a channel.
3. Get each channel's numeric ID. The simplest way: post any message in the
   channel, then open
   `https://api.telegram.org/bot<TOKEN>/getUpdates` and read `chat.id`.
   Supergroup and channel IDs are negative and begin `-100`.

### Slack

**Incoming Webhooks** → create one for the approved-designs channel → copy the
URL. One webhook posts to exactly one channel.

---

## Verify before going live

### 1. Signature format

⚠️ **This needs confirming against your account.** `developers.paymongo.com` was
blocked by network policy when this code was written, so the header grammar in
`includes/class-signature.php` was implemented from PayMongo's documented scheme
as understood, not read off the live docs:

```
Paymongo-Signature: t=<unix_ts>,te=<test_sig>,li=<live_sig>
signed payload:     "<unix_ts>.<raw_body>"
algorithm:          HMAC-SHA256, keyed with the whsk_… secret
compare:            te in test mode, li in live mode
```

A bare-hex-digest variant is also accepted, so an account sending the simpler
form still works.

Confirm with a real captured webhook:

```bash
php tools/verify-signature.php \
    --body=captured-payload.json \
    --header='t=1753000000,te=abc…' \
    --secret=whsk_… \
    --mode=test
```

On a mismatch the tool prints which candidate payload construction *would* have
matched, which tells you exactly what to change. Capture the payload by pointing
a test webhook at any request-logging endpoint and triggering a test payment —
save the body byte-for-byte, since re-serialised JSON never verifies.

### 2. Run the tests

```bash
php tools/verify-signature.php --self-test   # 12 checks: HMAC, replay, tampering
php tools/test-messages.php                  # 13 checks: escaping, money formatting
```

Both are plain CLI PHP and need no WordPress.

### 3. End-to-end

Place a test order in test mode and confirm a card lands in `#orders`, a record
in `#payables`, and — for a custom item — a request in `#design` and Slack.

---

## Routing

Implements the PLAN.md §5 table.

| Event | Channel | Content |
|---|---|---|
| Payment confirmed | `#orders` | Order card: number, items, dedication, date, destination, amount, method, **Viber number as tap-to-copy**, plus the create-the-group checklist |
| Payment confirmed | `#orders` | The canned Viber welcome message, as its own copyable message |
| Payment confirmed | `#payables` | Gross, fee, net, PayMongo reference |
| Order has a custom design | `#design` | Design request + reference image + admin link |
| Order has a custom design | Slack | Same, as Block Kit, into approved-designs |
| Order flagged CCI | `#cci_orders` | The order card, CCI-tagged, **instead of** `#orders` |
| Payment failed | `#orders` | Short notice with the reason |
| Daily 6 AM | `#production` | Bake list for today + tomorrow, quantities aggregated |
| Daily 6 AM | `#dispatch` | Delivery manifest: addresses, time windows, contact numbers |
| Daily 8 PM | `#general` | Orders placed, revenue, tomorrow's load |
| Finished-product shot | `#photos` | Image + order reference |

CCI orders go to `#cci_orders` *instead of* `#orders`, not as well — corporate
work should not be buried in retail volume. Change `Router::payment_confirmed()`
if you would rather have both.

### An order counts as "custom" when

- it contains an item in the `customize-cakes` category, **or**
- it has dedication text, **or**
- the customer uploaded a reference image.

Any of those means the design team is involved.

---

## Viber

Viber's public API **cannot create group chats or add members**. No amount of
code changes that, so PLAN.md §6 makes it automation-assisted manual, and this
plugin does the assisting:

- The `#orders` card carries the group name already formatted to the convention
  (`CL #1042 – Ana R. – Aug 2`) in a tap-to-copy block.
- The customer's Viber number is tap-to-copy too.
- The welcome message is posted as a separate message, ready to paste.
- The thank-you page and order email tell the customer to expect the invite,
  so the 2-hour SLA is a stated promise rather than a silence.

---

## Crons

Activation schedules both digests through WP-Cron. **WP-Cron only fires when
somebody visits the site**, which is no way to run a 6 AM bake list — at 6 AM
nobody is browsing.

Switch to a real cron job in hPanel:

1. Add to `wp-config.php`:
   ```php
   define( 'DISABLE_WP_CRON', true );
   ```
2. In hPanel → **Cron Jobs**, run every 5 minutes:
   ```
   cd /home/USER/public_html && /usr/bin/php wp-cron.php > /dev/null 2>&1
   ```

Digests are scheduled in the **site's** timezone via `current_datetime()`, so set
WordPress to Asia/Manila and 6 AM stays 6 AM regardless of the server clock.

To fire one by hand for testing:

```bash
wp cron event run cupcakelab_ops_morning_digest
wp cron event run cupcakelab_ops_evening_digest
```

---

## Bank transfer

Registered as a WooCommerce payment method. Required, not optional: QR Ph settles
over InstaPay and is capped near **₱50,000 per transaction**, so bulk and
corporate orders cannot go through it at all.

Configure under **WooCommerce → Settings → Payments → Bank transfer**:

- **Transfer instructions** — account name, bank, account number. Shown on the
  thank-you page and in the order email.
- **Minimum order total** — set to `50000` to offer it only where QRPh cannot
  reach, or leave at `0` to offer it always. PLAN.md §4 recommends keeping it
  after cards go live, since corporate clients often prefer invoice-and-transfer.

Orders are placed **on-hold**, not processing — nothing has been paid yet. Stock
is reduced so it is reserved. When the transfer lands, move the order to
processing and the `#orders` routing fires then.

---

## Fees in `#payables`

Real fee and net are used when PayMongo sends them in the payload. When it does
not, they are calculated from the configured rate and the message says
**(estimated)** — PLAN.md §4 records those rates as indicative and unverified, so
finance must not reconcile against a guess believing it to be fact.

Adjust the rates once confirmed at paymongo.com/pricing:

```php
add_filter( 'cupcakelab_ops_fee_rates', function ( array $rates ): array {
    $rates['qrph'] = array( 1.5, 0.0 );   // [ percent, fixed pesos ]
    $rates['card'] = array( 3.5, 15.0 );
    return $rates;
} );
```

---

## Safety properties

- **Signature checked before anything else.** Verified against the raw body
  bytes, using `hash_equals` for a timing-safe comparison. A failure returns 401
  and nothing is processed.
- **Replay window of 300 seconds** on timestamped headers.
- **Idempotent.** Processed event IDs are remembered for 24 hours, and the router
  additionally marks the order, so a PayMongo retry cannot double-post a card.
  The mark is written *before* sending: a duplicate arriving mid-send is likelier
  than a send failing, and a missing card gets noticed while a duplicate one
  quietly breaks the Viber SOP.
- **Unmatched orders return 200.** There is nothing to retry when the order is
  genuinely absent, and an error would make PayMongo retry for hours. The miss is
  logged.
- **Logs are redacted.** Anything keyed like a secret, token, signature or
  password is replaced before it reaches the log file, which on shared hosting is
  readable by anyone with FTP.
- **Telegram HTML is escaped.** An unescaped `&` in a dedication would make
  Telegram reject the whole message and silently lose the order card.

---

## Troubleshooting

| Symptom | Cause |
|---|---|
| Nothing in Telegram | Bot is not an **administrator** of the channel, or the chat ID is wrong. Check `WooCommerce → Status → Logs`, source `cupcakelab-ops` — the Telegram API reason is logged verbatim. |
| `chat not found` | Wrong chat ID, or the ID is missing the `-100` prefix. |
| `can't parse entities` | Something reached Telegram unescaped. Please report it — everything should go through `Message_Builder::esc()`. |
| Webhook returns 401 | Signature mismatch. Run `tools/verify-signature.php` against the captured payload. |
| Digests never fire | WP-Cron needs traffic. Set up the real cron job above. |
| Card posted twice | Both the webhook and a status transition fired before the guard was written. Check for `_cupcakelab_notified_paid` on the order. |
