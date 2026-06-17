# Meta App Review: Screen Recording Script

**Preparation Checklist Before Recording:**
- Ensure you are logged out of APIs Hub to start from the registration flow.
- Have a Meta Test Account (or a clean demo Facebook account) ready with an associated Facebook Page, Instagram Professional Account, and a Business Manager / Ad Account with some mock or historical data.
- Ensure your screen recording software captures the **entire browser window**, including the URL bar (Meta reviewers require this).

---

## Part 1: Registration, Project Creation, and Authentication (0:00 - 1:00)

**Visual Action:** Start on the APIs Hub Facade landing page. Click "Start for Free" and complete the registration flow. Log in to the platform.
> **🎙️ Voiceover:** "Hello, this is the APIs Hub platform. Our SaaS application allows marketing teams to aggregate their social media and advertising metrics into a unified, read-only analytics dashboard. First, I will register a new account and log in."

**Visual Action:** Navigate through the project creation flow. Create a new project workspace.
> **🎙️ Voiceover:** "Once logged in, the user creates a new Project. Each project in APIs Hub requires its own dedicated infrastructure."

**Visual Action:** Navigate to the **Data Sources / Integrations** page within the new project. Click the button to connect or authenticate a Meta account. 
> **🎙️ Voiceover:** "I will now connect a Meta account to configure the data sources for this project."

**Visual Action:** The Facebook OAuth consent window pops up. **Crucial:** Slowly scroll through the consent screen so the list of requested permissions is clearly visible. Ensure the browser URL bar showing your App ID or domain is visible.
> **🎙️ Voiceover:** "The user is prompted with the Meta consent screen, requesting read-only access to their Pages, Instagram accounts, and Ads data. We also request Business Asset User Profile Access to identify the user managing the assets."

**Visual Action:** Complete the OAuth flow. Show the Facade UI returning to the configuration screen.

---

## Part 2: Feature Configuration & Server Deployment (1:00 - 1:30)

**Visual Action:** Show the configuration screen for the Facebook Organic channel. Hover over or point to the specific configuration toggle buttons (FB Page metrics, FB posts, IG Account metrics, IG media, etc.).
> **🎙️ Voiceover:** "After authentication, the user configures the data sync parameters. Here, you can clearly see the distinct toggles that allow the user to enable or disable the syncing of specific assets—such as Facebook Page metrics, Facebook Posts, Instagram Account metrics, and Instagram Media. This configuration directly dictates which of the requested permissions will be actively used by the syncing engine."

**Visual Action:** Click the action to save the configurations. Wait for the loading state to complete before proceeding to deploy.
> **🎙️ Voiceover:** "Saving these organic configurations takes a few seconds because our backend actively verifies that all selected assets are fully accessible via the Meta API before locking in the configuration. Once verified, the user deploys the project. Behind the scenes, APIs Hub provisions an isolated server with its own strictly isolated database for this specific project."

**Visual Action:** Show the status transitioning to syncing. Navigate briefly to the Telemetry page.
> **🎙️ Voiceover:** "Once deployed, this isolated server securely begins syncing the requested historical metrics from the Meta API into the isolated database. Users can monitor this entire backend syncing process transparently from our Telemetry page. I will now pause the recording while the deployment and initial sync complete."

*--- [PAUSE YOUR SCREEN RECORDING HERE] ---*
*(Wait for the backend sync to finish. Resume the recording once the data is fully populated in the UI.)*
*--- [RESUME RECORDING] ---*

---

## Part 3: Demonstrating Ads Permissions (1:30 - 2:00)
*Targeting: `business_management`, `ads_read`*

**Visual Action:** Navigate to the **Facebook Marketing Dashboard** or Data Explorer. Add an on-screen text overlay here if you aren't doing voiceover: *"Post-Sync Data Verification"*.
> **🎙️ Voiceover:** "The isolated server deployment and data sync is complete. I'll navigate to our Facebook Marketing Dashboard to demonstrate how we use the advertising permissions. You'll notice we call this channel 'Marketing' rather than 'Ads.' A core purpose of APIs Hub is to integrate and manually map equivalent data across multiple platforms to make it universally comparable. Therefore, we don't just blindly sync all available data from the provider. We strictly extract only the specific metrics that serve our cross-platform analytics purpose and are relevant for high-level decision-making."

**Visual Action:** Point your mouse at charts showing Campaign Spend, Cost per Result (CPR), or Impressions. 
> **🎙️ Voiceover:** "Using the `ads_read` and `business_management` permissions, we fetch the user's active ad campaigns and display these normalized, read-only performance metrics—like Spend, Impressions, and Cost per Click—to the authenticated project owner."

---

## Part 4: Demonstrating Facebook Page Permissions (2:00 - 2:30)
*Targeting: `pages_show_list`, `pages_read_engagement`, `pages_read_user_content`, `Facebook Insights`*

**Visual Action:** Switch to the **Data Explorer** or the specific **Facebook Organic Dashboard**. Show a dropdown or list where the user can select their specific Facebook Page.
> **🎙️ Voiceover:** "Switching to our organic dashboard, this is where the Facebook and Instagram data visualisations are separated. We use `pages_show_list` to allow the user to select which of their managed Facebook pages they want to analyze."

**Visual Action:** Point to widgets showing Page Reach, Page Likes, and a list of recent Posts with their engagement numbers (Likes, Comments).
> **🎙️ Voiceover:** "Here, based on the toggles configured earlier, we use `Facebook Insights`, `pages_read_engagement`, and `pages_read_user_content` to retrieve and display read-only organic metrics. This includes the total page reach, as well as the text content and engagement counts for individual posts."

---

## Part 5: Demonstrating Instagram Permissions (2:30 - 3:00)
*Targeting: `instagram_basic`, `instagram_manage_insights`*

**Visual Action:** Switch to the **Instagram Dashboard** or the specific Instagram analytics tab within the Data Explorer.
> **🎙️ Voiceover:** "Next, separating out the Instagram analytics, we use the `instagram_basic` and `instagram_manage_insights` permissions. It is important to note that APIs Hub focuses strictly on Business and B2B analytics. Therefore, we exclusively support Instagram Professional and Business accounts."

**Visual Action:** Point to widgets showing Instagram Follower counts, Profile Views, and specific IG Media metrics (Reach, Saves, Comments).
> **🎙️ Voiceover:** "Because Meta's API hierarchically links Instagram Business accounts to a parent Facebook Page, the Instagram data inherently depends on the Facebook Page asset connection demonstrated earlier. By enabling the Instagram toggles, these permissions allow us to read the linked Instagram account's basic profile data and performance insights, which are displayed here in a strictly read-only capacity."

---

## Part 6: User Identity & Conclusion (3:00 - 3:15)
*Targeting: `Business Asset User Profile Access`*

**Visual Action:** Navigate to the settings or team management page where the authenticated user's Facebook profile name/ID might be displayed alongside their linked assets.
> **🎙️ Voiceover:** "Finally, we use `Business Asset User Profile Access` to accurately identify the user token associated with these business assets, ensuring we maintain proper security mapping within our isolated databases."

**Visual Action:** Return to the main dashboard overview.
> **🎙️ Voiceover:** "To reiterate, APIs Hub only uses these permissions to fetch and display read-only analytics to the authenticated owner within their isolated project environment. We do not modify campaigns, post content, or share this data with third parties. Thank you for your review."

*(End Recording)*
