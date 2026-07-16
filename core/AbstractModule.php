<?php

namespace Core;

abstract class AbstractModule implements ModuleContract
{
    public function migrate(string $fromVersion, string $toVersion): void
    {
        // لا يوجد ترحيل افتراضي؛ تقوم الإضافة بتنفيذه عند الحاجة.
    }

    public function uninstall(bool $keepData): void
    {
        // لا إجراء افتراضي.
    }

    public function registerAdminRoutes(Router $router): void
    {
    }

    public function registerPublicRoutes(Router $router): void
    {
    }

    public function adminMenu(): array
    {
        return [];
    }

    public function permissions(): array
    {
        return [];
    }

    public function boot(): void
    {
    }

    protected function runSqlFile(string $path): void
    {
        if (!is_file($path)) {
            return;
        }
        $sql = file_get_contents($path);
        $prefix = Database::prefix();
        $sql = str_replace('{prefix}', $prefix, $sql);

        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $statement) {
            if ($statement === '') {
                continue;
            }
            Database::pdo()->exec($statement);
        }
    }
}
