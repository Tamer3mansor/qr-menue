<?php

namespace App\Filament\Pages;

use App\Models\User;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Hash;
use LogicException;

/**
 * Lets a customer replace the password the platform issued them.
 */
class ChangePassword extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'تغيير كلمة المرور';

    protected static ?string $title = 'تغيير كلمة المرور';

    /**
     * Navigation items fall back to a sort of -1, so this sits below the other
     * account pages.
     */
    protected static ?int $navigationSort = 110;

    protected string $view = 'filament.pages.change-password';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                // The Form component is what emits the <form wire:submit="save">
                // element the footer button submits through.
                Form::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('current_password')
                                    ->label('كلمة المرور الحالية')
                                    ->password()
                                    ->revealable()
                                    ->required()
                                    ->autocomplete('current-password')
                                    // Checked here rather than with the `password`
                                    // rule so a wrong guess reads as a field
                                    // error instead of a 422.
                                    ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                        if (! Hash::check((string) $value, $this->user()->password)) {
                                            $fail(__('The provided password does not match your current password.'));
                                        }
                                    }),
                                TextInput::make('new_password')
                                    ->label('كلمة المرور الجديدة')
                                    ->password()
                                    ->revealable()
                                    ->required()
                                    ->minLength(8)
                                    ->confirmed()
                                    ->autocomplete('new-password')
                                    ->different('current_password')
                                    ->helperText('8 أحرف على الأقل، ولا تكون نفس كلمة المرور الحالية.'),
                                TextInput::make('new_password_confirmation')
                                    ->label('تأكيد كلمة المرور الجديدة')
                                    ->password()
                                    ->revealable()
                                    ->required()
                                    ->autocomplete('new-password'),
                            ]),
                    ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        $this->getSaveAction(),
                    ]),
            ]);
    }

    public function save(): void
    {
        $user = $this->user();

        $user->forceFill([
            'password' => $this->form->getState()['new_password'],
        ])->save();

        Notification::make()
            ->title('تم تغيير كلمة المرور.')
            ->success()
            ->send();

        $this->form->fill();
    }

    protected function getSaveAction(): Action
    {
        return Action::make('save')
            ->label('تغيير كلمة المرور')
            ->submit('save');
    }

    /**
     * The password is always the signed in customer's own, so the page never
     * asks for an id and can never be pointed at another account.
     */
    protected function user(): User
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            throw new LogicException('The change password page requires an authenticated user.');
        }

        return $user;
    }
}
