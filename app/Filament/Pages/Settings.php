<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use App\Models\Setting;
use Filament\Notifications\Notification;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static string $view = 'filament.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $setting = Setting::first() ?? new Setting();
        $this->form->fill([
            'meta_access_token' => $setting->meta_access_token,
            'ad_account_id' => $setting->ad_account_id,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('meta_access_token')
                    ->label('Meta Ads Access Token')
                    ->required(),
                TextInput::make('ad_account_id')
                    ->label('Ad Account ID')
                    ->required(),
            ])
            ->statePath('data')
            ->model(Setting::class);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $setting = Setting::first();
        if ($setting) {
            $setting->update($data);
        } else {
            Setting::create($data);
        }

        $this->form->fill($data);

        Notification::make()
            ->title('Success')
            ->body('Settings saved successfully!')
            ->success()
            ->send();
    }
}