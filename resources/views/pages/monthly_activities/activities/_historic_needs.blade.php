@if(isset($monthlyActivity))
    @foreach($historicNeedDetails as $label => $details)
        <section class="alert alert-secondary">
            <h6>{{ $label }} — للقراءة فقط</h6>
            <p>هذا الاحتياج غير متاح حاليًا. بياناته السابقة محفوظة ولن تتغير عند حفظ النموذج.</p>
            @foreach($details as $detail)
                <div class="border-top py-2">{{ $detail }}</div>
            @endforeach
        </section>
    @endforeach
@endif
