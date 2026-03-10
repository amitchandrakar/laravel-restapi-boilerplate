# Legacy Components: Role & Laravel Refactor Guide

This document describes what each pasted component does and how to replace it with standard Laravel patterns so the codebase can be simplified and maintained properly.

---

## 1. App\Alonti\Support\EncryptIdentity (trait)

**Role:** Obfuscates model primary keys for use in URLs (encode/decode IDs so they are not guessable).

- `getEncryptedIdAttribute()` – accessor: `$model->encrypted_id` returns a hashed/encoded version of `id`.
- `findByEncryptedId($encrypted_id)` – find a model by decoded ID.
- `decryptId($encrypted_id)` – returns the raw ID.

**Dependency:** Expects `app('hashid')` (likely Hashids or similar package bound in a service provider).

**Laravel refactor:**

- Use **Laravel’s built-in signed URLs** (`URL::signedRoute`, `signedRoute`, `validateSignature`) for one-off secure links, or
- Use a single **custom trait** that uses a registered binding (e.g. `Hashids::class` or a small `IdHasher` service) so the trait stays thin and testable.
- Optionally use **route model binding with a custom key** (e.g. hashed key in DB or a dedicated “public_id” column) instead of encoding the numeric ID.

**Target:** One small trait or one small service + optional signed URLs; remove dependency on a global `hashid` binding from models.

---

## 2. App\Alonti\Presenters\UserPresenter

**Role:** Prepares **User** data for UI (e.g. select boxes). Currently: builds a list of group orders for a dropdown (`groupOrderListForSelectBox`).

**Laravel refactor:**

- Move to **Resource classes** (e.g. `UserResource`, `GroupOrderSelectResource`) for API responses.
- For Blade/HTML select data, use **view composers**, **controller methods** that return JSON, or a **Form Request / DTO** that returns `['value' => id, 'text' => name]`.
- If logic grows, use a **small service** (e.g. `GroupOrderSelectService`) or a **query scope + resource** instead of a “Presenter” object that holds a User.

**Target:** No Presenter class; use Resources, view composers, or small services + resources.

---

## 3. App\Alonti\Cart\CartManager

**Role:** **Service class** for all cart operations:

- Get active cart (session vs invitee context).
- Cart item count, validation, availability (product/variant/option, state-based pricing).
- Add-on and option handling, “warm cookie” logic, tip helpers.

**Laravel refactor:**

- Keep as a **single service** (e.g. `App\Services\Cart\CartManager` or `CartService`), but:
    - Inject dependencies (e.g. `InvitationManager`, `Auth`, session) via constructor.
    - Prefer **Laravel’s Auth** and **session** facades/contracts instead of global helpers.
- Optionally split into smaller services (e.g. `CartAvailabilityService`, `CartPricingService`) if the class stays large.
- Use **Form Requests** for validation and **Actions/Jobs** for side effects (e.g. “add to cart”, “recalculate”) so the service stays focused.

**Target:** One (or a few) well-injected services under `App\Services\Cart\`; no “Alonti” namespace for new code.

---

## 4. App\Alonti\Coupon\UpdateCoupon

**Role:** **Service** for coupon-related updates on the cart:

- Calculate/update/delete item discounts.
- Handle “free product” and “warm cookie” cases.
- Update discount when zipcode changes.

**Laravel refactor:**

- Move to `App\Services\Coupon\UpdateCouponService` (or similar).
- Use **value objects** for “discount result” or “coupon application result” if needed.
- Call from **controllers** or **actions**, not from model methods; keep models free of coupon calculation logic.

**Target:** Service(s) under `App\Services\Coupon\`; coupon logic out of models.

---

## 5. App\Alonti\Order\ReOrder

**Role:** **Service** that creates a new cart from an existing order (re-order flow): creates cart (and optionally cart invitees), copies items from the order.

**Laravel refactor:**

- Rename/move to `App\Services\Order\ReOrderService` (or `CreateCartFromOrderService`).
- Use **DTOs** or **plain arrays** for “reorder request” and “reorder result” to keep the service testable.
- Trigger from a **controller** or **action**; avoid being called from inside Order model.

**Target:** One clear service under `App\Services\Order\`.

---

## 6. App\Alonti\Order\OrderPlacement

**Role:** **Service** that runs the full “place order” flow:

- Validates cart, creates Order, updates cart and rewards, handles payment (Paytrace, etc.), shipping, notifications.

**Laravel refactor:**

- Keep as the main **orchestrator** for “place order”, but move to `App\Services\Order\OrderPlacementService`.
- Delegate to:
    - **Jobs** (e.g. send confirmation email, update inventory).
    - **Small services** (payment, shipping, reward updates).
    - **Events + Listeners** (e.g. `OrderPlaced` → send mail, log, notify).
- Use **DB transactions** in one place (e.g. in the service or a dedicated action) and keep the flow readable.

**Target:** One high-level service + events/listeners + jobs; payment/shipping in dedicated services.

---

## 7. App\Mailer\* (UserMailer, OrderMailer, CartMailer, CartInviteeMailer, ProspectMailer)

**Role:** **Wrapper classes** that hold a model (User, Order, Cart, CartInvitee, MxProspect) and send specific emails via Laravel’s `Mail` facade and custom Mailable classes.

- **UserMailer:** welcome, password reset, CSM welcome, tax exempt, referral, unsubscribe, etc.
- **OrderMailer:** order confirmation, first order, void, modified, canceled.
- **CartMailer:** group order invitations, reminders.
- **CartInviteeMailer:** invitee decline, order completion.
- **ProspectMailer:** prospect subscribe/unsubscribe.

**Laravel refactor:**

- Prefer **Mailable classes + notifications** instead of Mailer classes:
    - One **Mailable** per email type (e.g. `WelcomeEmail`, `OrderConfirmation`).
    - Send from **controllers**, **listeners**, or **jobs** by calling `Mail::to(...)->send(new SomeMailable($model))`.
- For “notify when X happens”, use **Laravel Notifications** (`Notification::send`) or **event listeners** that send mail (e.g. `OrderPlaced` → `SendOrderConfirmation` listener).
- Move any “build recipient list” logic (e.g. CSM, directors) into the Mailable or a small **recipient resolver**; avoid large methods in Mailer classes.

**Target:** No Mailer classes; use Mailables + event listeners (or notifications) + optional small “recipient” helpers.

---

## 8. App\Models\Traits\Scope\CustomScope

**Role:** **Query scopes** used on several models:

- `scopeActive` – `status = 1`.
- `scopeDisplayStatus` – `display_status = 1`.
- `scopeParent` – `parent_id` null.
- `scopeWarmCookieCategory` – name like ‘%cookie%’.
- `scopeAvailableInStore` – filters by current cart/session cafe and `available_all_store` / `availableStore` relation.

**Laravel refactor:**

- Keep **scopes** on the models that need them; they are already a good Laravel pattern.
- Move the trait to a neutral namespace if you drop “Alonti” (e.g. keep under `App\Models\Traits\Scope`).
- **Important:** `scopeAvailableInStore` depends on **CartManager** and **session**. Prefer:
    - Passing **cafe_id** (or context) as a parameter into the scope, or
    - A **scope that accepts a cafe id** and using it from the controller/service so models don’t depend on session/cart.

**Target:** Scopes stay; reduce coupling to session/cart by passing context (e.g. cafe_id) where possible.

---

## 9. App\Models\Traits\Attribute\* (Category, Image, ProductSelection, ProductOption, Dietary, ProductVariant)

**Role:** **Accessors** (and one mutator) for presentation/formatting:

- **CategoryAttribute:** `url` – builds category URL (invitation vs normal).
- **ImageAttribute:** `image_path`, `medium_image_path`, `large_image_path`, `small_image_path`, `product_image_path`, `variant_image_path` – paths/URLs for image sizes (including S3-style URLs).
- **ProductSelectionAttribute:** `selection_name`, `name` (mutator/accessor) – sentence case and price suffix.
- **ProductOptionAttribute:** `error_message` – “Please choose …” message.
- **DietaryAttribute:** `dietary_name` – name + “Option Included” when pivot type is 2.
- **ProductVariantAttribute:** `package_name`, `tooltip`, `package_option` – HTML and display strings.

**Laravel refactor:**

- **Accessors/mutators** are a good fit; keep them but consider:
    - **API:** Prefer **Resource classes** (e.g. `CategoryResource`, `ImageResource`) to build the response array (including URLs and formatted fields) so the API doesn’t depend on model accessors that might be tuned for the web app.
    - **Web:** Keep traits for Blade/legacy; for new code, you can still use accessors or move “presentation” to Resources/ViewModels.
- **Image paths:** Prefer **config** (or env) for base URLs (e.g. `config('filesystems.disks.s3.url')`) and optionally **Laravel’s storage URL generation** so S3 URLs aren’t hardcoded in a trait.
- **sentenceCase:** Ensure it exists as a **helper** (e.g. in `bootstrap/helpers.php` or a small package); the trait depends on it.

**Target:** Keep traits for domain accessors; use Resources for API shape; move URLs/config out of traits into config and storage.

---

## 10. Summary: Where things should live after refactor

| Current                             | Role                        | Laravel-oriented target                                     |
| ----------------------------------- | --------------------------- | ----------------------------------------------------------- |
| **Alonti\Support\EncryptIdentity**  | Encode/decode IDs for URLs  | Small trait or IdHasher service; or signed URLs / public_id |
| **Alonti\Presenters\UserPresenter** | User/group data for selects | Resources, view composers, or small service                 |
| **Alonti\Cart\CartManager**         | Cart operations             | `App\Services\Cart\CartManager` (injected)                  |
| **Alonti\Coupon\UpdateCoupon**      | Coupon discount updates     | `App\Services\Coupon\*`                                     |
| **Alonti\Order\ReOrder**            | Re-order flow               | `App\Services\Order\ReOrderService`                         |
| **Alonti\Order\OrderPlacement**     | Place order flow            | `App\Services\Order\OrderPlacementService` + events/jobs    |
| **Mailer\***                        | Send emails for a model     | Mailables + event listeners / notifications                 |
| **Models\Traits\Scope\CustomScope** | Query scopes                | Keep; reduce session/cart coupling                          |
| **Models\Traits\Attribute\***       | Accessors for display       | Keep; API via Resources; URLs in config/storage             |

---

## 11. Extra dependencies to be aware of

- **EncryptIdentity:** `app('hashid')` – ensure a Hashids (or similar) provider is registered.
- **CustomScope:** `App\Alonti\Cart\CartManager` and session – refactor to pass cafe/context where possible.
- **Attribute traits:** `sentenceCase()` helper – add to `bootstrap/helpers.php` if missing.
- **Mailers:** Depend on many `App\Mail\*` Mailable classes and sometimes `storeEmailSentLogs()` – ensure those exist or are recreated as Mailables/listeners.
- **OrderPlacement / Cart / Coupon:** Depend on Payment (Paytrace, Authorizenet), InvitationManager, and other Alonti services – refactor in small steps and keep a single place for “place order” and “apply coupon”.

---

## 12. Suggested refactor order

1. **Document and stabilize** – You’re here (this doc).
2. **Move Alonti namespaced services** – Rename/move to `App\Services\*` and inject dependencies; keep behavior the same.
3. **Replace Mailers** – Introduce Mailables and event listeners; migrate one Mailer at a time.
4. **Decouple CustomScope** – Pass cafe_id (or context) from controller/service into scopes.
5. **EncryptIdentity** – Replace with a small service or Laravel signed URLs if you don’t need hashids.
6. **Presenters** – Replace with Resources / view composers / small services.
7. **Attribute traits** – Keep; move S3/base URLs to config and use Resources for API.

This order keeps behavior intact while moving toward a cleaner, Laravel-idiomatic structure so you can eventually remove or shrink the legacy “Alonti” and “Mailer” layers.
