# Model Dependencies – Copy from Old Project

## Package install & bindings (done)

- **hashids/hashids** ^4.0 – installed; bound as `app('hashid')` in `AppServiceProvider`; config: `config/hashids.php`.
- **kamerk22/amazongiftcode** ^1.0 – installed; used in `App\Alonti\Amazon\AmazonService`.
- **jenssegers/agent** ^2.6 – installed (e.g. for device/browser detection).
- **league/flysystem-aws-s3-v3** ^3.0 – installed (for S3 disk when using Laravel filesystem).

Helpers added in `bootstrap/helpers.php`: `sentenceCase()`, `storeEmailSentLogs()`.  
`EmailSentLog` model has `$fillable` for `storeEmailSentLogs()`.  
Fixed: `App\Alonti\Order\StorePayment` now uses `Illuminate\Support\Facades\Log` instead of `Log`.

### Still missing (copy or create)

- **App\Traits\PaytraceTrait** – used by `App\Alonti\Payment\Paytrace\PaytraceManager`. Copy from old project or create stub.
- **App\Mail\*** – Mailers reference many Mailable classes. Copy from old project or create as needed:
    - `InviteeDecline`, `InviteeOrderCompletion`, `CsmGroupOrderNotificationEmail`, `GroupOrderInvitation`, `RemindInvitationInvitation`
    - `ProspectSubscribeEmail`, `ProspectUnsubscribeEmail`
    - `CsmWelcomeEmail`, `FirstOrderNotification`, `OrderCanceled`, `OrderConfirmation`, `OrderModified`, `VoidConfirmation`
    - `CustomerReferralEmailToCsm`, `PasswordResetEmail`, `TaxExemptEmail`, `UserAmazonGiftCardEmail`, `UserReferralEmail`, `UserSubscribeEmail`, `UserUnsubscribeEmail`, `WelcomeEmail`
- **Authorize.net SDK** – `App\Alonti\Payment\Drivers\AuthorizenetDriver` uses `net\authorize\api\*`. Add `authorizenet/authorizenet` (or the package you used) via Composer if you use that driver.

---

# Model Dependencies – Copy from Old Project (original section)

Models in `app/Models` that depend on **non-Laravel, non-Model** classes are listed below. Copy the corresponding files from your old Laravel project into this one.

---

## 1. App\Alonti\* (custom app layer)

Copy the whole **`App\Alonti`** namespace (or at least these classes and their own dependencies):

| Class                                 | Used by model(s)                                          |
| ------------------------------------- | --------------------------------------------------------- |
| `App\Alonti\Support\EncryptIdentity`  | GroupOrder, User copy, CartInvitee, Order, Cart, CartItem |
| `App\Alonti\Presenters\UserPresenter` | User copy                                                 |
| `App\Alonti\Cart\CartManager`         | Offmenu, CartItem                                         |
| `App\Alonti\Coupon\UpdateCoupon`      | Cart, CartItem                                            |
| `App\Alonti\Order\ReOrder`            | Order                                                     |
| `App\Alonti\Order\OrderPlacement`     | Cart                                                      |

**Suggested path in this project:** `app/Alonti/Support/`, `app/Alonti/Presenters/`, `app/Alonti/Cart/`, `app/Alonti/Coupon/`, `app/Alonti/Order/`.

---

## 2. App\Mailer\* (mailer / notification helpers)

Copy the whole **`App\Mailer`** namespace:

| Class                          | Used by model(s) |
| ------------------------------ | ---------------- |
| `App\Mailer\UserMailer`        | User copy        |
| `App\Mailer\CartInviteeMailer` | CartInvitee      |
| `App\Mailer\OrderMailer`       | Order            |
| `App\Mailer\CartMailer`        | Cart             |
| `App\Mailer\ProspectMailer`    | MxProspect       |

**Suggested path in this project:** `app/Mailer/` (e.g. `UserMailer.php`, `CartInviteeMailer.php`, etc.).

---

## 3. App\Models\Traits\* (model traits)

Copy the **`App\Models\Traits`** directory from the old project. This project only has `app/Traits/ApiResponse.php`; it does **not** have `app/Models/Traits/`.

| Trait                                                   | Used by model(s)                                                                                       |
| ------------------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| `App\Models\Traits\Scope\CustomScope`                   | ProductSelection, PackageSize, ProductOption, Dietary, ProductAddOn, ProductVariant, Category, Product |
| `App\Models\Traits\Attribute\ProductSelectionAttribute` | ProductSelection                                                                                       |
| `App\Models\Traits\Attribute\ProductOptionAttribute`    | ProductOption                                                                                          |
| `App\Models\Traits\Attribute\ImageAttribute`            | Image                                                                                                  |
| `App\Models\Traits\Attribute\DietaryAttribute`          | Dietary                                                                                                |
| `App\Models\Traits\Attribute\ProductVariantAttribute`   | ProductVariant                                                                                         |
| `App\Models\Traits\Attribute\CategoryAttribute`         | Category                                                                                               |

**Suggested path in this project:**  
`app/Models/Traits/Scope/CustomScope.php`  
`app/Models/Traits/Attribute/ProductSelectionAttribute.php`, `ProductOptionAttribute.php`, `ImageAttribute.php`, `DietaryAttribute.php`, `ProductVariantAttribute.php`, `CategoryAttribute.php`

---

## 4. Laravel / vendor (no copy needed)

These are from Laravel or packages; ensure the packages are in `composer.json` and run `composer install`:

- `Illuminate\Database\Eloquent\SoftDeletes` – Laravel
- `Illuminate\Notifications\Notifiable` – Laravel
- `Illuminate\Auth\Authenticatable` / `Illuminate\Contracts\Auth\Authenticatable` – Laravel
- `Carbon\Carbon` – Laravel/carbon
- `Illuminate\Support\Facades\*` (Cache, DB, Auth, Log) – Laravel

---

## 5. Model‑to‑model references

All relationship targets (e.g. `User::class`, `Cafe::class`, `Product::class`) are other models in `App\Models`. No extra copy is needed for those as long as all model files are present.

---

## 6. Summary checklist (copy from old project)

- [ ] **app/Alonti/** – Support (EncryptIdentity), Presenters (UserPresenter), Cart (CartManager), Coupon (UpdateCoupon), Order (ReOrder, OrderPlacement)
- [ ] **app/Mailer/** – UserMailer, CartInviteeMailer, OrderMailer, CartMailer, ProspectMailer
- [ ] **app/Models/Traits/Scope/** – CustomScope
- [ ] **app/Models/Traits/Attribute/** – ProductSelectionAttribute, ProductOptionAttribute, ImageAttribute, DietaryAttribute, ProductVariantAttribute, CategoryAttribute

---

## 7. Note on `User copy.php`

`User copy.php` is the old User model (Alonti/Mailer/Presenter). The current `User.php` is the new one (Sanctum, HasRoles, Authenticatable). Keep one and remove or rename the other to avoid two `User` classes.

---

## 8. Note on `Exception` model

`app/Models/Exception.php` extends BaseModel but shares the name with PHP’s `Exception`. Any `catch (Exception $e)` or `use Exception` will refer to PHP’s class unless you use `\App\Models\Exception` explicitly. Consider renaming to e.g. `AppException` or `LoggedException` if it causes confusion.
