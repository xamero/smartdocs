<?php

namespace Database\Seeders;

use App\Models\Office;
use Illuminate\Database\Seeder;

class OfficeSeeder extends Seeder
{
    public function run(): void
    {
        // Create main offices
        $mayorsOffice = Office::updateOrCreate(
            ['code' => 'MAY'],
            [
                'name' => "Mayor's Office",
                'description' => 'Office of the Mayor',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        $viceMayorsOffice = Office::updateOrCreate(
            ['code' => 'VMO'],
            [
                'name' => "Vice Mayor's Office",
                'description' => 'Office of the Vice Mayor',
                'is_active' => true,
                'sort_order' => 2,
            ]
        );

        $treasurersOffice = Office::updateOrCreate(
            ['code' => 'TRE'],
            [
                'name' => "Treasurer's Office",
                'description' => 'Municipal Treasurer',
                'is_active' => true,
                'sort_order' => 3,
            ]
        );

        $accountantsOffice = Office::updateOrCreate(
            ['code' => 'ACC'],
            [
                'name' => "Accountant's Office",
                'description' => 'Municipal Accountant',
                'is_active' => true,
                'sort_order' => 4,
            ]
        );

        $budgetOffice = Office::updateOrCreate(
            ['code' => 'BUD'],
            [
                'name' => 'Budget Office',
                'description' => 'Budget and Management Office',
                'is_active' => true,
                'sort_order' => 5,
            ]
        );

        $hrOffice = Office::updateOrCreate(
            ['code' => 'HR'],
            [
                'name' => 'Human Resources Office',
                'description' => 'Human Resource Management Office',
                'is_active' => true,
                'sort_order' => 6,
            ]
        );

        $planningOffice = Office::updateOrCreate(
            ['code' => 'PDO'],
            [
                'name' => 'Planning and Development Office',
                'description' => 'Municipal Planning and Development Office',
                'is_active' => true,
                'sort_order' => 7,
            ]
        );

        $engineeringOffice = Office::updateOrCreate(
            ['code' => 'ENG'],
            [
                'name' => "Engineer's Office",
                'description' => 'Municipal Engineering Office',
                'is_active' => true,
                'sort_order' => 8,
            ]
        );

        $healthOffice = Office::updateOrCreate(
            ['code' => 'HEA'],
            [
                'name' => 'Health Office',
                'description' => 'Municipal Health Office',
                'is_active' => true,
                'sort_order' => 9,
            ]
        );

        $socialWelfareOffice = Office::updateOrCreate(
            ['code' => 'SWO'],
            [
                'name' => 'Social Welfare Office',
                'description' => 'Municipal Social Welfare and Development Office',
                'is_active' => true,
                'sort_order' => 10,
            ]
        );
    }
}
