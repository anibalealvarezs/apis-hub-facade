<x-filament-panels::page>
    <div class="prose dark:prose-invert max-w-none">
        <h2>{{ __('Understanding Performance Correlations') }}</h2>
        <p>
            {{ __('The Performance Correlations dashboard is an advanced exploratory analytics tool designed to help marketers find hidden relationships, synergies, and cannibalizations across their entire digital ecosystem.') }}
        </p>

        <h3>{{ __('Core Concepts') }}</h3>
        
        <h4>{{ __('1. Pearson Correlation Coefficient') }}</h4>
        <p>
            {{ __('At the heart of the dashboard is the Pearson Correlation Coefficient, a mathematical formula that returns a value between -1 and +1:') }}
        </p>
        <ul>
            <li><strong>+1 (Strong Positive):</strong> {{ __('When Metric A goes up, Metric B goes up proportionally.') }}</li>
            <li><strong>0 (No Correlation):</strong> {{ __('The two metrics have completely random movement relative to each other.') }}</li>
            <li><strong>-1 (Strong Negative):</strong> {{ __('When Metric A goes up, Metric B goes down proportionally.') }}</li>
        </ul>
        <p class="text-sm italic">
            {{ __('Note: Correlation does NOT imply causation. A strong correlation means the metrics move together, but it does not prove that one caused the other.') }}
        </p>

        <h4>{{ __('2. Analysis Levels') }}</h4>
        <p>{{ __('Raw numbers can sometimes hide true volatility. The dashboard allows you to transform the data before correlating it:') }}</p>
        <ul>
            <li><strong>{{ __('Level (Original)') }}:</strong> {{ __('The raw, daily numbers (e.g., total impressions).') }}</li>
            <li><strong>{{ __('Z-Score (Normalized)') }}:</strong> {{ __('Our recommended default. It converts the numbers into "Standard Deviations". This allows you to perfectly compare a metric that has millions of views with a metric that ranges from 1 to 10 on the same visual axis.') }}</li>
            <li><strong>{{ __('1st Difference (Δ)') }}:</strong> {{ __('Day-over-day change. It answers the question: "Did the metric grow or shrink today compared to yesterday?" Useful for removing long-term seasonal trends.') }}</li>
            <li><strong>{{ __('2nd Difference (ΔΔ)') }}:</strong> {{ __('The acceleration of change. Useful for highly exponential growth curves.') }}</li>
        </ul>

        <h4>{{ __('3. Lag (Time Shifts)') }}</h4>
        <p>
            {{ __('Marketing effects are rarely instantaneous. If you run a branding campaign on Facebook today, a user might not search for your brand on Google until 3 days later. The "Lag" selector allows you to shift a metric forward or backward in time.') }}
        </p>
        <p>
            <em>{{ __('Example:') }}</em> {{ __('Comparing Facebook Spend (Lag 0) vs Google Search Clicks (Lag +3) will test if Monday\'s spend correlates with Thursday\'s clicks.') }}
        </p>

        <hr class="my-8 border-gray-200 dark:border-gray-700">

        <h3>{{ __('Understanding the Visualizations') }}</h3>

        <h4>{{ __('The Comparison View') }}</h4>
        <p>
            {{ __('This is the main dual-line chart. If you are using Z-Score, the zero-line (0) represents the monthly average for each metric. Peaks above 0 are good days, valleys below 0 are bad days. If the blue and red lines dance together, you have a strong positive correlation.') }}
        </p>

        <h4>{{ __('Rolling Correlation (7-Day Window)') }}</h4>
        <p>
            {{ __('A single correlation number for a 30-day period can hide when a campaign stopped working. This chart calculates the Pearson correlation using a sliding 7-day window. It draws a line between -1 and +1. If you see the line drop from +0.8 to 0 in the middle of the month, that is the exact day your campaign suffered from "Ad Fatigue" or an algorithm update broke the synergy.') }}
        </p>

        <h4>{{ __('Scatter Plot (Distribution)') }}</h4>
        <p>
            {{ __('This chart removes time entirely. It plots Metric A on the X-axis and Metric B on the Y-axis. Look for clusters or lines. This is extremely useful for finding "diminishing returns"—for example, you might notice that once Spend crosses $500/day on the X-axis, the Conversions on the Y-axis stop growing and flatten out.') }}
        </p>
    </div>
</x-filament-panels::page>
