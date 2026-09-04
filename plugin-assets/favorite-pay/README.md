# Favorite Pay — Visual Asset Foundation

Welcome to the visual asset foundation for **Favorite Pay**, the shared payment processing, wallet, and transaction service for the Favorite CMS ecosystem.

---

## 1. Architectural Role & Purpose

**Favorite Pay** serves as the unified financial service layer across the Favorite ecosystem:

```text
Favorite CMS Core
        ↓
   Favorite Pay (Payments, Wallets, Transactions, Gateways)
        ↓
├── Favorite Digital  (Digital download & license sales)
├── Favorite Shop     (Physical & multi-vendor e-commerce)
└── Favorite Web      (Official theme presentation layer)
```

- **Domain Ownership**: Favorite Pay owns all payment-related business logic, wallet accounting, gateway abstractions (card gateways, manual Bangladesh payments, Binance/USDT crypto payments), and refund handling.
- **Service Consumers**: Ecosystem plugins (`Favorite Digital`, `Favorite Shop`) and themes (`Favorite Web`) consume Favorite Pay's public APIs/services rather than implementing custom or fragmented payment logic.
- **Scope of This Directory**: This repository manages **only static design and visual assets**. It contains zero runtime code, zero API handlers, zero database migrations, and zero payment gateway secrets.

---

## 2. Brand Relationship & Identity Policy

Favorite Pay is an integral part of the Favorite ecosystem:
- **Parent Brand Alignment**: Favorite Pay derives its visual foundation directly from the Favorite CMS master brand identity (`#2563EB` Favorite Blue, `#0EA5E9` Vivid Sky) with the dedicated emerald financial accent (`#10B981` Emerald Success) established in [`design/design-tokens.md`](../../design/design-tokens.md).
- **No Independent Identity**: Do **NOT** create a separate or disconnected brand system.
- **No Product Logo Yet**: Official product branding and sub-brand logotypes will be generated in a future phase directly from the master logo mark.

---

## 3. Directory Structure & Asset Inventory

```text
plugin-assets/favorite-pay/
├── README.md                  # This foundational guide
├── icons/                     # Domain-specific payment & wallet icons (24×24 SVG)
│   ├── payment-confirmed.svg  # Settled payment with verified badge
│   ├── payment-failed.svg     # Rejected/declined payment card indicator
│   ├── payment-processing.svg # In-flight transaction card with cycle arc
│   ├── refund.svg             # Financial reversal return arrow with coin
│   ├── transaction.svg        # Dual bidirectional transfer/ledger arrows
│   └── wallet-balance.svg     # Digital wallet with emerging value token
├── illustrations/             # Conceptual spot artwork (400×300 SVG)
│   ├── digital-wallet.svg     # Digital store of value, multi-channel balances
│   └── secure-payment.svg     # Trust shield, encrypted transmission, verification
├── payment-methods/           # Third-party gateway branding policies
│   └── README.md              # Ingestion rules (no unauthorized provider logos)
└── placeholders/              # Media and screenshot guidelines
    └── README.md              # Sizing specs (no fake payment data or PII)
```

---

## 4. Reusability & Avoided Duplicate Icons

To keep the repository clean and avoid redundant assets, the global `/icons/` directory was thoroughly inspected prior to creating new icons. The following global icons were intentionally **reused rather than duplicated**:

| Global Icon (`/icons/`) | Usage in Payment Flows | Action Taken |
|---|---|---|
| `icons/commerce/payment.svg` | Generic credit card / payment method icon | Reused globally (not duplicated) |
| `icons/commerce/wallet.svg` | Generic wallet icon | Reused globally (not duplicated) |
| `icons/commerce/money.svg` | Cash / currency symbol | Reused globally (not duplicated) |
| `icons/commerce/order.svg` | Purchase order / invoice sheet | Reused globally (not duplicated) |
| `icons/commerce/cart.svg` | Shopping cart / checkout trigger | Reused globally (not duplicated) |
| `icons/status/locked.svg` | General SSL / padlock security | Reused globally (not duplicated) |
| `icons/status/verified.svg` | Security seal / badge | Reused globally (not duplicated) |
| `icons/status/success.svg` | General successful operation | Reused globally (not duplicated) |
| `icons/status/pending.svg` | General awaiting action | Reused globally (not duplicated) |
| `icons/status/error.svg` | General error dialog | Reused globally (not duplicated) |

Only icons with **distinct payment-specific semantics** (`payment-processing`, `payment-confirmed`, `payment-failed`, `refund`, `transaction`, `wallet-balance`) were created in `plugin-assets/favorite-pay/icons/`.

---

## 5. Technical Specifications

Every SVG in this directory complies with strict technical standards:
- **Scalability**: Clean, scalable `viewBox` coordinates (`0 0 24 24` for icons, `0 0 400 300` for illustrations).
- **Pure Vector**: 100% vector XML markup. Zero embedded raster images, zero base64 data, zero external fonts.
- **Standardized Styling**: Icons use `stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"` for adaptive theming in light and dark modes.
- **Lightweight**: Icons are ~300–450 bytes each; illustrations are ~3.8–4.1 KB each.

---

## 6. Security & Confidentiality Rules

Favorite Pay assets must **NEVER** contain:
- Gateway API keys, access tokens, or webhooks secrets.
- Merchant IDs, secret keys, or test credentials (Stripe, bKash, Nagad, Binance, etc.).
- Real, production, or simulated customer personal data (PII).
- Real credit card numbers, CVVs, or wallet private keys.
- Application logic, PHP controllers, JS scripts, SQL dumps, or database credentials.
