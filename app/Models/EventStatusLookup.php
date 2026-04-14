<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventStatusLookup extends Model
{
    use HasFactory;

    protected $fillable = [
        'module',
        'code',
        'name',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public static function labelFor(string $module, ?string $code): string
    {
        if (! filled($code)) {
            return '-';
        }

        $lookup = static::query()
            ->forModule($module)
            ->where('code', $code)
            ->first();

        if ($lookup?->name) {
            return $lookup->name;
        }

        if ($module === 'agenda') {
            $translated = __('app.roles.relations.agenda.status_labels.' . $code);

            return $translated !== 'app.roles.relations.agenda.status_labels.' . $code
                ? $translated
                : (string) $code;
        }

        if ($module === 'monthly_activities') {
            $workflowLabel = __('workflow_ui.approvals.status_labels.' . $code);
            if ($workflowLabel !== 'workflow_ui.approvals.status_labels.' . $code) {
                return $workflowLabel;
            }

            $translated = __('app.roles.programs.monthly_activities.statuses.' . $code);

            return $translated !== 'app.roles.programs.monthly_activities.statuses.' . $code
                ? $translated
                : (string) $code;
        }

        return (string) $code;
    }
}
