<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record) {
            $data['collaborators_display'] = ProjectResource::getCollaboratorDisplayData($this->record);
            $data['sync_telemetry_channels'] = ProjectResource::getSyncTelemetryChannels($this->record);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Push effective TypeSafe API Key to tenant .env if remote admin key is available and tenant supports v1.16.0+
        $project = $this->record;
        if ($project && !empty($project->remote_admin_api_key) && $project->supportsAiClassification()) {
            try {
                $effectiveKey = $project->getEffectiveTypesafeApiKey() ?? '';
                $domain = config('app.network_domain') ?: 'apis-hub.cloud';
                $scheme = config('app.env') === 'local' ? 'http' : 'https';
                $hubUrl = "{$scheme}://{$project->subdomain}.{$domain}";

                $client = new \Anibalealvarezs\ApisHubApi\ApisHubApi(
                    baseUrl: $hubUrl,
                    apiKey: $project->remote_admin_api_key
                );

                $client->updateCredentials([
                    'TYPESAFE_API_KEY' => $effectiveKey,
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Could not push TypeSafe API Key to tenant node {$project->id}: " . $e->getMessage());
            }
        }
    }
}
