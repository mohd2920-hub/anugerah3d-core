<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'name',
    'recipient_scope',
    'selected_agent_ids',
    'subject',
    'body',
    'image_paths',
    'image_position',
    'created_by_admin_id',
    'last_sent_at',
    'last_sent_by_admin_id',
])]
class AgentEmailTemplate extends Model
{
    public const MAX_IMAGES = 4;

    public const MAX_IMAGE_DIMENSION = 400;

    public const ImagePositionTop = 'top';

    public const ImagePositionBottom = 'bottom';

    public const RecipientAllAgents = 'all_agents';

    public const RecipientSelectedAgents = 'selected_agents';

    protected $attributes = [
        'image_position' => self::ImagePositionTop,
    ];

    /**
     * @return array<string, string>
     */
    public static function imagePositions(): array
    {
        return [
            self::ImagePositionTop => 'Above body',
            self::ImagePositionBottom => 'Below body',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function recipientScopes(): array
    {
        return [
            self::RecipientAllAgents => 'All agent',
            self::RecipientSelectedAgents => 'Selected agent',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by_admin_id');
    }

    public function lastSentBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'last_sent_by_admin_id');
    }

    public function recipientAgents(): Builder
    {
        if ($this->recipient_scope === self::RecipientSelectedAgents) {
            $selectedAgentIds = $this->selectedAgentIds();

            return $selectedAgentIds === []
                ? Agent::query()->whereRaw('1 = 0')
                : Agent::query()->whereKey($selectedAgentIds)->orderBy('agt_name');
        }

        return Agent::query()->orderBy('agt_name');
    }

    /**
     * @return array<int, int>
     */
    public function selectedAgentIds(): array
    {
        return collect($this->selected_agent_ids ?? [])
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values()
            ->all();
    }

    public function recipientScopeLabel(): string
    {
        return self::recipientScopes()[$this->recipient_scope] ?? 'Selected agent';
    }

    /**
     * @return array<int, string>
     */
    public function imagePaths(): array
    {
        return collect($this->image_paths ?? [])
            ->filter(static fn ($path): bool => is_string($path) && $path !== '')
            ->take(self::MAX_IMAGES)
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'selected_agent_ids' => 'array',
            'image_paths' => 'array',
            'last_sent_at' => 'datetime',
        ];
    }
}
