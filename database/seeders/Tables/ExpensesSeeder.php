<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpensesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /**
         * Command :
         * artisan seed:generate --table-mode --all-tables --limit=500
         */
        $dataTables = [
            [
                'id' => 1,
                'expensecode' => 3300,
                'expensetype' => 'Bakery',
            ],
            [
                'id' => 2,
                'expensecode' => 3340,
                'expensetype' => 'Soda, Juice & Water',
            ],
            [
                'id' => 3,
                'expensecode' => 3355,
                'expensetype' => 'Dairy',
            ],
            [
                'id' => 4,
                'expensecode' => 3360,
                'expensetype' => 'Produce',
            ],
            [
                'id' => 5,
                'expensecode' => 3380,
                'expensetype' => 'Food Miscellaneous',
            ],
            [
                'id' => 6,
                'expensecode' => 3390,
                'expensetype' => 'Coffee',
            ],
            [
                'id' => 7,
                'expensecode' => 3410,
                'expensetype' => 'Meat',
            ],
            [
                'id' => 8,
                'expensecode' => '1320-00-000',
                'expensetype' => 'Employee Advance',
            ],
            [
                'id' => 9,
                'expensecode' => 5001,
                'expensetype' => 'Ice',
            ],
            [
                'id' => 10,
                'expensecode' => 5500,
                'expensetype' => 'Paper Products',
            ],
            [
                'id' => 11,
                'expensecode' => 5550,
                'expensetype' => 'Janitorial',
            ],
            [
                'id' => 12,
                'expensecode' => 5600,
                'expensetype' => 'Smallwares',
            ],
            [
                'id' => 13,
                'expensecode' => 5710,
                'expensetype' => 'Office Supplies',
            ],
            [
                'id' => 14,
                'expensecode' => 6000,
                'expensetype' => 'Maintenance Routine',
            ],
            [
                'id' => 15,
                'expensecode' => 6200,
                'expensetype' => 'Maintenance Special',
            ],
            [
                'id' => 16,
                'expensecode' => 6300,
                'expensetype' => 'License & Permit',
            ],
            [
                'id' => 17,
                'expensecode' => 6310,
                'expensetype' => 'Parking',
            ],
            [
                'id' => 18,
                'expensecode' => 6320,
                'expensetype' => 'Van Expense',
            ],
            [
                'id' => 20,
                'expensecode' => 5715,
                'expensetype' => 'Postage & Fedex',
            ],
            [
                'id' => 21,
                'expensecode' => 8005,
                'expensetype' => 'Advertising & Marketing',
            ],
            [
                'id' => 22,
                'expensecode' => 4031,
                'expensetype' => 'Tips',
            ],
            [
                'id' => 23,
                'expensecode' => 3330,
                'expensetype' => 'Beer & Wine',
            ],
            [
                'id' => 24,
                'expensecode' => '1352-11-001',
                'expensetype' => 'Due From Rutual',
            ],
            [
                'id' => 30,
                'expensecode' => 5720,
                'expensetype' => 'Uniforms',
            ],
            [
                'id' => 32,
                'expensecode' => '6325-11-033',
                'expensetype' => 'Taxi # 33',
            ],
            [
                'id' => 33,
                'expensecode' => '6310-01-81',
                'expensetype' => 'Parking - HOUSTON DM',
            ],
            [
                'id' => 34,
                'expensecode' => '6310-02-82',
                'expensetype' => 'Parking - DALLAS DM',
            ],
            [
                'id' => 35,
                'expensecode' => '6310-03-83',
                'expensetype' => 'Parking - CHICAGO DM',
            ],
            [
                'id' => 37,
                'expensecode' => '6350-01-81',
                'expensetype' => 'Auto Expense - HOUSTON DISTRICT MGR',
            ],
            [
                'id' => 38,
                'expensecode' => '6350-02-82',
                'expensetype' => 'Auto Expense - DALLAS DISTRICT MGR',
            ],
            [
                'id' => 39,
                'expensecode' => '6350-03-83',
                'expensetype' => 'Auto Expense - CHICAGO DISTRICT MGR',
            ],
            [
                'id' => 40,
                'expensecode' => '5710-01-81',
                'expensetype' => 'Office Supplies - HOUSTON DM',
            ],
            [
                'id' => 41,
                'expensecode' => '5710-02-82',
                'expensetype' => 'Office Supplies - DALLAS DM',
            ],
            [
                'id' => 42,
                'expensecode' => '5710-03-83',
                'expensetype' => 'Office Supplies - CHICAGO DM',
            ],
            [
                'id' => 43,
                'expensecode' => 5750,
                'expensetype' => 'Employee Incentives',
            ],
            [
                'id' => 44,
                'expensecode' => 1510,
                'expensetype' => 'Equipment & Fixtures (Above $ 500 Only]',
            ],
            [
                'id' => 47,
                'expensecode' => 8800,
                'expensetype' => 'Dues & Subscriptions',
            ],
            [
                'id' => 48,
                'expensecode' => 6660,
                'expensetype' => 'Fines & Penalties',
            ],
            [
                'id' => 49,
                'expensecode' => 6650,
                'expensetype' => 'Catering Rental',
            ],
            [
                'id' => 50,
                'expensecode' => 8000,
                'expensetype' => 'Employment Advertising',
            ],
            [
                'id' => 53,
                'expensecode' => '9510-00-00',
                'expensetype' => 'Miscellaneous Income',
            ],
            [
                'id' => 55,
                'expensecode' => 1100,
                'expensetype' => 'Store Change Fund',
            ],
            [
                'id' => 56,
                'expensecode' => '6320-11-56',
                'expensetype' => 'Van Expense - #56',
            ],
            [
                'id' => 58,
                'expensecode' => '6310-11-56',
                'expensetype' => 'Parking - #56',
            ],
            [
                'id' => 59,
                'expensecode' => '8005-11-56',
                'expensetype' => 'Advertising & Marketing - 56',
            ],
            [
                'id' => 61,
                'expensecode' => '6350-02-12',
                'expensetype' => 'Auto Expense - #12',
            ],
            [
                'id' => 62,
                'expensecode' => 6350,
                'expensetype' => 'Auto Expense',
            ],
            [
                'id' => 63,
                'expensecode' => '5710-11-56',
                'expensetype' => 'Office Supplies - #56',
            ],
            [
                'id' => 64,
                'expensecode' => '6350-11-56',
                'expensetype' => 'Auto Expense -  #56',
            ],
            [
                'id' => 65,
                'expensecode' => '6310-01-18',
                'expensetype' => 'Parking - #18',
            ],
            [
                'id' => 66,
                'expensecode' => '3380-11-056',
                'expensetype' => 'Food Miscellaneous - #56',
            ],
            [
                'id' => 67,
                'expensecode' => '5710-02-57',
                'expensetype' => 'Office Supplies -  #57',
            ],
            [
                'id' => 69,
                'expensecode' => 9506,
                'expensetype' => 'Gift Certificates',
            ],
            [
                'id' => 70,
                'expensecode' => 6400,
                'expensetype' => 'Cash Over/Short',
            ],
            [
                'id' => 71,
                'expensecode' => '6350-11-58',
                'expensetype' => 'Auto Expense - #58',
            ],
            [
                'id' => 72,
                'expensecode' => '6000-02-57',
                'expensetype' => 'Maintenance Routine - #57',
            ],
            [
                'id' => 73,
                'expensecode' => '8005-01-81',
                'expensetype' => 'Advertising & Marketing - Houston',
            ],
            [
                'id' => 74,
                'expensecode' => '6320-03-59',
                'expensetype' => 'Van Expense - #59',
            ],
            [
                'id' => 75,
                'expensecode' => '3380-03-059',
                'expensetype' => 'Food Miscellaneous - #59',
            ],
            [
                'id' => 76,
                'expensecode' => '5500-03-059',
                'expensetype' => 'Paper Products - #59',
            ],
            [
                'id' => 77,
                'expensecode' => '5550-03-59',
                'expensetype' => 'Janitorial - #59',
            ],
            [
                'id' => 78,
                'expensecode' => '6310-03-59',
                'expensetype' => 'Parking -  #59',
            ],
            [
                'id' => 79,
                'expensecode' => '5710-03-59',
                'expensetype' => 'Office Supplies - #59',
            ],
            [
                'id' => 80,
                'expensecode' => '6325-03-59',
                'expensetype' => 'Taxi - #59',
            ],
            [
                'id' => 82,
                'expensecode' => 3950,
                'expensetype' => 'Employee Meals',
            ],
            [
                'id' => 88,
                'expensecode' => '3380-11-058',
                'expensetype' => 'Food Miscellaneous - #58',
            ],
            [
                'id' => 89,
                'expensecode' => '3340-03-059',
                'expensetype' => 'Soda, Juice & Water - #59',
            ],
            [
                'id' => 90,
                'expensecode' => '3380-12-062',
                'expensetype' => 'Food Miscellaneous - #62',
            ],
            [
                'id' => 91,
                'expensecode' => '3380-12-064',
                'expensetype' => 'Food Miscellaneous - #64',
            ],
            [
                'id' => 92,
                'expensecode' => '6350-03-59',
                'expensetype' => 'Auto Expense - #59',
            ],
            [
                'id' => 94,
                'expensecode' => '6605-00-00',
                'expensetype' => 'CSM Special Team Auto Expense',
            ],
            [
                'id' => 95,
                'expensecode' => '3380-03-067',
                'expensetype' => 'Food Miscellaneous - #67',
            ],
            [
                'id' => 96,
                'expensecode' => '6310-03-67',
                'expensetype' => 'Parking - #67',
            ],
            [
                'id' => 97,
                'expensecode' => '6350-03-67',
                'expensetype' => 'Auto Expense - #67',
            ],
            [
                'id' => 99,
                'expensecode' => '8005-03-67',
                'expensetype' => 'Advertising & Marketing - 67',
            ],
            [
                'id' => 100,
                'expensecode' => '5710-02-70',
                'expensetype' => 'Office Supplies -  #70',
            ],
            [
                'id' => 101,
                'expensecode' => '8901-00-00',
                'expensetype' => 'Corporate Travel - Meals',
            ],
            [
                'id' => 102,
                'expensecode' => '5710-15-68',
                'expensetype' => 'Office Supplies - #68',
            ],
            [
                'id' => 103,
                'expensecode' => '3380-02-070',
                'expensetype' => 'Food Miscellaneous - #70',
            ],
            [
                'id' => 104,
                'expensecode' => '5600-02-70',
                'expensetype' => 'Smallwares - #70',
            ],
            [
                'id' => 105,
                'expensecode' => '5500-03-067',
                'expensetype' => 'Paper Products - #67',
            ],
            [
                'id' => 106,
                'expensecode' => '5550-03-67',
                'expensetype' => 'Janitorial - #67',
            ],
            [
                'id' => 107,
                'expensecode' => '5750-03-67',
                'expensetype' => 'Employee Incentives - #67',
            ],
            [
                'id' => 108,
                'expensecode' => '5600-02-57',
                'expensetype' => 'Smallwares - #57',
            ],
            [
                'id' => 109,
                'expensecode' => '3380-02-057',
                'expensetype' => 'Food Miscellaneous - #57',
            ],
            [
                'id' => 110,
                'expensecode' => '5600-02-32',
                'expensetype' => 'Smallwares - #32',
            ],
            [
                'id' => 111,
                'expensecode' => '5600-02-53',
                'expensetype' => 'Smallwares - #53',
            ],
            [
                'id' => 112,
                'expensecode' => '8885-00-00',
                'expensetype' => 'Relocation Expense',
            ],
            [
                'id' => 114,
                'expensecode' => '8000-02-06',
                'expensetype' => 'Employment Advertising #06',
            ],
            [
                'id' => 115,
                'expensecode' => '8000-02-57',
                'expensetype' => 'Employment Advertising #57',
            ],
            [
                'id' => 116,
                'expensecode' => '8000-02-70',
                'expensetype' => 'Employment Advertising #70',
            ],
            [
                'id' => 117,
                'expensecode' => '6320-02-06',
                'expensetype' => 'Van Expense - #06',
            ],
            [
                'id' => 118,
                'expensecode' => '6320-02-57',
                'expensetype' => 'Van Expense - #57',
            ],
            [
                'id' => 119,
                'expensecode' => '6650-02-70',
                'expensetype' => 'Catering Rental - #70',
            ],
            [
                'id' => 120,
                'expensecode' => '5500-02-070',
                'expensetype' => 'Paper Products - #70',
            ],
            [
                'id' => 121,
                'expensecode' => '8000-02-53',
                'expensetype' => 'Employment Advertising #53',
            ],
            [
                'id' => 122,
                'expensecode' => '8000-12-60',
                'expensetype' => 'Employment Advertising #60',
            ],
            [
                'id' => 123,
                'expensecode' => '8000-12-62',
                'expensetype' => 'Employment Advertising #62',
            ],
            [
                'id' => 124,
                'expensecode' => '5600-02-06',
                'expensetype' => 'Smallwares - #06',
            ],
            [
                'id' => 125,
                'expensecode' => '8000-12-65',
                'expensetype' => 'Employment Advertising #65',
            ],
            [
                'id' => 126,
                'expensecode' => '8000-02-32',
                'expensetype' => 'Employment Advertising #32',
            ],
            [
                'id' => 127,
                'expensecode' => '5710-11-58',
                'expensetype' => 'Office Supplies - #58',
            ],
            [
                'id' => 128,
                'expensecode' => '5500-11-058',
                'expensetype' => 'Paper Products - #58',
            ],
            [
                'id' => 129,
                'expensecode' => '5600-11-58',
                'expensetype' => 'Smallwares - #58',
            ],
            [
                'id' => 130,
                'expensecode' => '3380-11-004',
                'expensetype' => 'Food Miscellaneous - #04',
            ],
            [
                'id' => 131,
                'expensecode' => '5710-01-04',
                'expensetype' => 'Office Supplies - #04',
            ],
            [
                'id' => 132,
                'expensecode' => '5600-01-04',
                'expensetype' => 'Smallwares - #04',
            ],
            [
                'id' => 133,
                'expensecode' => '5600-11-56',
                'expensetype' => 'Smallwares - #56',
            ],
            [
                'id' => 134,
                'expensecode' => '5500-11-056',
                'expensetype' => 'Paper Products - #56',
            ],
            [
                'id' => 135,
                'expensecode' => '3380-11-069',
                'expensetype' => 'Food Miscellaneous - #69',
            ],
            [
                'id' => 136,
                'expensecode' => '5710-11-69',
                'expensetype' => 'Office Supplies - #69',
            ],
            [
                'id' => 137,
                'expensecode' => '6350-11-69',
                'expensetype' => 'Auto Expense - #69',
            ],
            [
                'id' => 138,
                'expensecode' => '5750-11-69',
                'expensetype' => 'Employee Incentives - #69',
            ],
            [
                'id' => 139,
                'expensecode' => '6000-11-69',
                'expensetype' => 'Maintenance Routine - #69',
            ],
            [
                'id' => 140,
                'expensecode' => '5500-13-051',
                'expensetype' => 'Paper Products - #51',
            ],
            [
                'id' => 141,
                'expensecode' => '5500-13-015',
                'expensetype' => 'Paper Products -  #15',
            ],
            [
                'id' => 143,
                'expensecode' => '9506-11-33',
                'expensetype' => 'Tab Accounts - #33',
            ],
            [
                'id' => 145,
                'expensecode' => '5550-11-69',
                'expensetype' => 'Janitorial - #69',
            ],
            [
                'id' => 146,
                'expensecode' => '5600-11-69',
                'expensetype' => 'Smallwares - #69',
            ],
            [
                'id' => 147,
                'expensecode' => '5710-03-67',
                'expensetype' => 'Office Supplies - #67',
            ],
            [
                'id' => 148,
                'expensecode' => '8000-02-82',
                'expensetype' => 'Employment Advertising - DALLAS',
            ],
            [
                'id' => 149,
                'expensecode' => '5550-01-04',
                'expensetype' => 'Janitorial - #04',
            ],
            [
                'id' => 150,
                'expensecode' => '6350-01-04',
                'expensetype' => 'Auto Expense - #04',
            ],
            [
                'id' => 151,
                'expensecode' => '8800-11-77',
                'expensetype' => 'Dues & Subscriptions - #77',
            ],
            [
                'id' => 152,
                'expensecode' => '5710-01-18',
                'expensetype' => 'Office Supplies - #18',
            ],
            [
                'id' => 153,
                'expensecode' => '8005-11-77',
                'expensetype' => 'Advertising & Marketing - 77',
            ],
            [
                'id' => 154,
                'expensecode' => '5750-03-59',
                'expensetype' => 'Employee Incentives - #59',
            ],
            [
                'id' => 155,
                'expensecode' => '6300-01-04',
                'expensetype' => 'License & Permit - #04',
            ],
            [
                'id' => 156,
                'expensecode' => '5550-11-58',
                'expensetype' => 'Janitorial - #58',
            ],
            [
                'id' => 157,
                'expensecode' => '5750-11-58',
                'expensetype' => 'Employee Incentives - #58',
            ],
            [
                'id' => 158,
                'expensecode' => '5500-11-069',
                'expensetype' => 'Paper Products - #69',
            ],
            [
                'id' => 159,
                'expensecode' => '5710-12-60',
                'expensetype' => 'Office Supplies - #60',
            ],
            [
                'id' => 160,
                'expensecode' => '5550-02-70',
                'expensetype' => 'Janitorial - #70',
            ],
            [
                'id' => 161,
                'expensecode' => '5550-12-71',
                'expensetype' => 'Janitorial - #71',
            ],
            [
                'id' => 162,
                'expensecode' => '6000-11-58',
                'expensetype' => 'Maintenance Routine - #58',
            ],
            [
                'id' => 163,
                'expensecode' => '3380-12-065',
                'expensetype' => 'Food Miscellaneous - #65',
            ],
            [
                'id' => 164,
                'expensecode' => '3380-12-071',
                'expensetype' => 'Food Miscellaneous - #71',
            ],
            [
                'id' => 166,
                'expensecode' => '8000-12-71',
                'expensetype' => 'Employment Advertising #71',
            ],
            [
                'id' => 167,
                'expensecode' => '5500-01-004',
                'expensetype' => 'Paper Products - #04',
            ],
            [
                'id' => 168,
                'expensecode' => '5750-11-004',
                'expensetype' => 'Employee Incentives - #04',
            ],
            [
                'id' => 169,
                'expensecode' => '6000-11-56',
                'expensetype' => 'Maintenance Routine - #56',
            ],
            [
                'id' => 170,
                'expensecode' => '8000-11-56',
                'expensetype' => 'Employment Advertising #56',
            ],
            [
                'id' => 171,
                'expensecode' => '6660-03-59',
                'expensetype' => 'Fines & Penalties - #59',
            ],
            [
                'id' => 172,
                'expensecode' => '8005-02-06',
                'expensetype' => 'Advertising & Marketing - 06',
            ],
            [
                'id' => 173,
                'expensecode' => '8005-02-57',
                'expensetype' => 'Advertising & Marketing - 57',
            ],
            [
                'id' => 174,
                'expensecode' => '8005-02-70',
                'expensetype' => 'Advertising & Marketing - 70',
            ],
            [
                'id' => 175,
                'expensecode' => '8005-12-71',
                'expensetype' => 'Advertising & Marketing - 71',
            ],
            [
                'id' => 176,
                'expensecode' => '5600-01-61',
                'expensetype' => 'Smallwares - #61',
            ],
            [
                'id' => 177,
                'expensecode' => '5550-11-56',
                'expensetype' => 'Janitorial - #56',
            ],
            [
                'id' => 178,
                'expensecode' => '8000-03-67',
                'expensetype' => 'Employment Advertising #67',
            ],
            [
                'id' => 180,
                'expensecode' => '5750-01-08',
                'expensetype' => 'Employee Incentives - #08',
            ],
            [
                'id' => 181,
                'expensecode' => '6350-12-60',
                'expensetype' => 'Auto Expense - #60',
            ],
            [
                'id' => 182,
                'expensecode' => '6000-03-67',
                'expensetype' => 'Maintenance Routine - #67',
            ],
            [
                'id' => 183,
                'expensecode' => '1352-00-000',
                'expensetype' => 'Misc AR',
            ],
            [
                'id' => 184,
                'expensecode' => '8000-12-64',
                'expensetype' => 'Employment Advertising #64',
            ],
            [
                'id' => 185,
                'expensecode' => '3380-13-051',
                'expensetype' => 'Food Miscellaneous - #51',
            ],
            [
                'id' => 186,
                'expensecode' => '5750-13-51',
                'expensetype' => 'Employee Incentives - #51',
            ],
            [
                'id' => 187,
                'expensecode' => '6000-13-51',
                'expensetype' => 'Maintenance Routine - #51',
            ],
            [
                'id' => 188,
                'expensecode' => '6300-13-51',
                'expensetype' => 'License & Permit - #51',
            ],
            [
                'id' => 189,
                'expensecode' => '3380-11-047',
                'expensetype' => 'Food Miscellaneous - #47',
            ],
            [
                'id' => 190,
                'expensecode' => '8005-11-02',
                'expensetype' => 'Advertising & Marketing - 02',
            ],
            [
                'id' => 191,
                'expensecode' => '8005-01-04',
                'expensetype' => 'Advertising & Marketing - 04',
            ],
            [
                'id' => 192,
                'expensecode' => '8005-01-14',
                'expensetype' => 'Advertising & Marketing - 14',
            ],
            [
                'id' => 193,
                'expensecode' => '8005-01-18',
                'expensetype' => 'Advertising & Marketing - 18',
            ],
            [
                'id' => 194,
                'expensecode' => '8005-11-47',
                'expensetype' => 'Advertising & Marketing - 47',
            ],
            [
                'id' => 195,
                'expensecode' => '8005-11-58',
                'expensetype' => 'Advertising & Marketing - 58',
            ],
            [
                'id' => 196,
                'expensecode' => '8005-12-64',
                'expensetype' => 'Advertising & Marketing - 64',
            ],
            [
                'id' => 197,
                'expensecode' => '8005-11-69',
                'expensetype' => 'Advertising & Marketing - 69',
            ],
            [
                'id' => 198,
                'expensecode' => '3380-11-049',
                'expensetype' => 'Food Miscellaneous - #49',
            ],
            [
                'id' => 200,
                'expensecode' => '6300-11-56',
                'expensetype' => 'License & Permit - #56',
            ],
            [
                'id' => 201,
                'expensecode' => '8005-01-07',
                'expensetype' => 'Advertising & Marketing - 07',
            ],
            [
                'id' => 202,
                'expensecode' => '1100-12-52',
                'expensetype' => 'Store Change Fund - #52',
            ],
            [
                'id' => 203,
                'expensecode' => '3380-12-052',
                'expensetype' => 'Food Miscellaneous - #52',
            ],
            [
                'id' => 204,
                'expensecode' => '5500-12-052',
                'expensetype' => 'Paper Products - #52',
            ],
            [
                'id' => 205,
                'expensecode' => '5550-12-52',
                'expensetype' => 'Janitorial - #52',
            ],
            [
                'id' => 206,
                'expensecode' => '6350-12-52',
                'expensetype' => 'Auto Expense - #52',
            ],
            [
                'id' => 207,
                'expensecode' => '6320-12-52',
                'expensetype' => 'Van Expense - #52',
            ],
            [
                'id' => 208,
                'expensecode' => '5710-12-52',
                'expensetype' => 'Office Supplies - #52',
            ],
            [
                'id' => 209,
                'expensecode' => '5600-12-52',
                'expensetype' => 'Smallwares - #52',
            ],
            [
                'id' => 211,
                'expensecode' => '6310-12-52',
                'expensetype' => 'Parking - #52',
            ],
            [
                'id' => 213,
                'expensecode' => '6000-12-52',
                'expensetype' => 'Maintenance Routine - #52',
            ],
            [
                'id' => 214,
                'expensecode' => '6310-12-71',
                'expensetype' => 'Parking - #71',
            ],
            [
                'id' => 215,
                'expensecode' => '5750-12-52',
                'expensetype' => 'Employee Incentives - #52',
            ],
            [
                'id' => 216,
                'expensecode' => '5600-12-71',
                'expensetype' => 'Smallwares - #71',
            ],
            [
                'id' => 218,
                'expensecode' => '5710-02-53',
                'expensetype' => 'Office Supplies - #53',
            ],
            [
                'id' => 219,
                'expensecode' => '6300-02-70',
                'expensetype' => 'License & Permit - #70',
            ],
            [
                'id' => 220,
                'expensecode' => '6300-11-49',
                'expensetype' => 'License & Permit - #49',
            ],
            [
                'id' => 221,
                'expensecode' => '6200-11-36',
                'expensetype' => 'Maintenance Special #36',
            ],
            [
                'id' => 222,
                'expensecode' => '3380-11-036',
                'expensetype' => 'Food Miscellaneous - #36',
            ],
            [
                'id' => 223,
                'expensecode' => '6350-02-70',
                'expensetype' => 'Auto Expense - #70',
            ],
            [
                'id' => 224,
                'expensecode' => '6350-12-71',
                'expensetype' => 'Auto Expense - #71',
            ],
            [
                'id' => 225,
                'expensecode' => '5710-11-77',
                'expensetype' => 'Office Supplies - #77',
            ],
            [
                'id' => 226,
                'expensecode' => '3380-11-077',
                'expensetype' => 'Food Miscellaneous - #77',
            ],
            [
                'id' => 227,
                'expensecode' => '5550-11-77',
                'expensetype' => 'Janitorial - #77',
            ],
            [
                'id' => 228,
                'expensecode' => '6350-11-77',
                'expensetype' => 'Auto Expense - #77',
            ],
            [
                'id' => 229,
                'expensecode' => '5500-11-077',
                'expensetype' => 'Paper Products - #77',
            ],
            [
                'id' => 230,
                'expensecode' => '6350-11-47',
                'expensetype' => 'Auto Expense - #47',
            ],
            [
                'id' => 231,
                'expensecode' => '6350-11-49',
                'expensetype' => 'Auto Expense - #49',
            ],
            [
                'id' => 232,
                'expensecode' => '8000-03-59',
                'expensetype' => 'Employment Advertising #59',
            ],
            [
                'id' => 233,
                'expensecode' => '8000-13-51',
                'expensetype' => 'Employment Advertising #51',
            ],
            [
                'id' => 234,
                'expensecode' => '6310-11-01',
                'expensetype' => 'Parking - #01',
            ],
            [
                'id' => 235,
                'expensecode' => '6310-11-02',
                'expensetype' => 'Parking - #02',
            ],
            [
                'id' => 236,
                'expensecode' => '6350-02-06',
                'expensetype' => 'Auto Expense - #06',
            ],
            [
                'id' => 237,
                'expensecode' => '5750-02-06',
                'expensetype' => 'Employee Incentives - #06',
            ],
            [
                'id' => 238,
                'expensecode' => '5750-12-62',
                'expensetype' => 'Employee Incentives - #62',
            ],
            [
                'id' => 239,
                'expensecode' => '6350-12-62',
                'expensetype' => 'Auto Expense - #62',
            ],
            [
                'id' => 241,
                'expensecode' => '6310-11-33',
                'expensetype' => 'Parking - #33',
            ],
            [
                'id' => 242,
                'expensecode' => '6310-11-36',
                'expensetype' => 'Parking - #36',
            ],
            [
                'id' => 243,
                'expensecode' => '5750-12-105',
                'expensetype' => 'Employee Incentives - #105',
            ],
            [
                'id' => 244,
                'expensecode' => '3380-12-105',
                'expensetype' => 'Food Miscellaneous - #105',
            ],
            [
                'id' => 245,
                'expensecode' => '7006-11-001',
                'expensetype' => 'Ritual Fees',
            ],
            [
                'id' => 246,
                'expensecode' => '6320-11-77',
                'expensetype' => 'Van Expense - #77',
            ],
            [
                'id' => 247,
                'expensecode' => '6320-12-103',
                'expensetype' => 'Van Expense - #103',
            ],
            [
                'id' => 248,
                'expensecode' => '6310-12-103',
                'expensetype' => 'Parking - #103',
            ],
            [
                'id' => 249,
                'expensecode' => '3380-12-103',
                'expensetype' => 'Food Miscellaneous - #103',
            ],
            [
                'id' => 250,
                'expensecode' => '5500-12-103',
                'expensetype' => 'Paper Products - #103',
            ],
            [
                'id' => 251,
                'expensecode' => '8800-11-56',
                'expensetype' => 'Dues & Subscriptions - #56',
            ],
            [
                'id' => 252,
                'expensecode' => '5600-01-18',
                'expensetype' => 'Smallwares - #18',
            ],
            [
                'id' => 253,
                'expensecode' => '5001-11-077',
                'expensetype' => 'ICE # 77',
            ],
            [
                'id' => 254,
                'expensecode' => '6000-11-077',
                'expensetype' => 'Maintenance Routine - #77',
            ],
            [
                'id' => 255,
                'expensecode' => '6310-11-77',
                'expensetype' => 'Parking #77',
            ],
            [
                'id' => 256,
                'expensecode' => '8005-11-01',
                'expensetype' => 'Advertising & Marketing - 01',
            ],
            [
                'id' => 257,
                'expensecode' => '6000-03-59',
                'expensetype' => 'Maintenance Routine - #59',
            ],
            [
                'id' => 258,
                'expensecode' => '6000-11-49',
                'expensetype' => 'Maintenance Routine - #49',
            ],
            [
                'id' => 259,
                'expensecode' => '6310-01-04',
                'expensetype' => 'Parking - #04',
            ],
            [
                'id' => 260,
                'expensecode' => '5710-11-033',
                'expensetype' => 'Office Supplies - #33',
            ],
            [
                'id' => 261,
                'expensecode' => '3380-13-074',
                'expensetype' => 'Food Miscellaneous - #74',
            ],
            [
                'id' => 262,
                'expensecode' => '6000-11-47',
                'expensetype' => 'Maintenance Routine - #47',
            ],
            [
                'id' => 263,
                'expensecode' => '6325-13-51',
                'expensetype' => 'Taxi - #51',
            ],
            [
                'id' => 264,
                'expensecode' => '6325-13-74',
                'expensetype' => 'Taxi - #74',
            ],
            [
                'id' => 265,
                'expensecode' => '5600-11-77',
                'expensetype' => 'Smallwares - #77',
            ],
            [
                'id' => 266,
                'expensecode' => '8005-03-83',
                'expensetype' => 'Advertising & Marketing - Chicago',
            ],
            [
                'id' => 267,
                'expensecode' => '6000-01-04',
                'expensetype' => 'Maintenance Routine - #04',
            ],
            [
                'id' => 268,
                'expensecode' => '6200-11-056',
                'expensetype' => 'Maintenance Special',
            ],
            [
                'id' => 269,
                'expensecode' => '5600-11-033',
                'expensetype' => 'Smallwares #33',
            ],
            [
                'id' => 270,
                'expensecode' => '5550-11-033',
                'expensetype' => 'Janitorial #33',
            ],
            [
                'id' => 271,
                'expensecode' => '5500-11-033',
                'expensetype' => 'Paper Products #33',
            ],
            [
                'id' => 272,
                'expensecode' => '3380-11-033',
                'expensetype' => 'Food Misc # 33',
            ],
            [
                'id' => 273,
                'expensecode' => '5750-11-033',
                'expensetype' => 'Employee Incentive #33',
            ],
            [
                'id' => 274,
                'expensecode' => '6200-11-033',
                'expensetype' => 'Maintenance Special #33',
            ],
            [
                'id' => 275,
                'expensecode' => '7027-11-033',
                'expensetype' => 'Worker Comp- Store 33',
            ],
            [
                'id' => 276,
                'expensecode' => '5001-11-056',
                'expensetype' => 'ICE #56',
            ],
            [
                'id' => 277,
                'expensecode' => '5750-11-056',
                'expensetype' => 'Employee Incentives - #56',
            ],
            [
                'id' => 278,
                'expensecode' => '8800-11-033',
                'expensetype' => 'Dues & Subscriptions - #33',
            ],
            [
                'id' => 279,
                'expensecode' => '5001-11-033',
                'expensetype' => 'ICE # 33',
            ],
            [
                'id' => 280,
                'expensecode' => '6000-11-033',
                'expensetype' => 'Maintenance Routine - #33',
            ],
            [
                'id' => 281,
                'expensecode' => '6300-11-033',
                'expensetype' => 'License & Permits #33',
            ],
            [
                'id' => 282,
                'expensecode' => '5750-11-036',
                'expensetype' => 'Employee Incentive #36',
            ],
            [
                'id' => 283,
                'expensecode' => '6300-11-047',
                'expensetype' => 'License & Permit - #47',
            ],
            [
                'id' => 284,
                'expensecode' => '5500-11-047',
                'expensetype' => 'Paper Products - #47',
            ],
            [
                'id' => 285,
                'expensecode' => '6660-11-056',
                'expensetype' => 'Fines & Penalties',
            ],
            [
                'id' => 286,
                'expensecode' => '6410-00-000',
                'expensetype' => 'R&D Corporate',
            ],
            [
                'id' => 287,
                'expensecode' => 17,
                'expensetype' => 'Atlanta',
            ],
        ];

        DB::table('expenses')->insert($dataTables);
    }
}
