# بناء إضافة جديدة (Module)

كل إضافة مستقلة تمامًا عن النواة وعن بقية الإضافات، وتعيش داخل مجلد خاص بها في `/modules/{slug}/`. تعطيل أي إضافة يجب ألا يؤثر إطلاقًا على عمل النواة أو الإضافات الأخرى.

## الهيكل الأساسي

```
modules/{slug}/
  module.json                 ملف تعريف الإضافة (إلزامي)
  Module.php                  الصنف الرئيسي (إلزامي) — namespace Modules\{Studly}
  install.sql                 جمل SQL لإنشاء جداول الإضافة (اختياري لكن شائع)
  Admin/Controllers/*.php     متحكمات لوحة التحكم — namespace Modules\{Studly}\Admin\Controllers
  Admin/Views/*.php           واجهات لوحة التحكم
  Front/Controllers/*.php     متحكمات الموقع العام — namespace Modules\{Studly}\Front\Controllers
  Front/Views/*.php           واجهات الموقع العام
```

`{Studly}` هو اسم المعرّف (slug) بصيغة StudlyCase، مثلًا `news` → `News`.

## module.json

```json
{
  "slug": "news",
  "name": "الأخبار",
  "description": "نظام إدارة أخبار متكامل.",
  "version": "1.0.0",
  "core_version": ">=1.0.0",
  "namespace": "Modules\\News",
  "author": "اسمك"
}
```

## Module.php

يجب أن يمتد الصنف الرئيسي من `Core\AbstractModule` (أو يُنفّذ `Core\ModuleContract` مباشرة):

```php
<?php
namespace Modules\News;

use Core\AbstractModule;
use Core\Router;

final class Module extends AbstractModule
{
    public function install(): void
    {
        $this->runSqlFile(__DIR__ . '/install.sql');
    }

    public function uninstall(bool $keepData): void
    {
        if (!$keepData) {
            \Core\Database::pdo()->exec('DROP TABLE IF EXISTS ' . \Core\Database::table('news'));
        }
    }

    public function registerAdminRoutes(Router $router): void
    {
        $router->get('/news', [\Modules\News\Admin\Controllers\NewsAdminController::class, 'index']);
        // ...
    }

    public function registerPublicRoutes(Router $router): void
    {
        $router->get('/news/{slug}', [\Modules\News\Front\Controllers\NewsFrontController::class, 'show']);
    }

    public function adminMenu(): array
    {
        return [['label' => 'الأخبار', 'url' => \Core\Support\Url::admin('news'), 'icon' => '📰', 'perm' => 'content.news']];
    }

    public function permissions(): array
    {
        return ['content.news' => 'إدارة الأخبار'];
    }
}
```

## قواعد مهمة

- **بادئة الجداول**: استخدم دائمًا `Core\Database::table('اسم_الجدول')` أو الرمز `{prefix}` داخل ملفات `install.sql` — لا تكتب اسم الجدول مباشرة أبدًا.
- **لا معاملات (Transactions) حول DDL**: جمل `CREATE TABLE`/`ALTER TABLE` في MySQL تُنفّذ التزامًا ضمنيًا، لذلك `install()` يجب ألا يُغلَّف بمعاملة PDO.
- **الأمان**: استخدم `Core\Support\Csrf::verifyRequestOrFail()` في كل معالج POST، و`Core\Support\Security::e()` عند طباعة أي بيانات في HTML، و`Core\Auth::requirePermission('...')` في بداية كل متحكم إداري.
- **الخصوصية**: لا تُضِف أي إضافة تعرض بيانات شخصية حساسة (أرقام جوال، عناوين) في الموقع العام مباشرة.
- **الصلاحيات**: أعد فقط الصلاحيات الجديدة الخاصة بإضافتك من `permissions()` — إن كانت إضافتك تستخدم صلاحية أساسية موجودة أصلًا في كتالوج النواة (مثل `content.pages`) فلا داعي لتكرارها.
- **عدم كسر النواة عند التعطيل**: `ModuleManager::boot()` يُحيط كل استدعاء لإضافة بمعالجة استثناءات، لكن يجب أن تتأكد أن أي كود يعتمد على وجود جدول إضافة أخرى يتحقق أولًا عبر `Database::tableExists()`.

## التثبيت من لوحة التحكم

من **الإضافات**، يمكن للمدير:
- رؤية كل الإضافات المكتشفة في `/modules` (سواء مثبتة أم لا).
- تثبيتها (ينفّذ `install()` وينشئ سجلًا في جدول `modules`).
- تفعيلها/تعطيلها دون حذف بياناتها.
- رفعها كملف ZIP مباشرة (يتطلب امتداد `zip` في PHP).
- إزالتها نهائيًا مع خيار صريح للاحتفاظ بالبيانات أو حذفها.
