<?php

namespace Database\Seeders;

use App\Modules\Events\Models\EventGuidanceVersion;
use Illuminate\Database\Seeder;

class RamadanIftarGuidanceSeeder extends Seeder
{
    public function run(): void
    {
        if (EventGuidanceVersion::query()->where('code', EventGuidanceVersion::RAMADAN_IFTAR)->exists()) {
            return;
        }

        EventGuidanceVersion::query()->create([
            'code' => EventGuidanceVersion::RAMADAN_IFTAR,
            'version_number' => 1,
            'title' => 'تعليمات عامة لإفطارات رمضان 2025',
            'content' => json_encode($this->sections(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'is_active' => true,
            'published_at' => now(),
        ]);
    }

    private function sections(): array
    {
        return [
            ['title' => 'الجهة المنفذة', 'icon' => 'fa-handshake', 'items' => ['يمكن استقبال الإفطارات من جمعيات أو أفراد أو مؤسسات القطاع العام أو الخاص، شريطة الالتزام بتعليمات الإفطارات الموثقة.']],
            ['title' => 'الجهة المستفيدة', 'icon' => 'fa-children', 'items' => ['يتم الحشد بما يحقق استفادة الأطفال المرتبطين بالمراكز والجمعيات.']],
            ['title' => 'المتطوعين', 'icon' => 'fa-people-group', 'items' => ['التنسيق مع ضابط ارتباط المتطوعين قبل موعد الإفطار بخمسة أيام.', 'يراعى توفير متطوع واحد تقريباً لكل سبعة أطفال.', 'التنسيق مسبقاً مع المتطوعين حول تخصيص وجبات لهم.']],
            ['title' => 'الوجبات', 'icon' => 'fa-utensils', 'emphasis' => true, 'items' => ['يكون الطعام من مطعم مرخص مهنياً وصحياً.', 'تكون كمية الوجبة كافية لشخص واحد.', 'تتضمن الوجبة التمر والماء والعصير والشوربة والطبق الرئيسي.', 'تغلف كل وجبة بشكل منفرد.']],
            ['title' => 'الكادر المرافق', 'icon' => 'fa-user-shield', 'items' => ['يرافق الأطفال مشرفون من الجهة المستفيدة نفسها، بمعدل مشرف واحد تقريباً لكل 5–7 أطفال.']],
            ['title' => 'فريق التنفيذ', 'icon' => 'fa-users-gear', 'items' => ['يضم فريق زها فريق الإشراف على الإفطار والعمال وفريق العلاقات ومنفذي الأنشطة.']],
            ['title' => 'موقع التنفيذ', 'icon' => 'fa-location-dot', 'items' => ['يراعى ملاءمة الطقس عند اختيار الموقع الداخلي أو الخارجي.', 'يراعى اتساع الموقع وسلامة الأطفال وتوفر الأثاث والطاولات والكراسي.', 'تجنب استخدام الغرف غير الملائمة للتنفيذ.']],
            ['title' => 'الموارد اللازمة', 'icon' => 'fa-box-open', 'emphasis' => true, 'items' => ['تتولى الجهة المنفذة توفير أغطية أو مفارش طعام بيضاء سادة.', 'توفير المحارم الورقية والمحارم المبللة أو المعطرة وعبوات العصير.', 'توفير عبوتي ماء لكل طفل والتمر والصحون والملاعق ذات الاستخدام الواحد.']],
            ['title' => 'الهدايا', 'icon' => 'fa-gift', 'items' => ['يجب أن تغطي كمية الهدايا المستفيدين المقصودين.', 'تكون الهدايا مناسبة للفئة العمرية ومتوافقة مع الأنظمة والتعليمات.']],
            ['title' => 'اعتبارات عامة', 'icon' => 'fa-triangle-exclamation', 'emphasis' => true, 'items' => ['الإفطارات المنفذة خارج مراكز زها لا تحتسب ضمن إفطارات مركز زها.', 'لا يسمح بالطبخ أو تقديم وصب الوجبات داخل المركز.']],
        ];
    }
}
