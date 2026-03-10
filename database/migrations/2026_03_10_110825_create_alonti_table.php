<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('abondand_cart', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('session_id')->nullable();
            $table->integer('cart_id')->nullable();
            $table->integer('alonti_user_id')->nullable();
            $table->integer('cafe_id')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('acl_phinxlog', function (Blueprint $table) {
            $table->bigInteger('version')->primary();
            $table->string('migration_name', 100)->nullable();
            $table->timestamp('start_time')->useCurrent();
            $table->timestamp('end_time')->useCurrent();
        });

        Schema::create('acos', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('parent_id')->nullable();
            $table->string('model')->nullable();
            $table->integer('foreign_key')->nullable();
            $table->string('alias')->nullable()->index('alias');
            $table->integer('lft')->nullable();
            $table->integer('rght')->nullable();

            $table->index(['lft', 'rght'], 'lft');
        });

        Schema::create('active_menu_categories', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('category_id');
            $table->integer('active_menu_id');
            $table->enum('is_deleted', ['no', 'yes']);
            $table->dateTime('created');
            $table->dateTime('modified');
        });

        Schema::create('active_menus', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('display_order');
            $table->string('image');
            $table->dateTime('created');
            $table->dateTime('modified');
        });

        Schema::create('active_next_nearest_lists', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('created_by');
            $table->mediumText('list_data');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->dateTime('list_date');
            $table->string('base_user_id');
            $table->dateTime('created');
            $table->dateTime('modified');
            $table->string('type');
            $table->string('total');
        });

        Schema::create('acumatica_api_logs', function (Blueprint $table) {
            $table->integer('id', true);
            $table->enum('type', ['customer', 'invoice', 'payment']);
            $table->integer('invoice_id')->nullable();
            $table->integer('customer_id')->nullable();
            $table->text('url');
            $table->text('request_body');
            $table->integer('response_status_code')->default(0);
            $table->text('response_body')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table
                ->enum('environment', ['live', 'sandbox'])
                ->default('live')
                ->comment('Environment where the API call was made (live or sandbox)');
        });

        Schema::create('acumatica_data_upload_history', function (Blueprint $table) {
            $table->integer('id', true);
            $table
                ->enum('type', ['customer', 'invoice', 'payment'])
                ->comment('Type of data upload (customer, invoice, payment)');
            $table->date('delivery_date')->comment('Date when data was delivered');
            $table->timestamp('created_at')->useCurrent()->comment('Timestamp when the record was created');
            $table
                ->enum('environment', ['live', 'sandbox'])
                ->default('live')
                ->comment('Environment where the upload was performed (live or sandbox)');
        });

        Schema::create('admin_access_tracks', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('contoller', 256)->nullable();
            $table->string('action', 256)->nullable();
            $table->integer('admin_id')->nullable();
            $table->string('admin_name')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });

        Schema::create('alonti_libraries', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('cat', 50)->nullable();
            $table->integer('sort');
            $table->string('hidden', 3)->nullable();
        });

        Schema::create('alonti_users', function (Blueprint $table) {
            $table->integer('group_id')->nullable()->index('gp_id');
            $table->string('acumatica_id')->nullable()->comment('This is unique id of Acumatica create customer API');
            $table
                ->string('acumatica_sandbox_id')
                ->nullable()
                ->comment('Acumatica customer ID for sandbox environment');
            $table->string('fname', 50)->nullable();
            $table->string('lname', 50)->nullable();
            $table->string('email', 80)->nullable()->index('usertableindex22');
            $table->string('secondary_email', 256)->nullable();
            $table->string('password', 256)->nullable();
            $table->integer('cafe_id')->nullable()->index('cafe_id');
            $table->integer('customermenu_id')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('secondary_phone')->nullable();
            $table->integer('industry_id')->nullable();
            $table->string('company', 100)->nullable()->index('company_name');
            $table->string('addr', 100)->nullable();
            $table->integer('id', true);
            $table->string('addr2', 50)->nullable();
            $table->string('city', 50)->nullable();
            $table->string('state', 50)->nullable();
            $table->string('zip', 20)->nullable();
            $table->string('cmpy_phone', 30)->nullable();
            $table->string('fax', 30)->nullable();
            $table->string('contact', 50)->nullable();
            $table->string('cmpy_email', 80)->nullable();
            $table->integer('payment_id')->nullable();
            $table->integer('txexempt')->nullable();
            $table->string('txexempt_file')->nullable();
            $table->string('mas90', 20)->nullable();
            $table->dateTime('lastdate')->nullable()->index('lastdate');
            $table->unsignedTinyInteger('hsacct');
            $table->string('others')->nullable();
            $table->text('staff_notes')->nullable();
            $table->integer('delivery_email')->nullable()->default(0);
            $table->integer('company_user_id')->nullable()->index('cmp_id');
            $table->string('mailout_enabled')->nullable();
            $table->dateTime('creation_date')->nullable();
            $table->dateTime('last_updated')->nullable();
            $table->string('account_status', 8)->nullable()->default('');
            $table->date('last_password_changed')->nullable();
            $table->string('forgot_password_link', 250)->nullable();
            $table->integer('forgot_password_link_valid')->default(0);
            $table->integer('submitter_staff_id')->nullable()->default(0);
            $table->string('email_bounce')->nullable();
            $table->enum('user_source', ['alonti', 'mx_group'])->default('alonti');
            $table->integer('user_source_id')->nullable()->index('user_source_id');
            $table->string('other_industry')->nullable();
            $table->string('physical_addr')->nullable();
            $table->string('physical_addr2')->nullable();
            $table->string('physical_city')->nullable();
            $table->string('physical_zip')->nullable();
            $table->string('physical_state')->nullable();
            $table->dateTime('last_email_sent')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('profile_image')->nullable();
            $table->string('unsubscribe_promotion')->nullable();
            $table->enum('user_category', ['high-value', 'customer', 'old-customer'])->nullable();
            $table->integer('active_cart_id')->nullable();
            $table->integer('type')->default(0)->comment('0:logged-in user, 1: guest-user');
            $table->string('remember_token')->nullable();
            $table
                ->integer('social_login')
                ->default(0)
                ->comment(
                    '1 = sign up via social platform, 0 = sign up via sign up form OR value got updated from 1 to 0 once we saved this user in our database'
                );
            $table->boolean('is_ezcater_customer')->default(false);
            $table->boolean('sms_opt_in')->default(false);

            $table->index(['fname', 'lname'], 'name');
            $table->index(['id'], 'usertableindex12');
        });

        Schema::create('amazon_api_logs', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id');
            $table->string('creationRequestId')->nullable();
            $table->string('gcid')->nullable();
            $table->decimal('amount', 19, 4)->nullable()->default(0);
            $table->text('request')->nullable();
            $table->text('response')->nullable();
            $table->string('url')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('anticipatedelitestatus', function (Blueprint $table) {
            $table->integer('OptionID');
            $table->string('OptionText', 128)->primary();
            $table->integer('Sort');
            $table->boolean('Isactive');
        });

        Schema::create('api_keys', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('key', 40)->unique();
            $table->smallInteger('level');
            $table->boolean('ignore_limits');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('api_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('api_key_id')->nullable()->index('api_logs_api_key_id_foreign');
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('route')->index();
            $table->string('method', 6)->index();
            $table->text('params');
            $table->string('ip_address');
            $table->timestamps();
        });

        Schema::create('aros', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('parent_id')->nullable();
            $table->string('model')->nullable();
            $table->integer('foreign_key')->nullable();
            $table->string('alias')->nullable()->index('alias');
            $table->integer('lft')->nullable();
            $table->integer('rght')->nullable();

            $table->index(['lft', 'rght'], 'lft');
        });

        Schema::create('aros_acos', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('aro_id');
            $table->integer('aco_id')->index('aco_id');
            $table->string('_create', 2)->default('0');
            $table->string('_read', 2)->default('0');
            $table->string('_update', 2)->default('0');
            $table->string('_delete', 2)->default('0');

            $table->unique(['aro_id', 'aco_id'], 'aro_id');
        });

        Schema::create('cafe_access', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('cafe_id')->index('cafe_id');
            $table->integer('alonti_user_id');
            $table->integer('route')->nullable();
        });

        Schema::create('cafegoals', function (Blueprint $table) {
            $table->integer('CafeGoalsID', true);
            $table->integer('Year')->nullable();
            $table->integer('Quarter')->nullable();
            $table->integer('DistrictID')->nullable();
            $table->decimal('Goal')->nullable();
        });

        Schema::create('cafes', function (Blueprint $table) {
            $table->integer('id', true)->unique('cafe_id');
            $table->integer('market_id')->nullable()->index('mk_id');
            $table->string('gl_code_id', 50)->nullable();
            $table->integer('cafenum')->nullable();
            $table->string('acumatica_customer_class_id')->nullable()->comment('This is Acumatica customer class id');
            $table->string('cafename', 100)->nullable();
            $table->string('addr', 100)->nullable();
            $table->string('addr2', 30)->nullable();
            $table->string('csz', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('fax', 50)->nullable();
            $table->integer('cm_id')->nullable();
            $table->string('manager', 50)->nullable();
            $table->string('mgremail', 80)->nullable();
            $table->char('txdelivery', 1)->nullable();
            $table->double('taxrate')->nullable();
            $table->integer('district_id')->nullable()->index('districtid');
            $table->string('active', 50)->nullable()->default('yes');
            $table->integer('catering_manager')->nullable();
            $table->integer('catering_manager2')->nullable()->default(0);
            $table->boolean('is_include')->nullable();
            $table->boolean('walking_sales')->nullable();
            $table->integer('csm_usrid')->nullable()->default(0);
            $table->integer('sales_area_id')->nullable();
            $table->unsignedTinyInteger('include_development_da')->default(0);
            $table->unsignedTinyInteger('include_development_dashboard')->default(0);
            $table->string('tetrad_area', 100)->nullable();
            $table->integer('supported_by')->nullable();
            $table->enum('csm', ['yes', 'no'])->default('no');
            $table->enum('lease', ['yes', 'no'])->default('no');
            $table->date('estimated_open_date')->nullable();
            $table->dateTime('last_modified');
            $table->string('ezcater_profile_email_taxable')->nullable();
            $table->string('ezcater_profile_email_non_taxable')->nullable();

            $table->primary(['id']);
        });

        Schema::create('calender', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('created_by')->index('created_by');
            $table->string('title');
            $table->dateTime('scheduled_date')->nullable();
            $table->string('description');
            $table->enum('status', ['Active', 'Deleted'])->default('Active');
            $table->boolean('notification_status')->default(false);
            $table->dateTime('created');
            $table->dateTime('modified');
        });

        Schema::create('calender_participants', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('calender_id');
            $table->string('user_email');
            $table->enum('response', ['Yes', 'No']);
            $table->text('response_description');
            $table->dateTime('created');
            $table->dateTime('modified');
        });

        Schema::create('campaign_log', function (Blueprint $table) {
            $table->integer('log_id', true)->index('ix_campaign_log');
            $table->integer('log_campaignid')->nullable()->index('ix_campaignid');
            $table->dateTime('log_date')->nullable();
            $table->integer('log_submissioncount')->nullable();
            $table->integer('log_unsubscribecount')->nullable()->default(0);
            $table->integer('log_clickcount')->nullable()->default(0);
            $table->integer('log_bouncecount')->nullable()->default(0);

            $table->primary(['log_id']);
        });

        Schema::create('cart_items_tracks', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('cart_id')->nullable();
            $table->integer('cart_item_id')->nullable()->index('cart_item_id');
            $table->integer('invitee_id')->nullable();
            $table->string('controller')->nullable();
            $table->string('action')->nullable();
            $table->text('item_info')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('cart_options_tracks', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('cart_item_id')->nullable();
            $table->integer('cart_option_id')->nullable();
            $table->string('controller')->nullable();
            $table->string('action')->nullable();
            $table->text('option_info')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('cart_tracks', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('cart_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('admin_id')->nullable();
            $table->string('controller')->nullable();
            $table->string('action')->nullable();
            $table->text('cart_info')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('category', 100)->nullable();
            $table->string('description', 200)->nullable();
            $table->string('image');
            $table->integer('sort')->nullable();
            $table->integer('max_limit')->nullable();
        });

        Schema::create('cc_tables', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('order_id')->nullable()->index('ord_id');
            $table->string('cctype', 30)->nullable();
            $table->string('ccname', 50)->nullable();
            $table->string('ccact', 50)->nullable();
            $table->integer('ccmonth')->nullable();
            $table->integer('ccyear')->nullable();
            $table->integer('cafe_id')->nullable();
            $table->integer('verified')->default(0);
        });

        Schema::create('cim_paids', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->integer('cim_payment_profile_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('admin_id')->nullable();
            $table->bigInteger('order_id')->nullable()->index('ord_id');
            $table->string('profile_id', 256)->nullable();
            $table->string('payment_profile_id', 256)->nullable();
            $table->string('shipping_id', 256)->nullable();
            $table->string('status', 50)->nullable()->default('Not Paid');
            $table->string('paiddate', 50)->nullable();
            $table->dateTime('paid_date')->nullable();
            $table->bigInteger('transaction_id')->nullable();
            $table->string('approval_code', 50)->nullable();
            $table->decimal('auth_amount', 19, 4)->nullable();
            $table->decimal('total_amount', 19, 4)->nullable();
            $table->string('payment_process', 100)->nullable()->comment(':Create:Auth:Capture:Void:Refund');
            $table->dateTime('auth_date')->nullable();
            $table->dateTime('captured_date')->nullable();
            $table->dateTime('void_date')->nullable();
            $table->dateTime('refund_date')->nullable();
            $table->integer('last_modified_by')->nullable();
            $table->text('notes')->nullable();
            $table->string('gateway_name', 256)->nullable()->default('ANET')->comment('ANET and PAYTRACE');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('cim_payment_profiles', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('cim_id')->nullable();
            $table->bigInteger('profile_id')->nullable();
            $table->string('payment_profile_id', 256)->nullable()->index('paymentprofile');
            $table->string('card_number', 50)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->boolean('is_display')->nullable();
            $table->unsignedTinyInteger('delete_status')->default(0);
            $table->integer('admin_id')->nullable();
            $table->string('gateway_name', 256)->nullable()->default('ANET')->comment('ANET and PAYTRACE');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->text('card_type')->nullable();
        });

        Schema::create('cims', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('email')->nullable();
            $table->string('profile_id', 256)->nullable();
            $table->string('shipping_id', 256)->nullable();
            $table->integer('alonti_user_id')->nullable();
            $table->unsignedTinyInteger('delete_status')->default(0);
            $table->integer('admin_id')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('state_id');
            $table->string('name');
            $table->string('code');
            $table->boolean('exclude')->default(false);
        });

        Schema::create('cog_list', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('cog_id', 50)->nullable();
            $table->string('cog_name', 50)->nullable();
        });

        Schema::create('coglist', function (Blueprint $table) {
            $table->integer('ID');
            $table->string('Cog ID', 50)->nullable();
            $table->string('Cog Name', 50)->nullable();
        });

        Schema::create('company_goals', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('cafe_id')->nullable();
            $table->string('compmonth', 15)->nullable();
            $table->string('compyear', 5)->nullable();
            $table->double('compgoal')->nullable();
            $table->integer('comMonthI')->nullable();
        });

        Schema::create('company_payment', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('company_id')->nullable();
            $table->integer('payment_id')->nullable();
            $table->dateTime('created');
            $table->dateTime('modified');
        });

        Schema::create('company_users', function (Blueprint $table) {
            $table->integer('id', true);
            $table
                ->string('acumatica_id')
                ->nullable()
                ->comment('This is unique id of Acumatica create customer/company API');
            $table->string('name', 150)->nullable()->index('cmp_name');
            $table->integer('cafe_id')->nullable();
            $table->string('mas90', 100)->nullable();
            $table->string('name_b', 100)->nullable();
            $table->integer('active')->nullable()->default(0);
            $table->string('domain', 30)->nullable();
        });

        Schema::create('configurations', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('column_key', 256);
            $table->string('column_value', 256);
            $table->string('field_key', 256);
            $table->string('field_value', 256);
            $table->integer('changed_by')->nullable();
            $table->text('comments')->nullable();
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->string('code');
        });

        Schema::create('coupon_cafe', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('coupon_id');
            $table->integer('cafe_id');
            $table->unsignedTinyInteger('status')->default(1);
            $table->dateTime('created');
            $table->dateTime('modified');
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('coupon', 40);
            $table->integer('promotion_type_id')->nullable();
            $table->decimal('price', 10, 0)->nullable();
            $table->integer('usage_limit')->nullable();
            $table->string('day')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('createdtoby');
            $table->dateTime('date');
        });

        Schema::create('csm_da', function (Blueprint $table) {
            $table->integer('id', true);
            $table->date('report_date');
            $table->text('data');
            $table->dateTime('created');
            $table->dateTime('modified');
        });

        Schema::create('customer_notes', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('customer_id');
            $table->string('note');
            $table->dateTime('note_date');
            $table->string('created_by');
            $table->enum('type', ['', 'customer', 'prospect'])->default('');
            $table->dateTime('created');
            $table->dateTime('modified');
        });

        Schema::create('customer_referrals', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id');
            $table->integer('cafe_id')->nullable();
            $table->integer('district_id')->nullable();
            $table->string('name', 256)->nullable();
            $table->string('email', 256);
            $table->unsignedTinyInteger('registered')->default(0);
            $table->unsignedTinyInteger('order_placed')->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('customermenus', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('menu_name', 100)->nullable();
            $table->decimal('mini', 19, 4)->nullable();
            $table->double('percnt')->nullable();
            $table->integer('flag')->nullable();
        });

        Schema::create('directors', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('market_id')->nullable();
            $table->string('title', 50)->nullable();
            $table->string('director', 50)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('fax', 50)->nullable();
            $table->string('email', 50)->nullable();
            $table->integer('alonti_user_id')->nullable();
            $table->dateTime('created')->nullable();
            $table->dateTime('modified')->nullable();
        });

        Schema::create('disable_dates', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('admin_id');
            $table->date('disabled_dates')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('district_access', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('district_id');
            $table->integer('admin_id');
            $table->unsignedTinyInteger('route')->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->tinyInteger('restrict_access')->nullable()->default(0);
        });

        Schema::create('districtgoals', function (Blueprint $table) {
            $table->integer('DistrictGoalsID', true);
            $table->integer('Year')->nullable();
            $table->integer('Quarter')->nullable();
            $table->integer('DistrictID')->nullable();
            $table->decimal('Goal')->nullable();
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 35)->nullable();
            $table->integer('sequence')->nullable();
            $table->decimal('filtered_dwp', 19, 4)->nullable();
            $table->integer('market_id')->nullable();
            $table->integer('admin_id')->nullable();
            $table->integer('catering_manager')->nullable();
            $table->integer('assistant_catering_manager')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('alonti_library_id')->nullable();
            $table->integer('subcategory_id')->nullable();
            $table->string('docname')->nullable();
            $table->string('descr', 100)->nullable();
            $table->dateTime('rdate')->nullable();
            $table->integer('sort');
            $table->string('hidden', 3)->nullable();
        });

        Schema::create('email_bounces', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('email', 100)->nullable();
            $table->dateTime('email_date')->nullable();
            $table->string('reason', 100)->nullable();
            $table->string('email_id', 100)->nullable();
            $table->integer('email_campaign_id')->nullable();
            $table->string('email_submissiondate', 50)->nullable();
        });

        Schema::create('email_campaign_images', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('image_name');
            $table->string('description');
            $table->enum('display', ['active', 'inactive']);
            $table->dateTime('created');
            $table->dateTime('modified');
        });

        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('campaign_label', 100)->nullable();
            $table->longText('campaign_criteria')->nullable();
            $table->string('campaign_subject', 254)->nullable();
            $table->longText('campaign_email')->nullable();
            $table->integer('campaign_owner')->nullable()->index('campaign_owner');
            $table->dateTime('campaign_creationdate')->nullable();
            $table->string('campaign_status', 25)->nullable();
            $table->longText('campaign_emailbody')->nullable();
            $table->dateTime('campaign_lastrandate')->nullable();
            $table->integer('campaign_district')->nullable()->default(0);
            $table->integer('campaign_csmmarket')->nullable()->default(0);

            $table->index(['campaign_label', 'campaign_status'], 'campaign_label_status');
            $table->index(['campaign_owner'], 'ix_email_campaigns');
        });

        Schema::create('email_gallaries', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('image_name');
            $table->string('descr')->nullable();
            $table->integer('alonti_user_id');
            $table->dateTime('created')->nullable();
        });

        Schema::create('email_logs', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('type', 20)->nullable()->index('type');
            $table->string('email', 100)->nullable()->index('email');
            $table->dateTime('email_date')->nullable()->index('email_date');
            $table->string('email_id', 100)->nullable()->index('email_id');
            $table->longText('details')->nullable();
            $table->integer('email_campaign_id')->nullable();
            $table->string('submission_date', 50)->nullable();
        });

        Schema::create('email_queue', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('email', 129);
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('subject');
            $table->string('config', 30);
            $table->string('template', 100);
            $table->string('layout', 50);
            $table->string('theme', 50);
            $table->string('format', 5);
            $table->longText('template_vars');
            $table->text('headers')->nullable();
            $table->boolean('sent')->default(false);
            $table->boolean('locked')->default(false);
            $table->integer('send_tries')->default(0);
            $table->dateTime('send_at')->nullable();
            $table->dateTime('created');
            $table->dateTime('modified')->nullable();
            $table->text('attachments')->nullable();
            $table->text('error')->nullable();
        });

        Schema::create('email_queue_phinxlog', function (Blueprint $table) {
            $table->bigInteger('version')->primary();
            $table->string('migration_name', 100)->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->boolean('breakpoint')->default(false);
        });

        Schema::create('email_sent_logs', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('email_from')->nullable();
            $table->string('email_to')->nullable();
            $table->string('email_cc')->nullable();
            $table->string('email_subject')->nullable();
            $table->text('email_message')->nullable();
            $table->dateTime('created_at')->nullable();
        });

        Schema::create('email_templates', function (Blueprint $table) {
            $table->integer('template_id')->nullable();
            $table->string('template_label', 100)->nullable();
            $table->longText('template_body')->nullable();
            $table->integer('template_owner')->nullable();
            $table->dateTime('template_creationdate')->nullable();
        });

        Schema::create('et_cog_list', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('cog_id', 50)->nullable();
            $table->string('cog_name', 50)->nullable();
        });

        Schema::create('et_coglist', function (Blueprint $table) {
            $table->integer('ID');
            $table->string('Cog ID', 50)->nullable();
            $table->string('Cog Name', 50)->nullable();
        });

        Schema::create('et_input', function (Blueprint $table) {
            $table->integer('inp_id');
            $table->integer('cafe_id');
            $table->dateTime('inputdate')->nullable();
            $table->decimal('txsales', 19, 4)->nullable();
            $table->decimal('ntxsales', 19, 4)->nullable();
            $table->decimal('labor', 19, 4)->nullable();
            $table->decimal('deposit1', 19, 4)->nullable();
            $table->decimal('deposit2', 19, 4)->nullable();
            $table->decimal('cateringcc', 19, 4)->nullable();
            $table->decimal('beersales', 19, 4)->nullable();
            $table->decimal('winesales', 19, 4)->nullable()->default(0);
            $table->decimal('salestax', 19, 4)->nullable();
            $table->decimal('addition', 19, 4)->nullable();
            $table->decimal('gift', 19, 4)->nullable();
            $table->decimal('shortage', 19, 4)->nullable();
            $table->string('chk', 3)->nullable();
        });

        Schema::create('et_inputs', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('cafe_id');
            $table->dateTime('inputdate')->nullable();
            $table->decimal('txsales', 19, 4)->nullable();
            $table->decimal('ntxsales', 19, 4)->nullable();
            $table->decimal('labor', 19, 4)->nullable();
            $table->decimal('deposit1', 19, 4)->nullable();
            $table->decimal('deposit2', 19, 4)->nullable();
            $table->decimal('cateringcc', 19, 4)->nullable();
            $table->decimal('beersales', 19, 4)->nullable();
            $table->decimal('winesales', 19, 4)->nullable()->default(0);
            $table->decimal('salestax', 19, 4)->nullable();
            $table->decimal('addition', 19, 4)->nullable();
            $table->decimal('gift', 19, 4)->nullable();
            $table->decimal('shortage', 19, 4)->nullable();
            $table->string('chk', 3)->nullable();
        });

        Schema::create('et_inventories', function (Blueprint $table) {
            $table->integer('id', true)->unique('inventory_id');
            $table->integer('cafe_id')->index('cafe_id');
            $table->unsignedTinyInteger('month');
            $table->smallInteger('year');
            $table->decimal('food', 19, 4);
            $table->decimal('beer', 19, 4);
            $table->decimal('wine', 19, 4)->default(0);

            $table->primary(['id']);
        });

        Schema::create('et_inventory', function (Blueprint $table) {
            $table->integer('inventory_id')->unique('inventory_id');
            $table->integer('cafe_id')->index('cafe_id');
            $table->unsignedTinyInteger('month');
            $table->smallInteger('year');
            $table->decimal('food', 19, 4);
            $table->decimal('beer', 19, 4);
            $table->decimal('wine', 19, 4)->default(0);
        });

        Schema::create('et_invoicedetails', function (Blueprint $table) {
            $table->integer('Id');
            $table->bigInteger('Invoice ID')->nullable();
            $table->string('Expense ID')->nullable();
            $table->string('Expense Description')->nullable();
            $table->decimal('Expense Total', 19, 4)->nullable();
            $table->char('Cafe number', 10)->nullable();
            $table->dateTime('Date')->nullable();
        });

        Schema::create('et_invoices', function (Blueprint $table) {
            $table->integer('id', true);
            $table->bigInteger('vendor_id')->nullable();
            $table->bigInteger('cafe_id')->nullable();
            $table->string('invoice_number', 50)->nullable();
            $table->dateTime('invoice_date')->nullable();
            $table->decimal('adjustment', 19, 4)->nullable();
            $table->string('adjustment_description')->nullable();
            $table->decimal('total', 19, 4)->nullable();
            $table->decimal('keg_deposit', 19, 4)->nullable();
        });

        Schema::create('et_invoices_details', function (Blueprint $table) {
            $table->integer('id', true);
            $table->bigInteger('et_invoice_id')->nullable();
            $table->string('expense_id')->nullable();
            $table->string('expense_description')->nullable();
            $table->decimal('expense_total', 19, 4)->nullable();
            $table->char('cafe_id', 10)->nullable();
            $table->dateTime('date')->nullable();
        });

        Schema::create('et_paidout', function (Blueprint $table) {
            $table->integer('pd_id');
            $table->integer('inp_id')->nullable();
            $table->integer('cafe_id')->nullable();
            $table->integer('exp_id')->nullable();
            $table->string('descr', 80)->nullable();
            $table->decimal('amount', 19, 4)->nullable();
        });

        Schema::create('et_paidouts', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('et_input_id')->nullable();
            $table->integer('cafe_id')->nullable();
            $table->integer('expense_id')->nullable();
            $table->string('description', 80)->nullable();
            $table->decimal('amount', 19, 4)->nullable();
        });

        Schema::create('exceptions', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('zipcode', 10)->nullable();
            $table->double('taxrate')->nullable();
            $table->integer('customermenu_id')->nullable();
            $table->integer('menu_id')->nullable();
            $table->integer('product_variant_id')->nullable();
            $table->integer('flag')->nullable();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('expensecode', 11);
            $table->string('expensetype', 50)->nullable();
        });

        Schema::create('ezcater_webhook_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('order_id')->nullable();
            $table->enum('event_type', ['accepted', 'modified', 'cancelled']);
            $table->json('payload');
            $table->enum('status', ['processed', 'failed'])->default('processed');
            $table->text('error_message')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('follow_ups', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('customer_id')->index('alontiusers_customer_id');
            $table->string('type');
            $table->string('dar_info');
            $table->dateTime('followup_date');
            $table->string('created_by');
            $table->dateTime('created');
            $table->dateTime('modified');
            $table->string('cafe_id');
        });

        Schema::create('food_available_stores', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('cafe_id')->comment('store cafe id');
            $table->string('cafe_num', 250)->comment('store cafe number to relate the state id');
            $table->string('cafe_name', 250)->comment('store cafe id');
            $table->integer('state_id')->comment('store cafe associated state id');
            $table
                ->integer('entity_id')
                ->comment('It can be category, sub category, product, variant, option, option selection table ids ');
            $table
                ->string('entity_name', 250)
                ->comment('It can be category, sub category, product, variant, option, option selection table names');
            $table
                ->enum('type', ['category', 'subcategory', 'product', 'variant', 'package', 'option', 'selection'])
                ->nullable();
            $table
                ->string('name', 250)
                ->nullable()
                ->comment('It can be category, sub category, product, variant, option, option selection names');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('gl_codes', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('code', 50)->nullable();
            $table->string('description', 50)->nullable();
        });

        Schema::create('goals', function (Blueprint $table) {
            $table->integer('goal_id')->primary();
            $table->string('year', 10);
            $table->string('quarter', 50)->nullable();
            $table->integer('mk_id')->nullable();
            $table->decimal('goal', 19, 4)->nullable();
        });

        Schema::create('group_order_configuration', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('cart_id');
            $table->integer('order_id')->nullable();
            $table->integer('cafe_id')->nullable();
            $table->integer('group_order_id');
            $table->date('response_date');
            $table->string('response_time');
            $table->decimal('invitee_budget', 19, 4)->nullable();
            $table->boolean('default_meal')->default(false)->comment('0: no default meal, 1: default meal');
            $table->integer('category_id')->nullable();
            $table->integer('product_id')->nullable();
            $table->integer('variant_id')->nullable();
            $table->string('options_selection_id')->nullable();
            $table->string('order_staus')->nullable();
            $table
                ->unsignedTinyInteger('leader_reminder_email')
                ->default(0)
                ->comment('reminder email notification should send once as per response date and time');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->dateTime('deleted_at');
        });

        Schema::create('group_order_configuration_tracks', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id')->nullable();
            $table->integer('cart_id')->nullable();
            $table->integer('group_order_config_id')->nullable();
            $table->text('configurations_info')->nullable();
            $table->string('controller')->nullable();
            $table->string('action')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('groups', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 50);
        });

        Schema::create('industries', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->dateTime('created');
            $table->dateTime('modified');
        });

        Schema::create('inputs', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('cafe_id')->index('cafe_id');
            $table->dateTime('inputdate')->nullable()->index('inputdate');
            $table->decimal('txsales', 19, 4)->default(0);
            $table->decimal('ntxsales', 19, 4)->nullable();
            $table->decimal('labor', 19, 4)->nullable();
            $table->decimal('vacation', 19, 4)->nullable();
            $table->decimal('deposit1', 19, 4)->nullable();
            $table->decimal('deposit2', 19, 4)->nullable();
            $table->decimal('walkincc', 19, 4)->nullable();
            $table->decimal('cateringcc', 19, 4)->nullable();
            $table->decimal('salestax', 19, 4)->nullable();
            $table->decimal('addition', 19, 4)->nullable();
            $table->decimal('gift', 19, 4)->nullable();
            $table->decimal('shortage', 19, 4)->nullable();
            $table->string('chk', 3)->default('');
            $table->decimal('tips', 19, 4)->nullable()->default(0);
        });

        Schema::create('invoice_details', function (Blueprint $table) {
            $table->integer('id', true);
            $table->bigInteger('invoice_id')->nullable();
            $table->string('expense_id')->nullable();
            $table->string('expense_description')->nullable();
            $table->decimal('expense_total', 19, 4)->nullable();
            $table->char('cafe_id', 10)->nullable()->index('cafenumber');
            $table->dateTime('date')->nullable()->index('date');
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->integer('id', true);
            $table->bigInteger('vendor_id')->nullable();
            $table->bigInteger('cafe_id')->nullable();
            $table->string('invoice_number', 50)->nullable();
            $table->dateTime('invoice_date')->nullable();
            $table->decimal('adjustment', 19, 4)->nullable();
            $table->string('adjustment_description')->nullable();
            $table->decimal('total', 19, 4)->nullable();
        });

        Schema::create('item_options', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('item_id')->nullable();
            $table->integer('shopping_cart_id')->nullable();
            $table->integer('option_id');
            $table->decimal('qty', 10);
            $table->decimal('unit_price', 10);
            $table->decimal('sale_price', 10);
            $table->decimal('discount', 10);
            $table->dateTime('created');
            $table->dateTime('modified');
        });

        Schema::create('items', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('order_id')->nullable()->index('ord_id');
            $table->integer('menu_id')->nullable()->index('mn_id');
            $table->string('comments', 254)->nullable();
            $table->decimal('price', 19, 4)->nullable();
            $table->decimal('qty', 19);
            $table->integer('mastermenu_price_id')->nullable();
            $table->integer('sel_1')->nullable();
            $table->integer('sel_2')->nullable();
            $table->integer('sel_3')->nullable();
            $table->integer('sel_4')->nullable();
            $table->float('discount')->nullable();
            $table->unsignedTinyInteger('free_delivery')->default(0);
            $table->integer('item_id')->nullable();
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->integer('id', true);
            $table->dateTime('postdate')->nullable();
            $table->string('job_title', 100)->nullable();
            $table->string('description', 1000)->nullable();
            $table->string('job_location', 100)->nullable();
            $table->string('contact', 200)->nullable();
            $table->string('email', 80)->nullable();
            $table->integer('flag')->nullable();
        });

        Schema::create('labor_goals', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('cafe_id')->nullable();
            $table->string('lgmonth', 15)->nullable();
            $table->string('lgyear', 5)->nullable();
            $table->double('laborgoal')->nullable();
        });

        Schema::create('laravel_failed_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        Schema::create('laravel_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('laravel_success_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('job_name');
            $table->longText('payload')->nullable();
            $table->timestamp('processed_at');
        });

        Schema::create('log_changedemails', function (Blueprint $table) {
            $table->integer('log_id');
            $table->integer('log_employeeid')->nullable();
            $table->integer('log_customerid')->nullable();
            $table->string('log_originalemail', 50)->nullable();
            $table->string('log_modifiedemail', 50)->nullable();
            $table->dateTime('log_date')->nullable();
        });

        Schema::create('log_pageloadtimes', function (Blueprint $table) {
            $table->integer('log_id');
            $table->string('log_page', 100)->nullable();
            $table->dateTime('log_date')->nullable();
            $table->string('log_loadtime', 5)->nullable();
        });

        Schema::create('market_access', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('market_id');
            $table->integer('admin_id');
            $table->unsignedTinyInteger('route')->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('markets', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 50)->nullable();
            $table->integer('customermenu_id')->nullable();
            $table->string('gl_code_id', 50)->nullable();
            $table->string('timezone_difference', 2)->nullable();
            $table
                ->integer('allow_weekend_orders')
                ->default(0)
                ->comment('1 = weekend order is enabled, 0 = weekend order is disabled');
            $table
                ->integer('allow_night_orders')
                ->default(0)
                ->comment('1 = night order is enabled, 0 = night order is disabled');
        });

        Schema::create('mastermenu_prices', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('mastermenu_id');
            $table->integer('serve');
            $table->integer('state_id')->nullable();
            $table->decimal('price', 19, 4);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('createdtoby');
            $table->dateTime('date');
        });

        Schema::create('mastermenus', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('category_id')->nullable();
            $table->string('item', 100)->nullable();
            $table->string('description', 1000)->nullable();
            $table->enum('price_type', ['single', 'multiple'])->default('single');
            $table->string('unit', 50)->nullable();
            $table->decimal('price', 19, 4)->nullable();
            $table->decimal('price_multiple', 19, 4)->nullable();
            $table->string('img', 100)->nullable();
            $table->integer('sort')->nullable();
            $table->integer('max_limit')->nullable();
            $table->unsignedTinyInteger('stay')->nullable();
            $table->unsignedTinyInteger('flag')->nullable();
            $table->unsignedTinyInteger('gluten')->nullable();
            $table->unsignedTinyInteger('veggie')->nullable();
            $table->unsignedTinyInteger('free')->default(0);
            $table->unsignedTinyInteger('sides')->default(0);
            $table->enum('unit_type', ['quantity', 'dozen', 'package', 'balloon', 'sticker'])->default('quantity');
            $table->enum('dozen_type', ['half', 'one'])->default('one');
            $table->enum('warm_cookie_add_ons', ['yes', 'no'])->default('no');
            $table->enum('warm_cookie_special', ['yes', 'no'])->default('no');
        });

        Schema::create('menu_download_settings', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('section_1_title')->nullable();
            $table->text('section_1_text_1')->nullable();
            $table->text('section_1_text_2')->nullable();
            $table->enum('section_1_status', ['active', 'inactive'])->default('active');
            $table->text('section_1_texas_menu')->nullable();
            $table->text('section_1_texas_menu_key')->nullable();
            $table->text('section_1_georgia_menu')->nullable();
            $table->text('section_1_georgia_menu_key')->nullable();
            $table->text('section_1_illinois_menu')->nullable();
            $table->text('section_1_illinois_menu_key')->nullable();
            $table->text('section_1_california_menu')->nullable();
            $table->text('section_1_california_menu_key')->nullable();
            $table->string('section_2_title')->nullable();
            $table->text('section_2_text_1')->nullable();
            $table->text('section_2_text_2')->nullable();
            $table->enum('section_2_status', ['active', 'inactive'])->default('active');
            $table->text('section_2_texas_menu')->nullable();
            $table->text('section_2_texas_menu_key')->nullable();
            $table->text('section_2_georgia_menu')->nullable();
            $table->text('section_2_georgia_menu_key')->nullable();
            $table->text('section_2_illinois_menu')->nullable();
            $table->text('section_2_illinois_menu_key')->nullable();
            $table->text('section_2_california_menu')->nullable();
            $table->text('section_2_california_menu_key')->nullable();
            $table->string('section_3_title')->nullable();
            $table->text('section_3_text_1')->nullable();
            $table->text('section_3_text_2')->nullable();
            $table->enum('section_3_status', ['active', 'inactive'])->default('active');
            $table->text('section_3_texas_menu')->nullable();
            $table->text('section_3_texas_menu_key')->nullable();
            $table->text('section_3_georgia_menu')->nullable();
            $table->text('section_3_georgia_menu_key')->nullable();
            $table->text('section_3_illinois_menu')->nullable();
            $table->text('section_3_illinois_menu_key')->nullable();
            $table->text('section_3_california_menu')->nullable();
            $table->text('section_3_california_menu_key')->nullable();
        });

        Schema::create('menu_extra_items', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('menu_id')->nullable();
            $table->integer('items')->nullable();
            $table->string('menu_item', 500)->nullable();
        });

        Schema::create('menu_state_prices', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('entity_id');
            $table->string('entity_name');
            $table->integer('state_id');
            $table->integer('city_id')->nullable();
            $table->decimal('price', 19, 4)->nullable();
            $table->decimal('price_multiple', 19, 4)->nullable();
            $table->date('created');
            $table->date('modified');
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('customermenu_id')->nullable()->index('cs_id');
            $table->integer('mastermenu_id')->nullable()->index('ms_id');
            $table->decimal('price', 19, 4)->nullable();
            $table->string('img', 30)->nullable();
            $table->integer('sort')->nullable();
            $table->integer('category_id')->nullable();
            $table->integer('flag')->nullable();
        });

        Schema::create('mx_prospects', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('fname', 50);
            $table->string('lname', 50);
            $table->string('email', 80);
            $table->integer('cafe_id')->nullable();
            $table->string('phone', 50);
            $table->string('company', 100);
            $table->string('addr', 100);
            $table->string('addr2', 50);
            $table->string('city', 50);
            $table->string('state', 50);
            $table->string('zip', 20);
            $table->dateTime('created');
            $table->dateTime('modified');
            $table->integer('prospect_id');
            $table->integer('status')->nullable()->comment('0:low value prospects,1:high value');
            $table->integer('active')->nullable()->comment('0:inactive,1:active');
            $table->dateTime('last_email_sent')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->integer('company_user_id')->nullable();
            $table->dateTime('restore_date')->nullable();
            $table->string('unsubscribe_promotion');
            $table->enum('email_bounce', ['no', 'yes']);
            $table->integer('created_by')->nullable()->index('created_by');
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->integer('NoteID', true);
            $table->string('Email', 128);
            $table->string('Notes', 8000);
            $table->dateTime('CreatedOn');
            $table->integer('CreatedBy');
        });

        Schema::create('offmenu_credits', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('credit', 100)->nullable();
            $table->integer('sort')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
        });

        Schema::create('offmenus', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('order_id')->nullable()->index('ord_id');
            $table->string('comments', 200)->nullable();
            $table->decimal('price', 19, 4)->nullable();
            $table->integer('qty')->nullable();
            $table->unsignedTinyInteger('txbl')->nullable();
            $table->unsignedTinyInteger('flag')->nullable();
            $table->unsignedTinyInteger('offmenu_credit_id')->nullable();
            $table->integer('coupon_id')->nullable();
            $table->string('vendor', 200)->nullable();
            $table->float('discount')->nullable();
            $table->unsignedTinyInteger('free_delivery')->default(0);
            $table->integer('serving_option_id')->nullable()->index('serving_option_id');
            $table->unsignedInteger('cart_id')->nullable()->index('cart_id');
        });

        Schema::create('oj_billing_address', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cart_id')->index('billing_address_cart_id_foreign');
            $table->string('first_name', 256);
            $table->string('last_name', 256);
            $table->string('email', 256)->nullable();
            $table->string('phone_number', 256)->nullable();
            $table->string('secondary_phone_number', 256)->nullable();
            $table->integer('company_id')->nullable()->index('billing_address_company_id_foreign');
            $table->integer('industry_id')->nullable()->index('billing_address_industry_id_foreign');
            $table->text('address1')->nullable();
            $table->text('address2')->nullable();
            $table->string('city', 256)->nullable();
            $table->string('state', 256)->nullable();
            $table->string('zipcode', 256)->nullable();
            $table->string('country', 256)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('oj_cart_invitees', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('cart_id');
            $table->integer('invitee_id');
            $table->integer('group_order_id');
            $table->integer('response')->default(1)->comment('1:Pending, 2:Accepted, 3:Declined, 4:Completed');
            $table->unsignedTinyInteger('resent_invitation')->default(0);
            $table->unsignedTinyInteger('update_response_status')->default(0);
            $table->integer('admin_id')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('oj_cart_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cart_id')->index('cart_items_cart_id_foreign');
            $table->unsignedInteger('category_id')->nullable()->index('cart_items_category_id_foreign');
            $table->unsignedInteger('product_id')->index('cart_items_product_id_foreign');
            $table->unsignedInteger('product_variant_id')->index('cart_items_product_variant_id_foreign');
            $table->unsignedInteger('product_package_id')->nullable()->index('cart_items_product_package_id_foreign');
            $table->decimal('package_price', 5)->nullable();
            $table->integer('package_size')->nullable();
            $table->unsignedInteger('product_dietary_id')->nullable()->index('product_dietary_id');
            $table->decimal('quantity', 10);
            $table->decimal('serve', 10)->nullable();
            $table->decimal('unit_price', 10)->nullable();
            $table->decimal('discount', 10, 4)->nullable();
            $table->decimal('total', 10)->nullable();
            $table->text('product_description')->nullable();
            $table->text('who_is_this_for')->nullable();
            $table->text('special_instruction')->nullable();
            $table->string('box_lunch_type', 256)->nullable();
            $table->text('product_name')->nullable();
            $table->unsignedInteger('state_price_id')->index('cart_items_state_price_id_foreign');
            $table->unsignedInteger('invitee_id')->nullable()->index('cart_items_invitee_id_foreign');
            $table->unsignedTinyInteger('is_invitee_default_meal')->default(0);
            $table->integer('package_state_price_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedTinyInteger('free_delivery')->default(0);
            $table->integer('free_item_id')->nullable();
            $table->integer('addon_cartitem_id')->nullable();
            $table->unsignedTinyInteger('is_free_item')->default(0);
        });

        Schema::create('oj_cart_options', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cart_item_id')->index('cart_options_cart_item_id_foreign');
            $table->unsignedInteger('product_option_id');
            $table->unsignedInteger('product_selection_id');
            $table->string('name')->nullable();
            $table->decimal('unit_price', 10)->nullable();
            $table->decimal('quantity', 19, 4)->nullable();
            $table->decimal('total', 19, 4)->nullable();
            $table->unsignedInteger('state_price_id')->index('cart_options_state_price_id_foreign');
            $table->boolean('is_free')->default(false)->comment('0:not free, 1: free');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('oj_carts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('order_id')->nullable()->index('carts_order_id_foreign');
            $table->string('session_id', 256)->nullable()->index('idx_oj_carts_session_id');
            $table->integer('user_id')->nullable()->index('carts_user_id_foreign');
            $table->integer('cafe_id')->nullable();
            $table->unsignedInteger('group_order_id')->nullable()->index('carts_group_order_id_foreign');
            $table->integer('promotion_type_id')->nullable()->index('carts_promotion_type_id_foreign');
            $table->double('discount')->nullable()->default(0);
            $table
                ->integer('discount_type')
                ->nullable()
                ->index('carts_discount_type_index')
                ->comment('1:Percentage, 2:Price');
            $table->integer('coupon_id')->nullable();
            $table->double('subtotal')->nullable()->default(0);
            $table->decimal('taxable', 10)->nullable()->default(0);
            $table->decimal('nontaxable', 10)->nullable()->default(0);
            $table->decimal('delivery_fee', 10)->nullable()->default(0);
            $table->decimal('sales_tax', 10)->nullable();
            $table->decimal('total', 10)->nullable()->default(0);
            $table->decimal('gratuity_percentage', 10)->nullable();
            $table->decimal('gratuity', 10)->nullable();
            $table->string('zipcode', 256)->nullable();
            $table->integer('state_id')->nullable();
            $table->text('group_order_notes')->nullable();
            $table->tinyInteger('type_of_checkout')->default(1)->comment('1:Customer, 2:guest');
            $table->integer('payment_id')->nullable();
            $table->string('company_payment_access_number')->nullable();
            $table->integer('cim_payment_profile_id')->nullable()->index('carts_cim_payment_profile_id_foreign');
            $table->string('cim_profile_id')->nullable()->comment('Authorize.net customer profile id');
            $table->string('payment_profile_id')->nullable()->comment('Authorize.net customer payment profile id');
            $table->text('personalized_message')->nullable();
            $table->string('order_name')->nullable();
            $table->string('order_status')->nullable();
            $table->boolean('status')->default(false)->comment('0 : Add, 1: Edit');
            $table
                ->boolean('gift_card_rewards')
                ->default(false)
                ->comment('0:Not opt to alonti rewards, 1:Opt to alonti rewards');
            $table->timestamps();
            $table->softDeletes();
            $table
                ->decimal('amazon_reward_applied', 10)
                ->nullable()
                ->default(0)
                ->comment('Amazon reward applied to the order at checkout');
        });

        Schema::create('oj_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('parent_id')->nullable()->index('categories_parent_id_foreign');
            $table->string('name', 256)->nullable();
            $table->text('description')->nullable();
            $table->tinyInteger('visible_to_invitee')->default(0)->comment('1:Yes, 0:No');
            $table->tinyInteger('type')->default(1)->comment('1:Single, 2:Bulk');
            $table->integer('display_order');
            $table->boolean('status')->default(true)->comment('0:inactive,1:active');
            $table
                ->boolean('is_discount_free')
                ->default(false)
                ->comment('Flag to indicate if category is discount free');
            $table->integer('display_status')->default(1)->comment('1:all,2:admin');
            $table->unsignedTinyInteger('available_all_store')->default(1);
            $table->unsignedTinyInteger('invitee_default_meal')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedTinyInteger('delivery_exception')->default(0)->comment('1: yes, 0: no');
            $table
                ->text('serving_options')
                ->comment(
                    'This column contains the value of serving ware options. The value will be comma separated serving ware option ids.'
                );
        });

        Schema::create('oj_dietaries', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 256);
            $table->boolean('status')->default(true)->comment('0:inactive,1:active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('oj_group_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->index('group_order_user_id');
            $table->string('name', 256);
            $table->text('notes');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('oj_images', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('entity_id');
            $table->string('entity_type', 256);
            $table->string('original_name', 256);
            $table->string('filename', 256);
            $table->string('alt', 256);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('oj_invitee_reponses', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cart_id')->index('invitee_reponses_cart_id_foreign');
            $table->unsignedInteger('group_order_id')->index('invitee_reponses_group_order_id_foreign');
            $table->unsignedInteger('invitee_id')->index('invitee_reponses_invitee_id_foreign');
            $table
                ->integer('responses')
                ->nullable()
                ->default(1)
                ->comment('1:Pending, 2:Accepted, 3:Declined, 4:Completed, 5:Resent');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('oj_invitees', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('group_order_id')->index('invitees_group_order_id_foreign');
            $table->string('name', 256);
            $table->string('email', 256);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('oj_menu_maps', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('old_entity_name');
            $table->integer('old_entity_id');
            $table->string('new_entity_name');
            $table->integer('new_entity_id');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->dateTime('created');
            $table->dateTime('modified');
        });

        Schema::create('oj_package_sizes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_variant_id')->index('package_sizes_product_variant_id_foreign');
            $table->integer('size');
            $table->unsignedTinyInteger('available_all_store')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('oj_product_add_ons', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id')->index('product_add_ons_product_id_foreign');
            $table->unsignedInteger('addon_product_id')->index('addon_product_id');
            $table->unsignedInteger('product_variant_id')->nullable()->index('product_variant_id');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('oj_product_dietaries', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id')->index('product_dietaries_product_id_foreign');
            $table->unsignedInteger('dietary_id')->index('product_dietaries_dietary_id_foreign');
            $table->integer('type')->default(1)->comment('1:Product,2:Option');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('oj_product_option_selections', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_option_id')->index('product_option_selections_product_option_id_foreign');
            $table
                ->unsignedInteger('product_selection_id')
                ->index('product_option_selections_product_selection_id_foreign');
            $table->unsignedTinyInteger('available_all_store')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('oj_product_options', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_variant_id')->index('product_options_product_variant_id_foreign');
            $table->string('name', 256);
            $table->text('description')->nullable();
            $table->unsignedInteger('product_id')->nullable()->index('product_id');
            $table->integer('minimum_qty')->default(1);
            $table->integer('maximum_qty')->default(1);
            $table->integer('display_order');
            $table->boolean('status')->default(true)->comment('0:inactive,1:active');
            $table->unsignedTinyInteger('available_all_store')->default(1);
            $table->unsignedTinyInteger('invitee_default_meal')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('oj_product_selection_dietaries', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedInteger('product_selection_id')->index('product_selection_id');
            $table->unsignedInteger('dietary_id')->index('dietary_id');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('oj_product_selections', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 256);
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('free')->default(0);
            $table->integer('display_order')->nullable();
            $table->boolean('status')->default(true)->comment('0:inactive,1:active');
            $table->timestamps();
            $table->softDeletes();
            $table->enum('is_assorted', ['yes', 'no'])->default('no');
        });

        Schema::create('oj_product_variants', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id')->index('product_variants_product_id_foreign');
            $table->string('name', 256);
            $table->text('description');
            $table->integer('sides');
            $table->boolean('status')->default(true)->comment('0:inactive,1:active');
            $table->unsignedTinyInteger('available_all_store')->default(1);
            $table->unsignedTinyInteger('invitee_default_meal')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('oj_products', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('category_id')->index('products_category_id_foreign');
            $table->string('name', 256);
            $table->text('description');
            $table->text('calories')->nullable();
            $table->string('serve', 256)->nullable();
            $table->string('sides')->nullable();
            $table->string('sides_tooltip')->nullable();
            $table->integer('unit_type')->default(1)->comment('1:quantity,2:dozen');
            $table->decimal('minimum_serve', 5);
            $table->decimal('quantity_interval', 5);
            $table->unsignedTinyInteger('free')->default(0);
            $table->integer('display_order');
            $table->boolean('status')->default(true)->comment('0:inactive,1:active');
            $table->boolean('include_product_variant')->default(false)->comment('1: yes, 0:no');
            $table->unsignedTinyInteger('available_all_store')->default(1);
            $table->unsignedTinyInteger('display_unit_price')->default(0);
            $table
                ->unsignedTinyInteger('apply_discount_per_unit')
                ->default(0)
                ->comment('This should be applied only the minimum serve is greater than 1');
            $table->unsignedTinyInteger('invitee_default_meal')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('oj_shipping_address', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cart_id')->index('shipping_address_cart_id_foreign');
            $table->string('first_name', 256);
            $table->string('last_name', 256)->nullable();
            $table->string('email', 256)->nullable();
            $table->string('phone_number', 256)->nullable();
            $table->string('secondary_phone_number', 256)->nullable();
            $table->integer('company_id')->nullable()->index('shipping_address_company_id_foreign');
            $table->integer('industry_id')->nullable()->index('shipping_address_industry_id_foreign');
            $table->integer('address_id')->nullable()->comment('This value is order id');
            $table->string('address1', 256)->nullable();
            $table->string('address2', 256)->nullable();
            $table->string('city', 256)->nullable();
            $table->string('state', 256)->nullable();
            $table->string('zipcode', 256)->nullable();
            $table->string('country', 256)->nullable();
            $table->tinyInteger('shipping_type')->default(1)->comment('1:Delivery, 2:Pickup');
            $table->boolean('delivery_as_gift')->default(false)->comment('1: deliver as a gift, 0: not a gift');
            $table->integer('cafe_id')->nullable()->index('shipping_address_cafe_id_foreign');
            $table->date('delivery_date');
            $table->integer('delivery_time');
            $table->text('delivery_instruction')->nullable();
            $table->text('notes')->nullable();
            $table->integer('number_of_members')->nullable();
            $table->boolean('contactless_delivery')->nullable()->default(false)->comment('0:No,1:Yes');
            $table->boolean('paper_products')->nullable()->default(false)->comment('1:Yes,o:no');
            $table->string('receiver_name')->nullable();
            $table->string('receiver_phone')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('oj_states_prices', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('entity_id');
            $table->string('entity_type', 256);
            $table->integer('state_id');
            $table->integer('city_id')->nullable();
            $table->decimal('price', 10)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('oj_unique_urls', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('entity_id');
            $table->string('entity_type', 256);
            $table->string('url', 256);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['url', 'entity_type'], 'unique_url_per_type');
        });

        Schema::create('options', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('mastermenu_id')->nullable()->index('ms_id');
            $table->string('options', 100)->nullable();
            $table->integer('display_order')->nullable();
            $table->decimal('price', 10)->nullable();
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->dateTime('created');
            $table->dateTime('modified');
        });

        Schema::create('order_tracks', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('order_id');
            $table->string('contoller', 256)->nullable();
            $table->string('action', 256)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->integer('admin_id')->nullable();
            $table->string('admin_name')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->integer('id', true);
            $table
                ->string('payment_acumatica_id')
                ->nullable()
                ->comment('This is unique id of Acumatica create payment API (other than credit cards)');
            $table
                ->string('payment_acumatica_sandbox_id')
                ->nullable()
                ->comment('Acumatica payment ID for sandbox environment');
            $table
                ->string('invoice_acumatica_id')
                ->nullable()
                ->comment('This is unique id of Acumatica create invoice API');
            $table
                ->string('invoice_acumatica_sandbox_id')
                ->nullable()
                ->comment('Acumatica invoice ID for sandbox environment');
            $table->integer('customermenu_id')->nullable();
            $table->integer('payment_id')->nullable();
            $table->integer('alonti_user_id')->nullable()->index('usr_id');
            $table->integer('cafe_id')->nullable()->index('cafe_id');
            $table->integer('sales_area_id')->nullable();
            $table->dateTime('ordate')->nullable()->index('ordate');
            $table->dateTime('d_date')->nullable()->index('d_date');
            $table->string('time_id', 40)->nullable();
            $table->string('d_addr', 150)->nullable();
            $table->string('status', 30)->nullable()->index('status');
            $table->decimal('taxable', 19, 4)->nullable();
            $table->decimal('nontaxable', 19, 4)->nullable();
            $table->decimal('delivery', 19, 4)->nullable();
            $table->decimal('delivery_adjustment', 19, 4)->default(0);
            $table->decimal('adjusted_delivery', 19, 4)->nullable();
            $table->decimal('salestax', 19, 4)->nullable();
            $table->decimal('total', 19, 4)->nullable();
            $table->decimal('gratuity', 19, 4)->nullable();
            $table->decimal('gratuity_percentage', 10, 4)->nullable();
            $table->string('notes', 1500)->nullable();
            $table->text('cookie_special_instruction')->nullable();
            $table->unsignedTinyInteger('gift_order')->default(0);
            $table->integer('d_sort')->nullable();
            $table->text('porder')->nullable();
            $table->integer('pdflag')->nullable();
            $table->integer('web')->nullable();
            $table->unsignedTinyInteger('d_check')->default(0);
            $table->integer('placetoderby')->nullable();
            $table->boolean('calledCustomer')->nullable()->default(false);
            $table->string('zipcode', 50)->nullable();
            $table->integer('cafe_override')->nullable();
            $table->string('orderName', 250)->nullable();
            $table->string('cmpName', 250)->nullable();
            $table->string('deliveryCity', 50)->nullable();
            $table->text('second_address')->nullable();
            $table->text('state')->nullable();
            $table->integer('blockaddress')->nullable()->default(0);
            $table->unsignedTinyInteger('address_status')->default(1);
            $table->dateTime('last_updated')->nullable();
            $table->text('occasion_message')->nullable();
            $table->integer('group_order_id')->nullable();
            $table->integer('checkout_type')->default(0)->comment('0:logged in checkout, 1: guest-checkout');
            $table->integer('is_new_order')->default(0)->comment('0:old-order, 1: new-order');
            $table->integer('confirmation_email')->nullable();
            $table
                ->boolean('gift_card_rewards')
                ->default(false)
                ->comment('0:Not opt to alonti rewards, 1:Opt to alonti rewards');
            $table->boolean('is_stax_overridden')->default(false)->comment('Is sales tax calculation overridden');
            $table
                ->decimal('amazon_reward', 10)
                ->default(0)
                ->comment('The amount of Amazon Reward used for this order.');
            $table
                ->integer('overridden_by_user_id')
                ->nullable()
                ->comment('ID of the user who overridden the order i.e adding both reward and promo code');
            $table->boolean('is_ezcater_order')->default(false);
            $table->string('ezcater_order_id')->nullable();
            $table->text('cancellation_reason')->nullable();
        });

        Schema::create('orders_back', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('customermenu_id')->nullable();
            $table->integer('payment_id')->nullable();
            $table->integer('alonti_user_id')->nullable()->index('usr_id');
            $table->integer('cafe_id')->nullable()->index('cafe_id');
            $table->dateTime('ordate')->nullable()->index('ordate');
            $table->dateTime('d_date')->nullable()->index('d_date');
            $table->string('time_id', 40)->nullable();
            $table->string('d_addr', 150)->nullable();
            $table->string('status', 30)->nullable()->index('status');
            $table->decimal('taxable', 19, 4)->nullable();
            $table->decimal('nontaxable', 19, 4)->nullable();
            $table->decimal('delivery', 19, 4)->nullable();
            $table->decimal('delivery_adjustment', 19, 4)->default(0);
            $table->decimal('adjusted_delivery', 19, 4)->nullable();
            $table->decimal('salestax', 19, 4)->nullable();
            $table->decimal('total', 19, 4)->nullable();
            $table->decimal('gratuity', 19, 4)->nullable();
            $table->string('notes', 1500)->nullable();
            $table->integer('d_sort')->nullable();
            $table->string('porder', 35)->nullable();
            $table->integer('pdflag')->nullable();
            $table->integer('web')->nullable();
            $table->unsignedTinyInteger('d_check')->default(0);
            $table->integer('placetoderby')->nullable();
            $table->boolean('calledCustomer')->nullable()->default(false);
            $table->string('zipcode', 50)->nullable();
            $table->integer('cafe_override')->nullable();
            $table->string('orderName', 250)->nullable();
            $table->string('cmpName', 250)->nullable();
            $table->string('deliveryCity', 50)->nullable();
            $table->integer('blockaddress')->nullable()->default(0);
        });

        Schema::create('paid_tracks', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('paid_id');
            $table->string('contoller', 256)->nullable();
            $table->string('action', 256)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->integer('admin_id')->nullable();
            $table->string('admin_name')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });

        Schema::create('paidouts', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('input_id')->nullable();
            $table->integer('cafe_id')->nullable();
            $table->integer('expense_id')->nullable();
            $table->string('description', 80)->nullable();
            $table->decimal('amount', 19, 4)->nullable();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('terms', 50)->nullable();
            $table->integer('flag')->nullable();
            $table->integer('sort')->nullable();
            $table->unsignedTinyInteger('default_payment')->default(0);
            $table->unsignedTinyInteger('visibility')->default(0);
            $table
                ->string('acumatica_term_id')
                ->nullable()
                ->comment('This is the Acumatica term ID associated with various payment types in Acumatica.');
            $table
                ->string('acumatica_payment_method_id')
                ->nullable()
                ->comment('This is the Acumatica term ID associated with various payment types in Acumatica.');
        });

        Schema::create('paytrace_api_logs', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id');
            $table->integer('cart_id')->nullable();
            $table->integer('order_id')->nullable();
            $table->integer('admin_id')->nullable();
            $table->text('api_error')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('modified_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->text('request_type')->nullable();
            $table->text('logs')->nullable();
        });

        Schema::create('paytrace_settings', function (Blueprint $table) {
            $table->integer('id', true);
            $table->enum('environment', ['live', 'sandbox'])->default('sandbox');
            $table->string('sandbox_username')->nullable();
            $table->string('sandbox_password')->nullable();
            $table->string('live_username')->nullable();
            $table->string('live_password')->nullable();
            $table->enum('alarm', ['yes', 'no'])->default('no');
            $table->string('alarm_emails')->nullable();
            $table->string('days_after')->nullable();
            $table->integer('updated_by')->nullable()->index('updated_by');
            $table->dateTime('updated_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('controller');
            $table->string('action');
            $table->integer('group_id');
        });

        Schema::create('phinxlog', function (Blueprint $table) {
            $table->bigInteger('version')->primary();
            $table->string('migration_name', 100)->nullable();
            $table->timestamp('start_time')->useCurrent();
            $table->timestamp('end_time')->useCurrent();
            $table->boolean('breakpoint')->default(false);
        });

        Schema::create('prep_categories', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table
                ->enum('has_sizes', ['yes', 'no'])
                ->default('no')
                ->comment('if yes then particular prep category will belong to bowl');
            $table
                ->text('no_of_rows')
                ->comment('This column indicates how many rows will appear in the Prep Sheet PDF');
            $table->integer('created_by')->nullable()->index('created_by');
            $table->integer('updated_by')->nullable()->index('updated_by');
            $table->timestamp('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->integer('deleted_by')->nullable()->index('deleted_by');
            $table->dateTime('deleted_at')->nullable();
            $table->integer('display_order')->nullable();
            $table
                ->enum('tally_sheet', ['no', 'yes'])
                ->default('no')
                ->comment(
                    'Not needed single line for every order. Needed only 1 line which will have total quantity of all prep items.'
                );
        });

        Schema::create('prep_item_station', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('prep_category_id')->index('prep_category_id');
            $table->integer('prep_item_id')->index('prep_item_id');
            $table->integer('prep_station_id')->index('prep_station_id');
            $table
                ->enum('has_sizes', ['yes', 'no'])
                ->default('no')
                ->comment('if yes then particular prep category will belong to bowl');
            $table->text('display_order')->nullable();
        });

        Schema::create('prep_items', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('created_by')->nullable()->index('created_by');
            $table->integer('updated_by')->nullable()->index('updated_by');
            $table->timestamp('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->integer('deleted_by')->nullable()->index('deleted_by');
            $table->dateTime('deleted_at')->nullable();
            $table->text('display_order')->nullable();
        });

        Schema::create('prep_package_deal_indexes', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('quantity');
            $table->integer('feed');
            $table->string('leafy_salad');
            $table->string('leafy_salad_alacart')->default('["0","0","0"]')->comment('Leafy Salad Alacarte');
            $table->string('pasta_salad');
            $table->string('pasta_salad_alacart')->default('["0","0","0"]')->comment('Pasta Salad Alacarte');
            $table->string('fruit_bowl');
            $table->string('fruit_bowl_alacart')->default('["0","0","0"]')->comment('Fruit Bowl Alacarte');
            $table->integer('fruit_tray');
            $table->integer('fruit_tray_alacart')->comment('Fruit Tray Alacarte');
            $table->integer('sweet_tray_cookie_brownie');
            $table->integer('sweet_tray_cookie_brownie_alacart');
            $table->integer('created_by')->nullable()->index('created_by');
            $table->integer('updated_by')->nullable()->index('updated_by');
            $table->timestamp('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->integer('deleted_by')->nullable()->index('deleted_by');
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('prep_product_option_selection_priorities', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedInteger('product_option_id')->nullable()->index('product_option_id');
            $table->unsignedInteger('selection_id')->nullable()->index('selection_id');
            $table->integer('priority')->nullable();
        });

        Schema::create('prep_sheet_mappings', function (Blueprint $table) {
            $table->integer('id', true);
            $table
                ->enum('type', ['product', 'selection', ''])
                ->comment('We are mapping prep items to products variant and selection on the same ui.');
            $table->enum('package_type', ['box', 'tray', '']);
            $table
                ->enum('selection_contain_package', ['yes', 'no'])
                ->default('no')
                ->comment('if yes then particular product"s selection will contain package');
            $table->unsignedInteger('variant_id')->nullable()->index('variant_id');
            $table->unsignedInteger('selection_id')->nullable()->index('selection_id');
            $table
                ->string('item_display')
                ->nullable()
                ->comment(
                    'Indicates the item display types like assorted, package deal, and serving unit. Based on this value logic will be applied to calculate the quantity.'
                );
            $table->string('prep_package_deal_index')->nullable();
            $table->integer('prep_category_id')->nullable()->index('prep_category_id');
            $table->integer('prep_station_id')->nullable()->index('prep_station_id');
            $table->integer('prep_item_id')->nullable()->index('prep_item_id');
            $table->integer('quantity')->nullable();
            $table
                ->enum('quantity_or_feed', ['quantity', 'feed'])
                ->default('quantity')
                ->comment(
                    'This column indicates whether a mapped product/selection that has item display as package del index refers quantity or feed.'
                );
            $table
                ->enum('serving_qty_round_off', ['yes', 'no'])
                ->default('yes')
                ->comment(
                    'If the choice is yes, the prep sheet quantity will be rounded off (2.X quantity will be shown as 3). If the choice is no, quantity 2.X will not be rounded off.'
                );
            $table->integer('created_by')->nullable()->index('created_by');
            $table->integer('updated_by')->nullable()->index('updated_by');
            $table->timestamp('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->integer('deleted_by')->nullable()->index('deleted_by');
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('prep_sheets', function (Blueprint $table) {
            $table->integer('id', true);
            $table->date('delivery_date')->nullable();
            $table->longText('order_ids')->nullable();
            $table->longText('prep_array')->nullable()->comment('This is prep sheet array in json encoded format.');
            $table->integer('created_by')->nullable()->index('created_by');
            $table->integer('updated_by')->nullable()->index('updated_by');
            $table->timestamp('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->integer('deleted_by')->nullable()->index('deleted_by');
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('prep_stations', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('prep_category_id')->index('prep_category_id');
            $table->string('name');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('bgcolor')->nullable();
            $table->integer('created_by')->nullable()->index('created_by');
            $table->integer('updated_by')->nullable()->index('updated_by');
            $table->timestamp('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->integer('deleted_by')->nullable()->index('deleted_by');
            $table->dateTime('deleted_at')->nullable();
            $table->text('display_order')->nullable();
        });

        Schema::create('promotion_product_selections', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('promotion_type_product_id');
            $table->integer('option_id');
            $table->integer('selection_id');
            $table->decimal('price', 19, 4)->nullable();
            $table->dateTime('created');
            $table->dateTime('modified');
        });

        Schema::create('promotion_type', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->integer('discount_type');
            $table->float('discount_value');
            $table->string('day_value');
            $table->integer('day_type');
            $table->boolean('all_orders')->default(true);
            $table->boolean('special')->default(false);
            $table->boolean('price_multiple')->default(false);
            $table
                ->decimal('min_order_value', 19, 4)
                ->nullable()
                ->comment(
                    'it applies to only all order is yes and the value might be order total or the sum of tax and nontax'
                );
            $table
                ->enum('applies_to', ['', 'order', 'category', 'product', 'variant', 'option', 'selection'])
                ->nullable()
                ->default('');
            $table
                ->string('spl_condition')
                ->nullable()
                ->comment('add some specific string to handle the logic. Ex : $2 off to specific product');
            $table->string('product_type')->nullable()->comment('single,multiple');
            $table->text('notes')->nullable()->comment('what is the purpose of this promotion type');
            $table->unsignedTinyInteger('free_delivery')->default(0);
            $table->unsignedTinyInteger('free_tax')->default(0);
            $table->dateTime('created');
            $table->dateTime('modified');
            $table->unsignedTinyInteger('status')->default(1);
        });

        Schema::create('promotion_type_menu', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('promotion_type_id');
            $table->integer('menu_id');
            $table->float('price');
            $table->dateTime('created');
            $table->dateTime('modified');
        });

        Schema::create('promotion_type_product', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedInteger('promotion_type_id');
            $table->unsignedInteger('product_id')->index('product_id');
            $table->unsignedInteger('product_variant_id')->nullable()->index('product_variant_id');
            $table->float('price');
            $table->dateTime('created')->nullable();
            $table->dateTime('modified')->nullable();
        });

        Schema::create('prospect_logs', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('prospect_id');
            $table->dateTime('date');
            $table->enum('type', ['killed', 'restored'])->default('killed');
            $table->integer('user_id');
            $table->dateTime('created');
            $table->dateTime('modified');
            $table->string('cafe_num');
        });

        Schema::create('referral_sales_areas', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('sales_area_id');
            $table->unsignedTinyInteger('available')->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('rewards', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id');
            $table->integer('cart_id')->nullable();
            $table->integer('order_id')->nullable();
            $table->integer('cafe_id')->nullable();
            $table->integer('customer_referral_id')->nullable();
            $table
                ->decimal('reward_value', 19, 4)
                ->comment('this amount is a total rewards of the particular order item total');
            $table->string('order_status')->nullable();
            $table->integer('paid_status')->default(0);
            $table
                ->decimal('paid_reward_value', 19, 4)
                ->nullable()
                ->default(0)
                ->comment('this amount is paid from the reward value');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('rewards_to_amazon', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id');
            $table->string('customer_amazon_email')->nullable();
            $table->decimal('cash_out_amount', 19, 4)->comment('this amount is sum of cash out');
            $table->integer('amazon_log_id')->nullable();
            $table->string('amazon_request')->nullable();
            $table
                ->unsignedTinyInteger('is_referral_rewards')
                ->default(0)
                ->comment('0:alonti order rewards, 1:alonti referral rewards');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('sales_areas', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 50)->nullable();
            $table->integer('cafenum')->nullable();
        });

        Schema::create('schedule', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('from_station_id', 100);
            $table->string('to_station_id', 100);
            $table->integer('sequence')->nullable();
            $table->integer('train_id');
        });

        Schema::create('selections', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('option_id')->nullable();
            $table->string('selection', 50)->nullable();
            $table->integer('chargeone')->nullable();
            $table->decimal('price', 19, 4)->nullable();
            $table->integer('display_order')->nullable();
            $table->tinyInteger('available')->default(0);
            $table->unsignedTinyInteger('free')->default(0);
        });

        Schema::create('serving_options', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->float('price');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('created_by')->nullable()->index('created_by');
            $table->integer('updated_by')->nullable()->index('updated_by');
            $table->timestamp('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->integer('deleted_by')->nullable()->index('deleted_by');
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->integer('id', true);
            $table->enum('google_status', ['active', 'inactive'])->default('inactive');
            $table->string('google_app_id')->nullable();
            $table->string('google_secret')->nullable();
            $table->enum('facebook_status', ['active', 'inactive'])->default('inactive');
            $table->string('facebook_app_id')->nullable();
            $table->string('facebook_secret')->nullable();
            $table->enum('linkedin_status', ['active', 'inactive'])->default('inactive');
            $table->string('linkedin_app_id')->nullable();
            $table->string('linkedin_secret')->nullable();
            $table->enum('twitter_status', ['active', 'inactive'])->default('inactive');
            $table->string('twitter_app_id')->nullable();
            $table->string('twitter_secret')->nullable();
            $table->string('paytrace_password')->nullable()->default('Z2VvcmdlODYj');
            $table
                ->decimal('amazon_reward_min_spend', 10)
                ->comment('The minimum spend required to use Amazon Reward as a payment method.');
        });

        Schema::create('shopping_carts', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('session_id')->nullable();
            $table->integer('selection_id')->nullable();
            $table->decimal('quantity', 19);
            $table->integer('mastermenu_price_id')->nullable();
            $table->dateTime('date')->nullable();
            $table->integer('menu_id')->nullable();
            $table->integer('sel_1')->nullable();
            $table->integer('sel_2')->nullable();
            $table->integer('sel_3')->nullable();
            $table->integer('sel_4')->nullable();
            $table->string('comments', 250)->nullable();
            $table->float('discount')->nullable();
            $table->unsignedTinyInteger('free_delivery')->default(0);
            $table->integer('item_id')->nullable();
        });

        Schema::create('social_signups', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id')->nullable()->index('user_id');
            $table->string('social_id')->nullable();
            $table->string('social_service')->nullable();
        });

        Schema::create('special_offers', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedTinyInteger('active')->default(1);
            $table->string('firstname', 50);
            $table->string('lastname', 50);
            $table->string('company', 100);
            $table->string('email', 80);
            $table->string('deliveryzipcode', 20);
            $table->enum('email_bounce', ['no', 'yes']);
            $table->integer('cafe_id')->nullable();
            $table->dateTime('last_updated')->nullable();
            $table->string('phone', 50)->nullable();
            $table->integer('is_catering_coach')->nullable();
        });

        Schema::create('special_offers_test', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedTinyInteger('active')->default(1);
            $table->string('firstname', 50);
            $table->string('lastname', 50);
            $table->string('company', 100);
            $table->string('email', 80);
            $table->string('deliveryzipcode', 20);
            $table->enum('email_bounce', ['no', 'yes']);
        });

        Schema::create('states', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('country_id');
            $table->string('name');
            $table->string('code');
            $table->boolean('status')->default(false);
        });

        Schema::create('subcategories', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('alonti_library_id')->nullable();
            $table->string('scat', 50)->nullable();
            $table->string('permission', 10)->nullable();
            $table->integer('sort');
            $table->string('hidden', 3)->nullable();
        });

        Schema::create('tax_rates', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('zipcode', 10)->nullable();
            $table->float('rate', 24)->nullable();
        });

        Schema::create('tb_fixemail', function (Blueprint $table) {
            $table->string('id', 64)->nullable()->unique('id');
            $table->integer('userID')->nullable();
            $table->bigInteger('profileID')->nullable();
        });

        Schema::create('times', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('time', 50)->nullable();
            $table->integer('sort')->nullable();
            $table->integer('night_time')->default(0)->comment('1 = night time, 0 = day time');
        });

        Schema::create('todo_list', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('creator_id');
            $table->string('todo_detail');
            $table->string('todo_type');
            $table->dateTime('todo_date')->nullable();
            $table->enum('status', ['ToDo', 'Resolved'])->default('ToDo');
            $table->dateTime('created');
            $table->dateTime('modified');
        });

        Schema::create('trains', function (Blueprint $table) {
            $table->integer('id')->nullable();
            $table->string('from_station', 10)->nullable();
            $table->string('to_station', 10)->nullable();
            $table->integer('train_id')->nullable();
        });

        Schema::create('unsubscribes', function (Blueprint $table) {
            $table->integer('unsubscribe_id');
            $table->string('unsubscribe_email', 100)->nullable();
            $table->dateTime('unsubscribe_date')->nullable();
        });

        Schema::create('user_configurations', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id');
            $table->unsignedTinyInteger('alonti_rewards')->default(0)->comment('0:no,1:yes');
            $table->string('reward_email')->nullable();
            $table->unsignedTinyInteger('created_by')->default(0)->comment('0:customer,1:admin');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('user_credit_card_address', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_cc_id');
            $table->string('name', 256)->nullable();
            $table->string('address', 256)->nullable();
            $table->string('city', 256)->nullable();
            $table->string('state', 256)->nullable();
            $table->string('zipcode', 256)->nullable();
            $table->boolean('status')->default(true)->comment('1:active, 0:inactive');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('user_segmentations', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id')->nullable();
            $table->integer('prospect_id')->nullable();
            $table->integer('cafe_id')->nullable();
            $table->integer('cafenum')->nullable();
            $table->string('fname')->nullable();
            $table->string('lname')->nullable();
            $table->string('email')->nullable();
            $table->string('company')->nullable();
            $table->integer('company_id')->nullable();
            $table->string('physical_addr')->nullable();
            $table->string('physical_zip')->nullable();
            $table->string('phone')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table
                ->unsignedInteger('segmentation_type')
                ->default(1)
                ->comment(
                    '1:high-value-customer, 2:top-potential-customer, 3:customer, 4:old-customer, 5: prospect 6:other'
                );
            $table->string('orders_calendar_days')->nullable()->comment('orders placed in the selected period');
            $table
                ->integer('order_count')
                ->nullable()
                ->comment('order count calculated as per the segmentation and calendar days from current date');
            $table
                ->decimal('total_tax', 19)
                ->nullable()
                ->comment(
                    'delivered order total calculated as per the segmentation and calendar days from current date'
                );
            $table
                ->decimal('total_nontax', 19)
                ->nullable()
                ->comment(
                    'delivered order total calculated as per the segmentation and calendar days from current date'
                );
            $table
                ->decimal('offmenu_total', 19)
                ->nullable()
                ->comment(
                    'delivered order offmenu total calculated as per the segmentation and calendar days from current date'
                );
            $table
                ->decimal('net_sales', 19)
                ->nullable()
                ->comment(
                    'delivered order net sales calculated as per the segmentation and calendar days from current date'
                );
            $table
                ->decimal('order_total', 19)
                ->nullable()
                ->comment(
                    'delivered order total calculated as per the segmentation and calendar days from current date'
                );
            $table
                ->decimal('avg_order', 19)
                ->nullable()
                ->comment(
                    'delivered order total calculated as per the segmentation and calendar days from current date'
                );
            $table
                ->decimal('discount_percentage', 19)
                ->nullable()
                ->comment(
                    'delivered order total calculated as per the segmentation and calendar days from current date'
                );
            $table->string('last_order_date')->nullable()->comment('last order date');
            $table->string('last_delivered_date')->nullable()->comment('last delivered order date');
            $table->string('unsubscribe_promotion')->nullable();
            $table->string('email_bounce')->nullable();
            $table->string('restore_date')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->integer('district_id');
        });

        Schema::create('user_tracks', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id')->nullable();
            $table->integer('prospect_id')->nullable();
            $table->string('contoller', 256)->nullable();
            $table->string('action', 256)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->integer('admin_id')->nullable();
            $table->string('admin_name')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip', 10)->nullable();
            $table->string('phone')->nullable();
            $table->string('vendor_number')->nullable();
        });

        Schema::create('website_banner_settings', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('link_text')->nullable();
            $table->string('link_url', 500)->nullable();
            $table->string('banner_image', 500)->nullable();
            $table->string('image_url', 500)->nullable();
            $table->integer('updated_by')->nullable()->index('updated_by');
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('zip_code_lat_lon', function (Blueprint $table) {
            $table->integer('ZipCodeID', true);
            $table->string('zipcode', 16);
            $table->string('Latitude', 50);
            $table->string('Longitude', 50);
        });

        Schema::create('zip_codes', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('zipcode', 53)->nullable();
            $table->integer('cafe_id')->nullable();
            $table->integer('district_id')->nullable();
            $table->integer('state_id');
            $table->unsignedTinyInteger('priority')->default(1);
            $table->dateTime('last_updated')->nullable();
            $table->unsignedTinyInteger('status')->default(1)->comment('1: active, 0: in-active');
            $table->float('rate')->nullable();
        });

        Schema::create('zipcodelatlon', function (Blueprint $table) {
            $table->integer('ZipCodeID', true);
            $table->string('ZipCode', 16);
            $table->string('Latitude', 50);
            $table->string('Longitude', 50);
        });

        Schema::table('api_logs', function (Blueprint $table) {
            $table
                ->foreign(['api_key_id'])
                ->references(['id'])
                ->on('api_keys')
                ->onUpdate('restrict')
                ->onDelete('restrict');
        });

        Schema::table('calender', function (Blueprint $table) {
            $table
                ->foreign(['created_by'], 'calender_ibfk_1')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('restrict')
                ->onDelete('restrict');
        });

        Schema::table('mx_prospects', function (Blueprint $table) {
            $table
                ->foreign(['created_by'], 'mx_prospects_ibfk_1')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('no action')
                ->onDelete('set null');
        });

        Schema::table('offmenus', function (Blueprint $table) {
            $table
                ->foreign(['serving_option_id'], 'offmenus_ibfk_1')
                ->references(['id'])
                ->on('serving_options')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table
                ->foreign(['cart_id'], 'offmenus_ibfk_2')
                ->references(['id'])
                ->on('oj_carts')
                ->onUpdate('restrict')
                ->onDelete('restrict');
        });

        Schema::table('oj_billing_address', function (Blueprint $table) {
            $table
                ->foreign(['cart_id'], 'billing_address_cart_id_foreign')
                ->references(['id'])
                ->on('oj_carts')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table
                ->foreign(['company_id'], 'billing_address_company_id_foreign')
                ->references(['id'])
                ->on('company_users')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table
                ->foreign(['industry_id'], 'billing_address_industry_id_foreign')
                ->references(['id'])
                ->on('industries')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        Schema::table('oj_cart_items', function (Blueprint $table) {
            $table
                ->foreign(['cart_id'], 'cart_items_cart_id_foreign')
                ->references(['id'])
                ->on('oj_carts')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table
                ->foreign(['category_id'], 'cart_items_category_id_foreign')
                ->references(['id'])
                ->on('oj_categories')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table
                ->foreign(['invitee_id'], 'cart_items_invitee_id_foreign')
                ->references(['id'])
                ->on('oj_invitees')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table
                ->foreign(['product_id'], 'cart_items_product_id_foreign')
                ->references(['id'])
                ->on('oj_products')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table
                ->foreign(['product_package_id'], 'cart_items_product_package_id_foreign')
                ->references(['id'])
                ->on('oj_package_sizes')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table
                ->foreign(['product_variant_id'], 'cart_items_product_variant_id_foreign')
                ->references(['id'])
                ->on('oj_product_variants')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table
                ->foreign(['state_price_id'], 'cart_items_state_price_id_foreign')
                ->references(['id'])
                ->on('oj_states_prices')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table
                ->foreign(['product_dietary_id'], 'oj_cart_items_ibfk_1')
                ->references(['id'])
                ->on('oj_dietaries')
                ->onUpdate('restrict')
                ->onDelete('restrict');
        });

        Schema::table('oj_cart_options', function (Blueprint $table) {
            $table
                ->foreign(['cart_item_id'], 'cart_options_cart_item_id_foreign')
                ->references(['id'])
                ->on('oj_cart_items')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table
                ->foreign(['state_price_id'], 'cart_options_state_price_id_foreign')
                ->references(['id'])
                ->on('oj_states_prices')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        Schema::table('oj_carts', function (Blueprint $table) {
            $table
                ->foreign(['group_order_id'], 'carts_group_order_id_foreign')
                ->references(['id'])
                ->on('oj_group_orders')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table
                ->foreign(['order_id'], 'carts_order_id_foreign')
                ->references(['id'])
                ->on('orders')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table
                ->foreign(['promotion_type_id'], 'carts_promotion_type_id_foreign')
                ->references(['id'])
                ->on('promotion_type')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        Schema::table('oj_categories', function (Blueprint $table) {
            $table
                ->foreign(['parent_id'], 'categories_parent_id_foreign')
                ->references(['id'])
                ->on('oj_categories')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        Schema::table('oj_group_orders', function (Blueprint $table) {
            $table
                ->foreign(['user_id'], 'group_order_user_id')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        Schema::table('oj_invitees', function (Blueprint $table) {
            $table
                ->foreign(['group_order_id'], 'invitees_group_order_id_foreign')
                ->references(['id'])
                ->on('oj_group_orders')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        Schema::table('oj_package_sizes', function (Blueprint $table) {
            $table
                ->foreign(['product_variant_id'], 'package_sizes_product_variant_id_foreign')
                ->references(['id'])
                ->on('oj_product_variants')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        Schema::table('oj_product_add_ons', function (Blueprint $table) {
            $table
                ->foreign(['addon_product_id'], 'oj_product_add_ons_ibfk_1')
                ->references(['id'])
                ->on('oj_products')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table
                ->foreign(['product_variant_id'], 'oj_product_add_ons_ibfk_2')
                ->references(['id'])
                ->on('oj_product_variants')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table
                ->foreign(['product_id'], 'product_add_ons_product_id_foreign')
                ->references(['id'])
                ->on('oj_products')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        Schema::table('oj_product_dietaries', function (Blueprint $table) {
            $table
                ->foreign(['dietary_id'], 'product_dietaries_dietary_id_foreign')
                ->references(['id'])
                ->on('oj_dietaries')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table
                ->foreign(['product_id'], 'product_dietaries_product_id_foreign')
                ->references(['id'])
                ->on('oj_products')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        Schema::table('oj_product_option_selections', function (Blueprint $table) {
            $table
                ->foreign(['product_option_id'], 'product_option_selections_product_option_id_foreign')
                ->references(['id'])
                ->on('oj_product_options')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table
                ->foreign(['product_selection_id'], 'product_option_selections_product_selection_id_foreign')
                ->references(['id'])
                ->on('oj_product_selections')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        Schema::table('oj_product_options', function (Blueprint $table) {
            $table
                ->foreign(['product_id'], 'oj_product_options_ibfk_1')
                ->references(['id'])
                ->on('oj_products')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table
                ->foreign(['product_variant_id'], 'product_options_product_variant_id_foreign')
                ->references(['id'])
                ->on('oj_product_variants')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        Schema::table('oj_product_selection_dietaries', function (Blueprint $table) {
            $table
                ->foreign(['product_selection_id'], 'oj_product_selection_dietaries_ibfk_1')
                ->references(['id'])
                ->on('oj_product_selections')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table
                ->foreign(['dietary_id'], 'oj_product_selection_dietaries_ibfk_2')
                ->references(['id'])
                ->on('oj_dietaries')
                ->onUpdate('restrict')
                ->onDelete('restrict');
        });

        Schema::table('oj_product_variants', function (Blueprint $table) {
            $table
                ->foreign(['product_id'], 'product_variants_product_id_foreign')
                ->references(['id'])
                ->on('oj_products')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        Schema::table('oj_products', function (Blueprint $table) {
            $table
                ->foreign(['category_id'], 'products_category_id_foreign')
                ->references(['id'])
                ->on('oj_categories')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        Schema::table('oj_shipping_address', function (Blueprint $table) {
            $table
                ->foreign(['cafe_id'], 'shipping_address_cafe_id_foreign')
                ->references(['id'])
                ->on('cafes')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table
                ->foreign(['cart_id'], 'shipping_address_cart_id_foreign')
                ->references(['id'])
                ->on('oj_carts')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table
                ->foreign(['company_id'], 'shipping_address_company_id_foreign')
                ->references(['id'])
                ->on('company_users')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        Schema::table('paytrace_settings', function (Blueprint $table) {
            $table
                ->foreign(['updated_by'], 'paytrace_settings_ibfk_1')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('no action')
                ->onDelete('no action');
        });

        Schema::table('prep_categories', function (Blueprint $table) {
            $table
                ->foreign(['created_by'], 'prep_categories_ibfk_1')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table
                ->foreign(['updated_by'], 'prep_categories_ibfk_2')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table
                ->foreign(['deleted_by'], 'prep_categories_ibfk_3')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('restrict')
                ->onDelete('restrict');
        });

        Schema::table('prep_item_station', function (Blueprint $table) {
            $table
                ->foreign(['prep_category_id'], 'prep_item_station_ibfk_1')
                ->references(['id'])
                ->on('prep_categories')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table
                ->foreign(['prep_item_id'], 'prep_item_station_ibfk_2')
                ->references(['id'])
                ->on('prep_items')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table
                ->foreign(['prep_station_id'], 'prep_item_station_ibfk_3')
                ->references(['id'])
                ->on('prep_stations')
                ->onUpdate('restrict')
                ->onDelete('restrict');
        });

        Schema::table('prep_items', function (Blueprint $table) {
            $table
                ->foreign(['created_by'], 'prep_items_ibfk_1')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table
                ->foreign(['updated_by'], 'prep_items_ibfk_2')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table
                ->foreign(['deleted_by'], 'prep_items_ibfk_3')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('restrict')
                ->onDelete('restrict');
        });

        Schema::table('prep_package_deal_indexes', function (Blueprint $table) {
            $table
                ->foreign(['created_by'], 'prep_package_deal_indexes_ibfk_1')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table
                ->foreign(['updated_by'], 'prep_package_deal_indexes_ibfk_2')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table
                ->foreign(['deleted_by'], 'prep_package_deal_indexes_ibfk_3')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('restrict')
                ->onDelete('restrict');
        });

        Schema::table('prep_product_option_selection_priorities', function (Blueprint $table) {
            $table
                ->foreign(['product_option_id'], 'prep_product_option_selection_priorities_ibfk_1')
                ->references(['id'])
                ->on('oj_product_options')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table
                ->foreign(['selection_id'], 'prep_product_option_selection_priorities_ibfk_2')
                ->references(['id'])
                ->on('oj_product_selections')
                ->onUpdate('restrict')
                ->onDelete('restrict');
        });

        Schema::table('prep_sheet_mappings', function (Blueprint $table) {
            $table
                ->foreign(['variant_id'], 'prep_sheet_mappings_ibfk_1')
                ->references(['id'])
                ->on('oj_product_variants')
                ->onUpdate('no action')
                ->onDelete('set null');
            $table
                ->foreign(['selection_id'], 'prep_sheet_mappings_ibfk_2')
                ->references(['id'])
                ->on('oj_product_selections')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table
                ->foreign(['prep_category_id'], 'prep_sheet_mappings_ibfk_3')
                ->references(['id'])
                ->on('prep_categories')
                ->onUpdate('no action')
                ->onDelete('set null');
            $table
                ->foreign(['prep_station_id'], 'prep_sheet_mappings_ibfk_4')
                ->references(['id'])
                ->on('prep_stations')
                ->onUpdate('no action')
                ->onDelete('set null');
            $table
                ->foreign(['prep_item_id'], 'prep_sheet_mappings_ibfk_5')
                ->references(['id'])
                ->on('prep_items')
                ->onUpdate('no action')
                ->onDelete('set null');
            $table
                ->foreign(['created_by'], 'prep_sheet_mappings_ibfk_6')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table
                ->foreign(['updated_by'], 'prep_sheet_mappings_ibfk_7')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table
                ->foreign(['deleted_by'], 'prep_sheet_mappings_ibfk_8')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('restrict')
                ->onDelete('restrict');
        });

        Schema::table('prep_sheets', function (Blueprint $table) {
            $table
                ->foreign(['created_by'], 'prep_sheets_ibfk_1')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table
                ->foreign(['updated_by'], 'prep_sheets_ibfk_2')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table
                ->foreign(['deleted_by'], 'prep_sheets_ibfk_3')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('restrict')
                ->onDelete('restrict');
        });

        Schema::table('prep_stations', function (Blueprint $table) {
            $table
                ->foreign(['prep_category_id'], 'prep_stations_ibfk_1')
                ->references(['id'])
                ->on('prep_categories')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table
                ->foreign(['created_by'], 'prep_stations_ibfk_2')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table
                ->foreign(['updated_by'], 'prep_stations_ibfk_3')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table
                ->foreign(['deleted_by'], 'prep_stations_ibfk_4')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('restrict')
                ->onDelete('restrict');
        });

        Schema::table('promotion_type_product', function (Blueprint $table) {
            $table
                ->foreign(['product_id'], 'promotion_type_product_ibfk_1')
                ->references(['id'])
                ->on('oj_products')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table
                ->foreign(['product_variant_id'], 'promotion_type_product_ibfk_2')
                ->references(['id'])
                ->on('oj_product_variants')
                ->onUpdate('restrict')
                ->onDelete('restrict');
        });

        Schema::table('serving_options', function (Blueprint $table) {
            $table
                ->foreign(['created_by'], 'serving_options_ibfk_1')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table
                ->foreign(['updated_by'], 'serving_options_ibfk_2')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table
                ->foreign(['deleted_by'], 'serving_options_ibfk_3')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('restrict')
                ->onDelete('restrict');
        });

        Schema::table('social_signups', function (Blueprint $table) {
            $table
                ->foreign(['user_id'], 'social_signups_ibfk_1')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('restrict')
                ->onDelete('restrict');
        });

        Schema::table('website_banner_settings', function (Blueprint $table) {
            $table
                ->foreign(['updated_by'], 'website_banner_settings_ibfk_1')
                ->references(['id'])
                ->on('alonti_users')
                ->onUpdate('no action')
                ->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('website_banner_settings', function (Blueprint $table) {
            $table->dropForeign('website_banner_settings_ibfk_1');
        });

        Schema::table('social_signups', function (Blueprint $table) {
            $table->dropForeign('social_signups_ibfk_1');
        });

        Schema::table('serving_options', function (Blueprint $table) {
            $table->dropForeign('serving_options_ibfk_1');
            $table->dropForeign('serving_options_ibfk_2');
            $table->dropForeign('serving_options_ibfk_3');
        });

        Schema::table('promotion_type_product', function (Blueprint $table) {
            $table->dropForeign('promotion_type_product_ibfk_1');
            $table->dropForeign('promotion_type_product_ibfk_2');
        });

        Schema::table('prep_stations', function (Blueprint $table) {
            $table->dropForeign('prep_stations_ibfk_1');
            $table->dropForeign('prep_stations_ibfk_2');
            $table->dropForeign('prep_stations_ibfk_3');
            $table->dropForeign('prep_stations_ibfk_4');
        });

        Schema::table('prep_sheets', function (Blueprint $table) {
            $table->dropForeign('prep_sheets_ibfk_1');
            $table->dropForeign('prep_sheets_ibfk_2');
            $table->dropForeign('prep_sheets_ibfk_3');
        });

        Schema::table('prep_sheet_mappings', function (Blueprint $table) {
            $table->dropForeign('prep_sheet_mappings_ibfk_1');
            $table->dropForeign('prep_sheet_mappings_ibfk_2');
            $table->dropForeign('prep_sheet_mappings_ibfk_3');
            $table->dropForeign('prep_sheet_mappings_ibfk_4');
            $table->dropForeign('prep_sheet_mappings_ibfk_5');
            $table->dropForeign('prep_sheet_mappings_ibfk_6');
            $table->dropForeign('prep_sheet_mappings_ibfk_7');
            $table->dropForeign('prep_sheet_mappings_ibfk_8');
        });

        Schema::table('prep_product_option_selection_priorities', function (Blueprint $table) {
            $table->dropForeign('prep_product_option_selection_priorities_ibfk_1');
            $table->dropForeign('prep_product_option_selection_priorities_ibfk_2');
        });

        Schema::table('prep_package_deal_indexes', function (Blueprint $table) {
            $table->dropForeign('prep_package_deal_indexes_ibfk_1');
            $table->dropForeign('prep_package_deal_indexes_ibfk_2');
            $table->dropForeign('prep_package_deal_indexes_ibfk_3');
        });

        Schema::table('prep_items', function (Blueprint $table) {
            $table->dropForeign('prep_items_ibfk_1');
            $table->dropForeign('prep_items_ibfk_2');
            $table->dropForeign('prep_items_ibfk_3');
        });

        Schema::table('prep_item_station', function (Blueprint $table) {
            $table->dropForeign('prep_item_station_ibfk_1');
            $table->dropForeign('prep_item_station_ibfk_2');
            $table->dropForeign('prep_item_station_ibfk_3');
        });

        Schema::table('prep_categories', function (Blueprint $table) {
            $table->dropForeign('prep_categories_ibfk_1');
            $table->dropForeign('prep_categories_ibfk_2');
            $table->dropForeign('prep_categories_ibfk_3');
        });

        Schema::table('paytrace_settings', function (Blueprint $table) {
            $table->dropForeign('paytrace_settings_ibfk_1');
        });

        Schema::table('oj_shipping_address', function (Blueprint $table) {
            $table->dropForeign('shipping_address_cafe_id_foreign');
            $table->dropForeign('shipping_address_cart_id_foreign');
            $table->dropForeign('shipping_address_company_id_foreign');
        });

        Schema::table('oj_products', function (Blueprint $table) {
            $table->dropForeign('products_category_id_foreign');
        });

        Schema::table('oj_product_variants', function (Blueprint $table) {
            $table->dropForeign('product_variants_product_id_foreign');
        });

        Schema::table('oj_product_selection_dietaries', function (Blueprint $table) {
            $table->dropForeign('oj_product_selection_dietaries_ibfk_1');
            $table->dropForeign('oj_product_selection_dietaries_ibfk_2');
        });

        Schema::table('oj_product_options', function (Blueprint $table) {
            $table->dropForeign('oj_product_options_ibfk_1');
            $table->dropForeign('product_options_product_variant_id_foreign');
        });

        Schema::table('oj_product_option_selections', function (Blueprint $table) {
            $table->dropForeign('product_option_selections_product_option_id_foreign');
            $table->dropForeign('product_option_selections_product_selection_id_foreign');
        });

        Schema::table('oj_product_dietaries', function (Blueprint $table) {
            $table->dropForeign('product_dietaries_dietary_id_foreign');
            $table->dropForeign('product_dietaries_product_id_foreign');
        });

        Schema::table('oj_product_add_ons', function (Blueprint $table) {
            $table->dropForeign('oj_product_add_ons_ibfk_1');
            $table->dropForeign('oj_product_add_ons_ibfk_2');
            $table->dropForeign('product_add_ons_product_id_foreign');
        });

        Schema::table('oj_package_sizes', function (Blueprint $table) {
            $table->dropForeign('package_sizes_product_variant_id_foreign');
        });

        Schema::table('oj_invitees', function (Blueprint $table) {
            $table->dropForeign('invitees_group_order_id_foreign');
        });

        Schema::table('oj_group_orders', function (Blueprint $table) {
            $table->dropForeign('group_order_user_id');
        });

        Schema::table('oj_categories', function (Blueprint $table) {
            $table->dropForeign('categories_parent_id_foreign');
        });

        Schema::table('oj_carts', function (Blueprint $table) {
            $table->dropForeign('carts_group_order_id_foreign');
            $table->dropForeign('carts_order_id_foreign');
            $table->dropForeign('carts_promotion_type_id_foreign');
        });

        Schema::table('oj_cart_options', function (Blueprint $table) {
            $table->dropForeign('cart_options_cart_item_id_foreign');
            $table->dropForeign('cart_options_state_price_id_foreign');
        });

        Schema::table('oj_cart_items', function (Blueprint $table) {
            $table->dropForeign('cart_items_cart_id_foreign');
            $table->dropForeign('cart_items_category_id_foreign');
            $table->dropForeign('cart_items_invitee_id_foreign');
            $table->dropForeign('cart_items_product_id_foreign');
            $table->dropForeign('cart_items_product_package_id_foreign');
            $table->dropForeign('cart_items_product_variant_id_foreign');
            $table->dropForeign('cart_items_state_price_id_foreign');
            $table->dropForeign('oj_cart_items_ibfk_1');
        });

        Schema::table('oj_billing_address', function (Blueprint $table) {
            $table->dropForeign('billing_address_cart_id_foreign');
            $table->dropForeign('billing_address_company_id_foreign');
            $table->dropForeign('billing_address_industry_id_foreign');
        });

        Schema::table('offmenus', function (Blueprint $table) {
            $table->dropForeign('offmenus_ibfk_1');
            $table->dropForeign('offmenus_ibfk_2');
        });

        Schema::table('mx_prospects', function (Blueprint $table) {
            $table->dropForeign('mx_prospects_ibfk_1');
        });

        Schema::table('calender', function (Blueprint $table) {
            $table->dropForeign('calender_ibfk_1');
        });

        Schema::table('api_logs', function (Blueprint $table) {
            $table->dropForeign('api_logs_api_key_id_foreign');
        });

        Schema::dropIfExists('zipcodelatlon');

        Schema::dropIfExists('zip_codes');

        Schema::dropIfExists('zip_code_lat_lon');

        Schema::dropIfExists('website_banner_settings');

        Schema::dropIfExists('vendors');

        Schema::dropIfExists('user_tracks');

        Schema::dropIfExists('user_segmentations');

        Schema::dropIfExists('user_credit_card_address');

        Schema::dropIfExists('user_configurations');

        Schema::dropIfExists('unsubscribes');

        Schema::dropIfExists('trains');

        Schema::dropIfExists('todo_list');

        Schema::dropIfExists('times');

        Schema::dropIfExists('tb_fixemail');

        Schema::dropIfExists('tax_rates');

        Schema::dropIfExists('subcategories');

        Schema::dropIfExists('states');

        Schema::dropIfExists('special_offers_test');

        Schema::dropIfExists('special_offers');

        Schema::dropIfExists('social_signups');

        Schema::dropIfExists('shopping_carts');

        Schema::dropIfExists('settings');

        Schema::dropIfExists('serving_options');

        Schema::dropIfExists('selections');

        Schema::dropIfExists('schedule');

        Schema::dropIfExists('sales_areas');

        Schema::dropIfExists('rewards_to_amazon');

        Schema::dropIfExists('rewards');

        Schema::dropIfExists('referral_sales_areas');

        Schema::dropIfExists('prospect_logs');

        Schema::dropIfExists('promotion_type_product');

        Schema::dropIfExists('promotion_type_menu');

        Schema::dropIfExists('promotion_type');

        Schema::dropIfExists('promotion_product_selections');

        Schema::dropIfExists('prep_stations');

        Schema::dropIfExists('prep_sheets');

        Schema::dropIfExists('prep_sheet_mappings');

        Schema::dropIfExists('prep_product_option_selection_priorities');

        Schema::dropIfExists('prep_package_deal_indexes');

        Schema::dropIfExists('prep_items');

        Schema::dropIfExists('prep_item_station');

        Schema::dropIfExists('prep_categories');

        Schema::dropIfExists('phinxlog');

        Schema::dropIfExists('permissions');

        Schema::dropIfExists('paytrace_settings');

        Schema::dropIfExists('paytrace_api_logs');

        Schema::dropIfExists('payments');

        Schema::dropIfExists('paidouts');

        Schema::dropIfExists('paid_tracks');

        Schema::dropIfExists('orders_back');

        Schema::dropIfExists('orders');

        Schema::dropIfExists('order_tracks');

        Schema::dropIfExists('options');

        Schema::dropIfExists('oj_unique_urls');

        Schema::dropIfExists('oj_states_prices');

        Schema::dropIfExists('oj_shipping_address');

        Schema::dropIfExists('oj_products');

        Schema::dropIfExists('oj_product_variants');

        Schema::dropIfExists('oj_product_selections');

        Schema::dropIfExists('oj_product_selection_dietaries');

        Schema::dropIfExists('oj_product_options');

        Schema::dropIfExists('oj_product_option_selections');

        Schema::dropIfExists('oj_product_dietaries');

        Schema::dropIfExists('oj_product_add_ons');

        Schema::dropIfExists('oj_package_sizes');

        Schema::dropIfExists('oj_menu_maps');

        Schema::dropIfExists('oj_invitees');

        Schema::dropIfExists('oj_invitee_reponses');

        Schema::dropIfExists('oj_images');

        Schema::dropIfExists('oj_group_orders');

        Schema::dropIfExists('oj_dietaries');

        Schema::dropIfExists('oj_categories');

        Schema::dropIfExists('oj_carts');

        Schema::dropIfExists('oj_cart_options');

        Schema::dropIfExists('oj_cart_items');

        Schema::dropIfExists('oj_cart_invitees');

        Schema::dropIfExists('oj_billing_address');

        Schema::dropIfExists('offmenus');

        Schema::dropIfExists('offmenu_credits');

        Schema::dropIfExists('notes');

        Schema::dropIfExists('mx_prospects');

        Schema::dropIfExists('menus');

        Schema::dropIfExists('menu_state_prices');

        Schema::dropIfExists('menu_extra_items');

        Schema::dropIfExists('menu_download_settings');

        Schema::dropIfExists('mastermenus');

        Schema::dropIfExists('mastermenu_prices');

        Schema::dropIfExists('markets');

        Schema::dropIfExists('market_access');

        Schema::dropIfExists('log_pageloadtimes');

        Schema::dropIfExists('log_changedemails');

        Schema::dropIfExists('laravel_success_jobs');

        Schema::dropIfExists('laravel_jobs');

        Schema::dropIfExists('laravel_failed_jobs');

        Schema::dropIfExists('labor_goals');

        Schema::dropIfExists('jobs');

        Schema::dropIfExists('items');

        Schema::dropIfExists('item_options');

        Schema::dropIfExists('invoices');

        Schema::dropIfExists('invoice_details');

        Schema::dropIfExists('inputs');

        Schema::dropIfExists('industries');

        Schema::dropIfExists('groups');

        Schema::dropIfExists('group_order_configuration_tracks');

        Schema::dropIfExists('group_order_configuration');

        Schema::dropIfExists('goals');

        Schema::dropIfExists('gl_codes');

        Schema::dropIfExists('food_available_stores');

        Schema::dropIfExists('follow_ups');

        Schema::dropIfExists('ezcater_webhook_logs');

        Schema::dropIfExists('expenses');

        Schema::dropIfExists('exceptions');

        Schema::dropIfExists('et_paidouts');

        Schema::dropIfExists('et_paidout');

        Schema::dropIfExists('et_invoices_details');

        Schema::dropIfExists('et_invoices');

        Schema::dropIfExists('et_invoicedetails');

        Schema::dropIfExists('et_inventory');

        Schema::dropIfExists('et_inventories');

        Schema::dropIfExists('et_inputs');

        Schema::dropIfExists('et_input');

        Schema::dropIfExists('et_coglist');

        Schema::dropIfExists('et_cog_list');

        Schema::dropIfExists('email_templates');

        Schema::dropIfExists('email_sent_logs');

        Schema::dropIfExists('email_queue_phinxlog');

        Schema::dropIfExists('email_queue');

        Schema::dropIfExists('email_logs');

        Schema::dropIfExists('email_gallaries');

        Schema::dropIfExists('email_campaigns');

        Schema::dropIfExists('email_campaign_images');

        Schema::dropIfExists('email_bounces');

        Schema::dropIfExists('documents');

        Schema::dropIfExists('districts');

        Schema::dropIfExists('districtgoals');

        Schema::dropIfExists('district_access');

        Schema::dropIfExists('disable_dates');

        Schema::dropIfExists('directors');

        Schema::dropIfExists('customermenus');

        Schema::dropIfExists('customer_referrals');

        Schema::dropIfExists('customer_notes');

        Schema::dropIfExists('csm_da');

        Schema::dropIfExists('coupons');

        Schema::dropIfExists('coupon_cafe');

        Schema::dropIfExists('countries');

        Schema::dropIfExists('configurations');

        Schema::dropIfExists('company_users');

        Schema::dropIfExists('company_payment');

        Schema::dropIfExists('company_goals');

        Schema::dropIfExists('coglist');

        Schema::dropIfExists('cog_list');

        Schema::dropIfExists('cities');

        Schema::dropIfExists('cims');

        Schema::dropIfExists('cim_payment_profiles');

        Schema::dropIfExists('cim_paids');

        Schema::dropIfExists('cc_tables');

        Schema::dropIfExists('categories');

        Schema::dropIfExists('cart_tracks');

        Schema::dropIfExists('cart_options_tracks');

        Schema::dropIfExists('cart_items_tracks');

        Schema::dropIfExists('campaign_log');

        Schema::dropIfExists('calender_participants');

        Schema::dropIfExists('calender');

        Schema::dropIfExists('cafes');

        Schema::dropIfExists('cafegoals');

        Schema::dropIfExists('cafe_access');

        Schema::dropIfExists('aros_acos');

        Schema::dropIfExists('aros');

        Schema::dropIfExists('api_logs');

        Schema::dropIfExists('api_keys');

        Schema::dropIfExists('anticipatedelitestatus');

        Schema::dropIfExists('amazon_api_logs');

        Schema::dropIfExists('alonti_users');

        Schema::dropIfExists('alonti_libraries');

        Schema::dropIfExists('admin_access_tracks');

        Schema::dropIfExists('acumatica_data_upload_history');

        Schema::dropIfExists('acumatica_api_logs');

        Schema::dropIfExists('active_next_nearest_lists');

        Schema::dropIfExists('active_menus');

        Schema::dropIfExists('active_menu_categories');

        Schema::dropIfExists('acos');

        Schema::dropIfExists('acl_phinxlog');

        Schema::dropIfExists('abondand_cart');
    }
};
