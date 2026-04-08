<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['super-admin', 'class-teacher', 'subject-teacher', 'parent'];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $permissions = [
            'manage school',
            'manage classes',
            'manage subjects',
            'manage students',
            'manage teachers',
            'enter scores',
            'manage attendance',
            'view reports',
            'generate pdf',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $super = Role::where('name', 'super-admin')->first();
        $super?->syncPermissions(Permission::all());

        $subjectTeacher = Role::where('name', 'subject-teacher')->first();
        $subjectTeacher?->syncPermissions(['enter scores', 'view reports']);

        $classTeacher = Role::where('name', 'class-teacher')->first();
        $classTeacher?->syncPermissions(['manage students', 'manage attendance', 'view reports', 'enter scores']);
    }
}
