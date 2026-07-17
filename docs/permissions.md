# الصلاحيات والأدوار

## الأدوار الافتراضية

يُنشئ معالج التثبيت أربعة أدوار أساسية (`is_system = 1`، غير قابلة للحذف):

| الدور | slug | الوصف |
|---|---|---|
| مدير النظام | `system_admin` | يملك كل الصلاحيات دائمًا، ولا يمكن تقييده من واجهة الأدوار. |
| مدير المحتوى | `content_manager` | الأخبار، المناسبات، الجمعات، الصفحات، الوسائط، الأرشيف، الإعلانات، شجرة النسب. |
| مدير التواصل | `communication_manager` | جوال العائلة، القائمة البريدية، قوالب الرسائل. لا يصل لإعدادات النظام الحساسة. |
| مشاهد | `viewer` | مشاهدة لوحة التحكم والتقارير فقط، دون أي تعديل. |

يمكن إنشاء أدوار إضافية عبر قاعدة البيانات مباشرة حاليًا (شاشة إنشاء دور جديد من الواجهة إضافة مستقبلية)؛ تعديل صلاحيات أي دور غير `system_admin` متاح من **لوحة التحكم → الأدوار والصلاحيات**.

## كتالوج الصلاحيات

معرّف في `core/Permissions.php`، مقسّم لمجموعات:

- **النظام والإعدادات**: `system.settings`, `system.users`, `system.modules`, `system.backup`, `system.logs`
- **المحتوى**: `content.news`, `content.events`, `content.gatherings`, `content.pages`, `content.media`, `content.archive`, `content.announcements`, `content.tree`
- **التواصل**: `directory.view`, `directory.manage`, `directory.export`, `newsletter.manage`, `templates.manage`
- **التقارير**: `reports.view`

كل إضافة يمكن أن تضيف صلاحياتها الخاصة عبر `permissions()` في `Module.php`، وتُخزَّن في جدول `permissions_extra` وتظهر تلقائيًا في شاشة تعديل الأدوار.

## التحقق من الصلاحية في الكود

```php
use Core\Auth;

Auth::requireLogin();                 // يحوّل لصفحة الدخول إن لم يكن مسجلًا
Auth::requirePermission('content.news'); // يُظهر رسالة 403 إن لم يملك الصلاحية
if (Auth::can('directory.export')) { ... }
```

## حماية بيانات جوال العائلة

الوصول لبيانات جوال العائلة يتطلب دائمًا صلاحية `directory.view` (للمشاهدة) أو `directory.manage` (للتعديل) أو `directory.export` (للتصدير) — لا يوجد أي مسار عام لعرضها، وكل وصول إداري لها يُسجَّل في سجل العمليات تلقائيًا.
