# Google App Review: Screen Recording Script

**Preparation Checklist Before Recording:**
- Ensure you are logged out of APIs Hub to start from the registration flow.
- Have a clean demo Google Account ready (or a test identity under the developer's Google account) that has access to:
  - At least one **verified Google Search Console property** (site URL) with historical query/impression data.
  - At least one **Google Analytics 4 property** with historical traffic data.
- Ensure your screen recording software captures the **entire browser window**, including the URL bar (Google reviewers require this). The Google OAuth consent screen must be visible in full so the requested scopes are legible.

---

## Part 1: Registration, Project Creation, and Authentication (0:00 - 1:00)

**Visual Action:** Start on the APIs Hub Facade landing page. Click "Start for Free" and complete the registration flow. Log in to the platform.
> **🎙️ Voiceover:** "Hello, this is the APIs Hub platform. Our SaaS application allows marketing teams to aggregate their organic search and website analytics metrics from multiple providers into a unified, read-only analytics dashboard. First, I will register a new account and log in."

**Visual Action:** Navigate through the project creation flow. Create a new project workspace.
> **🎙️ Voiceover:** "Once logged in, the user creates a new Project. Each project in APIs Hub requires its own dedicated infrastructure."

**Visual Action:** Navigate to the **Data Sources / Integrations** page within the new project. Click the button to connect or authenticate a Google account.
> **🎙️ Voiceover:** "I will now connect a Google account to configure the data sources for this project."

**Visual Action:** The Google OAuth consent window pops up. **Crucial:** Pause on the consent screen and scroll through it so the list of requested scopes is clearly visible (basic profile and email address, plus Google Search Console data and Google Analytics data). Ensure the browser URL bar showing `accounts.google.com` and the requesting application is visible.
> **🎙️ Voiceover:** "The user is prompted with the Google consent screen, which clearly lists the scopes we request: the basic profile and email address for the account, along with read-only access to their Google Search Console and Google Analytics properties."

**Visual Action:** Complete the OAuth flow. Show the Facade UI returning to the configuration screen.

---

## Part 2: Feature Configuration & Server Deployment (1:00 - 1:30)

**Visual Action:** Show the configuration screen for the Google channels. Hover over or point to the specific configuration toggles (Google Search Console sites, Google Analytics properties, etc.).
> **🎙️ Voiceover:** "After authentication, the user configures the data sync parameters. Here, you can clearly see the distinct toggles that allow the user to enable or disable the syncing of specific assets—such as individual Search Console properties and Google Analytics 4 properties. This configuration directly dictates which of the requested Google scopes will be actively used by the syncing engine."

**Visual Action:** Click the action to save the configurations. Wait for the loading state to complete before proceeding to deploy.
> **🎙️ Voiceover:** "Saving these configurations takes a few seconds because our backend actively verifies that all selected assets are fully accessible via the Google APIs before locking in the configuration. Once verified, the user deploys the project. Behind the scenes, APIs Hub provisions an isolated server with its own strictly isolated database for this specific project."

**Visual Action:** Show the status transitioning to syncing. Navigate briefly to the Telemetry page.
> **🎙️ Voiceover:** "Once deployed, this isolated server securely begins syncing the requested historical metrics from the Google APIs into the isolated database. Users can monitor this entire backend syncing process transparently from our Telemetry page. I will now pause the recording while the deployment and initial sync complete."

*--- [PAUSE YOUR SCREEN RECORDING HERE] ---*
*(Wait for the backend sync to finish. Resume the recording once the data is fully populated in the UI.)*
*--- [RESUME RECORDING] ---*

---

## Part 3: Demonstrating Google Search Console Permissions (1:30 - 2:00)
*Targeting: `https://www.googleapis.com/auth/webmasters.readonly`*

**Visual Action:** Navigate to the **Google Search Console dashboard** (under the Data Explorer / Google section). Add an on-screen text overlay here if you aren't doing voiceover: *"Post-Sync Data Verification"*.
> **🎙️ Voiceover:** "The isolated server deployment and data sync is complete. I'll navigate to our Google Search Console dashboard to demonstrate how we use the search console permission. A core purpose of APIs Hub is to integrate and manually map equivalent data across multiple platforms to make it universally comparable. Therefore, we don't just blindly sync all available data from the provider. We strictly extract only the specific metrics that serve our cross-platform analytics purpose and are relevant for high-level decision-making."

**Visual Action:** Show the property selector dropdown where the user chooses one of their verified Search Console properties.
> **🎙️ Voiceover:** "Using the `webmasters.readonly` scope, we fetch the user's verified Search Console properties and let them select which property they want to analyze."

**Visual Action:** Point your mouse at widgets showing Queries, Impressions, Click-Through Rate (CTR), and Average Position. Switch between the queries / pages tabs if available.
> **🎙️ Voiceover:** "Here you can see the read-only search performance metrics we display—total queries, impressions, click-through rate, and average position for the selected property. This data is shown strictly in a read-only capacity to the authenticated project owner."

---

## Part 4: Demonstrating Google Analytics Permissions (2:00 - 2:30)
*Targeting: `https://www.googleapis.com/auth/analytics.readonly`*

**Visual Action:** Navigate to the **Google Analytics dashboard** (labeled "Google Analytics 4 Insights" under the Google section).
> **🎙️ Voiceover:** "Next, we demonstrate the Google Analytics 4 data. Using the `analytics.readonly` scope, we retrieve the user's GA4 properties and aggregate the key performance metrics into a normalized, cross-platform view."

**Visual Action:** Show the GA4 property selector dropdown, then point to widgets showing Sessions, Users, Pageviews, Engagement, and Acquisition channels.
> **🎙️ Voiceover:** "For the selected Google Analytics 4 property, we display read-only metrics such as sessions, active users, pageviews, and acquisition channels. Like all data in APIs Hub, this is displayed strictly in a read-only capacity—we never modify the user's analytics configuration."

---

## Part 5: Conclusion (2:30 - 2:45)

**Visual Action:** Return to the main dashboard overview.
> **🎙️ Voiceover:** "To reiterate, APIs Hub only uses these permissions to fetch and display read-only analytics to the authenticated owner within their isolated project environment. We do not modify any search console or analytics configurations, or share this data with third parties. Thank you for your review."

*(End Recording)*
