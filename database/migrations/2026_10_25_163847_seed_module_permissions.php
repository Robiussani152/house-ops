<?php

use App\Policies\ModulePolicy;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (ModulePolicy::permissionNames() as $name) {
            Permission::findOrCreate($name, 'web');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('name', ModulePolicy::permissionNames())->delete();
    }
};
