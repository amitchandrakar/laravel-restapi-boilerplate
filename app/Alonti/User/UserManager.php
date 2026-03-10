<?php

declare(strict_types=1);


namespace App\Alonti\User;

use App\Models\Cim;
use App\Models\CimPaymentProfile;
use App\Models\CompanyPayment;
use App\Models\CompanyUser;
use App\Models\MxProspect;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Zipcode;
use Illuminate\Support\Facades\Auth;

/**
 * User Manager Service
 *
 * Core service class for user-related operations in the Alonti system:
 * - Payment method management for users and companies
 * - Recent delivery address retrieval and validation
 * - CIM payment profile management
 * - Company user creation and management
 * - Email prospect validation
 * - Integration between user accounts and company payment systems
 */
class UserManager
{
    /**
     * Get available payment methods for user
     *
     * Retrieves payment methods based on user context:
     * - For authenticated users: company-specific payments + user's payment + defaults
     * - For guest users: only default visible payments
     * - Merges company payments with system defaults
     * - Removes duplicates and maintains unique payment options
     *
     * @return \Illuminate\Database\Eloquent\Collection Payment methods available
     */
    public static function getUserPayment()
    {
        // Get default payments visible to all users
        $defaultPaymentIds = Payment::where(['default_payment' => 1, 'visibility' => 0])
            ->pluck('id')
            ->toArray();

        if (Auth::user()) {
            // Get company-specific payments for authenticated users
            $companyPaymentIds = CompanyPayment::where(['company_id' => Auth::user()->company_user_id])
                ->pluck('payment_id')
                ->unique()
                ->toArray();

            // Add user's personal payment method
            array_push($companyPaymentIds, Auth::user()->payment_id);
            $companyPaymentIds = array_unique($companyPaymentIds);

            // Merge with default payments to ensure all options are available
            foreach ($defaultPaymentIds as $key => $value) {
                if (!in_array($value, $companyPaymentIds)) {
                    $companyPaymentIds[] = $value;
                }
            }

            $payments = Payment::whereIn('id', $companyPaymentIds)->select('id', 'terms')->get();
        } else {
            // Guest users only get default payments
            $payments = Payment::whereIn('id', $defaultPaymentIds)->select('id', 'terms')->get();
        }

        return $payments;
    }

    /**
     * Get user's recent validated delivery addresses
     *
     * Retrieves and validates recent delivery addresses for authenticated users:
     * - Fetches addresses from user's order history
     * - Groups by delivery address to avoid duplicates
     * - Validates zipcodes against active zipcode database
     * - Filters out addresses with invalid zipcodes
     * - Returns unique addresses ordered by most recent
     *
     * @return \Illuminate\Support\Collection Validated recent delivery addresses
     */
    public static function getRecentDeliveryAddress()
    {
        if (Auth::user()) {
            // Get recent addresses from user's order history
            $recentAddress = Order::where(['alonti_user_id' => Auth::user()->id])
                ->existAddress() // Scope to filter orders with addresses
                ->orderBy('id', 'desc')
                ->select(['id', 'd_addr', 'second_address', 'zipcode', 'state', 'deliveryCity'])
                ->groupBy('d_addr')
                ->get();

            // Ensure unique addresses
            $recentAddress = $recentAddress->unique('d_addr');

            // Validate addresses against zipcode database
            $address = $recentAddress
                ->filter(function (Order $order) {
                    // TODO: Clean up address formatting
                    // $order->d_addr = trim(str_ireplace(", ,",",",$order->d_addr));

                    if ($order->zipcode && $order->d_addr) {
                        $zipcode = $order->zipcode;
                        $zipcode_valid = Zipcode::where(['zipcode' => $zipcode, 'status' => 1])->first();
                        if ($zipcode_valid) {
                            return $order;
                        }
                    }
                })
                ->values();

            return $address;
        }

        return collect([]);
    }

    /**
     * Get user's recent CIM payment profiles
     *
     * Retrieves Customer Information Manager (CIM) payment profiles for user:
     * - Fetches CIM records for the authenticated user
     * - Returns associated payment profiles for stored payment methods
     * - Used for displaying saved payment methods during checkout
     *
     * NOTE: Currently contains hardcoded user ID (186634) for testing purposes.
     * This should be updated to use Auth::user()->id in production.
     *
     * @return \Illuminate\Support\Collection CIM payment profiles
     */
    public static function getUserRecentPaymentProfile()
    {
        if (Auth::user()) {
            // TODO: Remove hardcoded user ID - currently using test account
            // 117432 id - 186634 user id, ghenry@ipsi.utexas.edu - cim table record
            $cimsDetails = Cim::where(['alonti_user_id' => 186634])->get();
            // $cimsDetails = Cim::where(['alonti_user_id'=>Auth::user()->id])->get();

            if (!empty($cimsDetails)) {
                return CimPaymentProfile::whereIn('profile_id', $cimsDetails->pluck('profile_id'))->get();
            }
        }

        return collect([]);
    }

    /**
     * Check if email exists in prospect database
     *
     * Searches the MxProspect table for existing email addresses:
     * - Uses LIKE operator for partial matching
     * - Returns first matching prospect record
     * - Used for email validation and duplicate prevention
     *
     * @param  string  $mail  Email address to search for
     * @return MxProspect|null First matching prospect or null
     */
    public function isEmailExistInProspect($mail)
    {
        return MxProspect::where('email', 'like', "%{$mail}%")->first();
    }

    /**
     * Get or create company user record
     *
     * Manages company user accounts in the system:
     * - Searches for existing company by name
     * - Creates new company user if none exists
     * - Associates default payment methods with new companies
     * - Optionally links company to specific cafe
     * - Sets up CompanyPayment relationships for payment processing
     *
     * @param  string  $company  Company name
     * @param  int|null  $cafe_id  Optional cafe ID to associate
     * @return CompanyUser|array Company user record or empty array
     */
    public function getCompanyUser($company, $cafe_id)
    {
        $companyUser = [];
        collect($companyUser); // This line appears to be unused

        if ($company != '') {
            // Try to find existing company user
            $companyUser = CompanyUser::where('name', $company)->first();

            if (!$companyUser) {
                // Create new company user if none exists
                $payments = Payment::where(['default_payment' => 1])->get();

                if ($cafe_id) {
                    $companyUser = CompanyUser::create([
                        'name' => $company,
                        'cafe_id' => $cafe_id,
                    ]);
                } else {
                    $companyUser = CompanyUser::create([
                        'name' => $company,
                    ]);
                }

                // Associate all default payments with the new company
                $payments->each(function ($payment) use ($companyUser) {
                    CompanyPayment::create([
                        'company_id' => $companyUser->id,
                        'payment_id' => $payment->id,
                    ]);
                });
            }
        }

        return $companyUser;
    }
}
