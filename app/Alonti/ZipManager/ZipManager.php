<?php

declare(strict_types=1);

namespace App\Alonti\ZipManager;

use App\Models\Cafe;
use App\Models\Cart;
use App\Models\Zipcode;

/**
 * Zip Manager Service
 *
 * Core service for geographic delivery area management in the Alonti system:
 * - Zipcode validation and resolution
 * - Delivery area assignment based on geographic location
 * - Cafe matching for delivery and pickup services
 * - Multi-cafe area handling with user selection
 * - State ID determination for tax and pricing calculations
 * - Session-based delivery context management
 * - Geographic proximity calculations (preserved for future use)
 */
class ZipManager
{
    /**
     * User-provided zipcode from input
     *
     * @var string
     */
    public $givenZipCode;

    /**
     * Resolved delivery area with cafe information
     *
     * @var object
     */
    public $alontiDeliveryArea;

    /**
     * Currently selected zipcode record
     *
     * @var Zipcode|null
     */
    public $selectedZipcode;

    /**
     * Initialize ZipManager with session data
     *
     * Loads delivery information from session:
     * - User's given zipcode from input
     * - Resolved delivery area with cafe details
     * - Resets selected zipcode for new operations
     */
    public function __construct()
    {
        $this->givenZipCode = session()->get('UserDeliveryInformation.givenZipCode');
        $this->alontiDeliveryArea = session()->get('UserDeliveryInformation.alontiDeliveryArea');
        $this->selectedZipcode = null;
    }

    /**
     * Set delivery area based on zipcode
     *
     * Primary method for establishing delivery context:
     * - Validates zipcode and finds associated cafes
     * - Handles three scenarios:
     *   1. No cafes found: returns false (no delivery available)
     *   2. Single cafe: automatically sets delivery area in session
     *   3. Multiple cafes: stores options for user selection
     *
     * Updates session with delivery area information and selection status.
     *
     * @param  string  $code  Zipcode to validate and process
     * @return \Illuminate\Support\Collection|false Zipcode records or false if none found
     */
    public function setDeliveryAreaByZip($code)
    {
        $zipcodeRecord = $this->findClosestZipcodeHavingCafe($code);

        // No cafes serve this zipcode
        if (!$zipcodeRecord->count()) {
            return false;
        }

        // Single cafe serves this zipcode - auto-select
        if ($zipcodeRecord->count() == 1) {
            session()->put('UserDeliveryInformation.alontiDeliveryAreaCount', $zipcodeRecord->count());
            session()->put('UserDeliveryInformation.alontiDeliveryArea', $zipcodeRecord[0]);
            session()->put('UserDeliveryInformation.givenZipCode', $code);
            session()->put('UserDeliveryInformation.deliveryAreaChosen', true);

            // Update instance properties
            $this->givenZipCode = session()->get('UserDeliveryInformation.givenZipCode');
            $this->alontiDeliveryArea = session()->get('UserDeliveryInformation.alontiDeliveryArea');

            return $zipcodeRecord;
        }

        // Multiple cafes serve this zipcode - require user selection
        if ($zipcodeRecord->count() > 1) {
            session()->put('UserDeliveryInformation.alontiDeliveryAreaCount', $zipcodeRecord->count());
            session()->put('UserDeliveryInformation.alontiDeliveryAreaList', $zipcodeRecord);
            session()->put('UserDeliveryInformation.deliveryAreaChosen', false);

            return $zipcodeRecord;
        }
    }

    /**
     * Find zipcode records with associated cafes
     *
     * Searches for active zipcode records that have cafe associations:
     * - Truncates zipcode to 5 digits for consistency
     * - Loads cafe relationship for delivery area context
     * - Only returns active zipcodes (status = 1)
     * - Preserves legacy geographic proximity code for future use
     *
     * @param  string  $code  Zipcode to search for
     * @return \Illuminate\Support\Collection Collection of zipcode records with cafes
     */
    public function findClosestZipcodeHavingCafe($code)
    {
        // Ensure consistent 5-digit zipcode format
        $code = substr($code, 0, 5);

        // Query active zipcode records with cafe relationships
        $zipcodeRecord = Zipcode::with('cafe')
            ->where([
                'zipcode' => $code,
                'status' => 1,
            ])
            ->get();

        return $zipcodeRecord;
    }

    /**
     * Select specific zipcode record by ID
     *
     * Used when user selects from multiple available cafes:
     * - Retrieves specific zipcode record with cafe relationship
     * - Updates session with selected delivery area
     * - Marks delivery area as chosen
     * - Updates instance properties with session data
     *
     * Typically called after presenting multiple cafe options to user.
     *
     * @param  int  $id  Zipcode record ID to select
     * @return Zipcode Selected zipcode record with cafe
     */
    public function findCafeWithId($id)
    {
        $zipcodeRecord = Zipcode::with('cafe')
            ->where([
                'id' => $id,
            ])
            ->first();

        // Update session with selected delivery area
        session()->put('UserDeliveryInformation.alontiDeliveryAreaCount', $zipcodeRecord->count());
        session()->put('UserDeliveryInformation.alontiDeliveryArea', $zipcodeRecord);
        session()->put('UserDeliveryInformation.givenZipCode', $zipcodeRecord->zipcode);
        session()->put('UserDeliveryInformation.deliveryAreaChosen', true);

        // Update instance properties
        $this->givenZipCode = session()->get('UserDeliveryInformation.givenZipCode');
        $this->alontiDeliveryArea = session()->get('UserDeliveryInformation.alontiDeliveryArea');

        return $zipcodeRecord;
    }

    /**
     * Find cafe objects (not zipcode records) for a given zipcode
     *
     * Returns actual Cafe models for display purposes:
     * - Useful for showing pickup locations or cafe details
     * - Returns Cafe objects rather than Zipcode records
     * - Queries cafes based on zipcode's associated cafe IDs
     * - Preserves legacy geographic proximity code
     *
     * @param  string  $code  Zipcode to find cafes for
     * @return \Illuminate\Support\Collection Collection of Cafe models
     */
    public function findClosestZipcodeHavingCafes($code)
    {
        // Ensure consistent 5-digit zipcode format
        $code = substr($code, 0, 5);

        // Find cafe IDs associated with this zipcode
        $zipcodeRecord = Zipcode::where([
            'zipcode' => $code,
            'status' => 1,
        ]);
        $cafeNum = $zipcodeRecord->pluck('cafe_id');

        // Return actual Cafe objects
        $zipcodeRecord = Cafe::whereIn('cafenum', $cafeNum)->get();

        return $zipcodeRecord;
    }

    /**
     * Reverse lookup: find zipcode record by cafe number
     *
     * Useful for finding delivery area information when starting from a cafe:
     * - Returns first active zipcode for the given cafe
     * - Includes cafe relationship for complete context
     * - Used in cafe-specific operations
     *
     * @param  string  $cafenum  Cafe number to find zipcode for
     * @return Zipcode|null First zipcode record for the cafe
     */
    public function findZipcodeByCafeNum($cafenum)
    {
        return Zipcode::with('cafe')
            ->where(['cafe_id' => $cafenum, 'status' => 1])
            ->first();
    }

    /**
     * Get user-provided zipcode from session
     *
     * Returns the original zipcode entered by the user.
     *
     * @return string|null User's given zipcode
     */
    public function getUserGivenZipCode()
    {
        return $this->givenZipCode;
    }

    /**
     * Get resolved delivery area object
     *
     * Returns the complete delivery area information including cafe details.
     *
     * @return object|null Delivery area object with cafe information
     */
    public function getAlontiDeliveryArea()
    {
        return $this->alontiDeliveryArea;
    }

    /**
     * Get state ID for pricing and tax calculations
     *
     * Critical method that determines pricing context through multiple fallbacks:
     * 1. Current delivery area state (primary)
     * 2. Invitation cart state (for group order invitees)
     * 3. Default Houston zipcode (77074) as final fallback
     *
     * Ensures that pricing calculations always have a valid state context,
     * even in edge cases where delivery area isn't fully resolved.
     *
     * @return string State ID for pricing calculations
     */
    public function getDeliveryZipcodeStateId()
    {
        $stateId = '';

        // Primary: Use current delivery area state
        if (!empty($this->alontiDeliveryArea)) {
            $stateId = $this->alontiDeliveryArea->state_id;
        }
        // Fallback 1: Use invitation cart state for group order invitees
        elseif (session('invitation.cart_id')) {
            $cart = Cart::where('id', session('invitation.cart_id'))->first();
            $stateId = $cart ? $cart->state_id : '';
            // Note: This line appears to store state_id in givenZipCode session key (potential bug)
            session(['UserDeliveryInformation.givenZipCode' => $stateId]);
        }
        // Fallback 2: Use default Houston zipcode for pricing
        else {
            $stateId = Zipcode::where('zipcode', '77074')->pluck('state_id')->first();
        }

        return $stateId;
    }
}
