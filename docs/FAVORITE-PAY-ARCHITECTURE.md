# Favorite Pay — Architecture & Implementation Blueprint

**Document Version**: 1.0.0  
**Status**: Architectural Proposal / Implementation Blueprint  
**Plugin Identifier**: `favorite-pay`  
**Target Platform**: Favorite CMS Core (`Favorite-CMS-Universal`)  
**Ecosystem Dependencies**: Favorite Pay (Foundation) → Favorite Digital, Favorite Shop, Favorite Web Theme  

> [!NOTE]
> **Ecosystem Architectural Reference & Implementation Blueprint**
> This document is an architectural reference and implementation blueprint for the Favorite Pay payment system. It is **NOT** application runtime code, and it does **NOT** replace the standalone Favorite Pay source-code repository. It is archived here in `Favorite-CMS-Assets/docs/` because this repository serves as the central documentation and asset hub for the entire Favorite CMS ecosystem. Application source code, PHP classes, database migrations, and runtime tests reside exclusively in the Favorite Pay source project.

---

## 1. Executive Summary & Purpose

**Favorite Pay** is the authoritative, shared financial infrastructure, payment gateway orchestrator, and digital wallet service for the entire Favorite CMS ecosystem.

In accordance with ecosystem architecture principles:
- **Favorite Pay** encapsulates all financial transactions, currency conversions, exchange-rate snapshot locking, gateway adapters, refund handling, and ledger accounting.
- **Consumer Plugins** (such as **Favorite Digital** and **Favorite Shop**) are strictly forbidden from implementing payment gateway drivers, storing merchant credentials, or processing direct payments. They interact exclusively through Favorite Pay's public service contracts and event interfaces.
- **Presentation Themes** (such as **Favorite Web**) consume payment selection interfaces and status indicators via public template operations and API endpoints, remaining completely free of financial business logic.

This blueprint establishes a battle-tested, modular, and secure foundation designed to run efficiently on standard hosting environments while maintaining strict enterprise-grade financial integrity.

---

## 2. Scope of Architecture

This specification covers:
1. Architectural contracts between Favorite Pay and Favorite CMS Core.
2. Interservice protocols for consumer plugins (Favorite Digital, Favorite Shop).
3. Presentation boundaries for theme consumers (Favorite Web).
4. Gateway abstraction layers supporting Manual Bangladesh payments (bKash, Nagad, Bank Transfer), International Cards, and Crypto (Binance Pay / USDT).
5. Immutable BDT-centric currency conversion and exchange-rate locking mechanics.
6. Non-floating-point financial data structures and double-entry wallet ledger accounting.
7. Lifecycle management, database migrations, security barriers, and testing strategies.

---

## 3. What Favorite Pay Owns

Favorite Pay is the sole owner and authoritative system of record for:
- **Payment Gateways & Providers**: Configuration, credential storage, webhooks, and driver abstractions.
- **Payment Intents & Transactions**: Creation, state machine transitions, attempt logging, and verification.
- **Currency & Exchange Rates**: Base accounting currency (BDT), exchange rate sources, rate refresh schedules, and transaction-time conversion snapshots.
- **Transaction Amount Locking**: Guaranteeing that checkout amounts cannot diverge due to real-time currency fluctuations.
- **Refund & Reversal Processing**: Transaction-level and item-level refund workflows.
- **Digital Wallet & Customer Balance**: Account balances, deposit/recharge workflows, hold/settlement states, and immutable append-only ledger entries.
- **Financial Audit Logs**: Cryptographic signatures, webhook payloads, attempt histories, and operator reconciliation logs.

---

## 4. What Favorite Pay Explicitly Does NOT Own

To ensure strict separation of concerns, Favorite Pay does **NOT** own or manage:
- **Product Catalogs**: Digital downloads, licenses, physical products, SKUs, or inventory (owned by Favorite Digital and Favorite Shop).
- **Shopping Carts & Order Workflows**: Cart contents, order lines, shipping rules, delivery zones, or order state management (owned by Favorite Digital and Favorite Shop).
- **Cash on Delivery (COD) Workflow**: Order delivery and physical collection logistics (owned by Favorite Shop; Pay provides only state abstraction).
- **Customer User Profiles**: Authentication, user accounts, and role permissions (owned by Core `UserEngine` and `AuthenticationEngine`).
- **HTML Storefront Layouts**: Checkout page UI design, theme styling, and CSS frameworks (owned by Favorite Web Theme).

---

## 5. Dependency Relationship with Favorite CMS Core

Favorite Pay is implemented as a standard first-party plugin (`favorite-pay`) conforming strictly to the Core Extension System:

```text
Favorite CMS Core
  ├── Application            → Service Container & IoC resolution
  ├── Database               → PDO transactional persistence (MySQL/SQLite)
  ├── Migrator               → Schema creation & versioned PHP migration execution
  ├── Setting                → Gateway settings, toggle switches, and rate thresholds
  ├── Router                 → Public webhook routes and customer redirect endpoints
  ├── Hook                   → Action & Filter event bus (`favorite.pay.*`) with listener isolation
  ├── AdminMenu              → Admin dashboard menu registration and navigation
  └── PluginManager          → Plugin manifest validation, lifecycle, and failure isolation
```

### Core Stack Reality Check
Inspection of the actual Favorite CMS Core repository (`Favorite-CMS-Universal`) confirms that the platform is built with **PHP 8.1+** using Composer PSR-4 autoloading (`FavoriteCMS\`), PDO database abstraction, and a lightweight, zero-dependency architecture. Favorite Pay's implementation blueprint directly matches the **actual PHP 8.1+ Core**, adhering to its native PluginManager, Database, and Hook architecture.

---

## 6. Dependency Relationship with Favorite Digital

```text
┌─────────────────────────────────────────────────────────────┐
│                      Favorite Digital                       │
│  (Digital Products, Category Memberships, File Downloads)   │
└──────────────────────────────┬──────────────────────────────┘
                               │ Consumes ServiceContract
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                        Favorite Pay                         │
│   (PaymentIntent -> Process -> Lock -> Settle -> Event)     │
└──────────────────────────────┬──────────────────────────────┘
                               │ Publishes Event
                               ▼
┌─────────────────────────────────────────────────────────────┐
│               favorite.pay.payment.succeeded                │
│    (Favorite Digital unlocks download token / membership)   │
└─────────────────────────────────────────────────────────────┘
```

- **Invocation**: Digital calls Favorite Pay to initialize a `PaymentIntent` referencing `source_plugin="favorite.plugin.digital"` and `source_reference="ORDER-DIGITAL-1001"`.
- **Amount & Currency**: Digital passes the BDT base price. Pay locks the rate if customer chooses a foreign display currency.
- **Fulfillment**: Digital listens to `favorite.pay.payment.succeeded`. Upon receipt, Digital activates download entitlements or membership validity. Digital never inspects card numbers or gateway secrets.

---

## 7. Dependency Relationship with Favorite Shop

- **Physical Products & Shipping**: Favorite Shop calculates subtotal + delivery fee in **BDT**.
- **Payment Hand-off**: Shop creates a `PaymentIntent` via Favorite Pay for online options (Card, Manual BD, Binance).
- **Cash on Delivery (COD) Isolation**:
  - COD does **not** route through online payment gateway drivers.
  - Shop manages the physical delivery journey.
  - Upon doorstep delivery and driver cash collection, Shop logs the payment status through Favorite Pay's offline settlement API, creating an audit trail without mocking an online gateway.

---

## 8. Relationship with Favorite Web Theme

- Favorite Web embeds payment selection components into checkout templates via Core presentation decorators and public API endpoints (`/api/v1/pay/methods`, `/api/v1/pay/intents`).
- The theme does **not** process webhooks or manage payment states.
- The theme receives localized payment method icons, gateway badges, and fee breakdowns directly from Favorite Pay's public metadata endpoints.

---

## 9. Database Ownership & Isolation Principles

1. **Table Prefix**: All Favorite Pay tables use the reserved prefix `favorite_pay_*`.
2. **Strict Isolation**: No other plugin (Digital or Shop) may query or modify `favorite_pay_*` tables directly via SQL. All interaction must pass through public PHP service contracts or HTTP/REST APIs.
3. **Transaction Safety**: All multi-step operations (such as balance debits and transaction status updates) execute inside explicit Core Database transactions:
   ```php
   $db->transaction(function (Database $db) {
       // atomic operations
   });
   ```

---

## 10. Proposed Database Schema Model (Minimal & Normalized)

To ensure high performance and maintainability on both SQLite (local/dev) and PostgreSQL (production), the schema is kept minimal, normalized, and strictly typed.

```
┌─────────────────────────┐       ┌─────────────────────────┐
│  favorite_pay_gateways  │       │   favorite_pay_rates    │
├─────────────────────────┤       ├─────────────────────────┤
│ id (VARCHAR PK)         │       │ code (VARCHAR(3) PK)    │
│ driver (VARCHAR)        │       │ rate_to_bdt (NUMERIC)   │
│ is_active (BOOLEAN)     │       │ updated_at (TIMESTAMP)  │
│ config (JSON/TEXT)      │       └─────────────────────────┘
└───────────┬─────────────┘
            │ 1:N
            ▼
┌─────────────────────────┐       ┌─────────────────────────┐
│favorite_pay_transactions│ 1:N   │  favorite_pay_attempts  │
├─────────────────────────┼───────►├─────────────────────────┤
│ id (VARCHAR(36) PK)     │       │ id (VARCHAR(36) PK)     │
│ source_plugin (VARCHAR) │       │ transaction_id (FK)     │
│ source_ref (VARCHAR)    │       │ gateway_id (FK)         │
│ amount_base_bdt (BIGINT)│       │ attempt_number (INT)    │
│ amount_pay (BIGINT)     │       │ status (VARCHAR)        │
│ currency_pay (VARCHAR(3)│       │ raw_response (JSON/TEXT)│
│ rate_snapshot (NUMERIC) │       │ created_at (TIMESTAMP)  │
│ status (VARCHAR)        │       └─────────────────────────┘
│ created_at (TIMESTAMP)  │
└───────────┬─────────────┘
            │ 1:N
            ▼
┌─────────────────────────┐       ┌─────────────────────────┐
│  favorite_pay_refunds   │       │  favorite_pay_wallets   │
├─────────────────────────┤       ├─────────────────────────┤
│ id (VARCHAR(36) PK)     │       │ user_id (VARCHAR(36) PK)│
│ transaction_id (FK)     │       │ balance_bdt (BIGINT)    │
│ amount_bdt (BIGINT)     │       │ is_locked (BOOLEAN)     │
│ reason (TEXT)           │       └───────────┬─────────────┘
│ status (VARCHAR)        │                   │ 1:N
└─────────────────────────┘                   ▼
                                  ┌─────────────────────────┐
                                  │favorite_pay_wallet_entry│
                                  ├─────────────────────────┤
                                  │ id (VARCHAR(36) PK)     │
                                  │ user_id (VARCHAR(36) FK)│
                                  │ amount_bdt (BIGINT)     │
                                  │ balance_after (BIGINT)  │
                                  │ entry_type (VARCHAR)    │
                                  │ reference (VARCHAR)     │
                                  │ created_at (TIMESTAMP)  │
                                  └─────────────────────────┘
```

### Table 1: `favorite_pay_gateways`
- **Purpose**: Registry of available payment provider configurations.
- **Fields**:
  - `gateway_id` (`VARCHAR(64)`, PK): e.g. `manual_bkash`, `manual_nagad`, `manual_bank`, `stripe_cards`, `binance_pay`.
  - `driver` (`VARCHAR(64)`, Indexed): Driver implementation class key.
  - `title` (`VARCHAR(128)`): Display name (e.g., "bKash Personal / Merchant").
  - `is_active` (`BOOLEAN`, Default `False`): Operator toggle.
  - `supported_currencies` (`VARCHAR(255)`): Comma-separated ISO codes (`BDT`, `USD`).
  - `settings_encrypted` (`TEXT`): Sensitive API keys/credentials encrypted via Core Secret infrastructure.
  - `sort_order` (`INTEGER`, Default `0`).

### Table 2: `favorite_pay_rates`
- **Purpose**: Currency exchange rate ledger relative to base BDT.
- **Fields**:
  - `currency_code` (`VARCHAR(3)`, PK): e.g., `USD`, `EUR`, `INR`, `GBP`.
  - `rate_to_bdt` (`NUMERIC(18, 6)`): Multiplier where `1 Unit = X BDT` (e.g. 1 USD = 122.500000 BDT).
  - `source` (`VARCHAR(64)`): e.g., `manual_operator`, `openexchangerates`.
  - `updated_at` (`TIMESTAMP`, Indexed).

### Table 3: `favorite_pay_transactions`
- **Purpose**: Primary financial transaction ledger and state record.
- **Fields**:
  - `transaction_id` (`VARCHAR(36)`, PK): UUID v4.
  - `source_plugin` (`VARCHAR(64)`, Indexed): `favorite.plugin.digital`, `favorite.plugin.shop`, or `core.wallet`.
  - `source_reference` (`VARCHAR(128)`, Indexed): External order/license ID (e.g. `ORD-98214`).
  - `user_id` (`VARCHAR(36)`, Indexed, Nullable): Customer ID.
  - `amount_base_bdt` (`BIGINT`): Base amount in Bangladeshi Poisha (1 BDT = 100 Poisha). **Integer minor-unit**.
  - `amount_pay` (`BIGINT`): Actual charge amount in payment currency minor-units (e.g. Cents for USD).
  - `currency_pay` (`VARCHAR(3)`): ISO currency code of charge (`BDT`, `USD`).
  - `exchange_rate_locked` (`NUMERIC(18, 6)`): The exact immutable exchange rate applied.
  - `status` (`VARCHAR(32)`, Indexed): `draft`, `pending`, `processing`, `succeeded`, `failed`, `cancelled`, `refunded`, `partially_refunded`.
  - `payment_method_type` (`VARCHAR(32)`): `manual_bd`, `card`, `crypto`, `wallet`, `cod`.
  - `created_at` (`TIMESTAMP`, Indexed).
  - `updated_at` (`TIMESTAMP`).
  - `settled_at` (`TIMESTAMP`, Nullable).

### Table 4: `favorite_pay_attempts`
- **Purpose**: Audit trail of every interaction with a payment provider or submission attempt.
- **Fields**:
  - `attempt_id` (`VARCHAR(36)`, PK): UUID v4.
  - `transaction_id` (`VARCHAR(36)`, FK `favorite_pay_transactions.transaction_id`, Indexed).
  - `gateway_id` (`VARCHAR(64)`, Indexed).
  - `attempt_number` (`INTEGER`): Incremental attempt index.
  - `provider_reference` (`VARCHAR(255)`, Indexed, Nullable): External charge ID, bKash TrxID, or Binance prepay ID.
  - `status` (`VARCHAR(32)`): `initiated`, `submitted`, `verified`, `declined`, `error`.
  - `customer_payload` (`TEXT`, Nullable): Sanitized submission info (e.g. sender mobile number, last 4 digits).
  - `gateway_response` (`TEXT`, Nullable): Raw sanitized JSON response from webhook/gateway.
  - `error_message` (`TEXT`, Nullable): Sanitized error explanation.
  - `created_at` (`TIMESTAMP`).

### Table 5: `favorite_pay_refunds`
- **Purpose**: Tracks payment reversals and partial refunds.
- **Fields**:
  - `refund_id` (`VARCHAR(36)`, PK): UUID v4.
  - `transaction_id` (`VARCHAR(36)`, FK `favorite_pay_transactions.transaction_id`, Indexed).
  - `amount_bdt` (`BIGINT`): Refunded amount in base BDT Poisha.
  - `reason` (`TEXT`): Administrative justification.
  - `status` (`VARCHAR(32)`): `pending`, `succeeded`, `failed`.
  - `operator_id` (`VARCHAR(36)`, Nullable): Admin user approving the refund.
  - `created_at` (`TIMESTAMP`).

### Table 6: `favorite_pay_wallets`
- **Purpose**: Holds customer wallet summary balance.
- **Fields**:
  - `user_id` (`VARCHAR(36)`, PK): Core user identity.
  - `balance_bdt` (`BIGINT`, Default `0`): Current balance in BDT Poisha.
  - `is_locked` (`BOOLEAN`, Default `False`): Administrative hold flag.
  - `updated_at` (`TIMESTAMP`).

### Table 7: `favorite_pay_wallet_entries`
- **Purpose**: Immutable append-only double-entry financial ledger for wallets.
- **Fields**:
  - `entry_id` (`VARCHAR(36)`, PK): UUID v4.
  - `user_id` (`VARCHAR(36)`, FK `favorite_pay_wallets.user_id`, Indexed).
  - `amount_bdt` (`BIGINT`): Signed integer (+credit, -debit) in BDT Poisha.
  - `balance_after_bdt` (`BIGINT`): Running balance after applying entry.
  - `entry_type` (`VARCHAR(32)`): `deposit`, `purchase`, `refund`, `manual_adjustment`.
  - `reference_id` (`VARCHAR(128)`, Indexed): Associated `transaction_id` or administrative memo.
  - `created_at` (`TIMESTAMP`).

---

## 11. Payment Provider Abstraction Layer

To ensure provider neutrality, all gateways implement a uniform runtime protocol.

```python
from typing import Protocol, Mapping, Any
from dataclasses import dataclass

@dataclass(frozen=True)
class GatewayRequest:
    transaction_id: str
    amount_pay: int              # in minor units
    currency_pay: str            # ISO code
    customer_email: str
    customer_phone: str | None
    return_url: str
    cancel_url: str
    metadata: Mapping[str, str]

@dataclass(frozen=True)
class GatewayResponse:
    action_type: str             # "redirect", "render_instructions", "completed", "error"
    redirect_url: str | None = None
    provider_reference: str | None = None
    instructions: Mapping[str, Any] | None = None
    error_message: str | None = None

@dataclass(frozen=True)
class WebhookVerificationResult:
    is_valid: bool
    transaction_id: str | None
    provider_reference: str | None
    amount_paid: int | None
    currency_paid: str | None
    status: str                  # "succeeded", "failed", "pending"
    raw_payload: Mapping[str, Any]

class PaymentGatewayDriver(Protocol):
    driver_id: str
    
    def initialize(self, config: Mapping[str, Any]) -> None:
        ...
        
    def initiate_payment(self, request: GatewayRequest) -> GatewayResponse:
        ...
        
    def verify_webhook(self, headers: Mapping[str, str], body: bytes) -> WebhookVerificationResult:
        ...
        
    def verify_manual_submission(self, attempt_id: str, submission: Mapping[str, Any]) -> bool:
        ...
        
    def process_refund(self, transaction_id: str, amount_pay: int, reason: str) -> bool:
        ...
```

---

## 12. Manual Bangladesh Payment Workflow

Manual mobile financial services (bKash, Nagad, Rocket, Upay) and direct Bank Wire Transfers follow an **Asynchronous Verified Reconciliation** pattern:

```text
Customer Checkout
       │
       ▼
1. Customer selects bKash / Nagad / Bank
       │
       ▼
2. Pay displays official Merchant/Personal Number, Account Details & Instructions
       │
       ▼
3. Customer performs transaction on their mobile device or bank portal
       │
       ▼
4. Customer enters Sender Mobile Number & Transaction ID (TrxID) in checkout modal
       │
       ▼
5. Pay creates a `favorite_pay_attempts` entry (status: "submitted")
       │ Transaction state -> "pending_verification"
       ▼
6. Admin Panel Alert: Admin navigates to "Favorite Pay → Manual Payments"
       │
       ├── Admin verifies TrxID & amount on official MFS merchant statement / bank portal
       │
       ▼
7. Operator Action:
       ├── If VALID: Clicks [Approve & Settle]
       │      → Transaction status -> "succeeded"
       │      → Event `favorite.pay.payment.succeeded` dispatched
       │      → Digital/Shop fulfills order automatically
       │
       └── If INVALID / DUPLICATE: Clicks [Reject]
              → Transaction status -> "failed"
              → Customer notified to re-submit or cancel
```

### Protection Against TrxID Reuse
- System enforces unique index on `(gateway_id, provider_reference)` for verified attempts, strictly preventing the same TrxID from being credited twice.

---

## 13. Card Payment Gateway Abstraction

- **Zero PCI Scope**: Favorite Pay will **never** capture, handle, or store raw 16-digit Primary Account Numbers (PANs), CVVs, or card PINs on the server.
- **Execution Flow**:
  1. Favorite Pay requests a checkout session from the configured card provider (e.g., Stripe, SSLCommerz, or 2Checkout).
  2. Provider returns a secure client secret or redirect URL.
  3. Customer completes 3D-Secure authentication on the provider's PCI-DSS Level 1 compliant hosted page or via secure tokenized iframe.
  4. Webhook confirms settlement via cryptographic HMAC signature verification before updating transaction state.

---

## 14. Binance Pay / USDT Crypto Gateway Abstraction

- **Protocol**: Integration with Binance Pay Merchant API (or generic Web3 USDT gateway).
- **Execution Flow**:
  1. Pay converts base BDT amount into USD/USDT minor units (e.g. 120.00 USDT = 120000000 micro-units or standard 6/8 decimal representation) and locks the exchange rate.
  2. Gateway driver calls `POST /binancepay/openapi/v2/order`.
  3. Gateway returns `universalUrl` (for mobile deep-linking) and `qrContent` (for desktop QR scanning).
  4. Customer scans QR in Binance App and authorizes payment.
  5. Binance sends signed webhook payload (`cert-serial` and `signature` verified against Binance public key).
  6. Pay updates transaction to `succeeded` and releases order.
- **Tolerance**: Exact amount matching required; no underpayment allowed.

---

## 15. Currency Conversion & Exchange-Rate Locking Model

### Core Rule
**Base Accounting Currency is BDT (Bangladeshi Taka). All internal balance tracking and catalog base values are anchored in BDT.**

### Three Distinct Currency Layers
1. **Base Amount (`amount_base_bdt`)**: The true accounting cost of the goods/services in BDT.
2. **Display Amount (`amount_display`)**: Informational converted price shown to browsing visitors based on their chosen UI currency.
3. **Payment Amount (`amount_pay`)**: The exact binding monetary value charged through the payment gateway.

### Checkout Conversion Snapshot (Rate Locking)
To prevent currency volatility from causing order discrepancies:
- When the customer initiates checkout in a foreign currency (e.g. USD), Favorite Pay creates a `PaymentIntent` and **captures an immutable snapshot**:
  ```json
  {
    "base_amount_bdt": 1225000,
    "exchange_rate": 122.500000,
    "rate_timestamp": "2026-09-04T10:15:00Z",
    "rate_source": "central_bank",
    "charge_amount": 10000,
    "charge_currency": "USD"
  }
  ```
- **Guaranteed TTL**: The locked rate is valid for **30 minutes**. If payment is completed within this window, the locked rate applies regardless of real-time market changes. If the window expires, the intent is invalidated, and the customer must refresh the checkout rate.

---

## 16. Money Precision & Safe Numeric Representation

> [!CRITICAL]
> **Floating-point arithmetic (`float`, `double`) is strictly forbidden for all financial calculations in Favorite Pay.**

### Integer Minor-Unit Storage Standard
All monetary values are stored as **64-bit integers (`BIGINT`) representing minor currency units**:
- **BDT**: Stored in **Poisha** (1 BDT = 100 Poisha). Example: `৳120.50` is stored as `12050`.
- **USD / EUR**: Stored in **Cents** (1 USD = 100 Cents). Example: `$10.00` is stored as `1000`.
- **Crypto (USDT)**: Stored in **micro-units** (1 USDT = $10^6$ units, 6 decimal places). Example: `12.500000 USDT` is stored as `12500000`.

### Precision Conversion Formula
Conversions use high-precision fixed-point integer math:
$$\text{amount\_pay} = \left\lfloor \frac{\text{amount\_bdt} \times \text{minor\_multiplier}}{\text{rate\_to\_bdt}} + 0.5 \right\rfloor$$
All intermediate division is evaluated using Python's `decimal.Decimal` configured with `ROUND_HALF_EVEN` before casting to integer minor units.

---

## 17. Wallet Architecture & Ledger Accounting

### Resolution of the Currency Conflict (BDT vs. Dollar Wallet)
> [!IMPORTANT]
> **Architectural Decision Point**: The project requirements noted a potential conflict between a BDT base accounting currency and a previously proposed Dollar-balance wallet concept.
> 
> **Architectural Recommendation**:
> Storing raw, fluctuating foreign currency balances alongside local BDT accounting invites silent currency mismatch bugs and regulatory foreign-exchange liabilities.
> 
> **Solution**:
> 1. **Primary Wallet Ledger is strictly denominated in BDT**.
> 2. Customers depositing via USD Card or USDT will have their payment converted to BDT at the time of recharge using the locked exchange rate.
> 3. Customers may view their balance estimated in USD for display purposes, but the underlying immutable ledger balance is BDT.

### Append-Only Double-Entry Ledger Principles
1. **No Direct Mutation**: The `balance_bdt` column on `favorite_pay_wallets` is a cached snapshot. The true balance is mathematically derived from the sum of rows in `favorite_pay_wallet_entries`.
2. **Reconciliation Invariant**:
   $$\text{Current Balance} = \sum \text{amount\_bdt}$$
3. **Atomic Decrements**: Debits require a database row lock (`SELECT ... FOR UPDATE`) to prevent race conditions and overdrafts:
   ```sql
   SELECT balance_bdt FROM favorite_pay_wallets WHERE user_id = :uid FOR UPDATE;
   ```

---

## 18. Public Service & API Contracts

Favorite Pay registers a typed public service contract into the Core `ServiceContainer` under the key `engine.pay`:

```python
class FavoritePayService(Protocol):
    def create_payment_intent(
        self,
        source_plugin: str,
        source_reference: str,
        amount_bdt: int,
        customer_email: str,
        customer_phone: str | None = None,
        allowed_methods: tuple[str, ...] = (),
        metadata: Mapping[str, str] | None = None
    ) -> PaymentIntent:
        """Called by Digital or Shop to initiate a payment flow."""
        ...

    def get_transaction(self, transaction_id: str) -> TransactionRecord | None:
        """Retrieve authoritative transaction status."""
        ...

    def verify_transaction(self, transaction_id: str) -> TransactionStatus:
        """Perform real-time check or re-verification."""
        ...

    def request_refund(
        self,
        transaction_id: str,
        amount_bdt: int,
        reason: str,
        operator_id: str | None = None
    ) -> RefundResult:
        """Initiate full or partial refund."""
        ...

    def get_wallet_balance(self, user_id: str) -> int:
        """Returns balance in BDT minor units."""
        ...

    def debit_wallet(
        self,
        user_id: str,
        amount_bdt: int,
        reference: str,
        memo: str
    ) -> WalletEntryResult:
        """Atomically debit user wallet for a purchase."""
        ...

    def convert_amount(
        self,
        amount_bdt: int,
        target_currency: str
    ) -> ConversionQuote:
        """Returns conversion quote with locked TTL."""
        ...
```

---

## 19. Events & Hook Lifecycle

All events are dispatched through the Core `EventEngine` adhering to the required dot-separated naming convention:

| Event Name | Producer | Payload Fields | Consumer Usage |
|---|---|---|---|
| `favorite.pay.payment.created` | `favorite-pay` | `transaction_id`, `source_plugin`, `amount_bdt` | Audit, analytics |
| `favorite.pay.payment.pending` | `favorite-pay` | `transaction_id`, `gateway_id`, `attempt_id` | Customer checkout polling |
| `favorite.pay.payment.succeeded` | `favorite-pay` | `transaction_id`, `source_plugin`, `source_reference`, `amount_bdt`, `currency_pay`, `settled_at` | **Digital**: grant download/membership.<br>**Shop**: mark order paid & dispatch. |
| `favorite.pay.payment.failed` | `favorite-pay` | `transaction_id`, `source_plugin`, `source_reference`, `reason` | **Digital/Shop**: notify customer to retry. |
| `favorite.pay.payment.refunded` | `favorite-pay` | `transaction_id`, `refund_id`, `amount_bdt`, `source_reference` | **Digital**: revoke license.<br>**Shop**: trigger restock. |
| `favorite.pay.wallet.credited` | `favorite-pay` | `user_id`, `amount_bdt`, `new_balance` | Customer email/SMS notification |
| `favorite.pay.wallet.debited` | `favorite-pay` | `user_id`, `amount_bdt`, `new_balance`, `reference` | Customer receipt dispatch |

---

## 20. Security Architecture & Threat Mitigation

1. **CSRF & Session Defense**: Public checkout APIs require Core `CredentialToken` or session verification.
2. **Signature Verification**: All inbound webhooks (Stripe, Binance) require cryptographic HMAC signature validation prior to parsing JSON payloads. Webhooks with invalid signatures are discarded with HTTP 400.
3. **Idempotency**: All webhook handlers and internal settlement calls require an `Idempotency-Key` or unique transaction hash to prevent duplicate crediting.
4. **Amount Verification**: Never trust the client. The payment driver verifies that the webhook payment amount exactly matches `amount_pay` on record.
5. **Zero Floating Point Math**: Safe integer minor-unit math eliminates rounding exploit vectors.
6. **Masking Sensitive Data**: Passwords, MFS PINs, cardholder data, and webhook bearer tokens are scrubbed from logs via the Core `_SENSITIVE` filter.

---

## 21. Failure Isolation & Fault Tolerance

- **Provider Failure Containment**: If Binance API or bKash gateway is down, the failure is caught within the driver layer. Core CMS, Favorite Digital, and Favorite Shop remain 100% operational.
- **Graceful Fallback**: If an online gateway fails during checkout, the customer is presented with alternative available methods (e.g. Card fallback or Manual MFS).
- **Asynchronous Retry**: Webhook delivery acknowledges receipt with HTTP 200/202, queuing verification tasks to avoid holding HTTP worker threads open.

---

## 22. Admin Management Architecture

The Admin dashboard is registered via Core `application.admin` (`PluginAdmin.register_module`):
- **Payments & Transactions**: Searchable filterable ledger of all transactions with real-time status pills.
- **Manual Payment Approvals**: Dedicated queue for reviewing submitted bKash/Nagad TrxIDs with single-click [Verify] and [Reject] actions.
- **Gateways Configuration**: Toggle individual gateways, configure encrypted credentials, and test sandbox connections.
- **Exchange Rates Table**: Live exchange rate overview with manual override capability and auto-sync scheduler.
- **Wallet Auditing**: User balance lookup, transaction history audit, and manual administrative credit/debit adjustments with mandatory reason logging.

---

## 23. Customer-Facing UI & Presentation Layer

Favorite Pay exposes standardized presentation models for the **Favorite Web Theme**:
1. **Checkout Method Selector**:
   - Clean list of active payment methods with official icons from [`plugin-assets/favorite-pay/icons/`](file:///e:/Favorite-CMS-Assets/plugin-assets/favorite-pay/icons/).
2. **Manual Payment Form Component**:
   - Number copy-to-clipboard widget, step-by-step transaction instructions, and input fields for Sender Phone & TrxID.
3. **Payment Status Modal**:
   - Live polling widget for asynchronous gateway redirects and QR code scan status.
4. **Customer Wallet Dashboard**:
   - Balance overview, transaction history list, and recharge voucher input.

---

## 24. Plugin Lifecycle Management

### Installation
1. Register extension manifest in `plugin.json`.
2. Core `DatabaseMigrationEngine` runs `favorite_pay_*` table creation.
3. Seed default currency exchange rates (BDT = 1.0, USD = 122.50).
4. Register default payment methods (manual bKash, Nagad, Bank).

### Activation
1. Verify database schema is up-to-date.
2. Bind public `engine.pay` service to Core `ServiceContainer`.
3. Register routes with `RoutingEngine` and `APIEngine`.
4. Register event contracts with `EventEngine`.

### Deactivation
1. Unregister routes and API operations.
2. Withdraw public `engine.pay` service.
3. Active consumer plugins (Digital/Shop) receive a graceful `PaymentServiceUnavailable` exception if checkout is attempted.

### Uninstallation (Destructive)
1. Verify no consumer plugins (Digital, Shop) are actively dependent on Pay. If dependent plugins exist, uninstall is blocked.
2. Drop `favorite_pay_*` tables only if the operator explicitly provides `--purge-data` flag.

---

## 25. Database Migration Strategy

- Migrations follow Core `Migrator` standards.
- Stored under `plugins/favorite-pay/database/migrations/`.
- Each migration defines an explicit class with `up()` and `down()` methods receiving `FavoriteCMS\Core\Database`.
- Migrations are executed on plugin activation and via the Core migration runner.
- Normal application startup never executes DDL.

---

## 26. Comprehensive Testing Strategy

| Test Domain | Target | Strategy |
|---|---|---|
| **Unit Tests** | Monetary arithmetic | Test integer minor-unit conversions across odd amounts and fractional rates (e.g. ৳120 to USD cents). Verify zero rounding leakage. |
| **Idempotency** | Webhooks & Settlements | Inject duplicate webhook events with identical TrxIDs. Verify transaction is credited exactly once. |
| **Race Conditions** | Wallet debits | Concurrently simulate multiple debits against a single wallet balance. Verify no overdraft. |
| **Gateway Mocks** | Provider adapters | Mock HTTP responses for Binance, Card, and MFS. Test timeout handling, connection resets, and invalid signature rejection. |
| **Ecosystem Contract** | Digital/Shop Handshake | End-to-end simulation of Digital purchasing flow: `create_intent` → `mock_payment` → `event_dispatch` → `entitlement_granted`. |

---

## 27. Performance Considerations & Shared Hosting Compatibility

1. **Lightweight PHP 8.1+ Footprint**: Strict types, zero external heavy dependencies, native PDO prepared statements.
2. **Indexed Queries**: Every foreign key and lookup column (`transaction_id`, `source_reference`, `user_id`, `status`) is indexed.
3. **Database Connection Reuse**: Leverages Core PDO instance from Application container to minimize connection overhead on shared databases.

---

## 28. Security, Compliance & Data Retention

- **PCI-DSS SAQ A Scope**: By strictly offloading card entry to hosted tokenized providers, the merchant operates under the lowest-burden PCI Self-Assessment Questionnaire (SAQ A).
- **Data Minimization**: Never store full customer card details, passwords, or MFS PINs.
- **Audit Immutability**: Financial transactions and ledger rows are never deleted. Status changes append new timestamped records.
- **Data Retention**: Retain transaction and audit records for a minimum of 7 years in compliance with standard commercial accounting standards.

---

## 29. Phased Implementation Roadmap

To maintain exceptional code quality and prevent architectural regressions, implementation will proceed across 12 focused phases:

```text
Phase 1:  Plugin Manifest & Core Service Contracts
Phase 2:  Database Schema & Core Migrations
Phase 3:  Transaction State Machine & Intent Engine
Phase 4:  Currency Engine, Rate Tables & Conversion Snapshot Locking
Phase 5:  Manual Bangladesh Payment Gateway Driver (bKash, Nagad, Bank)
Phase 6:  Append-Only Wallet System & Customer Balance Ledger
Phase 7:  Credit/Debit Card Gateway Driver (Hosted Tokenized Provider)
Phase 8:  Binance Pay / USDT Crypto Gateway Driver
Phase 9:  Refunds, Reversals & Dispute Management Engine
Phase 10: Admin Management Console & Manual Approval Queue
Phase 11: Public Consumer SDK & Event Handshakes (Digital, Shop, Web)
Phase 12: End-to-End Integration, Idempotency & Concurrency Test Suite
```

---

## 30. Unresolved Architectural Decisions Requiring Operator Approval

The following four specific decisions require explicit operator review and confirmation before code implementation begins:

### Decision Point 1: Wallet Denomination Model
- **Option A (Recommended)**: **BDT-Denominated Wallet**. All wallet balances and ledger entries are stored in BDT Poisha. Deposits in foreign currencies (USD/Crypto) are converted at deposit time and locked.
- **Option B**: **Multi-Currency Wallet**. Maintain separate balance ledgers per currency (one BDT wallet, one USD wallet). Increases complexity significantly.

### Decision Point 2: Exchange Rate Sourcing
- **Option A (Recommended)**: **Hybrid Operator + Free API**. Operator sets base manual rates in Admin, with an optional scheduled job querying a free open rate provider (e.g. Open Exchange Rates / Bangladesh Bank daily rate).
- **Option B**: **Strictly Manual Operator Rates**. Rates only change when an admin manually updates the rate table in the dashboard.

### Decision Point 3: Manual Payment Auto-Verification Threshold
- **Option A (Recommended)**: **100% Manual Operator Approval**. Every bKash/Nagad TrxID submission must be manually cross-checked by an admin on the merchant app/statement before order release.
- **Option B**: **API Auto-Reconciliation**. Direct automated verification via official bKash Merchant Checkout API / Nagad PG API (requires formal corporate merchant contracts).

### Decision Point 4: Technology Stack Alignment
- **Confirmation**: Acknowledge that Favorite Pay is developed as a native **PHP 8.1+** plugin matching the actual Favorite CMS Core codebase (`Favorite-CMS-Universal`), using Composer PSR-4, PDO database abstraction, and the Core PluginManager.

---
*End of Architectural Specification.*

