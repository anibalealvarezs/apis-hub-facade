<?php

namespace App\Filament\Account\Pages;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

class EmailNotifications extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static string $view = 'filament.account.pages.email-notifications';

    protected static ?int $navigationSort = 60;

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('Preferences');
    }

    public function getTitle(): string
    {
        return __('Email Notifications');
    }

    public static function getNavigationLabel(): string
    {
        return __('Email Notifications');
    }

    public function mount(): void
    {
        $user = auth()->user();
        $storedPrefs = $user->email_notification_preferences ?? [];

        // All configurable notifications default to enabled (true)
        $this->form->fill([
            // Security & Credentials
            'api_key_rotated' => (bool) ($storedPrefs['api_key_rotated'] ?? true),
            'user_api_key_rotated' => (bool) ($storedPrefs['user_api_key_rotated'] ?? true),

            // Workspace & Collaborators
            'invitation_accepted' => (bool) ($storedPrefs['invitation_accepted'] ?? true),
            'collaborator_left_project' => (bool) ($storedPrefs['collaborator_left_project'] ?? true),
            'member_expelled_from_project' => (bool) ($storedPrefs['member_expelled_from_project'] ?? true),

            // Integrations & Pipelines
            'integration_disconnected' => (bool) ($storedPrefs['integration_disconnected'] ?? true),

            // Billing & Subscriptions
            'tier_upgraded' => (bool) ($storedPrefs['tier_upgraded'] ?? true),
            'billing_access_revoked' => (bool) ($storedPrefs['billing_access_revoked'] ?? true),
            'billing_profile_assignment_requested' => (bool) ($storedPrefs['billing_profile_assignment_requested'] ?? true),
            'billing_profile_assignment_processed' => (bool) ($storedPrefs['billing_profile_assignment_processed'] ?? true),

            // Performance & Threshold Monitoring
            'alert_triggered' => (bool) ($storedPrefs['alert_triggered'] ?? true),

            // Support & Help Desk
            'ticket_created' => (bool) ($storedPrefs['ticket_created'] ?? true),
            'ticket_created_for_user' => (bool) ($storedPrefs['ticket_created_for_user'] ?? true),
            'ticket_status_changed' => (bool) ($storedPrefs['ticket_status_changed'] ?? true),
            'ticket_user_reply' => (bool) ($storedPrefs['ticket_user_reply'] ?? true),

            // Locked Key Notifications (Always true, read-only)
            'verify_pending_email' => true,
            'queued_verify_email' => true,
            'queued_reset_password' => true,
            'invitation_sent' => true,
            'projects_suspended' => true,
            'billing_assignment_expired' => true,
            'billing_assignment_cancelled' => true,
            'billing_payment_failed' => true,
            'project_invitation_mail' => true,
            'billing_invitation_mail' => true,
            'project_transfer_mail' => true,
            'admin_registration_alert' => true,
            'welcome_alpha_lead' => true,
        ]);
    }

    public function form(Form $form): Form
    {
        $keyNotificationTooltip = __('This is a key notification and therefore it cannot be disabled.');

        return $form
            ->schema([
                Section::make(__('Essential & Security System Emails (Mandatory)'))
                    ->description(__('These are critical transactional and system notifications required for security, account recovery, billing enforcement, and legal compliance.'))
                    ->icon('heroicon-o-shield-check')
                    ->collapsible()
                    ->schema([
                        Toggle::make('verify_pending_email')
                            ->label(__('Email Verification / Pending Email Updates'))
                            ->helperText(__('Sent when requesting email changes to confirm ownership.'))
                            ->default(true)
                            ->disabled()
                            ->hintAction(
                                \Filament\Forms\Components\Actions\Action::make('locked_verify_pending')
                                    ->icon('heroicon-m-lock-closed')
                                    ->tooltip($keyNotificationTooltip)
                            ),

                        Toggle::make('queued_verify_email')
                            ->label(__('New Account Email Verification'))
                            ->helperText(__('Sent upon registration to activate the user account.'))
                            ->default(true)
                            ->disabled()
                            ->hintAction(
                                \Filament\Forms\Components\Actions\Action::make('locked_queued_verify')
                                    ->icon('heroicon-m-lock-closed')
                                    ->tooltip($keyNotificationTooltip)
                            ),

                        Toggle::make('queued_reset_password')
                            ->label(__('Password Reset Instructions'))
                            ->helperText(__('Contains secure authentication links to recover your password.'))
                            ->default(true)
                            ->disabled()
                            ->hintAction(
                                \Filament\Forms\Components\Actions\Action::make('locked_reset_pass')
                                    ->icon('heroicon-m-lock-closed')
                                    ->tooltip($keyNotificationTooltip)
                            ),

                        Toggle::make('projects_suspended')
                            ->label(__('Project Suspension Notices'))
                            ->helperText(__('Critical alert notifying when projects are halted due to subscription expiry or quota limits.'))
                            ->default(true)
                            ->disabled()
                            ->hintAction(
                                \Filament\Forms\Components\Actions\Action::make('locked_projects_suspended')
                                    ->icon('heroicon-m-lock-closed')
                                    ->tooltip($keyNotificationTooltip)
                            ),

                        Toggle::make('billing_payment_failed')
                            ->label(__('Payment Failed & Grace Period Warnings'))
                            ->helperText(__('Urgent notice when a recurring subscription charge fails.'))
                            ->default(true)
                            ->disabled()
                            ->hintAction(
                                \Filament\Forms\Components\Actions\Action::make('locked_payment_failed')
                                    ->icon('heroicon-m-lock-closed')
                                    ->tooltip($keyNotificationTooltip)
                            ),

                        Toggle::make('billing_assignment_expired')
                            ->label(__('Billing Profile Request Expired'))
                            ->helperText(__('Alerts project owners when an unanswered billing profile request times out.'))
                            ->default(true)
                            ->disabled()
                            ->hintAction(
                                \Filament\Forms\Components\Actions\Action::make('locked_assignment_expired')
                                    ->icon('heroicon-m-lock-closed')
                                    ->tooltip($keyNotificationTooltip)
                            ),

                        Toggle::make('billing_assignment_cancelled')
                            ->label(__('Billing Profile Request Cancelled'))
                            ->helperText(__('Notifies billing profile owners when a pending project link is cancelled.'))
                            ->default(true)
                            ->disabled()
                            ->hintAction(
                                \Filament\Forms\Components\Actions\Action::make('locked_assignment_cancelled')
                                    ->icon('heroicon-m-lock-closed')
                                    ->tooltip($keyNotificationTooltip)
                            ),

                        Toggle::make('invitation_sent')
                            ->label(__('Collaborator Invitation Audit (InvitationSent)'))
                            ->helperText(__('Confirmation email dispatching new team workspace invites.'))
                            ->default(true)
                            ->disabled()
                            ->hintAction(
                                \Filament\Forms\Components\Actions\Action::make('locked_invitation_sent')
                                    ->icon('heroicon-m-lock-closed')
                                    ->tooltip($keyNotificationTooltip)
                            ),

                        Toggle::make('project_invitation_mail')
                            ->label(__('Workspace Invitation Email (ProjectInvitationMail)'))
                            ->helperText(__('Direct invitation email with one-click access link sent to new collaborators.'))
                            ->default(true)
                            ->disabled()
                            ->hintAction(
                                \Filament\Forms\Components\Actions\Action::make('locked_invitation_mail')
                                    ->icon('heroicon-m-lock-closed')
                                    ->tooltip($keyNotificationTooltip)
                            ),

                        Toggle::make('billing_invitation_mail')
                            ->label(__('Billing Profile Invitation (BillingInvitationMail)'))
                            ->helperText(__('Invitations allowing users to join an organizational billing profile.'))
                            ->default(true)
                            ->disabled()
                            ->hintAction(
                                \Filament\Forms\Components\Actions\Action::make('locked_billing_invite')
                                    ->icon('heroicon-m-lock-closed')
                                    ->tooltip($keyNotificationTooltip)
                            ),

                        Toggle::make('project_transfer_mail')
                            ->label(__('Project Ownership Transfer Request (ProjectTransferMail)'))
                            ->helperText(__('Legal and operational confirmation of project ownership handover.'))
                            ->default(true)
                            ->disabled()
                            ->hintAction(
                                \Filament\Forms\Components\Actions\Action::make('locked_transfer_mail')
                                    ->icon('heroicon-m-lock-closed')
                                    ->tooltip($keyNotificationTooltip)
                            ),

                        Toggle::make('admin_registration_alert')
                            ->label(__('Administrator Registration Audit (AdminRegistrationAlert)'))
                            ->helperText(__('System alert for platform administrators on user registrations.'))
                            ->default(true)
                            ->disabled()
                            ->hintAction(
                                \Filament\Forms\Components\Actions\Action::make('locked_admin_reg')
                                    ->icon('heroicon-m-lock-closed')
                                    ->tooltip($keyNotificationTooltip)
                            ),

                        Toggle::make('welcome_alpha_lead')
                            ->label(__('Alpha Access Welcome (WelcomeAlphaLead)'))
                            ->helperText(__('Transactional welcome email sent to alpha access waitlist applicants.'))
                            ->default(true)
                            ->disabled()
                            ->hintAction(
                                \Filament\Forms\Components\Actions\Action::make('locked_alpha_welcome')
                                    ->icon('heroicon-m-lock-closed')
                                    ->tooltip($keyNotificationTooltip)
                            ),
                    ])
                    ->columns(2),

                Section::make(__('Security & Access Credentials'))
                    ->description(__('Choose whether to receive email notices when API keys are created or regenerated.'))
                    ->icon('heroicon-o-key')
                    ->schema([
                        Toggle::make('api_key_rotated')
                            ->label(__('Project Public API Key Rotated'))
                            ->helperText(__('Receive an email whenever the shared public API key for your project is rotated.'))
                            ->default(true),

                        Toggle::make('user_api_key_rotated')
                            ->label(__('Personal Scoped API Key Rotated'))
                            ->helperText(__('Receive an email when your personal API access key is regenerated.'))
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make(__('Integrations & Data Ingestion'))
                    ->description(__('Alerts regarding remote API connections and synchronization tokens.'))
                    ->icon('heroicon-o-server-stack')
                    ->schema([
                        Toggle::make('integration_disconnected')
                            ->label(__('Channel Token Disconnected / Revoked'))
                            ->helperText(__('Receive an email immediately if an advertising or analytics OAuth token fails or is disconnected.'))
                            ->default(true),
                    ])
                    ->columns(1),

                Section::make(__('Team & Project Collaboration'))
                    ->description(__('Stay updated on team member actions within your projects.'))
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Toggle::make('invitation_accepted')
                            ->label(__('Collaborator Accepted Invitation'))
                            ->helperText(__('Receive an email when an invited user joins your project.'))
                            ->default(true),

                        Toggle::make('collaborator_left_project')
                            ->label(__('Collaborator Left Project'))
                            ->helperText(__('Receive an email when a team member removes themselves from your project.'))
                            ->default(true),

                        Toggle::make('member_expelled_from_project')
                            ->label(__('Collaborator Expelled'))
                            ->helperText(__('Receive an email confirmation when access to a project is revoked.'))
                            ->default(true),
                    ])
                    ->columns(3),

                Section::make(__('Billing & Subscriptions'))
                    ->description(__('Updates regarding your plan tiers and profile sharing.'))
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Toggle::make('tier_upgraded')
                            ->label(__('Subscription Tier Upgraded'))
                            ->helperText(__('Receive a confirmation email when your account tier or plan is upgraded.'))
                            ->default(true),

                        Toggle::make('billing_access_revoked')
                            ->label(__('Billing Profile Access Revoked'))
                            ->helperText(__('Receive an email if an owner revokes your permission to bill against their profile.'))
                            ->default(true),

                        Toggle::make('billing_profile_assignment_requested')
                            ->label(__('Billing Profile Request Received'))
                            ->helperText(__('Receive an email when a project owner requests to use your billing profile.'))
                            ->default(true),

                        Toggle::make('billing_profile_assignment_processed')
                            ->label(__('Billing Profile Request Approved / Rejected'))
                            ->helperText(__('Receive an email when your request to attach a billing profile has been processed.'))
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make(__('Automated Alerts & Anomaly Detection'))
                    ->description(__('Email notifications triggered by custom KPI and metric thresholds.'))
                    ->icon('heroicon-o-exclamation-triangle')
                    ->schema([
                        Toggle::make('alert_triggered')
                            ->label(__('Performance Metric Alerts'))
                            ->helperText(__('Receive emails when an automated alert rule triggers an upper or lower limit breach.'))
                            ->default(true),
                    ])
                    ->columns(1),

                Section::make(__('Support & Help Desk'))
                    ->description(__('Notifications regarding your technical support inquiries.'))
                    ->icon('heroicon-o-lifebuoy')
                    ->schema([
                        Toggle::make('ticket_created_for_user')
                            ->label(__('Ticket Created Confirmation'))
                            ->helperText(__('Receive an email confirmation whenever a new support ticket is created.'))
                            ->default(true),

                        Toggle::make('ticket_status_changed')
                            ->label(__('Ticket Status Updates'))
                            ->helperText(__('Receive an email when your ticket status changes (e.g. Waiting on User, Resolved).'))
                            ->default(true),

                        Toggle::make('ticket_user_reply')
                            ->label(__('Ticket Conversation Replies'))
                            ->helperText(__('Receive an email whenever an administrator or team member replies to a support ticket.'))
                            ->default(true),

                        Toggle::make('ticket_created')
                            ->label(__('New Support Ticket Dispatch (Staff / Admin)'))
                            ->helperText(__('Receive an email notification when a new ticket is submitted in the platform.'))
                            ->default(true),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        // Only persist configurable keys
        $configurableKeys = [
            'api_key_rotated',
            'user_api_key_rotated',
            'invitation_accepted',
            'collaborator_left_project',
            'member_expelled_from_project',
            'integration_disconnected',
            'tier_upgraded',
            'billing_access_revoked',
            'billing_profile_assignment_requested',
            'billing_profile_assignment_processed',
            'alert_triggered',
            'ticket_created',
            'ticket_created_for_user',
            'ticket_status_changed',
            'ticket_user_reply',
        ];

        $preferences = [];
        foreach ($configurableKeys as $key) {
            $preferences[$key] = (bool) ($state[$key] ?? true);
        }

        $user = auth()->user();
        $user->email_notification_preferences = $preferences;
        $user->save();

        Notification::make()
            ->title(__('Notification preferences saved successfully.'))
            ->success()
            ->send();
    }
}
