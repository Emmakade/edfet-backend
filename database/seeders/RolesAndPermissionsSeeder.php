<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // create roles
        $roles = ['super-admin','class-teacher','subject-teacher','parent'];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // example permissions (you can expand later)
        $permissions = [
            'manage school',
            'manage classes',
            'manage subjects',
            'manage students',
            'enter scores',
            'view reports',
            'generate pdf'
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // attach broad permissions to roles
        $super = Role::where('name','super-admin')->first();
        $super->givePermissionTo(Permission::all());

        // subject teacher: enter scores, view reports
        $subjectTeacher = Role::where('name','subject-teacher')->first();
        $subjectTeacher->givePermissionTo(['enter scores','view reports']);

        // class teacher: manage students, view reports, enter attendance, class remarks
        $classTeacher = Role::where('name','class-teacher')->first();
        $classTeacher->givePermissionTo(['manage students','view reports','enter scores']);
    }
}
