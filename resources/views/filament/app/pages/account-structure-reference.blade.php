<x-filament-panels::page>
    <div class="space-y-6">
        <div class="prose prose-sm max-w-none text-gray-500 dark:text-gray-400 mb-2">
            {{ __('Understand the relationship between your user account, billing profiles, projects, and team collaboration.') }}
        </div>

                @php
            $id = \Illuminate\Support\Str::slug(__('User Accounts vs. Billing Profiles'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-user" class="h-5 w-5 text-primary-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('User Accounts vs. Billing Profiles') }}</span>
                        <x-filament::icon 
                            icon="heroicon-o-link" 
                            class="h-4 w-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" 
                            x-show="!copied"
                        />
                        <x-filament::icon 
                            icon="heroicon-o-check" 
                            class="h-4 w-4 text-success-500" 
                            x-show="copied"
                            x-cloak
                        />
                    </a>
                </div>
            </x-slot>

            <div class="prose dark:prose-invert max-w-none text-sm">
                <p>
                    {!! __('In APIs Hub, subscriptions are :strong_start not :strong_end attached to your User Account. Instead, they are attached to :strong_start Billing Profiles :strong_end.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}
                </p>
                <ul>
                    <li><strong>{{ __('User Account:') }}</strong> {{ __('Your personal login credentials. It identifies who you are, but it does not dictate what features you can access or how many projects you can create.') }}</li>
                    <li><strong>{{ __('Billing Profile:') }}</strong> {{ __('A separate entity that holds the subscription (Free, Pro, Enterprise, etc.). A single User Account can own or have access to multiple Billing Profiles simultaneously.') }}</li>
                </ul>
            </div>
        </x-filament::section>

                @php
            $id = \Illuminate\Support\Str::slug(__('Billing Profiles & Tiers'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-credit-card" class="h-5 w-5 text-success-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('Billing Profiles & Tiers') }}</span>
                        <x-filament::icon 
                            icon="heroicon-o-link" 
                            class="h-4 w-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" 
                            x-show="!copied"
                        />
                        <x-filament::icon 
                            icon="heroicon-o-check" 
                            class="h-4 w-4 text-success-500" 
                            x-show="copied"
                            x-cloak
                        />
                    </a>
                </div>
            </x-slot>

            <div class="prose dark:prose-invert max-w-none text-sm">
                <p>
                    {{ __('Because subscriptions belong to Billing Profiles, the Tier limits (such as max projects, max assets, and API access) apply to the Billing Profile, not the user.') }}
                </p>
                <ul>
                    <li>{!! __('If you own a :strong_start Pro Tier :strong_end Billing Profile, that specific profile can sponsor up to 5 projects and 100 assets.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}</li>
                    <li>{{ __('When you create a new Project, you must link it to one of your available Billing Profiles. The project will then consume the limits of that specific profile.') }}</li>
                    <li>{{ __('You can upgrade or downgrade a Billing Profile without affecting other profiles you might own.') }}</li>
                </ul>
            </div>
        </x-filament::section>

                @php
            $id = \Illuminate\Support\Str::slug(__('Sharing Billing Profiles'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-share" class="h-5 w-5 text-indigo-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('Sharing Billing Profiles') }}</span>
                        <x-filament::icon 
                            icon="heroicon-o-link" 
                            class="h-4 w-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" 
                            x-show="!copied"
                        />
                        <x-filament::icon 
                            icon="heroicon-o-check" 
                            class="h-4 w-4 text-success-500" 
                            x-show="copied"
                            x-cloak
                        />
                    </a>
                </div>
            </x-slot>

            <div class="prose dark:prose-invert max-w-none text-sm">
                <p>
                    {{ __('Enterprise tier Billing Profiles can be shared with other users. This allows agencies and large organizations to centralize billing while distributing projects.') }}
                </p>
                <ul>
                    <li>{{ __('When you share a Billing Profile with another user, they can create their own projects and assign them to your Billing Profile.') }}</li>
                    <li>{{ __('The projects they create will consume your profile\'s quota, and you (as the billing owner) will be responsible for the charges.') }}</li>
                    <li>{{ __('You can revoke access to a shared Billing Profile at any time. If you do, their projects will be reassigned or suspended if they lack an alternative paid profile.') }}</li>
                </ul>
            </div>
        </x-filament::section>

                @php
            $id = \Illuminate\Support\Str::slug(__('Project Ownership vs. Billing Ownership'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-shield-check" class="h-5 w-5 text-warning-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('Project Ownership vs. Billing Ownership') }}</span>
                        <x-filament::icon 
                            icon="heroicon-o-link" 
                            class="h-4 w-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" 
                            x-show="!copied"
                        />
                        <x-filament::icon 
                            icon="heroicon-o-check" 
                            class="h-4 w-4 text-success-500" 
                            x-show="copied"
                            x-cloak
                        />
                    </a>
                </div>
            </x-slot>

            <div class="prose dark:prose-invert max-w-none text-sm">
                <p>
                    {{ __('APIs Hub is built upon a fundamental duality of perspectives to facilitate modern agency-client relationships:') }}
                </p>
                <ul>
                    <li>{!! __(':strong_start Technical Perspective (Project Ownership): :strong_end Dictates who controls the data, integrations, and infrastructure.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}</li>
                    <li>{!! __(':strong_start Administrative Perspective (Billing Profile): :strong_end Dictates who controls the payments, subscriptions, and quota scaling.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}</li>
                </ul>
                <p>
                    {{ __('This separation is by design. It allows agencies to seamlessly onboard clients and manage their technical execution, while giving clients the option to handle the billing directly. Conversely, it empowers brands to start their own projects and later delegate the technical management to third-party agencies without risking their payment methods.') }}
                </p>
                <p>
                    {{ __('Crucially, this architecture resolves common conflicts of interest in the digital marketing industry regarding data ownership. When a collaboration contract ends, Project Ownership can be smoothly transferred between parties with absolute ease, ensuring that credentials, historical data, and infrastructure remain intact while cleanly severing the administrative billing ties.') }}
                </p>
                <ul>
                    <li>{{ __('Projects are completely independent from Billing Profiles. The user who creates a project is its absolute Owner.') }}</li>
                    <li>{{ __('Only the Project Owner can delete the project, transfer its ownership, or manage its infrastructure.') }}</li>
                    <li>{{ __('A project can be transferred to a different user. When transferred, the new owner can choose to keep it on the current Billing Profile (if shared) or attach it to their own Billing Profile.') }}</li>
                    <li>{!! __('Even if a project uses a Billing Profile owned by someone else, :strong_start the Project Owner retains full data sovereignty :strong_end. The billing owner cannot access the project\'s data unless explicitly invited as a collaborator.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}</li>
                </ul>
            </div>
        </x-filament::section>

                @php
            $id = \Illuminate\Support\Str::slug(__('Project Collaboration Dynamics'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-users" class="h-5 w-5 text-blue-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('Project Collaboration Dynamics') }}</span>
                        <x-filament::icon 
                            icon="heroicon-o-link" 
                            class="h-4 w-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" 
                            x-show="!copied"
                        />
                        <x-filament::icon 
                            icon="heroicon-o-check" 
                            class="h-4 w-4 text-success-500" 
                            x-show="copied"
                            x-cloak
                        />
                    </a>
                </div>
            </x-slot>

            <div class="prose dark:prose-invert max-w-none text-sm">
                <p>
                    {{ __('Once a project is created and sponsored by a Billing Profile, the Project Owner can invite other users to collaborate.') }}
                </p>
                <ul>
                    <li>{!! __('Collaborators are invited using their User Account email. While a paid Billing Profile is not strictly required to accept an invitation, :strong_start Free Tier accounts have specific collaboration limitations :strong_end (see below).', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}</li>
                    <li>{{ __('The permissions of a collaborator are defined by their Project Role (Admin, Editor, Viewer), not by their billing status.') }}</li>
                    <li>{{ __('A single User Account can be the Owner of some projects, an Admin in others, and a Viewer in projects belonging to external clients.') }}</li>
                </ul>
            </div>
        </x-filament::section>

                @php
            $id = \Illuminate\Support\Str::slug(__('Free Tier Collaboration Limitations'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-information-circle" class="h-5 w-5 text-gray-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('Free Tier Collaboration Limitations') }}</span>
                        <x-filament::icon 
                            icon="heroicon-o-link" 
                            class="h-4 w-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" 
                            x-show="!copied"
                        />
                        <x-filament::icon 
                            icon="heroicon-o-check" 
                            class="h-4 w-4 text-success-500" 
                            x-show="copied"
                            x-cloak
                        />
                    </a>
                </div>
            </x-slot>

            <div class="prose dark:prose-invert max-w-none text-sm">
                <p>
                    {{ __('To prevent abuse, APIs Hub enforces strict limits on Free Tier accounts. The freemium version is intended exclusively for testing the platform, personal usage, or onboarding team members who have no personal projects.') }}
                </p>
                <p>
                    {!! __('A user with :strong_start only a Free Tier billing profile :strong_end cannot collaborate on third-party projects unless one of the following conditions is met:', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}
                </p>
                <ul>
                    <li>{{ __('They delete their own free project.') }}</li>
                    <li>{{ __('They upgrade their billing profile to a paid tier.') }}</li>
                    <li>{{ __('They register their account using a direct collaboration invitation link or an invitation code.') }}</li>
                </ul>
                <p>
                    {!! __('This ensures that the free tier is not used for professional usage disguised as personal usage. However, it does not act as a barrier for organizational cooperation: if you intend to use the platform for :strong_start both :strong_end working for an organization and maintaining personal side projects, this qualifies as a professional user and requires a paid tier.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}
                </p>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
