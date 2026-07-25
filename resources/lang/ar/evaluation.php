<?php
return [
    'title' => 'تقييمات الأنشطة', 'forms' => 'نماذج وأسئلة التقييم', 'post_execution' => 'إكمال ما بعد التنفيذ', 'submitted_value' => 'القيمة المدخلة', 'corrected_value' => 'القيمة الصحيحة',
    'statuses' => ['pending' => 'بانتظار التحقق', 'correct' => 'صحيح', 'incorrect' => 'غير صحيح', 'evaluated' => 'تم تقييمه'],
    'visibility' => ['branch_only' => 'الفرع فقط', 'authorized_users' => 'المستخدمون المخولون'],
    'validation' => ['corrected_required' => 'القيمة الصحيحة مطلوبة عند اختيار غير صحيح.', 'verification_incomplete' => 'يجب التحقق من جميع قيم ما بعد التنفيذ أولًا.', 'duplicate' => 'تم تقييم هذا النشاط مسبقًا.', 'required_answer' => 'إجابة هذا السؤال مطلوبة.', 'score_range' => 'يجب أن تكون الدرجة ضمن نطاق السؤال.', 'configuration' => 'إعدادات النموذج غير صالحة.', 'ineligible' => 'لا يمكن تقييم نشاط ملغي أو مرفوض.', 'integer' => 'يجب إدخال عدد صحيح.', 'numeric' => 'يجب إدخال رقم.', 'boolean' => 'القيمة المنطقية غير صالحة.'],
    'messages' => ['verification_saved' => 'تم حفظ قرارات التحقق.', 'submitted' => 'تم إرسال التقييم وتغيير حالة النشاط.', 'visibility_updated' => 'تم تحديث ظهور نتيجة التقييم.'],
    'dashboard' => ['activities'=>'إجمالي الأنشطة','branches'=>'الفروع','pending_verification'=>'بانتظار التحقق','incorrect'=>'بيانات غير صحيحة','pending_evaluation'=>'بانتظار التقييم','evaluated'=>'تم تقييمه','average'=>'متوسط التقييم','this_month'=>'تقييمات هذا الشهر','latest'=>'أحدث التقييمات'],
    'notifications' => ['incorrect_title'=>'بيانات ما بعد التنفيذ غير صحيحة','incorrect_message'=>'تم تسجيل تصحيح على بيانات نشاط :activity.','completed_title'=>'اكتمل تقييم النشاط','completed_message'=>'تم تقييم :activity بنتيجة :score/10.'],
];
