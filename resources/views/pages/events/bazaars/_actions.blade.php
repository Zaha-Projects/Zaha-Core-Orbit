<div class="bazaar-sticky-actions">
<a href="{{ isset($bazaar) ? route('events.bazaars.show',$bazaar) : route('events.bazaars.index') }}" class="btn btn-light"><i class="fas fa-xmark"></i> إلغاء</a>
<button type="submit" name="submit_action" value="draft" class="btn btn-outline-secondary"><i class="fas fa-floppy-disk"></i> حفظ كمسودة</button>
@if(auth()->user()->hasRole('super_admin') || auth()->user()->can('bazaars.submit'))<button type="submit" name="submit_action" value="submit" class="btn btn-bazaar"><i class="fas fa-paper-plane"></i> إرسال للاعتماد</button>@endif
</div>
