<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Modules\Events\Models\EventGuidanceVersion;
use App\Modules\Events\Models\EventSubjectTypes;
use App\Modules\Events\Models\ExecutionNeedType;
use App\Modules\Events\Models\MonitoringMethod;
use App\Modules\Events\Models\MonitoringReport;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Models\SubjectExecutionNeed;
use App\Modules\Events\Support\RamadanPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Role;

class RamadanIftarDemoSeeder extends Seeder
{
    private const BRANCH_ID = 23;

    public function run(): void
    {
        $this->call([EventReferenceDataSeeder::class, CanonicalExecutionNeedTypeSeeder::class, RamadanPeriodSeeder::class, RamadanIftarGuidanceSeeder::class]);

        $branch = Branch::query()->find(self::BRANCH_ID);
        if (! $branch) throw new RuntimeException('Ramadan demo requires branch_id = 23. Seed the approved branch catalogue first.');
        $user = $this->demoUsers();
        $period = RamadanPeriod::active();
        if (! $period) throw new RuntimeException('Ramadan demo requires an active administratively configurable Ramadan period.');
        $guidance = EventGuidanceVersion::currentForRamadan();
        if (! $guidance) throw new RuntimeException('Ramadan demo requires published Ramadan guidance.');
        $workflow = Workflow::query()->where('module', RamadanIftar::WORKFLOW_MODULE)->where('is_active', true)->with('steps')->first();
        if (! $workflow) throw new RuntimeException('Ramadan demo requires the active Ramadan Iftar workflow. Run WorkflowSeeder after roles exist.');

        $scenarios = [
            ['key'=>'01','title'=>'[DEMO-RAMADAN-01] إفطار حي الأمل','day'=>1,'status'=>'draft','execution'=>'planned','workflow'=>null,'monitoring'=>null],
            ['key'=>'02','title'=>'[DEMO-RAMADAN-02] إفطار براعم زها','day'=>4,'status'=>'submitted','execution'=>'planned','workflow'=>'pending','monitoring'=>null],
            ['key'=>'03','title'=>'[DEMO-RAMADAN-03] إفطار أصدقاء المركز','day'=>7,'status'=>'submitted','execution'=>'planned','workflow'=>'in_progress','monitoring'=>null],
            ['key'=>'04','title'=>'[DEMO-RAMADAN-04] إفطار الأسرة الداعمة','day'=>10,'status'=>'approved','execution'=>'planned','workflow'=>'approved','monitoring'=>null],
            ['key'=>'05','title'=>'[DEMO-RAMADAN-05] إفطار فرحة طفل','day'=>10,'status'=>'approved','execution'=>'in_progress','workflow'=>'approved','monitoring'=>null],
            ['key'=>'06','title'=>'[DEMO-RAMADAN-06] إفطار ليالي الخير','day'=>14,'status'=>'approved','execution'=>'completed','workflow'=>'approved','monitoring'=>null],
            ['key'=>'07','title'=>'[DEMO-RAMADAN-07] إفطار سنابل العطاء','day'=>18,'status'=>'approved','execution'=>'completed','workflow'=>'approved','monitoring'=>'submitted'],
            ['key'=>'08','title'=>'[DEMO-RAMADAN-08] إفطار قناديل زها','day'=>22,'status'=>'approved','execution'=>'completed','workflow'=>'approved','monitoring'=>'approved'],
            ['key'=>'09','title'=>'[DEMO-RAMADAN-09] إفطار ختام الشهر','day'=>27,'status'=>'approved','execution'=>'completed','workflow'=>'approved','monitoring'=>'approved','closed'=>true],
        ];

        foreach ($scenarios as $index => $scenario) {
            $date = $period['start']->addDays($scenario['day']);
            if ($date->gt($period['end'])) $date = $period['end'];
            $iftar = RamadanIftar::query()->updateOrCreate(
                ['branch_id' => self::BRANCH_ID, 'title' => $scenario['title']],
                [
                    'relations_officer_id'=>$user->id, 'created_by'=>$user->id,
                    'description'=>'بيانات عرض اصطناعية لتجربة واجهات إفطارات رمضان.',
                    'planned_date'=>$date->toDateString(), 'actual_date'=>$scenario['execution']==='completed'?$date->toDateString():null,
                    'time_from'=>'17:00', 'time_to'=>'20:00', 'location_type'=>RamadanIftar::LOCATION_INSIDE_CENTER,
                    'location_name'=>$branch->name, 'supporting_entity_name'=>$index%2?'مؤسسة الخير التجريبية':'جمعية الأمل التجريبية',
                    'host_type'=>RamadanIftar::HOST_CENTER, 'planned_meals_count'=>50+($index*10),
                    'actual_meals_count'=>$scenario['execution']==='completed'?50+($index*10):null,
                    'expected_attendance'=>45+($index*8), 'actual_attendance'=>$scenario['execution']==='completed'?43+($index*8):null,
                    'status'=>$scenario['status'], 'execution_status'=>$scenario['execution'], 'version_number'=>1,
                    'guidance_version_id'=>$guidance->id, 'guidance_accepted_at'=>now(),
                    'submitted_at'=>$scenario['workflow']?now():null, 'approved_at'=>$scenario['workflow']==='approved'?now():null,
                    'closed_at'=>!empty($scenario['closed'])?now():null,
                ]
            );
            $this->syncWorkflow($workflow, $iftar, $scenario['workflow']);
            $this->syncNeeds($iftar, $index, $scenario['execution']);
            $this->syncMonitoring($iftar, $user, $scenario['monitoring']);
        }

        $approved = RamadanIftar::query()->where('branch_id', self::BRANCH_ID)->where('title', '[DEMO-RAMADAN-04] إفطار الأسرة الداعمة')->firstOrFail();
        RamadanIftar::query()->updateOrCreate(
            ['branch_id'=>self::BRANCH_ID,'title'=>'[DEMO-RAMADAN-10] مسودة تعديل إفطار الأسرة الداعمة'],
            array_merge($approved->only(['relations_officer_id','created_by','planned_date','time_from','time_to','location_type','location_name','supporting_entity_name','host_type','planned_meals_count','expected_attendance','guidance_version_id','guidance_accepted_at']), [
                'description'=>'نسخة تجريبية ثانية مرتبطة بالخطة المعتمدة.', 'status'=>RamadanIftar::STATUS_DRAFT,
                'execution_status'=>RamadanIftar::EXECUTION_STATUS_PLANNED, 'version_number'=>2, 'parent_version_id'=>$approved->id,
            ])
        );
    }

    private function demoUsers(): User
    {
        $roles = [
            'relations_officer'=>'مسؤول علاقات رمضان التجريبي', 'supervisor'=>'رئيس فرع رمضان التجريبي',
            'branch_coordinator'=>'منسق فروع رمضان التجريبي', 'relations_manager'=>'مدير علاقات رمضان التجريبي',
            'executive_manager'=>'مدير تنفيذي رمضان التجريبي',
        ];
        foreach ($roles as $roleName => $displayName) {
            if (! Role::query()->where('name',$roleName)->exists()) throw new RuntimeException("Ramadan demo requires existing role: {$roleName}.");
            $user = User::query()->firstOrCreate(
                ['email'=>"ramadan.demo.{$roleName}@zaha.invalid"],
                ['name'=>$displayName,'branch_id'=>self::BRANCH_ID,'status'=>'active','password'=>Hash::make('ChangeMe-Ramadan-2026')]
            );
            if ((int)$user->branch_id !== self::BRANCH_ID) throw new RuntimeException("Demo user {$user->email} must belong to branch_id = 23.");
            $user->syncRoles([$roleName]);
            if ($roleName === 'relations_officer') $relationsOfficer = $user;
        }

        return $relationsOfficer;
    }

    private function syncWorkflow(Workflow $workflow, RamadanIftar $iftar, ?string $status): void
    {
        if (! $status) return;
        $step = $status === 'in_progress' ? $workflow->steps->get(1) : $workflow->steps->first();
        WorkflowInstance::query()->updateOrCreate(
            ['workflow_id'=>$workflow->id,'entity_type'=>RamadanIftar::class,'entity_id'=>$iftar->id],
            ['current_step_id'=>$status==='approved'?null:optional($step)->id,'status'=>$status,'started_at'=>now(),'completed_at'=>$status==='approved'?now():null]
        );
    }

    private function syncNeeds(RamadanIftar $iftar, int $index, string $execution): void
    {
        $codes = $index % 2 ? ['volunteers','supplies','transport'] : ['official_correspondence','media_coverage','gifts_shields'];
        foreach (ExecutionNeedType::query()->whereIn('code',$codes)->get() as $type) {
            SubjectExecutionNeed::query()->updateOrCreate(
                ['subject_type'=>EventSubjectTypes::RAMADAN_IFTAR,'subject_id'=>$iftar->id,'execution_need_type_id'=>$type->id],
                ['is_required'=>true,'planned_details'=>'تفاصيل تخطيط تجريبية: '.$type->name,'status'=>$execution==='completed'?'completed':'pending','actual_details'=>$execution==='completed'?'تم التنفيذ ضمن العرض التجريبي.':null,'completed_at'=>$execution==='completed'?now():null]
            );
        }
    }

    private function syncMonitoring(RamadanIftar $iftar, User $user, ?string $status): void
    {
        if (! $status) return;
        $method = MonitoringMethod::query()->active()->ordered()->firstOrFail();
        MonitoringReport::query()->updateOrCreate(
            ['subject_type'=>EventSubjectTypes::RAMADAN_IFTAR,'subject_id'=>$iftar->id,'monitoring_method_id'=>$method->id],
            ['monitor_user_id'=>$user->id,'observed_at'=>now(),'general_notes'=>'متابعة تجريبية لأغراض العرض.','status'=>$status,'submitted_at'=>now()]
        );
    }
}
