# TaskFlow — Full Stack CRUD Operations (PHP)

مشروع متكامل لتطبيق ويب لإدارة المهام يدعم كافة عمليات **CRUD (إنشاء، قراءة، تعديل، حذف)** مع واجهة أمامية عصرية (Modern Frontend) وخلفية برمجية مبنية بلغة **PHP** مع واجهة برمجية **RESTful JSON API** وقاعدة بيانات **SQLite / MySQL**.

---

## 🌟 مميزات المشروع (Features)

- **Backend RESTful API (PHP)**:
  - معمارية نقية بدون أطر عمل خارجية معقدة لضمان سهولة الفحص والتشغيل.
  - استخدام **PDO** مع **Prepared Statements** لحماية كاملة ضد هجمات SQL Injection.
  - ردود موحدة بصيغة JSON مع معالجة الأخطاء وأكواد الـ HTTP الصحيحة (`200 OK`, `201 Created`, `400 Bad Request`, `404 Not Found`, `422 Unprocessable Entity`).
  - قاعدة بيانات افتراضية **SQLite** تعمل فوراً وبشكل تلقائي بمجرد تشغيل المشروع دون الحاجة لإعداد خادم MySQL أو ضبط كلمات مرور (مع إمكانية التبديل إلى MySQL بضغطة زر).

- **Frontend (Vanilla HTML5 / CSS3 / JavaScript)**:
  - تصميم عصري وفاخر مع تأثيرات **Glassmorphism** وتدرجات ألوان متناسقة.
  - لوحة إحصائيات حية (**Live Metrics**): إجمالي المهام، المكتملة، قيد التنفيذ، والمعلقة.
  - عمليات CRUD كاملة وسلسة:
    - **Create**: نافذة منبثقة (Modal) لإضافة مهمة مع التحقق من صحة المدخلات.
    - **Read**: عرض تفاعلي للبطاقات مع فرز وفلترة وبحث فوري.
    - **Update**: تعديل بيانات المهمة بالكامل، وتحديث سريع لحالة الإنجاز بمجرد الضغط على الـ Checkbox.
    - **Delete**: زر حذف مع نافذة تأكيد لمنع الحذف بالخطأ وتنبيهات Toast.
  - دعم الوضع الليلي والنهاري (**Dark / Light Mode**) مع حفظ التفضيل في المتصفح.

---

## 🚀 طريقة تشغيل المشروع (Quick Start)

كل ما تحتاجه هو فتح موجه الأوامر (Terminal / PowerShell) في مجلد المشروع وتشغيل السيرفر المدمج في PHP:

```bash
php -S 127.0.0.1:8000
```

ثم افتح المتصفح على الرابط:
👉 **[http://127.0.0.1:8000](http://127.0.0.1:8000)**

> **ملاحظة:** تم تهيئة قاعدة البيانات تلقائياً ببيانات أولية تجريبية بمجرد تشغيل المشروع.

---

## 📁 هيكل المشروع (Project Structure)

```text
project 1/
│
├── api/
│   ├── db.php            # اتصال قاعدة البيانات (PDO SQLite/MySQL) والتهيئة التلقائية
│   ├── response.php      # دالة موحدة لإرسال استجابات JSON و CORS
│   └── tasks.php         # مسار الـ RESTful API للتعامل مع العمليات (GET, POST, PUT, PATCH, DELETE)
│
├── css/
│   └── style.css         # التصميم الكامل، نظام المتغيرات، التنسيق المتجاوب، والـ Glassmorphism
│
├── js/
│   └── app.js            # منطق الواجهة، الاتصال بـ API عبر Fetch، إدارة الحالة، النوافذ، والتنبيهات
│
├── database/
│   └── schema.sql        # ملف السكيما التوضيحي لقواعد بيانات SQLite و MySQL
│
├── data/
│   └── tasks.db          # قاعدة بيانات SQLite التي تنشأ تلقائياً وتخزن البيانات
│
├── index.php             # الصفحة الرئيسية وواجهة المستخدم
└── README.md             # ملف التوثيق ودليل الاستخدام
```

---

## 📡 توثيق الـ API (API Endpoints)

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/tasks.php` | جلب جميع المهام (يدعم: `status`, `priority`, `search`, `sort_by`, `order`) |
| `GET` | `/api/tasks.php?id={id}` | جلب تفاصيل مهمة محددة عبر الـ ID |
| `GET` | `/api/tasks.php?action=stats` | جلب إحصائيات المهام (الإجمالي، المكتمل، المعلق) |
| `POST` | `/api/tasks.php` | إضافة مهمة جديدة (يتطلب `title` كحقل إجباري) |
| `PUT` | `/api/tasks.php?id={id}` | تحديث بيانات مهمة موجودة |
| `PATCH` | `/api/tasks.php?id={id}` | تحديث سريع لحالة المهمة (`completed` / `pending`) |
| `DELETE` | `/api/tasks.php?id={id}` | حذف مهمة محددة نهائياً من قاعدة البيانات |

### مثال على جسم الطلب (JSON Request Body) لإنشاء مهمة:
```json
{
  "title": "إكمال مشروع الـ Full Stack",
  "description": "توثيق واختبار كافة عمليات الـ CRUD وتسليم المشروع",
  "category": "Work",
  "priority": "high",
  "status": "pending",
  "due_date": "2026-11-01"
}
```

---

## 🔄 التبديل إلى MySQL (اختياري)
إذا رغبت في استخدام MySQL بدلاً من SQLite:
1. افتح الملف [`api/db.php`](file:///c:/Users/Alrowad/Desktop/project%201/api/db.php).
2. قم بتغيير السطر الأول:
   ```php
   define('DB_DRIVER', 'mysql');
   ```
3. اضبط إعدادات الاتصال (`MYSQL_HOST`, `MYSQL_DB`, `MYSQL_USER`, `MYSQL_PASS`) حسب سيرفرك المحلي (XAMPP / WampServer).
"# Full-Stack-Basic-CRUD-Operations" 
