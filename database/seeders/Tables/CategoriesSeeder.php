<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesSeeder extends Seeder
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
                'category' => 'Breakfast',
                'description' => 'minimum 5 persons <br> Please place your breakfast orders by 3:00 p.m. the previous day. 
',
                'image' => ' ',
                'sort' => 901,
                'max_limit' => 5,
            ],
            [
                'id' => 2,
                'category' => 'Sandwiches',
                'description' => 'minimum 6 persons. <br><a  href="sanddesc.asp?hd=2">Click here for detailed sandwich descriptions</a>',
                'image' => ' ',
                'sort' => 902,
                'max_limit' => 6,
            ],
            [
                'id' => 3,
                'category' => 'Package Deals',
                'description' => 'minimum 5 persons <br><a  href="sanddesc.asp?hd=2">Click here for detailed sandwich descriptions</a>
 


',
                'image' => ' ',
                'sort' => 903,
                'max_limit' => 5,
            ],
            [
                'id' => 4,
                'category' => 'Box Lunches',
                'description' => 'All box lunches include chips and fresh baked jumbo cookie <br> <a  href="sanddesc.asp?hd=2">Click here for detailed sandwich descriptions</a>


',
                'image' => ' ',
                'sort' => 904,
                'max_limit' => 6,
            ],
            [
                'id' => 5,
                'category' => 'Hot Plates',
                'description' => 'minimum 5 persons <br>
Hot Plates are served in disposable aluminium pans.<br>
Chaffer service is available for an additional fee. Please select Chaffer Service on the menu. 
',
                'image' => ' ',
                'sort' => 905,
                'max_limit' => 6,
            ],
            [
                'id' => 6,
                'category' => 'Side Salads',
                'description' => 'minimum 6 persons<br>
some side salads may require 24 hour notice',
                'image' => ' ',
                'sort' => 911,
                'max_limit' => 6,
            ],
            [
                'id' => 7,
                'category' => 'Soup',
                'description' => 'Select from our famous Italian Wedding soup or Soup of the Day.  All our soups are homemade!
',
                'image' => ' ',
                'sort' => 912,
                'max_limit' => 5,
            ],
            [
                'id' => 8,
                'category' => 'Dessert Trays ',
                'description' => 'minimum 5 persons
',
                'image' => ' ',
                'sort' => 913,
                'max_limit' => 5,
            ],
            [
                'id' => 9,
                'category' => 'Chips & Dips/Hors d\'Oeuvres',
                'description' => 'minimum 5 persons;
24 hour notice, please.
',
                'image' => ' ',
                'sort' => 915,
                'max_limit' => 10,
            ],
            [
                'id' => 10,
                'category' => 'Beverages',
                'description' => '',
                'image' => ' ',
                'sort' => 917,
                'max_limit' => 5,
            ],
            [
                'id' => 12,
                'category' => 'Entree Salads',
                'description' => 'Entree salads are served individually and are accompanied with garlic bread.',
                'image' => ' ',
                'sort' => 900,
                'max_limit' => 0,
            ],
            [
                'id' => 14,
                'category' => 'Miscellaneous',
                'description' => 'Add off-menu items to the order and issue credits.',
                'image' => ' ',
                'sort' => 920,
                'max_limit' => 0,
            ],
            [
                'id' => 15,
                'category' => 'Low Carb Entree Salads',
                'description' => 'Entree salads are served individually and with chef\'s suggested dressing ',
                'image' => ' ',
                'sort' => 909,
                'max_limit' => 0,
            ],
            [
                'id' => 17,
                'category' => 'Entree Buffets for VIP Clients',
                'description' => 'minimum 6 persons <br>
served with green salad, jumbo cookies and appropriate breads<br>
24 hours notice, please',
                'image' => ' ',
                'sort' => 907,
                'max_limit' => 0,
            ],
            [
                'id' => 18,
                'category' => 'All Day Meal Package',
                'description' => '',
                'image' => ' ',
                'sort' => 908,
                'max_limit' => 0,
            ],
            [
                'id' => 19,
                'category' => 'Holiday Menu 2005',
                'description' => '',
                'image' => ' ',
                'sort' => 950,
                'max_limit' => 0,
            ],
            [
                'id' => 20,
                'category' => 'Holiday Menu 2006',
                'description' => '',
                'image' => ' ',
                'sort' => 951,
                'max_limit' => 0,
            ],
            [
                'id' => 21,
                'category' => 'Sides',
                'description' => 'minimum 5 persons <br>
',
                'image' => ' ',
                'sort' => 906,
                'max_limit' => 0,
            ],
            [
                'id' => 22,
                'category' => 'Holiday Menu 2007',
                'description' => '',
                'image' => '  ',
                'sort' => 952,
                'max_limit' => 0,
            ],
            [
                'id' => 23,
                'category' => 'Special Offers',
                'description' => 'minimum 5 persons',
                'image' => ' ',
                'sort' => 901,
                'max_limit' => 0,
            ],
            [
                'id' => 24,
                'category' => 'Holiday Menu 2008',
                'description' => 'Holiday Menu 2008',
                'image' => ' ',
                'sort' => 953,
                'max_limit' => 0,
            ],
            [
                'id' => 25,
                'category' => 'Holiday Breakfast Package Deals!  Nov 1st - Dec 31st  One Day Advance Notice ',
                'description' => 'Surprise your staff or clients with one of our tasty Holiday Breakfast Package Deals!  One day advance notice please.    ',
                'image' => '',
                'sort' => 10,
                'max_limit' => 0,
            ],
            [
                'id' => 26,
                'category' => 'Holiday Hot Plate Package Deals!  Nov 1st - Dec 31st  One Day Advance Notice  ',
                'description' => 'Warm up your holiday party with our Yummy Hot Plates and sides!  One day advance notice please.',
                'image' => '  ',
                'sort' => 15,
                'max_limit' => 0,
            ],
            [
                'id' => 27,
                'category' => 'Holiday Party Favorites!  Nov 1st - Dec 31st  One Day Advance Notice ',
                'description' => 'Add to lunch or mix and match for your after hours holiday event!  One day advance notice please. ',
                'image' => '  ',
                'sort' => 20,
                'max_limit' => 0,
            ],
            [
                'id' => 28,
                'category' => 'Holiday Beverages!  Nov 1st - Dec 31st  One Day Advance Notice ',
                'description' => 'Cool or holiday warm beverages to accompany your party!  One day advance notice please. ',
                'image' => '  ',
                'sort' => 25,
                'max_limit' => 0,
            ],
            [
                'id' => 29,
                'category' => 'Holiday Desserts!  Nov 1st - Dec 31st  One Day Advance Notice ',
                'description' => 'Top off a successful gathering with enticing desserts!  One day advance notice please. ',
                'image' => '   ',
                'sort' => 30,
                'max_limit' => 0,
            ],
            [
                'id' => 31,
                'category' => 'Warm Selections',
                'description' => 'Warm Selections',
                'image' => 'breakfast_cat.png  ',
                'sort' => 100,
                'max_limit' => 0,
            ],
            [
                'id' => 32,
                'category' => 'Fresh Pastries & More',
                'description' => 'Sweet & Tasty',
                'image' => 'breakfast_cat.png  ',
                'sort' => 150,
                'max_limit' => 0,
            ],
            [
                'id' => 33,
                'category' => 'Sandwiches, Pressatas and Wraps ',
                'description' => 'Hearty and Appetizing. ',
                'image' => 'sandwichpressatas_cat.png   ',
                'sort' => 200,
                'max_limit' => 0,
            ],
            [
                'id' => 34,
                'category' => 'Platinum Package Deals - 2 Sides plus Premium Sweets Selection ',
                'description' => 'Includes a choice of two sides and -  : Kettle Chips, Green Salad, Caesar Salad, Pesto Pasta Salad, Tomato Basil Pasta Salad, or Fresh Fruit. Cobb Salad add $8, Chicken Caesar Salad add $8',
                'image' => 'packagedeals_cat.png         ',
                'sort' => 300,
                'max_limit' => 0,
            ],
            [
                'id' => 35,
                'category' => 'Box Lunches',
                'description' => 'Convenient and Delicious. ',
                'image' => 'boxlunch_cat.png   ',
                'sort' => 450,
                'max_limit' => 0,
            ],
            [
                'id' => 36,
                'category' => 'Salad Bowls',
                'description' => 'Great for a lunchtime entree or as a side plate with Sandwiches and Pressatas. ',
                'image' => 'saladbowl_cat.png  ',
                'sort' => 400,
                'max_limit' => 0,
            ],
            [
                'id' => 37,
                'category' => 'Hot Plate Meals',
                'description' => 'Treat your guests with our unique and yummy Hot Plate Meals.',
                'image' => 'hotplates_cat.png  ',
                'sort' => 500,
                'max_limit' => 0,
            ],
            [
                'id' => 38,
                'category' => 'Hot Sides ',
                'description' => 'Add an extra side to your Hot Plate Meal, Sandwich or Pressata selection. ',
                'image' => ' ',
                'sort' => 980,
                'max_limit' => 0,
            ],
            [
                'id' => 39,
                'category' => 'Hors d\' Oeuvres',
                'description' => 'Scrumptious Hors d\'Oeuvres. ',
                'image' => 'horsdoevres_cat.png   ',
                'sort' => 550,
                'max_limit' => 0,
            ],
            [
                'id' => 40,
                'category' => 'Desserts ',
                'description' => 'Don\'t forget the Sweet Life. ',
                'image' => 'dessert_cat.png ',
                'sort' => 600,
                'max_limit' => 0,
            ],
            [
                'id' => 41,
                'category' => 'Beverages ',
                'description' => 'Full selection of morning and lunch beverages. ',
                'image' => 'CoffeeBox.png ',
                'sort' => 650,
                'max_limit' => 0,
            ],
            [
                'id' => 42,
                'category' => 'eT Burger Bar Catering ',
                'description' => 'Double meat burgers featuring fresh 100% Certified Angus beef, hand formed, and flame-grilled.  Served in a build-your-own format with buns, spresds, and toppings.  ',
                'image' => ' ',
                'sort' => 925,
                'max_limit' => 0,
            ],
            [
                'id' => 43,
                'category' => 'Premium Entrees - Allow 24 Hours ',
                'description' => 'An impressive assortment of gourmet-inspired entrees that will help make your next event a memorable one. ',
                'image' => ' ',
                'sort' => 982,
                'max_limit' => 0,
            ],
            [
                'id' => 44,
                'category' => 'test package deal ',
                'description' => 'test',
                'image' => '  ',
                'sort' => 961,
                'max_limit' => 0,
            ],
            [
                'id' => 45,
                'category' => 'Silver Package Deal - Chips plus 6 Cookies ',
                'description' => '',
                'image' => '     ',
                'sort' => 320,
                'max_limit' => 0,
            ],
            [
                'id' => 46,
                'category' => 'Gold Package Deals - 1 Side plus Cookie Box',
                'description' => '',
                'image' => '    ',
                'sort' => 310,
                'max_limit' => 0,
            ],
            [
                'id' => 47,
                'category' => 'Certified Gluten-Free Options for Individuals ',
                'description' => 'We practice caution and proper procedures when preparing gluten-free items, however gluten is present in all of our kitchens. Ingredients have been verified as gluten-free by a third-party consultant ',
                'image' => '',
                'sort' => 510,
                'max_limit' => null,
            ],
            [
                'id' => 48,
                'category' => 'Certified Gluten-Free Options For Groups ',
                'description' => 'We practice caution and proper procedures when preparing gluten-free items, however gluten is present in all of our kitchens. Ingredients have been verified as gluten-free by a third-party consultant.',
                'image' => '',
                'sort' => 520,
                'max_limit' => null,
            ],
            [
                'id' => 49,
                'category' => 'Vegetarian Options for Individuals',
                'description' => 'Our vegetarian options do not include meat, fish or shellfish. However, milk, egg products, rennet and enzymes from animal sources may be present.',
                'image' => '',
                'sort' => 530,
                'max_limit' => null,
            ],
            [
                'id' => 50,
                'category' => 'Vegetarian Options for Groups',
                'description' => 'Our vegetarian options do not include meat, fish or shellfish. However, milk, egg products, rennet and enzymes from animal sources may be present.',
                'image' => '',
                'sort' => 540,
                'max_limit' => null,
            ],
            [
                'id' => 51,
                'category' => 'Breakfast Boxes ',
                'description' => '    ',
                'image' => '',
                'sort' => 50,
                'max_limit' => null,
            ],
            [
                'id' => 52,
                'category' => 'Soups and More',
                'description' => 'Soups and More  ',
                'image' => '',
                'sort' => 350,
                'max_limit' => null,
            ],
            [
                'id' => 53,
                'category' => 'Breakfast Package Deals',
                'description' => 'Breakfast Package Deals',
                'image' => '',
                'sort' => 40,
                'max_limit' => null,
            ],
            [
                'id' => 54,
                'category' => 'Value-added Package Deal Extras',
                'description' => 'Value-added Package Deal Extras',
                'image' => '',
                'sort' => 42,
                'max_limit' => null,
            ],
            [
                'id' => 55,
                'category' => 'Holiday Value-added Package Deal Extras Nov 1st -Dec 31st One Day Advanced Notice',
                'description' => 'Holiday Value-added Package Deal Extras',
                'image' => '',
                'sort' => 12,
                'max_limit' => null,
            ],
            [
                'id' => 56,
                'category' => 'Powerbowls',
                'description' => 'These healthy superfoods in a bowl pack all of the high protein and low-calorie macronutrients you need to fuel your day.',
                'image' => '',
                'sort' => 425,
                'max_limit' => null,
            ],
            [
                'id' => 58,
                'category' => 'Warm Cookies',
                'description' => 'Warm Cookies',
                'image' => '',
                'sort' => 1,
                'max_limit' => null,
            ],
        ];

        DB::table('categories')->insert($dataTables);
    }
}
