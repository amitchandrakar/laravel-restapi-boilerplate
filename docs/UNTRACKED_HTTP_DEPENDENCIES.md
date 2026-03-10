# Untracked HTTP Files – Missing Dependencies

Use this list to copy-paste missing files from your old project so the pasted **controllers, middlewares, and requests** work.

---

## 1. Missing classes (copy from old project)

| Missing class                       | Used in                            | Notes                                                |
| ----------------------------------- | ---------------------------------- | ---------------------------------------------------- |
| `App\Models\CustomerReferralReward` | `Api/V1/Api/UserController.php`    | Model (e.g. `customer_referral_rewards` table).      |
| `App\Events\EzCaterOrderPlaced`     | `Api/V1/Api/EzCaterController.php` | Event class.                                         |
| `App\Events\EzCaterOrderUpdated`    | `Api/V1/Api/EzCaterController.php` | Event class.                                         |
| `App\Mail\ApplyHouseAccount`        | `Api/V1/Auth/UserController.php`   | Mailable for house account application.              |
| `App\Traits\SocialAuthSettings`     | `Api/V1/Auth/LoginController.php`  | Trait for social auth config (e.g. Google/Facebook). |

---

## 2. Laravel auth traits (removed in Laravel 11)

These are **not** in Laravel 11’s core. Either copy from an old Laravel 8/9/10 app or use a package (e.g. `laravel/ui`, `laravel/breeze`, `laravel/fortify`).

| Trait                                                 | Used in                             |
| ----------------------------------------------------- | ----------------------------------- |
| `Illuminate\Foundation\Auth\AuthenticatesUsers`       | `Auth/LoginController.php`          |
| `Illuminate\Foundation\Auth\RegistersUsers`           | `Auth/RegisterController.php`       |
| `Illuminate\Foundation\Auth\SendsPasswordResetEmails` | `Auth/ForgotPasswordController.php` |
| `Illuminate\Foundation\Auth\ResetsPasswords`          | `Auth/ResetPasswordController.php`  |

---

## 3. Optional: database / config

- **EzCaterController** uses table `ezcater_webhook_logs`. Add a migration for it if you use the ezCater flow.
- **Social auth**: If you use `SocialAuthSettings` and Socialite, ensure `config/services.php` (and any `.env`) has the right keys (e.g. Google, Facebook).

---

## 4. Import fixes applied in this repo

These were fixed so the pasted code points at existing classes:

- **Api/CartController.php**: `App\Http\Requests\CouponRequest` → `App\Http\Requests\Api\V1\CouponRequest`
- **V1/CartController.php**: `App\Http\Requests\CartAddRequest` / `CartUpdateRequest` → `App\Http\Requests\Api\V1\CartAddRequest` / `CartUpdateRequest`
- **ProductController.php**: `use DB;` → `use Illuminate\Support\Facades\DB;`

---

## 5. Duplicate / cleanup

- **`Controller copy.php`** under `Api/V1/`: duplicate of Controller; safe to delete after confirming nothing references it.
