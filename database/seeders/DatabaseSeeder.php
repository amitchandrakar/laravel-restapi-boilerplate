<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Seeder order matches migration table creation order (parents before children)
     * so foreign keys are satisfied. FK constraints are disabled during seed for resilience.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        // Allow MySQL zero dates (0000-00-00) during seed; exported data may contain them.
        $originalSqlMode = DB::selectOne('SELECT @@session.sql_mode AS mode')->mode ?? '';
        $seedSqlMode = preg_replace('/NO_ZERO_DATE|NO_ZERO_IN_DATE/', '', $originalSqlMode);
        $seedSqlMode = trim(preg_replace('/,\s*,/', ',', $seedSqlMode), ',');
        DB::statement('SET SESSION sql_mode = ?', [$seedSqlMode]);

        try {
            $this->call(\Database\Seeders\Tables\AbondandCartSeeder::class);
            $this->call(\Database\Seeders\Tables\AclPhinxlogSeeder::class);
            $this->call(\Database\Seeders\Tables\AcosSeeder::class);
            $this->call(\Database\Seeders\Tables\ActiveMenuCategoriesSeeder::class);
            $this->call(\Database\Seeders\Tables\ActiveMenusSeeder::class);
            $this->call(\Database\Seeders\Tables\ActiveNextNearestListsSeeder::class);
            $this->call(\Database\Seeders\Tables\AcumaticaApiLogsSeeder::class);
            $this->call(\Database\Seeders\Tables\AcumaticaDataUploadHistorySeeder::class);
            $this->call(\Database\Seeders\Tables\AdminAccessTracksSeeder::class);
            $this->call(\Database\Seeders\Tables\AlontiLibrariesSeeder::class);
            $this->call(\Database\Seeders\Tables\AlontiUsersSeeder::class);
            $this->call(\Database\Seeders\Tables\AmazonApiLogsSeeder::class);
            $this->call(\Database\Seeders\Tables\AnticipatedelitestatusSeeder::class);
            $this->call(\Database\Seeders\Tables\ApiKeysSeeder::class);
            $this->call(\Database\Seeders\Tables\ApiLogsSeeder::class);
            $this->call(\Database\Seeders\Tables\ArosSeeder::class);
            $this->call(\Database\Seeders\Tables\ArosAcosSeeder::class);
            $this->call(\Database\Seeders\Tables\CafeAccessSeeder::class);
            $this->call(\Database\Seeders\Tables\CafegoalsSeeder::class);
            $this->call(\Database\Seeders\Tables\CafesSeeder::class);
            $this->call(\Database\Seeders\Tables\CalenderSeeder::class);
            $this->call(\Database\Seeders\Tables\CalenderParticipantsSeeder::class);
            $this->call(\Database\Seeders\Tables\CampaignLogSeeder::class);
            $this->call(\Database\Seeders\Tables\CartItemsTracksSeeder::class);
            $this->call(\Database\Seeders\Tables\CartOptionsTracksSeeder::class);
            $this->call(\Database\Seeders\Tables\CartTracksSeeder::class);
            $this->call(\Database\Seeders\Tables\CategoriesSeeder::class);
            $this->call(\Database\Seeders\Tables\CcTablesSeeder::class);
            $this->call(\Database\Seeders\Tables\CimPaidsSeeder::class);
            $this->call(\Database\Seeders\Tables\CimPaymentProfilesSeeder::class);
            $this->call(\Database\Seeders\Tables\CimsSeeder::class);
            $this->call(\Database\Seeders\Tables\CitiesSeeder::class);
            $this->call(\Database\Seeders\Tables\CogListSeeder::class);
            $this->call(\Database\Seeders\Tables\CoglistSeeder::class);
            $this->call(\Database\Seeders\Tables\CompanyGoalsSeeder::class);
            $this->call(\Database\Seeders\Tables\CompanyPaymentSeeder::class);
            $this->call(\Database\Seeders\Tables\CompanyUsersSeeder::class);
            $this->call(\Database\Seeders\Tables\ConfigurationsSeeder::class);
            $this->call(\Database\Seeders\Tables\CountriesSeeder::class);
            $this->call(\Database\Seeders\Tables\CouponCafeSeeder::class);
            $this->call(\Database\Seeders\Tables\CouponsSeeder::class);
            $this->call(\Database\Seeders\Tables\CsmDaSeeder::class);
            $this->call(\Database\Seeders\Tables\CustomerNotesSeeder::class);
            $this->call(\Database\Seeders\Tables\CustomerReferralsSeeder::class);
            $this->call(\Database\Seeders\Tables\CustomermenusSeeder::class);
            $this->call(\Database\Seeders\Tables\DirectorsSeeder::class);
            $this->call(\Database\Seeders\Tables\DisableDatesSeeder::class);
            $this->call(\Database\Seeders\Tables\DistrictAccessSeeder::class);
            $this->call(\Database\Seeders\Tables\DistrictgoalsSeeder::class);
            $this->call(\Database\Seeders\Tables\DistrictsSeeder::class);
            $this->call(\Database\Seeders\Tables\DocumentsSeeder::class);
            $this->call(\Database\Seeders\Tables\EmailBouncesSeeder::class);
            $this->call(\Database\Seeders\Tables\EmailCampaignImagesSeeder::class);
            $this->call(\Database\Seeders\Tables\EmailCampaignsSeeder::class);
            $this->call(\Database\Seeders\Tables\EmailGallariesSeeder::class);
            $this->call(\Database\Seeders\Tables\EmailLogsSeeder::class);
            $this->call(\Database\Seeders\Tables\EmailQueueSeeder::class);
            $this->call(\Database\Seeders\Tables\EmailQueuePhinxlogSeeder::class);
            $this->call(\Database\Seeders\Tables\EmailSentLogsSeeder::class);
            $this->call(\Database\Seeders\Tables\EmailTemplatesSeeder::class);
            $this->call(\Database\Seeders\Tables\EtCogListSeeder::class);
            $this->call(\Database\Seeders\Tables\EtCoglistSeeder::class);
            $this->call(\Database\Seeders\Tables\EtInputSeeder::class);
            $this->call(\Database\Seeders\Tables\EtInputsSeeder::class);
            $this->call(\Database\Seeders\Tables\EtInventoriesSeeder::class);
            $this->call(\Database\Seeders\Tables\EtInventorySeeder::class);
            $this->call(\Database\Seeders\Tables\EtInvoicedetailsSeeder::class);
            $this->call(\Database\Seeders\Tables\EtInvoicesSeeder::class);
            $this->call(\Database\Seeders\Tables\EtInvoicesDetailsSeeder::class);
            $this->call(\Database\Seeders\Tables\EtPaidoutSeeder::class);
            $this->call(\Database\Seeders\Tables\EtPaidoutsSeeder::class);
            $this->call(\Database\Seeders\Tables\ExceptionsSeeder::class);
            $this->call(\Database\Seeders\Tables\ExpensesSeeder::class);
            $this->call(\Database\Seeders\Tables\EzcaterWebhookLogsSeeder::class);
            $this->call(\Database\Seeders\Tables\FollowUpsSeeder::class);
            $this->call(\Database\Seeders\Tables\FoodAvailableStoresSeeder::class);
            $this->call(\Database\Seeders\Tables\GlCodesSeeder::class);
            $this->call(\Database\Seeders\Tables\GoalsSeeder::class);
            $this->call(\Database\Seeders\Tables\GroupOrderConfigurationSeeder::class);
            $this->call(\Database\Seeders\Tables\GroupOrderConfigurationTracksSeeder::class);
            $this->call(\Database\Seeders\Tables\GroupsSeeder::class);
            $this->call(\Database\Seeders\Tables\IndustriesSeeder::class);
            $this->call(\Database\Seeders\Tables\InputsSeeder::class);
            $this->call(\Database\Seeders\Tables\InvoiceDetailsSeeder::class);
            $this->call(\Database\Seeders\Tables\InvoicesSeeder::class);
            $this->call(\Database\Seeders\Tables\ItemOptionsSeeder::class);
            $this->call(\Database\Seeders\Tables\ItemsSeeder::class);
            $this->call(\Database\Seeders\Tables\LaborGoalsSeeder::class);
            $this->call(\Database\Seeders\Tables\LaravelFailedJobsSeeder::class);
            $this->call(\Database\Seeders\Tables\LaravelJobsSeeder::class);
            $this->call(\Database\Seeders\Tables\LaravelSuccessJobsSeeder::class);
            $this->call(\Database\Seeders\Tables\LogChangedemailsSeeder::class);
            $this->call(\Database\Seeders\Tables\LogPageloadtimesSeeder::class);
            $this->call(\Database\Seeders\Tables\MarketAccessSeeder::class);
            $this->call(\Database\Seeders\Tables\MarketsSeeder::class);
            $this->call(\Database\Seeders\Tables\MastermenuPricesSeeder::class);
            $this->call(\Database\Seeders\Tables\MastermenusSeeder::class);
            $this->call(\Database\Seeders\Tables\MenuDownloadSettingsSeeder::class);
            $this->call(\Database\Seeders\Tables\MenuExtraItemsSeeder::class);
            $this->call(\Database\Seeders\Tables\MenuStatePricesSeeder::class);
            $this->call(\Database\Seeders\Tables\MenusSeeder::class);
            $this->call(\Database\Seeders\Tables\MxProspectsSeeder::class);
            $this->call(\Database\Seeders\Tables\NotesSeeder::class);
            $this->call(\Database\Seeders\Tables\OffmenuCreditsSeeder::class);
            $this->call(\Database\Seeders\Tables\OffmenusSeeder::class);
            $this->call(\Database\Seeders\Tables\OjBillingAddressSeeder::class);
            $this->call(\Database\Seeders\Tables\OjCartInviteesSeeder::class);
            $this->call(\Database\Seeders\Tables\OjCartItemsSeeder::class);
            $this->call(\Database\Seeders\Tables\OjCartOptionsSeeder::class);
            $this->call(\Database\Seeders\Tables\OjCartsSeeder::class);
            $this->call(\Database\Seeders\Tables\OjCategoriesSeeder::class);
            $this->call(\Database\Seeders\Tables\OjDietariesSeeder::class);
            $this->call(\Database\Seeders\Tables\OjGroupOrdersSeeder::class);
            $this->call(\Database\Seeders\Tables\OjImagesSeeder::class);
            $this->call(\Database\Seeders\Tables\OjInviteeReponsesSeeder::class);
            $this->call(\Database\Seeders\Tables\OjInviteesSeeder::class);
            $this->call(\Database\Seeders\Tables\OjMenuMapsSeeder::class);
            $this->call(\Database\Seeders\Tables\OjPackageSizesSeeder::class);
            $this->call(\Database\Seeders\Tables\OjProductAddOnsSeeder::class);
            $this->call(\Database\Seeders\Tables\OjProductDietariesSeeder::class);
            $this->call(\Database\Seeders\Tables\OjProductOptionSelectionsSeeder::class);
            $this->call(\Database\Seeders\Tables\OjProductOptionsSeeder::class);
            $this->call(\Database\Seeders\Tables\OjProductSelectionDietariesSeeder::class);
            $this->call(\Database\Seeders\Tables\OjProductSelectionsSeeder::class);
            $this->call(\Database\Seeders\Tables\OjProductVariantsSeeder::class);
            $this->call(\Database\Seeders\Tables\OjProductsSeeder::class);
            $this->call(\Database\Seeders\Tables\OjShippingAddressSeeder::class);
            $this->call(\Database\Seeders\Tables\OjStatesPricesSeeder::class);
            $this->call(\Database\Seeders\Tables\OjUniqueUrlsSeeder::class);
            $this->call(\Database\Seeders\Tables\OptionsSeeder::class);
            $this->call(\Database\Seeders\Tables\OrderTracksSeeder::class);
            $this->call(\Database\Seeders\Tables\OrdersSeeder::class);
            $this->call(\Database\Seeders\Tables\OrdersBackSeeder::class);
            $this->call(\Database\Seeders\Tables\PaidTracksSeeder::class);
            $this->call(\Database\Seeders\Tables\PaidoutsSeeder::class);
            $this->call(\Database\Seeders\Tables\PaymentsSeeder::class);
            $this->call(\Database\Seeders\Tables\PaytraceApiLogsSeeder::class);
            $this->call(\Database\Seeders\Tables\PaytraceSettingsSeeder::class);
            $this->call(\Database\Seeders\Tables\PermissionsSeeder::class);
            $this->call(\Database\Seeders\Tables\PhinxlogSeeder::class);
            $this->call(\Database\Seeders\Tables\PrepCategoriesSeeder::class);
            $this->call(\Database\Seeders\Tables\PrepItemStationSeeder::class);
            $this->call(\Database\Seeders\Tables\PrepItemsSeeder::class);
            $this->call(\Database\Seeders\Tables\PrepPackageDealIndexesSeeder::class);
            $this->call(\Database\Seeders\Tables\PrepProductOptionSelectionPrioritiesSeeder::class);
            $this->call(\Database\Seeders\Tables\PrepSheetMappingsSeeder::class);
            $this->call(\Database\Seeders\Tables\PrepSheetsSeeder::class);
            $this->call(\Database\Seeders\Tables\PrepStationsSeeder::class);
            $this->call(\Database\Seeders\Tables\PromotionProductSelectionsSeeder::class);
            $this->call(\Database\Seeders\Tables\PromotionTypeSeeder::class);
            $this->call(\Database\Seeders\Tables\PromotionTypeMenuSeeder::class);
            $this->call(\Database\Seeders\Tables\PromotionTypeProductSeeder::class);
            $this->call(\Database\Seeders\Tables\ProspectLogsSeeder::class);
            $this->call(\Database\Seeders\Tables\ReferralSalesAreasSeeder::class);
            $this->call(\Database\Seeders\Tables\RewardsSeeder::class);
            $this->call(\Database\Seeders\Tables\RewardsToAmazonSeeder::class);
            $this->call(\Database\Seeders\Tables\SalesAreasSeeder::class);
            $this->call(\Database\Seeders\Tables\ScheduleSeeder::class);
            $this->call(\Database\Seeders\Tables\SelectionsSeeder::class);
            $this->call(\Database\Seeders\Tables\ServingOptionsSeeder::class);
            $this->call(\Database\Seeders\Tables\SettingsSeeder::class);
            $this->call(\Database\Seeders\Tables\ShoppingCartsSeeder::class);
            $this->call(\Database\Seeders\Tables\SocialSignupsSeeder::class);
            $this->call(\Database\Seeders\Tables\SpecialOffersSeeder::class);
            $this->call(\Database\Seeders\Tables\SpecialOffersTestSeeder::class);
            $this->call(\Database\Seeders\Tables\StatesSeeder::class);
            $this->call(\Database\Seeders\Tables\SubcategoriesSeeder::class);
            $this->call(\Database\Seeders\Tables\TaxRatesSeeder::class);
            $this->call(\Database\Seeders\Tables\TbFixemailSeeder::class);
            $this->call(\Database\Seeders\Tables\TimesSeeder::class);
            $this->call(\Database\Seeders\Tables\TodoListSeeder::class);
            $this->call(\Database\Seeders\Tables\TrainsSeeder::class);
            $this->call(\Database\Seeders\Tables\UnsubscribesSeeder::class);
            $this->call(\Database\Seeders\Tables\UserConfigurationsSeeder::class);
            $this->call(\Database\Seeders\Tables\UserCreditCardAddressSeeder::class);
            $this->call(\Database\Seeders\Tables\UserSegmentationsSeeder::class);
            $this->call(\Database\Seeders\Tables\UserTracksSeeder::class);
            $this->call(\Database\Seeders\Tables\VendorsSeeder::class);
            $this->call(\Database\Seeders\Tables\WebsiteBannerSettingsSeeder::class);
            $this->call(\Database\Seeders\Tables\ZipCodeLatLonSeeder::class);
            $this->call(\Database\Seeders\Tables\ZipCodesSeeder::class);
            $this->call(\Database\Seeders\Tables\ZipcodelatlonSeeder::class);
        } finally {
            if ($originalSqlMode !== '') {
                DB::statement('SET SESSION sql_mode = ?', [$originalSqlMode]);
            }
            Schema::enableForeignKeyConstraints();
        }
    }
}
